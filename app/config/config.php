<?php
/**
 * Configuración principal de la aplicación
 * bestbigdata - Sistema de Consulta Telefónica
 */

// Evitar acceso directo
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Configuración de la aplicación
define('APP_NAME', 'bestbigdata');
define('APP_VERSION', '2.0.0');

// Configuración de URL base (ajustar según el entorno)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Leer entorno desde .htaccess (SetEnv APP_ENV) o variable de sistema
$environment = $_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?: 'development';
define('ENVIRONMENT', $environment);

// En servidor de producción no usar subdirectorio
if ($environment === 'production') {
    define('BASE_URL', $protocol . '://' . $host);

    define('DB_HOST', 'localhost');
    define('DB_NAME', 'bestbigdata');
    define('DB_USER', 'developers');
    define('DB_PASS', 'Luisda0806*++');
} else {
    define('BASE_URL', $protocol . '://' . $host . '/BestBigData');

    define('DB_HOST', 'localhost');
    define('DB_NAME', 'bestbigdata');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}
define('DB_CHARSET', 'utf8mb4');

// NOTA: La configuración de bases CDR está centralizada en app/models/Conexion.php

// Configuración de errores (cambiar a 0 en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zona horaria
date_default_timezone_set('America/Bogota');

// Configuración de sesión (solo si no hay sesión activa)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
}
