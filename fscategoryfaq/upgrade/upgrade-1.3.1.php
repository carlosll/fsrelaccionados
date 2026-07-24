<?php
/**
 * FS Category FAQ SEO — Actualización a 1.3.1
 *
 * Da de alta las claves de Configuration del panel "Diseño y colores"
 * (introducidas en 1.3.0/1.3.1) para tiendas donde el módulo ya estaba
 * instalado. install() solo corre en una instalación nueva, así que sin
 * este script las claves nunca se escribirían en la base de datos real.
 *
 * PrestaShop incluye este archivo y llama a upgrade_module_1_3_1($module)
 * automáticamente al detectar que la versión instalada es anterior a la
 * del config.xml (Module::runUpgradeModule(), ver .ref/prestashop-8.2/Module.php).
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_3_1(Module $module): bool
{
    $defaults = [
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
        'FSCATEGORYFAQ_MAX_WIDTH' => 'full',
    ];

    foreach ($defaults as $key => $value) {
        // No pisar un valor que ya exista (por ejemplo, si esta función
        // se llegara a ejecutar más de una vez).
        if (Configuration::get($key) === false) {
            if (!Configuration::updateValue($key, $value)) {
                return false;
            }
        }
    }

    return true;
}
