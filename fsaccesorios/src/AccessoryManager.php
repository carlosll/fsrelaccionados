<?php
/**
 * fsaccesorios - Accessory data manager
 *
 * Handles retrieval, formatting, and validation of accessory product data.
 * Centralizes all business logic for accessory operations.
 */

class AccessoryManager
{
    /** @var Context */
    private $context;

    /** @var int */
    private $idLang;

    /** @var int */
    private $idShop;

    /** @var Link */
    private $link;

    /**
     * @param Context $context PrestaShop context
     * @param int     $idLang  Language ID
     * @param int     $idShop  Shop ID
     * @param Link    $link    Link instance for URL generation
     */
    public function __construct($context, $idLang, $idShop, $link)
    {
        $this->context = $context;
        $this->idLang = (int) $idLang;
        $this->idShop = (int) $idShop;
        $this->link = $link;
    }

    /**
     * Get accessories with all display data for the hook template.
     *
     * @param int $idProduct Main product ID
     *
     * @return array Formatted accessories ready for template
     */
    public function getAccessoriesForDisplay($idProduct)
    {
        $idProduct = (int) $idProduct;
        $product = new Product($idProduct, false, $this->idLang, $this->idShop);

        if (!Validate::isLoadedObject($product) || !$product->active) {
            return [];
        }

        $accessories = $product->getAccessories($this->idLang, true);

        if (empty($accessories)) {
            return [];
        }

        $result = [];
        foreach ($accessories as $accessory) {
            $formatted = $this->formatAccessoryData($accessory);
            if ($formatted !== null) {
                $result[] = $formatted;
            }
        }

        return $result;
    }

    /**
     * Format a single accessory for template display.
     *
     * @param array $accessory Raw accessory data from Product::getAccessories()
     *
     * @return array|null Formatted data or null if product is invalid
     */
    private function formatAccessoryData($accessory)
    {
        $idProduct = (int) $accessory['id_product'];
        $idProductAttribute = (int) $accessory['id_product_attribute'];

        // Verify the accessory product exists and is active
        $accessoryProduct = new Product($idProduct, false, $this->idLang, $this->idShop);
        if (!Validate::isLoadedObject($accessoryProduct) || !$accessoryProduct->active) {
            return null;
        }

        // Cover image URL
        $cover = Product::getCover($idProduct);
        $imageType = ImageType::getFormattedName('small');
        $imageUrl = $this->link->getImageLink(
            isset($accessory['link_rewrite']) ? $accessory['link_rewrite'] : 'product',
            $cover ? (int) $cover['id_image'] : $idProduct,
            $imageType
        );

        // Price with taxes, including reductions
        $priceRaw = Product::getPriceStatic(
            $idProduct,
            true,   // with taxes
            null,   // default attribute
            6,      // precision
            null,   // currency (context default)
            false,  // no division
            true,   // with reduction
            1,      // quantity
            false,  // force associated tax
            null,   // customer
            null,   // cart
            null,   // address
            $specificPriceOutput, // output
            true,   // with eco tax
            true,   // use group reduction
            $this->context,
            $this->idShop
        );

        // Format price for display
        $formattedPrice = Tools::displayPrice($priceRaw);

        // Product URL
        $productLink = $this->link->getProductLink(
            $idProduct,
            isset($accessory['link_rewrite']) ? $accessory['link_rewrite'] : null,
            null,
            null,
            $this->idLang,
            $this->idShop,
            $idProductAttribute
        );

        // Stock: not managed in this store — always available
        $quantityAvailable = 999;
        $available = true;

        // Minimal quantity
        $minimalQuantity = isset($accessory['minimal_quantity']) && (int) $accessory['minimal_quantity'] > 0
            ? (int) $accessory['minimal_quantity']
            : 1;

        // Combinations (if the product has attributes)
        $combinations = $this->getProductCombinations($idProduct);

        // Product reference
        $reference = '';
        if (!empty($idProductAttribute)) {
            $combination = new Combination($idProductAttribute);
            if (Validate::isLoadedObject($combination)) {
                $reference = $combination->reference;
            }
        }
        if (empty($reference) && isset($accessory['reference'])) {
            $reference = $accessory['reference'];
        }

        return [
            'id_product'           => $idProduct,
            'id_product_attribute' => $idProductAttribute,
            'name'                 => $accessory['name'],
            'price'                => $formattedPrice,
            'price_raw'            => $priceRaw,
            'cover'                => $imageUrl,
            'link'                 => $productLink,
            'available'            => $available,
            'quantity_available'   => (int) $quantityAvailable,
            'minimal_quantity'     => $minimalQuantity,
            'max_quantity'         => $available ? (int) $quantityAvailable : 0,
            'reference'            => $reference,
            'has_combinations'     => !empty($combinations),
            'combinations'         => $combinations,
        ];
    }

