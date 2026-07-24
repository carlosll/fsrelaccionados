<?php
/**
 * FS Category FAQ SEO — SQL de desinstalación
 *
 * Elimina las tablas del módulo.
 * Usado desde Fs_Category_Faq::uninstall() si FSCATEGORYFAQ_KEEP_DATA está desactivado.
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];

$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'fs_category_faq`;';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'fs_category_faq_lang`;';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}

return true;
