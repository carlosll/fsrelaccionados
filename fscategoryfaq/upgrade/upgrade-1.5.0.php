<?php
/**
 * FS Category FAQ SEO — Actualización a 1.5.0
 *
 * Añade funcionalidad de importación y exportación de FAQs desde/hacia
 * archivos JSON. No requiere cambios en BD ni nuevas claves de
 * Configuration — toda la lógica está en el controlador.
 *
 * Este archivo existe para que PrestaShop reconozca el salto de versión
 * al actualizar desde cualquier versión anterior (Module::runUpgradeModule()
 * en .ref/prestashop-8.2/Module.php:585-735).
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_5_0(Module $module): bool
{
    return true;
}
