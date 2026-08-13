<?php
/**
 * fsaccesorios - Product accessories module for PrestaShop 8.2 / 9.x
 *
 * Displays native product accessories with checkbox selection,
 * quantity controls, and one-click add-to-cart functionality.
 *
 * @author    FS
 * @copyright 2026 FS
 * @license   MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Fsaccesorios extends Module
{
    public function __construct()
    {
        $this->name = 'fsaccesorios';
        $this->tab = 'front_office_features';
        $this->version = '1.0.14';
        $this->author = 'FS';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.2.0',
            'max' => '9.99.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('FS Accesorios');
        $this->description = $this->l(
            'Display product accessories with one-click checkout. Customers can select one or more accessories with quantity controls and add everything to cart at once.'
        );
        $this->confirmUninstall = $this->l(
            'Are you sure you want to uninstall FS Accesorios?'
        );
    }

    /**
     * Install the module: register hooks.
     *
     * @return bool
     */
    public function install()
    {
        return parent::install()
            && $this->registerHook('displayProductAdditionalInfo')
            && $this->registerHook('displayFooterProduct')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('displayHeader');
    }

    /**
     * Uninstall the module.
     *
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Render the accessories block on the product page.
     *
     * @param array $params Hook parameters containing the product data
     *
     * @return string Rendered HTML or empty string if no accessories
     */
    public function hookDisplayProductAdditionalInfo($params)
    {
        return $this->renderAccessoriesBlock($params);
    }

    /**
     * Fallback hook for themes without displayProductAdditionalInfo.
     *
     * @param array $params Hook parameters containing the product data
     *
     * @return string Rendered HTML or empty string if no accessories
     */
    public function hookDisplayFooterProduct($params)
    {
        return $this->renderAccessoriesBlock($params);
    }

    /**
     * Render the accessories block from hook parameters.
     *
     * @param array $params Hook parameters
     *
     * @return string Rendered HTML or empty string
     */
    private function renderAccessoriesBlock($params)
    {
        if (!isset($params['product']['id_product'])) {
            return '';
        }

        $idProduct = (int) $params['product']['id_product'];

        if (!class_exists('AccessoryManager')) {
            require_once $this->getLocalPath() . 'src/AccessoryManager.php';
        }

        $manager = new AccessoryManager(
            $this->context,
            $this->context->language->id,
            $this->context->shop->id,
            $this->context->link
        );

        $accessories = $manager->getAccessoriesForDisplay($idProduct);

        if (empty($accessories)) {
            return '';
        }

        $this->context->smarty->assign([
            'fs_accessories' => $accessories,
            'fs_module_dir'  => $this->_path,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/accessories.tpl');
    }

    /**
     * Load CSS and JS assets on the product page.
     *
     * @param array $params Hook parameters
     */
    public function hookActionFrontControllerSetMedia($params)
    {
        $controller = $this->context->controller;

        // Only load on product pages (including quick view)
        if (!$controller instanceof ProductController
            && !($controller instanceof Module
                && $controller->name === 'quickview')
        ) {
            return;
        }

        $this->context->controller->registerStylesheet(
            'fsaccesorios-css',
            'modules/' . $this->name . '/views/css/accessories.css',
            [
                'media'    => 'all',
                'priority' => 200,
                'version'  => $this->version,
            ]
        );

        $this->context->controller->registerJavascript(
            'fsaccesorios-js',
            'modules/' . $this->name . '/views/js/accessories.js',
            [
                'position' => 'bottom',
                'priority' => 200,
            ]
        );

        // Pass configuration to JS
        Media::addJsDef([
            'fsaccesorios_controller' => $this->context->link->getModuleLink(
                $this->name,
                'cart',
                [],
                true
            ),
            'fsaccesorios_cart_url' => $this->context->link->getPageLink('cart'),
            'fsaccesorios_token' => Tools::getToken(false),
            'fsaccesorios_i18n' => [
                'adding'    => $this->l('Adding...'),
                'success'   => $this->l('Products added to cart successfully'),
                'no_access' => $this->l('Select at least one accessory.'),
                'error'     => $this->l('An error occurred. Please try again.'),
            ],
        ]);
    }

    /**
     * Fallback: load JS directly. Some performance modules (jprestaspeedpack)
     * may strip assets registered via actionFrontControllerSetMedia.
     * A direct script tag bypasses the asset pipeline.
     *
     * @return string Script tag or empty
     */
    public function hookDisplayHeader()
    {
        $controller = $this->context->controller;

        if (!$controller instanceof ProductController
            && !($controller instanceof Module
                && $controller->name === 'quickview')
        ) {
            return '';
        }

        return '<script defer src="'
            . $this->_path . 'views/js/accessories.js?v=' . $this->version
            . '"></script>';
    }
}
