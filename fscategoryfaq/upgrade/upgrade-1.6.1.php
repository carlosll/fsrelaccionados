<?php
/**
 * FS Category FAQ SEO — Actualización a 1.6.1
 *
 * Repara la pestaña del menú (posiblemente borrada por v1.6.0)
 * y añade el nuevo selector de modo de apertura de FAQs.
 *
 * Si la pestaña no existe, la crea desde cero. Si existe pero está
 * mal ubicada, la repara. La ponemos bajo el Dashboard (Inicio)
 * para garantizar visibilidad.
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_6_1(Module $module): bool
{
    // ── 1. Reparar o crear la pestaña del menú ──
    $tabId = Tab::getIdFromClassName('AdminFsCategoryFaq');

    // Buscar padre fiable: Dashboard (existe en toda instalación PS)
    $parentId = (int) Tab::getIdFromClassName('AdminDashboard');
    if ($parentId <= 0) {
        // Último recurso: buscar cualquier tab con id_parent = 0
        $parentId = (int) Db::getInstance()->getValue(
            'SELECT id_tab FROM ' . _DB_PREFIX_ . 'tab WHERE id_parent = 0 ORDER BY position ASC LIMIT 1'
        );
    }

    if ($tabId) {
        // La pestaña existe — actualizarla
        $tab = new Tab($tabId);
        if (Validate::isLoadedObject($tab)) {
            $tab->id_parent = $parentId;
            $tab->name = [];
            foreach (Language::getLanguages() as $lang) {
                $tab->name[(int) $lang['id_lang']] = 'FAQs';
            }
            $tab->icon = 'help';
            $tab->save();
        }
    } else {
        // La pestaña fue borrada (por v1.6.0) — crearla desde cero
        $tab = new Tab();
        $tab->class_name = 'AdminFsCategoryFaq';
        $tab->module = $module->name;
        $tab->id_parent = $parentId;
        $tab->name = [];
        foreach (Language::getLanguages() as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'FAQs';
        }
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
