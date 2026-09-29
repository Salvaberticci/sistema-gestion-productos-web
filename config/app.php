<?php
define('APP_NAME', 'El Rebusque');
define('APP_VERSION', '1.0.0');

/**
 * Detecta automaticamente la ruta web del proyecto para que funcione en
 * cualquier entorno (XAMPP, produccion, subcarpetas) sin editar este archivo.
 * Se puede forzar con la variable de entorno APP_BASE_PATH.
 */
if (getenv('APP_BASE_PATH') !== false) {
    $basePath = getenv('APP_BASE_PATH');
} else {
    $basePath = '';
    $docRoot = isset($_SERVER['DOCUMENT_ROOT'])
        ? str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/'))
        : '';
    $appRoot = str_replace('\\', '/', dirname(__DIR__));
    $prefix = $docRoot . '/';

    // Solo si la raiz del proyecto esta realmente dentro del DOCUMENT_ROOT
    if ($docRoot !== '' && strpos($appRoot . '/', $prefix) === 0) {
        $basePath = rtrim(substr($appRoot . '/', strlen($prefix)), '/');
    }
}

define('APP_URL', $basePath);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('PRODUCT_IMG_DIR', __DIR__ . '/../assets/images/products/');
