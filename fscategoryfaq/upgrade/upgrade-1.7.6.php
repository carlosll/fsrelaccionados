<?php
/**
 * FS Category FAQ SEO — Actualización a 1.7.6
 *
 * Registra los nuevos hooks displayHome y displayHomeBottom
 * para la página de inicio.
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_7_6(Module $module): bool
{
    // ── 1. Registrar los nuevos hooks displayHome y displayHomeBottom ──
    $newHooks = ['displayHome', 'displayHomeBottom'];

    foreach ($newHooks as $hookName) {
        $idHook = (int) Hook::getIdByName($hookName);
        if ($idHook > 0) {
            $alreadyRegistered = (bool) Db::getInstance()->getValue(
                'SELECT 1 FROM `' . _DB_PREFIX_ . 'hook_module`
                 WHERE `id_hook` = ' . $idHook . '
                 AND `id_module` = ' . (int) $module->id
            );
            if (!$alreadyRegistered) {
                $module->registerHook($hookName);
            }
        }
    }

    // ── 2. Si el usuario tenía el hook de home en el antiguo default (displayFooter),
    //     migrarlo al nuevo default (displayHome). Si lo cambió manualmente, respetarlo. ──
    $currentHomeHook = Configuration::get('FSCATEGORYFAQ_HOOK_HOME');
    if ($currentHomeHook === 'displayFooter' || $currentHomeHook === false) {
        Configuration::updateValue('FSCATEGORYFAQ_HOOK_HOME', 'displayHome');
    }

    return true;
}
