<?php
/**
 * FS Category FAQ SEO — Seguridad
 *
 * Previene el listado de directorios.
 * Redirige al front office de la tienda.
 */

header('HTTP/1.0 403 Forbidden');
header('Location: ../../../index.php');
exit;
