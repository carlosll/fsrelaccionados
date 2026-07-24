<?php
/**
 * FS Category FAQ SEO
 *
 * Módulo para PrestaShop 8.x / 9.x que permite añadir bloques de preguntas
 * frecuentes (FAQ) con datos estructurados JSON-LD FAQPage en páginas
 * de categoría, inicio, CMS y fabricante.
 *
 * @author    FS
 * @copyright 2026 FS
 * @license   Non-Commercial — Uso bajo licencia
 * @version   1.7.8
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

// Autoload de las clases del módulo
require_once __DIR__ . '/classes/FsCategoryFaq.php';

use FSCategoryFaq\FsCategoryFaq as FaqModel;

class Fs_Category_Faq extends Module
{
    /**
     * Bandera para evitar que el bloque de FAQs se renderice dos veces
     * cuando varios hooks disparan en la misma página.
     */
    private $faqRendered = false;

    /**
     * Tipos de entidad soportados.
     */
    public const ENTITY_TYPES = [
        'category' => 'Categoría',
        'home' => 'Inicio',
        'cms' => 'Página CMS',
        'manufacturer' => 'Fabricante',
    ];

    /**
     * Pestañas del menú back office que crea el módulo.
     * El padre gestiona automáticamente install/uninstall de tabs.
     */
    public $tabs = [
        [
            'name' => 'FAQs',
            'class_name' => 'AdminFsCategoryFaq',
            'visible' => true,
            'icon' => 'help',
        ],
    ];

    /**
     * Hooks que registra el módulo.
     */
    private const MODULE_HOOKS = [
        'displayHeader',
        'displayCategoryFaq',
        'displayCategoryFooter',
        'displayFooterProduct',
        'displayFooter',
        'displayFullWidthCategoryFooter',
        'displayWrapperBottom',
        'displayHome',
        'displayHomeBottom',
    ];

    /**
     * Mapas de diseño: traducen una opción elegida en el back office a un
     * valor CSS concreto. Nunca se interpola texto libre del admin en el
     * <style> generado; solo estos valores whitelisteados o colores hex
     * validados por regex (ver sanitizeHexColor()).
     */
    private const RADIUS_MAP = [
        'sharp' => '4px',
        'soft' => '12px',
        'round' => '20px',
        'xround' => '28px',
    ];

    private const DENSITY_MAP = [
        'compact' => [
            'question_padding' => '0.75rem 2.5rem 0.75rem 1rem',
            'answer_padding' => '0.75rem 1rem 1rem 1rem',
            'item_margin' => '0 0 0.5rem 0',
        ],
        'comfortable' => [
            'question_padding' => '1rem 2.75rem 1rem 1.25rem',
            'answer_padding' => '1rem 1.25rem 1.25rem 1.25rem',
            'item_margin' => '0 0 0.75rem 0',
        ],
        'spacious' => [
            'question_padding' => '1.375rem 3rem 1.375rem 1.5rem',
            'answer_padding' => '1.25rem 1.5rem 1.75rem 1.5rem',
            'item_margin' => '0 0 1.125rem 0',
        ],
    ];

    private const SHADOW_MAP = [
        'none' => ['shadow' => 'none', 'shadow_hover' => 'none'],
        'soft' => ['shadow' => '0 1px 3px rgba(0,0,0,.06)', 'shadow_hover' => '0 10px 24px rgba(0,0,0,.10)'],
        'strong' => ['shadow' => '0 2px 6px rgba(0,0,0,.10)', 'shadow_hover' => '0 16px 36px rgba(0,0,0,.18)'],
    ];

    private const TEXT_SCALE_MAP = [
        'compact' => ['title' => '1.25rem', 'question' => '0.9375rem', 'answer' => '0.875rem'],
        'normal' => ['title' => '1.5rem', 'question' => '1rem', 'answer' => '0.9375rem'],
        'large' => ['title' => '1.875rem', 'question' => '1.0625rem', 'answer' => '1rem'],
        'xlarge' => ['title' => '2.25rem', 'question' => '1.125rem', 'answer' => '1.0625rem'],
    ];

    private const TITLE_SIZE_MAP = [
        's' => '1.25rem',
        'm' => '1.5rem',
        'l' => '1.875rem',
        'xl' => '2.25rem',
        'xxl' => '2.75rem',
    ];

    private const OPEN_MODE_OPTIONS = ['all_closed', 'first_open', 'all_open'];

    private const MAX_WIDTH_MAP = [
        'full' => '100%',
        'xl' => '1200px',
        'lg' => '960px',
        'md' => '800px',
    ];

    private const ICON_STYLES = ['chevron', 'plusminus', 'none'];

    public function __construct()
    {
        $this->name = 'fs_category_faq';
        $this->tab = 'administration';
        $this->version = '1.7.8';
        $this->author = 'FS';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '9.99.99'];

        parent::__construct();

        $this->displayName = $this->trans('FS Category FAQ SEO', [], 'Modules.Fs_category_faq.Main');
        $this->description = $this->trans(
            'Añade bloques de preguntas frecuentes con datos estructurados JSON-LD FAQPage en páginas de categoría, inicio, CMS y fabricante.',
            [],
            'Modules.Fs_category_faq.Main'
        );
        $this->confirmUninstall = $this->trans(
            '¿Estás seguro de que quieres desinstalar este módulo?',
            [],
            'Modules.Fs_category_faq.Main'
        );
    }

    // ---------------------------------------------------------------
    //  INSTALACIÓN / DESINSTALACIÓN
    // ---------------------------------------------------------------

    /**
     * Instala el módulo: registra hooks, crea tablas y guarda configuración por defecto.
     */
    public function install(): bool
    {
        return parent::install()
            && $this->registerHooks()
            && $this->installDatabase()
            && $this->installDefaultConfig();
    }

    /**
     * Desinstala el módulo.
     */
    public function uninstall(): bool
    {
        $keepData = (bool) Configuration::get('FSCATEGORYFAQ_KEEP_DATA');

        return parent::uninstall()
            && $this->uninstallConfig()
            && ($keepData || $this->uninstallDatabase());
    }

    /**
     * Registra todos los hooks necesarios.
     */
    private function registerHooks(): bool
    {
        foreach (self::MODULE_HOOKS as $hook) {
            if (!$this->registerHook($hook)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Crea las tablas de base de datos.
     */
    private function installDatabase(): bool
    {
        return (bool) require __DIR__ . '/sql/install.php';
    }

    /**
     * Borra las tablas de base de datos.
     */
    private function uninstallDatabase(): bool
    {
        return (bool) require __DIR__ . '/sql/uninstall.php';
    }

    /**
     * Guarda la configuración por defecto del módulo.
     */
    private function installDefaultConfig(): bool
    {
        $defaults = [
            'FSCATEGORYFAQ_ENABLED' => true,
            'FSCATEGORYFAQ_SHOW_TITLE' => true,
            'FSCATEGORYFAQ_TITLE' => 'Preguntas frecuentes sobre {entity_name}',
            'FSCATEGORYFAQ_ENABLE_JSONLD' => true,
            'FSCATEGORYFAQ_MAX_FAQS' => 10,
            'FSCATEGORYFAQ_HOOK_CATEGORY' => 'displayCategoryFooter',
            'FSCATEGORYFAQ_HOOK_HOME' => 'displayHome',
            'FSCATEGORYFAQ_HOOK_CMS' => 'displayFooter',
            'FSCATEGORYFAQ_HOOK_MANUFACTURER' => 'displayCategoryFaq',
            'FSCATEGORYFAQ_USE_ACCORDION' => true,
            'FSCATEGORYFAQ_OPEN_MODE' => 'first_open',
            'FSCATEGORYFAQ_EXTRA_CSS_CLASS' => '',
            'FSCATEGORYFAQ_KEEP_DATA' => true,
            'FSCATEGORYFAQ_COLOR_ACCENT' => '#1066B2',
            'FSCATEGORYFAQ_COLOR_BG' => '#ffffff',
            'FSCATEGORYFAQ_COLOR_QUESTION' => '#1a1a2e',
            'FSCATEGORYFAQ_COLOR_ANSWER' => '#4b5563',
            'FSCATEGORYFAQ_COLOR_BORDER' => '#e5e7eb',
            'FSCATEGORYFAQ_RADIUS' => 'soft',
            'FSCATEGORYFAQ_DENSITY' => 'comfortable',
            'FSCATEGORYFAQ_SHADOW' => 'soft',
            'FSCATEGORYFAQ_ICON_STYLE' => 'chevron',
            'FSCATEGORYFAQ_TEXT_SCALE' => 'normal',
            'FSCATEGORYFAQ_TITLE_SIZE' => 'm',
            'FSCATEGORYFAQ_MAX_WIDTH' => 'full',
            'FSCATEGORYFAQ_MARGIN_TOP' => '0',
            'FSCATEGORYFAQ_MARGIN_BOTTOM' => '2rem',
        ];

        foreach ($defaults as $key => $value) {
            if (!Configuration::updateValue($key, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Elimina la configuración del módulo.
     */
    private function uninstallConfig(): bool
    {
        $keys = [
            'FSCATEGORYFAQ_ENABLED',
            'FSCATEGORYFAQ_SHOW_TITLE',
            'FSCATEGORYFAQ_TITLE',
            'FSCATEGORYFAQ_ENABLE_JSONLD',
            'FSCATEGORYFAQ_MAX_FAQS',
            'FSCATEGORYFAQ_HOOK_CATEGORY',
            'FSCATEGORYFAQ_HOOK_HOME',
            'FSCATEGORYFAQ_HOOK_CMS',
            'FSCATEGORYFAQ_HOOK_MANUFACTURER',
            'FSCATEGORYFAQ_USE_ACCORDION',
            'FSCATEGORYFAQ_OPEN_MODE',
            'FSCATEGORYFAQ_EXTRA_CSS_CLASS',
            'FSCATEGORYFAQ_KEEP_DATA',
            'FSCATEGORYFAQ_COLOR_ACCENT',
            'FSCATEGORYFAQ_COLOR_BG',
            'FSCATEGORYFAQ_COLOR_QUESTION',
            'FSCATEGORYFAQ_COLOR_ANSWER',
            'FSCATEGORYFAQ_COLOR_BORDER',
            'FSCATEGORYFAQ_RADIUS',
            'FSCATEGORYFAQ_DENSITY',
            'FSCATEGORYFAQ_SHADOW',
            'FSCATEGORYFAQ_ICON_STYLE',
            'FSCATEGORYFAQ_TEXT_SCALE',
            'FSCATEGORYFAQ_TITLE_SIZE',
            'FSCATEGORYFAQ_MAX_WIDTH',
            'FSCATEGORYFAQ_MARGIN_TOP',
            'FSCATEGORYFAQ_MARGIN_BOTTOM',
        ];

        foreach ($keys as $key) {
            Configuration::deleteByName($key);
        }

        return true;
    }

    // ---------------------------------------------------------------
    //  CONFIGURACIÓN DEL MÓDULO (BACK OFFICE)
    // ---------------------------------------------------------------

    /**
     * Muestra y procesa el formulario de configuración del módulo.
     */
    public function getContent(): string
    {
        $output = '';

        // Auto-registrar hooks que pudieran faltar (evita tener que reinstalar)
        $this->ensureHooksRegistered();

        // Procesar el formulario si se ha enviado
        if (Tools::isSubmit('submitFSCategoryFaqConfig')) {
            $output .= $this->processConfigForm();
        }

        // Enlace al gestor de FAQs
        $faqManagerUrl = $this->context->link->getAdminLink('AdminFsCategoryFaq');
        $output .= $this->displayConfirmation(
            $this->trans(
                'Para gestionar las preguntas frecuentes, accede al',
                [],
                'Modules.Fs_category_faq.Main'
            )
            . ' <a href="' . $faqManagerUrl . '" class="btn btn-primary" style="margin-left:8px;">'
            . $this->trans('Gestor de FAQs', [], 'Modules.Fs_category_faq.Main')
            . '</a>'
        );

        return $output . $this->renderConfigForm();
    }

    /**
     * Asegura que todos los hooks necesarios estén registrados.
     * Así no hace falta reinstalar al añadir hooks nuevos.
     */
    private function ensureHooksRegistered(): void
    {
        foreach (self::MODULE_HOOKS as $hook) {
            $idHook = (int) Hook::getIdByName($hook);
            if ($idHook > 0) {
                $alreadyRegistered = (bool) Db::getInstance()->getValue(
                    'SELECT 1 FROM `' . _DB_PREFIX_ . 'hook_module`
                     WHERE `id_hook` = ' . $idHook . '
                     AND `id_module` = ' . (int) $this->id
                );
                if (!$alreadyRegistered) {
                    $this->registerHook($hook);
                }
            }
        }
    }

    /**
     * Procesa el envío del formulario de configuración.
     */
    private function processConfigForm(): string
    {
        $formValues = [
            'FSCATEGORYFAQ_ENABLED' => (bool) Tools::getValue('FSCATEGORYFAQ_ENABLED'),
            'FSCATEGORYFAQ_SHOW_TITLE' => (bool) Tools::getValue('FSCATEGORYFAQ_SHOW_TITLE'),
            'FSCATEGORYFAQ_TITLE' => (string) Tools::getValue('FSCATEGORYFAQ_TITLE'),
            'FSCATEGORYFAQ_ENABLE_JSONLD' => (bool) Tools::getValue('FSCATEGORYFAQ_ENABLE_JSONLD'),
            'FSCATEGORYFAQ_MAX_FAQS' => (int) Tools::getValue('FSCATEGORYFAQ_MAX_FAQS'),
            'FSCATEGORYFAQ_HOOK_CATEGORY' => (string) Tools::getValue('FSCATEGORYFAQ_HOOK_CATEGORY'),
            'FSCATEGORYFAQ_HOOK_HOME' => (string) Tools::getValue('FSCATEGORYFAQ_HOOK_HOME'),
            'FSCATEGORYFAQ_HOOK_CMS' => (string) Tools::getValue('FSCATEGORYFAQ_HOOK_CMS'),
            'FSCATEGORYFAQ_HOOK_MANUFACTURER' => (string) Tools::getValue('FSCATEGORYFAQ_HOOK_MANUFACTURER'),
            'FSCATEGORYFAQ_USE_ACCORDION' => (bool) Tools::getValue('FSCATEGORYFAQ_USE_ACCORDION'),
            'FSCATEGORYFAQ_OPEN_MODE' => $this->sanitizeChoice((string) Tools::getValue('FSCATEGORYFAQ_OPEN_MODE'), self::OPEN_MODE_OPTIONS, 'first_open'),
            'FSCATEGORYFAQ_EXTRA_CSS_CLASS' => (string) Tools::getValue('FSCATEGORYFAQ_EXTRA_CSS_CLASS'),
            'FSCATEGORYFAQ_KEEP_DATA' => (bool) Tools::getValue('FSCATEGORYFAQ_KEEP_DATA'),
            'FSCATEGORYFAQ_COLOR_ACCENT' => $this->sanitizeHexColor((string) Tools::getValue('FSCATEGORYFAQ_COLOR_ACCENT'), '#1066B2'),
            'FSCATEGORYFAQ_COLOR_BG' => $this->sanitizeHexColor((string) Tools::getValue('FSCATEGORYFAQ_COLOR_BG'), '#ffffff'),
            'FSCATEGORYFAQ_COLOR_QUESTION' => $this->sanitizeHexColor((string) Tools::getValue('FSCATEGORYFAQ_COLOR_QUESTION'), '#1a1a2e'),
            'FSCATEGORYFAQ_COLOR_ANSWER' => $this->sanitizeHexColor((string) Tools::getValue('FSCATEGORYFAQ_COLOR_ANSWER'), '#4b5563'),
            'FSCATEGORYFAQ_COLOR_BORDER' => $this->sanitizeHexColor((string) Tools::getValue('FSCATEGORYFAQ_COLOR_BORDER'), '#e5e7eb'),
            'FSCATEGORYFAQ_RADIUS' => $this->sanitizeChoice((string) Tools::getValue('FSCATEGORYFAQ_RADIUS'), array_keys(self::RADIUS_MAP), 'soft'),
            'FSCATEGORYFAQ_DENSITY' => $this->sanitizeChoice((string) Tools::getValue('FSCATEGORYFAQ_DENSITY'), array_keys(self::DENSITY_MAP), 'comfortable'),
            'FSCATEGORYFAQ_SHADOW' => $this->sanitizeChoice((string) Tools::getValue('FSCATEGORYFAQ_SHADOW'), array_keys(self::SHADOW_MAP), 'soft'),
            'FSCATEGORYFAQ_ICON_STYLE' => $this->sanitizeChoice((string) Tools::getValue('FSCATEGORYFAQ_ICON_STYLE'), self::ICON_STYLES, 'chevron'),
            'FSCATEGORYFAQ_TEXT_SCALE' => $this->sanitizeChoice((string) Tools::getValue('FSCATEGORYFAQ_TEXT_SCALE'), array_keys(self::TEXT_SCALE_MAP), 'normal'),
            'FSCATEGORYFAQ_TITLE_SIZE' => $this->sanitizeChoice((string) Tools::getValue('FSCATEGORYFAQ_TITLE_SIZE'), array_keys(self::TITLE_SIZE_MAP), 'm'),
            'FSCATEGORYFAQ_MAX_WIDTH' => $this->sanitizeChoice((string) Tools::getValue('FSCATEGORYFAQ_MAX_WIDTH'), array_keys(self::MAX_WIDTH_MAP), 'full'),
            'FSCATEGORYFAQ_MARGIN_TOP' => $this->sanitizeMarginValue((string) Tools::getValue('FSCATEGORYFAQ_MARGIN_TOP'), '0'),
            'FSCATEGORYFAQ_MARGIN_BOTTOM' => $this->sanitizeMarginValue((string) Tools::getValue('FSCATEGORYFAQ_MARGIN_BOTTOM'), '2rem'),
        ];

        // Validar
        $maxFaqs = $formValues['FSCATEGORYFAQ_MAX_FAQS'];
        if ($maxFaqs < 1 || $maxFaqs > 50) {
            return $this->displayError(
                $this->trans('El número máximo de FAQs debe estar entre 1 y 50.', [], 'Modules.Fs_category_faq.Main')
            );
        }

        // Validar título
        if (empty(trim($formValues['FSCATEGORYFAQ_TITLE']))) {
            $formValues['FSCATEGORYFAQ_TITLE'] = 'Preguntas frecuentes';
        }

        // Guardar
        foreach ($formValues as $key => $value) {
            Configuration::updateValue($key, $value);
        }

        // Limpiar caché
        $this->clearFaqCache();

        return $this->displayConfirmation(
            $this->trans('Configuración guardada correctamente.', [], 'Modules.Fs_category_faq.Main')
        );
    }

    /**
     * Renderiza el formulario de configuración con HelperForm.
     */
    private function renderConfigForm(): string
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submitFSCategoryFaqConfig';
        $helper->default_form_language = (int) $this->context->language->id;

        // Los fallbacks del 5º parámetro cubren instalaciones que
        // actualizaron desde versiones antiguas donde algunas claves
        // de Configuration no existían todavía.
        $helper->fields_value = [
            'FSCATEGORYFAQ_ENABLED' => Configuration::get('FSCATEGORYFAQ_ENABLED', null, null, null, true),
            'FSCATEGORYFAQ_SHOW_TITLE' => Configuration::get('FSCATEGORYFAQ_SHOW_TITLE', null, null, null, true),
            'FSCATEGORYFAQ_TITLE' => Configuration::get('FSCATEGORYFAQ_TITLE', null, null, null, 'Preguntas frecuentes sobre {entity_name}'),
            'FSCATEGORYFAQ_ENABLE_JSONLD' => Configuration::get('FSCATEGORYFAQ_ENABLE_JSONLD', null, null, null, true),
            'FSCATEGORYFAQ_MAX_FAQS' => Configuration::get('FSCATEGORYFAQ_MAX_FAQS', null, null, null, 10),
            'FSCATEGORYFAQ_HOOK_CATEGORY' => Configuration::get('FSCATEGORYFAQ_HOOK_CATEGORY', null, null, null, 'displayCategoryFooter'),
            'FSCATEGORYFAQ_HOOK_HOME' => Configuration::get('FSCATEGORYFAQ_HOOK_HOME', null, null, null, 'displayHome'),
            'FSCATEGORYFAQ_HOOK_CMS' => Configuration::get('FSCATEGORYFAQ_HOOK_CMS', null, null, null, 'displayFooter'),
            'FSCATEGORYFAQ_HOOK_MANUFACTURER' => Configuration::get('FSCATEGORYFAQ_HOOK_MANUFACTURER', null, null, null, 'displayFullWidthCategoryFooter'),
            'FSCATEGORYFAQ_USE_ACCORDION' => Configuration::get('FSCATEGORYFAQ_USE_ACCORDION', null, null, null, true),
            'FSCATEGORYFAQ_OPEN_MODE' => Configuration::get('FSCATEGORYFAQ_OPEN_MODE', null, null, null, 'first_open'),
            'FSCATEGORYFAQ_EXTRA_CSS_CLASS' => Configuration::get('FSCATEGORYFAQ_EXTRA_CSS_CLASS', null, null, null, ''),
            'FSCATEGORYFAQ_KEEP_DATA' => Configuration::get('FSCATEGORYFAQ_KEEP_DATA', null, null, null, true),
            'FSCATEGORYFAQ_COLOR_ACCENT' => Configuration::get('FSCATEGORYFAQ_COLOR_ACCENT', null, null, null, '#1066B2'),
            'FSCATEGORYFAQ_COLOR_BG' => Configuration::get('FSCATEGORYFAQ_COLOR_BG', null, null, null, '#ffffff'),
            'FSCATEGORYFAQ_COLOR_QUESTION' => Configuration::get('FSCATEGORYFAQ_COLOR_QUESTION', null, null, null, '#1a1a2e'),
            'FSCATEGORYFAQ_COLOR_ANSWER' => Configuration::get('FSCATEGORYFAQ_COLOR_ANSWER', null, null, null, '#4b5563'),
            'FSCATEGORYFAQ_COLOR_BORDER' => Configuration::get('FSCATEGORYFAQ_COLOR_BORDER', null, null, null, '#e5e7eb'),
            'FSCATEGORYFAQ_RADIUS' => Configuration::get('FSCATEGORYFAQ_RADIUS', null, null, null, 'soft'),
            'FSCATEGORYFAQ_DENSITY' => Configuration::get('FSCATEGORYFAQ_DENSITY', null, null, null, 'comfortable'),
            'FSCATEGORYFAQ_SHADOW' => Configuration::get('FSCATEGORYFAQ_SHADOW', null, null, null, 'soft'),
            'FSCATEGORYFAQ_ICON_STYLE' => Configuration::get('FSCATEGORYFAQ_ICON_STYLE', null, null, null, 'chevron'),
            'FSCATEGORYFAQ_TEXT_SCALE' => Configuration::get('FSCATEGORYFAQ_TEXT_SCALE', null, null, null, 'normal'),
            'FSCATEGORYFAQ_TITLE_SIZE' => Configuration::get('FSCATEGORYFAQ_TITLE_SIZE', null, null, null, 'm'),
            'FSCATEGORYFAQ_MAX_WIDTH' => Configuration::get('FSCATEGORYFAQ_MAX_WIDTH', null, null, null, 'full'),
            'FSCATEGORYFAQ_MARGIN_TOP' => Configuration::get('FSCATEGORYFAQ_MARGIN_TOP', null, null, null, '0'),
            'FSCATEGORYFAQ_MARGIN_BOTTOM' => Configuration::get('FSCATEGORYFAQ_MARGIN_BOTTOM', null, null, null, '2rem'),
        ];

        return $helper->generateForm([$this->getConfigFormDefinition()]);
    }

    /**
     * Define la estructura del formulario de configuración.
     */
    private function getConfigFormDefinition(): array
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Configuración', [], 'Modules.Fs_category_faq.Main'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    // === Bloque General ===
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Módulo activo', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Sí', [], 'Modules.Fs_category_faq.Main')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.Fs_category_faq.Main')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Mostrar título del bloque', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_SHOW_TITLE',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Sí', [], 'Modules.Fs_category_faq.Main')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.Fs_category_faq.Main')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Título por defecto', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_TITLE',
                        'desc' => $this->trans(
                            'Usa {entity_name} o %category_name% para insertar el nombre de la categoría, página o fabricante. Ej: "Preguntas frecuentes sobre {entity_name}"',
                            [],
                            'Modules.Fs_category_faq.Main'
                        ),
                        'size' => 60,
                        'maxlength' => 255,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Nº máximo de FAQs visibles', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_MAX_FAQS',
                        'desc' => $this->trans('Entre 1 y 50. Las FAQs sobrantes no se mostrarán.', [], 'Modules.Fs_category_faq.Main'),
                        'size' => 5,
                        'maxlength' => 2,
                    ],

                    // === Bloque Visual ===
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Usar acordeón', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_USE_ACCORDION',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Sí', [], 'Modules.Fs_category_faq.Main')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.Fs_category_faq.Main')],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Apertura de las FAQs', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_OPEN_MODE',
                        'desc' => $this->trans('Controla cómo se muestran las FAQs al cargar la página. Solo aplica si el acordeón está activo.', [], 'Modules.Fs_category_faq.Main'),
                        'options' => [
                            'query' => [
                                ['id' => 'all_closed', 'name' => $this->trans('Todas cerradas — el usuario abre la que le interesa', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'first_open', 'name' => $this->trans('Primera abierta — la primera FAQ abierta, resto cerradas (recomendado)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'all_open', 'name' => $this->trans('Todas abiertas — todas las FAQs visibles al cargar', [], 'Modules.Fs_category_faq.Main')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Clase CSS adicional', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_EXTRA_CSS_CLASS',
                        'desc' => $this->trans('Para adaptar el diseño a tu tema. Ej: "panda-faq"', [], 'Modules.Fs_category_faq.Main'),
                        'size' => 40,
                        'maxlength' => 100,
                    ],

                    // === Bloque Diseño y colores ===
                    [
                        'type' => 'color',
                        'label' => $this->trans('Color de acento', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_COLOR_ACCENT',
                        'desc' => $this->trans('Icono, borde del ítem abierto y enlaces. Por defecto, el azul de tu marca.', [], 'Modules.Fs_category_faq.Main'),
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->trans('Color de fondo de las tarjetas', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_COLOR_BG',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->trans('Color del texto de la pregunta', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_COLOR_QUESTION',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->trans('Color del texto de la respuesta', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_COLOR_ANSWER',
                    ],
                    [
                        'type' => 'color',
                        'label' => $this->trans('Color de los bordes', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_COLOR_BORDER',
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Redondeo de esquinas', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_RADIUS',
                        'options' => [
                            'query' => [
                                ['id' => 'sharp', 'name' => $this->trans('Recto', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'soft', 'name' => $this->trans('Suave (recomendado)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'round', 'name' => $this->trans('Redondeado', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'xround', 'name' => $this->trans('Muy redondeado', [], 'Modules.Fs_category_faq.Main')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Densidad del espaciado', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_DENSITY',
                        'options' => [
                            'query' => [
                                ['id' => 'compact', 'name' => $this->trans('Compacta', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'comfortable', 'name' => $this->trans('Normal (recomendado)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'spacious', 'name' => $this->trans('Amplia', [], 'Modules.Fs_category_faq.Main')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Intensidad de la sombra', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_SHADOW',
                        'options' => [
                            'query' => [
                                ['id' => 'none', 'name' => $this->trans('Ninguna', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'soft', 'name' => $this->trans('Sutil (recomendado)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'strong', 'name' => $this->trans('Marcada', [], 'Modules.Fs_category_faq.Main')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Estilo del icono', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_ICON_STYLE',
                        'options' => [
                            'query' => [
                                ['id' => 'chevron', 'name' => $this->trans('Flecha (recomendado)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'plusminus', 'name' => $this->trans('Más / menos', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'none', 'name' => $this->trans('Sin icono', [], 'Modules.Fs_category_faq.Main')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Tamaño del texto', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_TEXT_SCALE',
                        'desc' => $this->trans('Escala proporcionalmente la pregunta y la respuesta. El título se controla por separado en el campo inferior.', [], 'Modules.Fs_category_faq.Main'),
                        'options' => [
                            'query' => [
                                ['id' => 'compact', 'name' => $this->trans('Compacto', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'normal', 'name' => $this->trans('Normal (recomendado)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'large', 'name' => $this->trans('Grande', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'xlarge', 'name' => $this->trans('Extra grande', [], 'Modules.Fs_category_faq.Main')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Tamaño del título', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_TITLE_SIZE',
                        'desc' => $this->trans('Controla solo el tamaño del título del bloque de FAQs, independientemente de la escala del texto.', [], 'Modules.Fs_category_faq.Main'),
                        'options' => [
                            'query' => [
                                ['id' => 's', 'name' => $this->trans('Pequeño (1.25rem)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'm', 'name' => $this->trans('Mediano (1.5rem)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'l', 'name' => $this->trans('Grande (1.875rem)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'xl', 'name' => $this->trans('Extra grande (2.25rem)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'xxl', 'name' => $this->trans('XXL (2.75rem)', [], 'Modules.Fs_category_faq.Main')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Ancho del bloque', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_MAX_WIDTH',
                        'desc' => $this->trans('"100% del contenedor" evita que el bloque quede recortado y ocupa todo el ancho disponible.', [], 'Modules.Fs_category_faq.Main'),
                        'options' => [
                            'query' => [
                                ['id' => 'full', 'name' => $this->trans('100% del contenedor (recomendado)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'xl', 'name' => $this->trans('Máximo 1200px, centrado', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'lg', 'name' => $this->trans('Máximo 960px, centrado', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'md', 'name' => $this->trans('Máximo 800px, centrado', [], 'Modules.Fs_category_faq.Main')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Margen superior del bloque', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_MARGIN_TOP',
                        'desc' => $this->trans('Separación con el contenido anterior. Usa valores CSS: 0, 1rem, 2rem, 20px… Ej: "2rem" (recomendado: 0).', [], 'Modules.Fs_category_faq.Main'),
                        'size' => 10,
                        'maxlength' => 20,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Margen inferior del bloque', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_MARGIN_BOTTOM',
                        'desc' => $this->trans('Separación con el contenido posterior. Usa valores CSS: 0, 1rem, 2rem, 20px… Ej: "2rem" (recomendado: 2rem).', [], 'Modules.Fs_category_faq.Main'),
                        'size' => 10,
                        'maxlength' => 20,
                    ],

                    // === Bloque Hooks por tipo de página ===
                    [
                        'type' => 'select',
                        'label' => $this->trans('Hook en categorías', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_HOOK_CATEGORY',
                        'desc' => $this->trans('Dónde mostrar las FAQs en páginas de categoría. En Panda usa displayCategoryFooter.', [], 'Modules.Fs_category_faq.Main'),
                        'options' => [
                            'query' => [
                                ['id' => 'displayCategoryFooter', 'name' => $this->trans('displayCategoryFooter — Panda, bajo productos (recomendado)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'displayFullWidthCategoryFooter', 'name' => $this->trans('displayFullWidthCategoryFooter — bajo el wrapper, antes del footer', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'displayWrapperBottom', 'name' => $this->trans('displayWrapperBottom — bajo el wrapper, antes del footer', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'displayCategoryFaq', 'name' => $this->trans('displayCategoryFaq (requiere {hook} en plantilla)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'displayFooterProduct', 'name' => $this->trans('displayFooterProduct', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'displayFooter', 'name' => $this->trans('displayFooter — dentro del footer (respaldo)', [], 'Modules.Fs_category_faq.Main')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Hook en inicio', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_HOOK_HOME',
                        'desc' => $this->trans('Dónde mostrar las FAQs en la página de inicio.', [], 'Modules.Fs_category_faq.Main'),
                        'options' => [
                            'query' => [
                                ['id' => 'displayHome', 'name' => $this->trans('displayHome — contenido central de la home (recomendado)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'displayHomeBottom', 'name' => $this->trans('displayHomeBottom — zona inferior de la home', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'displayFooter', 'name' => $this->trans('displayFooter — dentro del footer', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'displayFooterProduct', 'name' => $this->trans('displayFooterProduct', [], 'Modules.Fs_category_faq.Main')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Hook en páginas CMS', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_HOOK_CMS',
                        'desc' => $this->trans('Dónde mostrar las FAQs en páginas CMS.', [], 'Modules.Fs_category_faq.Main'),
                        'options' => [
                            'query' => [
                                ['id' => 'displayFooter', 'name' => $this->trans('displayFooter (recomendado)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'displayFooterProduct', 'name' => $this->trans('displayFooterProduct', [], 'Modules.Fs_category_faq.Main')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Hook en fabricantes', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_HOOK_MANUFACTURER',
                        'desc' => $this->trans('Dónde mostrar las FAQs en páginas de fabricante.', [], 'Modules.Fs_category_faq.Main'),
                        'options' => [
                            'query' => [
                                ['id' => 'displayCategoryFaq', 'name' => $this->trans('displayCategoryFaq — justo debajo de los productos (recomendado)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'displayCategoryFooter', 'name' => $this->trans('displayCategoryFooter — bajo productos (Panda)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'displayFullWidthCategoryFooter', 'name' => $this->trans('displayFullWidthCategoryFooter — bajo el wrapper principal, antes del footer', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'displayWrapperBottom', 'name' => $this->trans('displayWrapperBottom — bajo el wrapper, antes del footer', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'displayFooter', 'name' => $this->trans('displayFooter — dentro del footer (respaldo)', [], 'Modules.Fs_category_faq.Main')],
                                ['id' => 'displayFooterProduct', 'name' => $this->trans('displayFooterProduct', [], 'Modules.Fs_category_faq.Main')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],

                    // === Bloque JSON-LD ===
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Activar datos estructurados JSON-LD', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_ENABLE_JSONLD',
                        'is_bool' => true,
                        'desc' => $this->trans(
                            'Genera schema FAQPage para Google. Solo incluye FAQs visibles. El contenido debe coincidir con lo mostrado en pantalla.',
                            [],
                            'Modules.Fs_category_faq.Main'
                        ),
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Sí', [], 'Modules.Fs_category_faq.Main')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.Fs_category_faq.Main')],
                        ],
                    ],

                    // === Bloque Desinstalación ===
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Conservar datos al desinstalar', [], 'Modules.Fs_category_faq.Main'),
                        'name' => 'FSCATEGORYFAQ_KEEP_DATA',
                        'is_bool' => true,
                        'desc' => $this->trans(
                            'Si está activo, las tablas y FAQs se conservan al desinstalar el módulo. Recomendado para no perder contenido accidentalmente.',
                            [],
                            'Modules.Fs_category_faq.Main'
                        ),
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Sí (conservar)', [], 'Modules.Fs_category_faq.Main')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No (borrar todo)', [], 'Modules.Fs_category_faq.Main')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Guardar', [], 'Modules.Fs_category_faq.Main'),
                    'class' => 'btn btn-primary pull-right',
                ],
            ],
        ];
    }

    // ---------------------------------------------------------------
    //  HOOKS FRONT OFFICE
    // ---------------------------------------------------------------

    /**
     * Carga CSS y JS solo en páginas relevantes con FAQs activas.
     */
    public function hookDisplayHeader(array $params): string
    {
        if (!$this->isModuleEnabled()) {
            return '';
        }

        $entity = $this->detectCurrentEntity();
        if ($entity === null) {
            return '';
        }

        $faqs = FaqModel::getByEntity(
            $entity['type'],
            $entity['id'],
            (int) $this->context->language->id,
            (int) $this->context->shop->id,
            (int) Configuration::get('FSCATEGORYFAQ_MAX_FAQS')
        );

        if (empty($faqs)) {
            return '';
        }

        $this->context->controller->registerStylesheet(
            'fs-category-faq-front',
            'modules/' . $this->name . '/views/css/front.css',
            ['media' => 'all', 'priority' => 50]
        );

        $this->context->controller->registerJavascript(
            'fs-category-faq-front',
            'modules/' . $this->name . '/views/js/front.js',
            ['position' => 'bottom', 'priority' => 50]
        );

        return '';
    }

    /**
     * Hook personalizado displayCategoryFaq (principal, requiere editar plantilla).
     */
    public function hookDisplayCategoryFaq(array $params): string
    {
        return $this->renderFaqBlock($params);
    }

    /**
     * Hook del tema Panda: displayCategoryFooter (sin modificar plantilla).
     */
    public function hookDisplayCategoryFooter(array $params): string
    {
        return $this->renderFaqBlock($params);
    }

    /**
     * Hook de fallback en zona inferior de producto/categoría.
     */
    public function hookDisplayFooterProduct(array $params): string
    {
        return $this->renderFaqBlock($params);
    }

    /**
     * Hook universal de respaldo (footer de todas las páginas).
     *
     * Solo renderiza si el bloque no ha sido ya mostrado por otro hook
     * y si la página actual tiene FAQs asociadas.
     */
    public function hookDisplayFooter(array $params): string
    {
        // Si el bloque ya se renderizó por otro hook, no duplicar.
        if ($this->faqRendered) {
            return '';
        }

        // Solo actuar si estamos en una página con entidad detectada.
        $entity = $this->detectCurrentEntity($params);
        if ($entity === null) {
            return '';
        }

        return $this->renderFaqBlock($params);
    }

    /**
     * Hook del tema Panda: displayFullWidthCategoryFooter (full_width_bottom_container).
     *
     * Ideal para fabricantes y categorías: justo debajo del wrapper principal,
     * antes del footer. Mejor visibilidad que displayFooter.
     */
    public function hookDisplayFullWidthCategoryFooter(array $params): string
    {
        return $this->renderFaqBlock($params);
    }

    /**
     * Hook nativo: displayWrapperBottom (wrapper_bottom_container).
     *
     * Alternativa al footer para cualquier tipo de página.
     */
    public function hookDisplayWrapperBottom(array $params): string
    {
        return $this->renderFaqBlock($params);
    }

    /**
     * Hook nativo: displayHome (contenido central de la página de inicio).
     *
     * Ideal para mostrar FAQs en la zona principal de la home, con máxima visibilidad.
     */
    public function hookDisplayHome(array $params): string
    {
        return $this->renderFaqBlock($params);
    }

    /**
     * Hook nativo: displayHomeBottom (zona inferior del contenido de la home).
     *
     * Alternativa para mostrar FAQs más abajo en la página de inicio,
     * justo antes del footer.
     */
    public function hookDisplayHomeBottom(array $params): string
    {
        return $this->renderFaqBlock($params);
    }

    /**
     * Renderiza el bloque de FAQs completo.
     */
    private function renderFaqBlock(array $params): string
    {
        // Evitar doble renderizado si otro hook ya mostró el bloque
        if ($this->faqRendered) {
            return '';
        }

        if (!$this->isModuleEnabled()) {
            return '';
        }

        $entity = $this->detectCurrentEntity($params);

        if ($entity === null) {
            return '';
        }

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $maxFaqs = (int) Configuration::get('FSCATEGORYFAQ_MAX_FAQS');

        $faqs = FaqModel::getByEntity(
            $entity['type'],
            $entity['id'],
            $idLang,
            $idShop,
            $maxFaqs
        );

        if (empty($faqs)) {
            return '';
        }

        // Construir el título del bloque
        $blockTitle = $this->buildBlockTitle($entity['name']);

        // Generar JSON-LD si está activo
        $jsonLd = '';
        if ((bool) Configuration::get('FSCATEGORYFAQ_ENABLE_JSONLD')) {
            $jsonLd = $this->generateJsonLd($faqs);
        }

        $faqCount = count($faqs);
        $faqCountLabel = $faqCount === 1
            ? $this->trans('1 pregunta', [], 'Modules.Fs_category_faq.Main')
            : sprintf($this->trans('%d preguntas', [], 'Modules.Fs_category_faq.Main'), $faqCount);

        $this->context->smarty->assign([
            'faqs' => $faqs,
            'entity_name' => $entity['name'],
            'block_title' => $blockTitle,
            'show_title' => (bool) Configuration::get('FSCATEGORYFAQ_SHOW_TITLE'),
            'use_accordion' => (bool) Configuration::get('FSCATEGORYFAQ_USE_ACCORDION'),
            'open_mode' => $this->sanitizeChoice((string) Configuration::get('FSCATEGORYFAQ_OPEN_MODE'), self::OPEN_MODE_OPTIONS, 'first_open'),
            'json_ld' => $jsonLd,
            'extra_css_class' => (string) Configuration::get('FSCATEGORYFAQ_EXTRA_CSS_CLASS'),
            'design_style' => $this->buildDesignStyle(),
            'icon_style' => $this->sanitizeChoice((string) Configuration::get('FSCATEGORYFAQ_ICON_STYLE'), self::ICON_STYLES, 'chevron'),
            'faq_count_label' => $faqCountLabel,
        ]);

        $cacheId = $this->getCacheId(
            'fs_category_faq|' . $entity['type'] . '|' . $entity['id'] . '|' . $idLang . '|' . $idShop
        );

        $this->faqRendered = true;

        $html = $this->display(__FILE__, 'views/templates/hook/category_faq.tpl', $cacheId);

        return $html;
    }

    /**
     * Detecta la entidad actual (categoría, inicio, CMS, fabricante)
     * a partir de los parámetros del hook o del contexto.
     *
     * @return array{type: string, id: int, name: string}|null
     */
    private function detectCurrentEntity(?array $params = null): ?array
    {
        $controller = $this->context->controller;
        $phpSelf = $controller->php_self ?? '';

        // 1. Si el hook pasa id_category, lo usamos directamente
        if (isset($params['id_category']) && !empty($params['id_category'])) {
            $idCategory = (int) $params['id_category'];
            $category = new Category($idCategory, $this->context->language->id);
            if (Validate::isLoadedObject($category)) {
                return [
                    'type' => 'category',
                    'id' => $idCategory,
                    'name' => $category->name,
                ];
            }
        }

        // 2. Detectar por controlador
        switch ($phpSelf) {
            case 'category':
                $idCategory = (int) Tools::getValue('id_category');
                if ($idCategory > 0) {
                    $category = new Category($idCategory, $this->context->language->id);
                    if (Validate::isLoadedObject($category)) {
                        return [
                            'type' => 'category',
                            'id' => $idCategory,
                            'name' => $category->name,
                        ];
                    }
                }
                break;

            case 'index':
                return [
                    'type' => 'home',
                    'id' => 0,
                    'name' => $this->context->shop->name,
                ];

            case 'cms':
                if (isset($params['cms']) && Validate::isLoadedObject($params['cms'])) {
                    $cms = $params['cms'];
                } else {
                    $idCms = (int) Tools::getValue('id_cms');
                    $cms = new CMS($idCms, $this->context->language->id);
                }

                if (Validate::isLoadedObject($cms)) {
                    return [
                        'type' => 'cms',
                        'id' => (int) $cms->id,
                        'name' => $cms->meta_title ?: $this->trans('Página', [], 'Modules.Fs_category_faq.Main'),
                    ];
                }
                break;

            case 'manufacturer':
                if (isset($params['manufacturer']) && Validate::isLoadedObject($params['manufacturer'])) {
                    $manufacturer = $params['manufacturer'];
                } else {
                    $idManufacturer = (int) Tools::getValue('id_manufacturer');
                    $manufacturer = new Manufacturer($idManufacturer, $this->context->language->id);
                }

                if (Validate::isLoadedObject($manufacturer)) {
                    return [
                        'type' => 'manufacturer',
                        'id' => (int) $manufacturer->id,
                        'name' => $manufacturer->name,
                    ];
                }
                break;
        }

        return null;
    }

    /**
     * Construye el título del bloque sustituyendo {entity_name}.
     */
    private function buildBlockTitle(string $entityName): string
    {
        $title = (string) Configuration::get('FSCATEGORYFAQ_TITLE');

        // Soportar ambos placeholders: {entity_name} (nuestro) y %category_name% (spec)
        $title = str_replace(['{entity_name}', '%category_name%'], $entityName, $title);

        return $title;
    }

    /**
     * Genera el JSON-LD FAQPage a partir de las FAQs.
     */
    private function generateJsonLd(array $faqs): string
    {
        $mainEntity = [];

        foreach ($faqs as $faq) {
            $question = trim((string) ($faq['question'] ?? ''));
            $answer = trim((string) ($faq['answer'] ?? ''));

            // Saltamos FAQs vacías
            if ($question === '' || $answer === '') {
                continue;
            }

            // Convertir respuesta a texto plano para el schema (sin HTML)
            $allowedTags = '<p><strong><em><ul><ol><li><a><br>';
            $answerText = strip_tags($answer, $allowedTags);
            // Eliminar atributos de los tags permitidos (solo texto)
            $answerText = preg_replace('/<([a-z]+)\s[^>]*>/i', '<$1>', $answerText);
            // Eliminar etiquetas HTML para texto plano limpio
            $plainAnswer = trim(strip_tags($answerText));
            // Decodificar entidades HTML
            $plainAnswer = html_entity_decode($plainAnswer, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $plainAnswer,
                ],
            ];
        }

        if (empty($mainEntity)) {
            return '';
        }

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        ];

        return json_encode(
            $jsonLd,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    // ---------------------------------------------------------------
    //  UTILIDADES
    // ---------------------------------------------------------------

    /**
     * Indica si el módulo está habilitado globalmente.
     */
    private function isModuleEnabled(): bool
    {
        return (bool) Configuration::get('FSCATEGORYFAQ_ENABLED');
    }

    /**
     * Construye las propiedades --fs-faq-* a partir de la configuración
     * de diseño guardada, listas para volcarse dentro de un <style>.
     *
     * Todos los valores pasan por sanitizeHexColor()/sanitizeChoice() antes
     * de llegar aquí (tanto al guardar en processConfigForm() como aquí,
     * por si el valor en BD quedó corrupto por otra vía), así que nunca se
     * interpola texto libre del admin en CSS.
     */
    private function buildDesignStyle(): string
    {
        $vars = [];

        $vars['--fs-faq-accent'] = $this->sanitizeHexColor((string) Configuration::get('FSCATEGORYFAQ_COLOR_ACCENT'), '#1066B2');
        $vars['--fs-faq-item-bg'] = $this->sanitizeHexColor((string) Configuration::get('FSCATEGORYFAQ_COLOR_BG'), '#ffffff');
        $vars['--fs-faq-question-color'] = $this->sanitizeHexColor((string) Configuration::get('FSCATEGORYFAQ_COLOR_QUESTION'), '#1a1a2e');
        $vars['--fs-faq-answer-color'] = $this->sanitizeHexColor((string) Configuration::get('FSCATEGORYFAQ_COLOR_ANSWER'), '#4b5563');
        $vars['--fs-faq-item-border'] = $this->sanitizeHexColor((string) Configuration::get('FSCATEGORYFAQ_COLOR_BORDER'), '#e5e7eb');

        $radiusKey = $this->sanitizeChoice((string) Configuration::get('FSCATEGORYFAQ_RADIUS'), array_keys(self::RADIUS_MAP), 'soft');
        $vars['--fs-faq-item-radius'] = self::RADIUS_MAP[$radiusKey];

        $densityKey = $this->sanitizeChoice((string) Configuration::get('FSCATEGORYFAQ_DENSITY'), array_keys(self::DENSITY_MAP), 'comfortable');
        $density = self::DENSITY_MAP[$densityKey];
        $vars['--fs-faq-question-padding'] = $density['question_padding'];
        $vars['--fs-faq-answer-padding'] = $density['answer_padding'];
        $vars['--fs-faq-item-margin'] = $density['item_margin'];

        $shadowKey = $this->sanitizeChoice((string) Configuration::get('FSCATEGORYFAQ_SHADOW'), array_keys(self::SHADOW_MAP), 'soft');
        $shadow = self::SHADOW_MAP[$shadowKey];
        $vars['--fs-faq-item-shadow'] = $shadow['shadow'];
        $vars['--fs-faq-item-shadow-hover'] = $shadow['shadow_hover'];

        $scaleKey = $this->sanitizeChoice((string) Configuration::get('FSCATEGORYFAQ_TEXT_SCALE'), array_keys(self::TEXT_SCALE_MAP), 'normal');
        $scale = self::TEXT_SCALE_MAP[$scaleKey];
        // El título usa su propio control independiente; pregunta y respuesta usan la escala general
        $titleSizeKey = $this->sanitizeChoice((string) Configuration::get('FSCATEGORYFAQ_TITLE_SIZE'), array_keys(self::TITLE_SIZE_MAP), 'm');
        $vars['--fs-faq-title-size'] = self::TITLE_SIZE_MAP[$titleSizeKey];
        $vars['--fs-faq-question-font-size'] = $scale['question'];
        $vars['--fs-faq-answer-font-size'] = $scale['answer'];

        $widthKey = $this->sanitizeChoice((string) Configuration::get('FSCATEGORYFAQ_MAX_WIDTH'), array_keys(self::MAX_WIDTH_MAP), 'full');
        $vars['--fs-faq-max-width'] = self::MAX_WIDTH_MAP[$widthKey];

        $vars['--fs-faq-margin-top'] = $this->sanitizeMarginValue((string) Configuration::get('FSCATEGORYFAQ_MARGIN_TOP'), '0');
        $vars['--fs-faq-margin-bottom'] = $this->sanitizeMarginValue((string) Configuration::get('FSCATEGORYFAQ_MARGIN_BOTTOM'), '2rem');

        $css = '';
        foreach ($vars as $name => $value) {
            $css .= $name . ':' . $value . ';';
        }

        return $css;
    }

    /**
     * Valida un color hexadecimal (#rrggbb). Si no es válido, devuelve el
     * valor por defecto en vez de dejar pasar texto libre hacia el CSS.
     */
    private function sanitizeHexColor(string $value, string $default): string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? $value : $default;
    }

    /**
     * Valida que un valor esté en una lista blanca de opciones permitidas.
     * Si no lo está, devuelve el valor por defecto.
     *
     * @param string[] $allowed
     */
    private function sanitizeChoice(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * Valida que un valor de margen CSS sea seguro. Solo permite 0,
     * números positivos con unidades estándar (px, rem, em, %, vh, vw)
     * o la palabra clave auto. Si no es válido, devuelve el default.
     */
    private function sanitizeMarginValue(string $value, string $default): string
    {
        $value = trim($value);
        if ($value === '') {
            return $default;
        }
        return preg_match('/^(0|[1-9]\d*\.?\d*(px|rem|em|%|vh|vw|cm|mm|in|pt|pc)|auto)$/', $value) ? $value : $default;
    }

    /**
     * Limpia la caché del módulo para todas las variantes.
     */
    public function clearFaqCache(): void
    {
        $this->_clearCache('views/templates/hook/category_faq.tpl');
    }
}
