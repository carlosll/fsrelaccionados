<?php
/**
 * FS Category FAQ SEO — Actualización a 1.6.0
 *
 * Cambios:
 * - Mueve la pestaña al menú Configuración actualizando el tab
 *   existente directamente vía SQL + Tab::save().
 * - Añade claves Configuration nuevas.
 * - Migra FSCATEGORYFAQ_OPEN_DEFAULT → FSCATEGORYFAQ_OPEN_MODE.
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_6_0(Module $module): bool
{
    // ── 1. Mover la pestaña a la sección Configurar ──
    $tabId = Tab::getIdFromClassName('AdminFsCategoryFaq');
    if ($tabId) {
        $tab = new Tab($tabId);
        if (Validate::isLoadedObject($tab)) {
            // Buscar el ID del padre en la sección CONFIGURE.
            // Probamos varios padres conocidos de PS 8.x.
            $parentId = (int) Tab::getIdFromClassName('AdminParentPreferences');
            if (!$parentId) {
                $parentId = (int) Tab::getIdFromClassName('ShopParameters');
            }
            if (!$parentId) {
                // Último recurso: buscar cualquier tab de nivel 0 en la
                // sección de administración (CONFIGURE).
                $parentId = (int) Db::getInstance()->getValue(
                    'SELECT t.id_tab FROM ' . _DB_PREFIX_ . 'tab t
                     INNER JOIN ' . _DB_PREFIX_ . 'tab_lang tl ON t.id_tab = tl.id_tab
                     WHERE t.id_parent = 0 AND tl.name = "Configuración"
                     LIMIT 1'
                );
            }

            if ($parentId > 0) {
                $tab->id_parent = $parentId;
            }

            // Renombrar
            $tab->name = [];
            foreach (Language::getLanguages() as $lang) {
                $tab->name[(int) $lang['id_lang']] = 'FAQs';
            }

            $tab->save();
        }
    }

    // ── 2. Nuevas claves de Configuration ──
    if (!Configuration::updateValue('FSCATEGORYFAQ_TITLE_SIZE', 'm')) {
        return false;
    }

    // Migrar el antiguo FSCATEGORYFAQ_OPEN_DEFAULT (bool) al nuevo
    // FSCATEGORYFAQ_OPEN_MODE (string con 3 valores).
    $oldOpenDefault = Configuration::get('FSCATEGORYFAQ_OPEN_DEFAULT');
    if ($oldOpenDefault !== false) {
        // Si estaba activado → 'all_open'; si no → 'first_open' (nuevo default)
        $newMode = $oldOpenDefault ? 'all_open' : 'first_open';
        Configuration::updateValue('FSCATEGORYFAQ_OPEN_MODE', $newMode);
        // Limpiar la clave antigua
        Configuration::deleteByName('FSCATEGORYFAQ_OPEN_DEFAULT');
    } else {
        // Nunca se había configurado: usar el default 'first_open'
        Configuration::updateValue('FSCATEGORYFAQ_OPEN_MODE', 'first_open');
    }

    return true;
}
