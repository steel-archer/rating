<?php

/**
 * Router script for PHP built-in server (used in CI DAST scan).
 * Serves static files with security headers, routes the rest through index.php.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = $_SERVER['DOCUMENT_ROOT'] . $path;

if ($path !== '/' && is_file($file)) {
    header('X-Content-Type-Options: nosniff');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

    return false;
}

require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
