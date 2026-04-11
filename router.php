<?php
/**
 * Router pro PHP built-in server.
 * Statické soubory (CSS, JS, obrázky) servíruje přímo,
 * vše ostatní předává do index.php.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

// Pokud soubor fyzicky existuje a není to adresář → servíruj přímo
if ($uri !== '/' && is_file($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
        'json' => 'application/json',
        'webmanifest' => 'application/manifest+json',
    ];
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }
    return false; // PHP built-in server obslouží soubor
}

// Vše ostatní → index.php
require __DIR__ . '/index.php';
