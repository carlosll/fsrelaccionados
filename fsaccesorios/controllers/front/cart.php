<?php
/**
 * fsaccesorios - Front Controller: Multi-product Add to Cart
 *
 * Handles AJAX POST requests to add the main product plus
 * selected accessories to the cart in a single atomic operation.
 *
 * URL: /module/fsaccesorios/cart?action=addMultiple&ajax=1
 * Method: POST
 * Content-Type: application/json
 *
 * Request body:
 * {
 *   "id_product": 123,
 *   "quantity": 1,
 *   "id_customization": 0,
 *   "accessories": [
 *     {"id_product": 45, "id_product_attribute": 0, "quantity": 2},
 *     {"id_product": 67, "id_product_attribute": 12, "quantity": 1}
 *   ]
 * }
 *
 * Response (success):
 * {
 *   "success": true,
 *   "cart_url": "https://shop.example.com/cart",
 *   "cart_count": 3
 * }
 *
 * Response (error):
 * {
 *   "success": false,
 *   "errors": ["Product X is not available", "Insufficient stock for Y"]
 * }
 */

class FsaccesoriosCartModuleFrontController extends ModuleFrontController
{
    /** @var bool Enable AJAX mode — suppresses full page rendering */
    public $ajax = true;

    /**
     * Disable display to prevent full page rendering in AJAX mode.
     */
    public function display()
    {
        return '';
    }

    /**
     * Handle the POST request: validate, add products to cart, return JSON.
     */
    public function postProcess()
    {
        // --- Security checks ---
        if (Tools::getValue('action') !== 'addMultiple' || !Tools::getValue('ajax')) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'errors'  => ['Invalid request'],
            ]));
        }

        // Verify this is a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->ajaxDie(json_encode([
                'success' => false,
                'errors'  => ['Only POST requests are accepted'],
            ]));
        }

        // --- Read and parse JSON body ---
        $rawBody = Tools::file_get_contents('php://input');
        $body = json_decode($rawBody, true);

        if (!$body || !isset($body['id_product']) || (int) $body['id_product'] <= 0) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'errors'  => ['Invalid or missing product data in request'],
            ]));
        }

        // --- CSRF token validation ---
        $receivedToken = isset($body['token']) ? (string) $body['token'] : '';
        if (empty($receivedToken) || $receivedToken !== Tools::getToken(false)) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'errors'  => ['Invalid security token. Please refresh the page and try again.'],
            ]));
        }

        $idProduct       = (int) $body['id_product'];
        $quantity        = (int) ($body['quantity'] ?? 1);
        $idCustomization = (int) ($body['id_customization'] ?? 0);
        $accessories     = $body['accessories'] ?? [];

        // Sanity check on main quantity
        if ($quantity < 1) {
            $quantity = 1;
        }

        // --- Validate main product ---
        $mainProduct = new Product($idProduct, false, $this->context->language->id, $this->context->shop->id);
        if (!Validate::isLoadedObject($mainProduct) || !$mainProduct->active) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'errors'  => ['The main product is not available'],
            ]));
        }

        // --- Validate accessories ---
        $manager = $this->getAccessoryManager();
        $validation = $manager->validateAccessories($accessories, $idProduct);

        if (!$validation['valid']) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'errors'  => $validation['errors'],
            ]));
        }

        // --- Ensure cart exists ---
        $cart = $this->context->cart;

        if (!Validate::isLoadedObject($cart) || !$cart->id) {
            $cart->id_currency = $this->context->currency->id;
            $cart->id_lang = $this->context->language->id;
            $cart->id_shop = $this->context->shop->id;
            $cart->id_shop_group = $this->context->shop->id_shop_group;

            if (!$cart->add()) {
                $this->ajaxDie(json_encode([
                    'success' => false,
                    'errors'  => ['Could not create shopping cart'],
                ]));
            }

            // Store cart ID in cookie so subsequent requests use the same cart
            $this->context->cookie->id_cart = $cart->id;

            // Force context to use the new cart
            $this->context->cart = $cart;
        }

        // --- Add products to cart ---
        $errors = [];

        // 1. Add main product
        $mainResult = $cart->updateQty(
            $quantity,
            $idProduct,
            null,              // id_product_attribute (null = default)
            $idCustomization,  // id_customization
            'up',              // operator: 'up' = add/increase
            0,                 // id_address_delivery
            null               // shop (null = context shop)
        );

        if (!$mainResult) {
            $errors[] = sprintf(
                $this->module->l('Could not add the main product to cart', 'cart'),
            );
        }

        // 2. Add each selected accessory
        if (empty($errors)) {
            foreach ($accessories as $accessory) {
                $accIdProduct      = (int) $accessory['id_product'];
                $accIdAttr         = (int) ($accessory['id_product_attribute'] ?? 0);
                $accQty            = (int) $accessory['quantity'];
                $accCustomization  = (int) ($accessory['id_customization'] ?? 0);

                if ($accQty < 1) {
                    continue;
                }

                $accResult = $cart->updateQty(
                    $accQty,
                    $accIdProduct,
                    $accIdAttr ?: null,
                    $accCustomization,
                    'up'
                );

                if (!$accResult) {
                    $accProduct = new Product($accIdProduct, false, $this->context->language->id);
                    $accName = Validate::isLoadedObject($accProduct)
                        ? ($accProduct->name[$this->context->language->id] ?? 'Unknown')
                        : 'Unknown';

                    $errors[] = sprintf(
                        $this->module->l('Could not add "%s" to cart', 'cart'),
                        $accName
                    );
                }
            }
        }

        // --- Refresh cart totals ---
        $cart->update();

        // --- Prepare response ---
        if (!empty($errors)) {
            // Partial failure: some products couldn't be added
            $this->ajaxDie(json_encode([
                'success' => false,
                'errors'  => $errors,
            ]));
        }

        // Full success
        $this->ajaxDie(json_encode([
            'success'    => true,
            'cart_url'   => $this->context->link->getPageLink('cart', null, null, [], false),
            'cart_count' => (int) $cart->nbProducts(),
            'products'   => array_map(function ($p) {
                return [
                    'id_product' => (int) $p['id_product'],
                    'name'       => $p['name'],
                    'quantity'   => (int) $p['cart_quantity'],
                ];
            }, $cart->getProducts()),
        ]));
    }

    /**
     * Lazy-load and return the AccessoryManager instance.
     *
     * @return AccessoryManager
     */
    private function getAccessoryManager()
    {
        if (!class_exists('AccessoryManager')) {
            require_once _PS_MODULE_DIR_ . 'fsaccesorios/src/AccessoryManager.php';
        }

        return new AccessoryManager(
            $this->context,
            $this->context->language->id,
            $this->context->shop->id,
            $this->context->link
        );
    }
}
