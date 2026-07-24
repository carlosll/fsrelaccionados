<?php
/**
 * FS Category FAQ SEO — SQL de instalación
 *
 * Crea las tablas necesarias para el módulo.
 * Usado desde Fs_Category_Faq::install().
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'fs_category_faq` (
    `id_faq` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_type` VARCHAR(32) NOT NULL DEFAULT "category",
    `entity_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `id_shop` INT UNSIGNED NOT NULL DEFAULT 1,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `position` INT UNSIGNED NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_faq`),
    KEY `idx_entity_shop` (`entity_type`, `entity_id`, `id_shop`),
    KEY `idx_active_position` (`active`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'fs_category_faq_lang` (
    `id_faq` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `question` VARCHAR(255) NOT NULL,
    `answer` TEXT NOT NULL,
    PRIMARY KEY (`id_faq`, `id_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}

return true;