    /**
     * Get attribute combinations with prices for a product.
     *
     * @param int $idProduct Product ID
     *
     * @return array Combinations with id_attribute, name, price, and default flag
     */
    private function getProductCombinations($idProduct)
    {
        $product = new Product($idProduct, false, $this->idLang);
        $combinations = $product->getAttributesGroups($this->idLang);

        if (empty($combinations)) {
            return [];
        }

        $result = [];
        $seen = [];

        foreach ($combinations as $comb) {
            $idAttr = (int) $comb['id_product_attribute'];
            if (isset($seen[$idAttr])) {
                continue;
            }
            $seen[$idAttr] = true;

            // Stock: not managed in this store — always available
            $qtyAvailable = 999;

            $priceImpact = Product::getPriceStatic(
                $idProduct,
                true,
                $idAttr,
                6
            );

            $result[] = [
                'id_product_attribute' => $idAttr,
                'name'                => $comb['group_name'] . ': ' . $comb['attribute_name'],
                'price'               => Tools::displayPrice($priceImpact),
                'price_raw'           => $priceImpact,
                'quantity'            => (int) $qtyAvailable,
                'default_on'          => (int) $comb['default_on'] === 1,
            ];
        }

        return $result;
    }

    /**
     * Validate accessory data received from a POST request.
     *
     * @param array $accessories   Array of accessories from JSON body
     * @param int   $mainProductId Main product ID to verify relationships
     *
     * @return array ['valid' => bool, 'errors' => string[]]
     */
    public function validateAccessories($accessories, $mainProductId)
    {
        if (empty($accessories) || !is_array($accessories)) {
            return ['valid' => true, 'errors' => []]; // No accessories is valid, just add main product
        }

        $errors = [];

        foreach ($accessories as $index => $acc) {
            $idProduct = (int) ($acc['id_product'] ?? 0);
            $idAttr    = (int) ($acc['id_product_attribute'] ?? 0);
            $qty       = (int) ($acc['quantity'] ?? 0);

            // Validate product ID
            if ($idProduct <= 0) {
                $errors[] = sprintf(
                    'Invalid product ID at position %d',
                    $index + 1
                );
                continue;
            }

            // Verify product exists and is active
            $product = new Product($idProduct, false, $this->idLang, $this->idShop);
            if (!Validate::isLoadedObject($product) || !$product->active) {
                $errors[] = sprintf(
                    'Product "%s" (ID: %d) is not available',
                    (is_array($product->name) ? ($product->name[$this->idLang] ?? reset($product->name)) : ($product->name ?: 'Product #' . $idProduct)),
                    $idProduct
                );
                continue;
            }

            $productName = is_array($product->name)
                ? ($product->name[$this->idLang] ?? reset($product->name))
                : $product->name;
            $productName = $productName ?: 'Product #' . $idProduct;

            // Verify it's actually an accessory of the main product
            if (!$this->isAccessoryOf($idProduct, $mainProductId)) {
                $errors[] = sprintf(
                    'Product "%s" is not an accessory of this product',
                    $productName
                );
                continue;
            }

            // Verify the selected combination belongs to this product
            if ($idAttr > 0) {
                $combination = new Combination($idAttr);
                if (!Validate::isLoadedObject($combination)
                    || (int) $combination->id_product !== $idProduct
                ) {
                    $errors[] = sprintf(
                        'Invalid combination for product "%s"',
                        $productName
                    );
                    continue;
                }
            }

            // Validate quantity
            $minimalQty = 1;
            if (isset($product->minimal_quantity) && (int) $product->minimal_quantity > 0) {
                $minimalQty = (int) $product->minimal_quantity;
            }

            if ($qty < $minimalQty) {
                $errors[] = sprintf(
                    'Minimum quantity for "%s" is %d',
                    $productName,
                    $minimalQty
                );
                continue;
            }

            // Stock: not managed in this store — skip validation
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Check if a product is registered as an accessory of another product.
     *
     * Uses the ps_accessory table that PrestaShop populates
     * when configuring accessories in the product editor.
     *
     * @param int $idProduct     Accessory product ID
     * @param int $idMainProduct Main product ID
     *
     * @return bool
     */
    public function isAccessoryOf($idProduct, $idMainProduct)
    {
        return (bool) Db::getInstance()->getValue(
            'SELECT 1 FROM ' . _DB_PREFIX_ . 'accessory
             WHERE id_product_1 = ' . (int) $idMainProduct . '
             AND id_product_2 = ' . (int) $idProduct
        );
    }
}
