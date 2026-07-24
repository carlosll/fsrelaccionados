<?php
/**
 * FS Category FAQ SEO — Actualización a 1.6.2
 *
 * Repara la pestaña del menú y añade configuraciones nuevas.
 * Usa la API oficial de PrestaShop (installTab) para crear
 * la pestaña con permisos correctos en cualquier versión.
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_6_2(Module $module): bool
{
    // ── 1. Reparar o crear la pestaña del menú ──
    $tabId = Tab::getIdFromClassName('AdminFsCategoryFaq');

    // Eliminar la pestaña si existe (puede estar mal ubicada o sin permisos)
    if ($tabId) {
        $oldTab = new Tab($tabId);
        if (Validate::isLoadedObject($oldTab)) {
            $oldTab->delete();
        }
    }

    // Buscar "Catálogo" por nombre en la BD
    $sql = 'SELECT t.id_tab FROM ' . _DB_PREFIX_ . 'tab t'
         . ' INNER JOIN ' . _DB_PREFIX_ . 'tab_lang tl ON t.id_tab = tl.id_tab'
         . " WHERE (tl.name = 'Catálogo' OR tl.name = 'Catalog' OR tl.name LIKE '%Cat%logo%')";
    $parentId = (int) Db::getInstance()->getValue($sql);
    if ($parentId <= 0) {
        $parentId = (int) Tab::getIdFromClassName('AdminCategories');
    }
    if ($parentId <= 0) {
        $parentId = (int) Tab::getIdFromClassName('AdminProducts');
    }
    if ($parentId <= 0) {
        $parentId = (int) Tab::getIdFromClassName('AdminParentOrders');
    }
    if ($parentId <= 0) {
        $parentId = (int) Db::getInstance()->getValue(
            'SELECT id_tab FROM ' . _DB_PREFIX_ . 'tab WHERE id_parent = 0 ORDER BY position ASC LIMIT 1'
        );
    }

    // Recrear la pestaña con la API oficial del módulo
    // (esto crea el tab Y concede permisos automáticamente)
    $tabNames = [];
    foreach (Language::getLanguages() as $lang) {
        $tabNames[(int) $lang['id_lang']] = 'FAQs';
    }

    // installTab() es público en PS 8.x — crea el tab y da permisos
    if (method_exists($module, 'installTab')) {
        $module->installTab('AdminFsCategoryFaq', $tabNames, $parentId, 'help');
    } else {
        // Fallback manual (no debería llegar aquí en PS 8.x)
        $tab = new Tab();
        $tab->class_name = 'AdminFsCategoryFaq';
        $tab->module = $module->name;
        $tab->id_parent = $parentId;
        $tab->name = $tabNames;
        $tab->icon = 'help';
        $tab->active = true;
        $tab->save();
    }

    // ── 2. Migrar FSCATEGORYFAQ_OPEN_DEFAULT → FSCATEGORYFAQ_OPEN_MODE ──
    $oldOpenDefault = Configuration::get('FSCATEGORYFAQ_OPEN_DEFAULT');
    if ($oldOpenDefault !== false) {
        $newMode = $oldOpenDefault ? 'all_open' : 'first_open';
        Configuration::updateValue('FSCATEGORYFAQ_OPEN_MODE', $newMode);
        Configuration::deleteByName('FSCATEGORYFAQ_OPEN_DEFAULT');
    } else {
        Configuration::updateValue('FSCATEGORYFAQ_OPEN_MODE', 'first_open');
    }

    return true;
}
