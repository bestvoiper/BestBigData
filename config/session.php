<?php
/**
 * Configuración de sesiones
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tiempo de expiración de sesión (2 horas)
define('SESSION_TIMEOUT', 7200);

// Verificar si la sesión ha expirado
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header('Location: /DetectNUM/login.php?expired=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// Verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Verificar si es admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Verificar si es cliente
function isClient() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'cliente';
}

// Requerir login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /DetectNUM/login.php');
        exit;
    }
    checkSessionTimeout();
}

// Requerir admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /DetectNUM/cliente/dashboard.php');
        exit;
    }
}

// Requerir cliente
function requireClient() {
    requireLogin();
    if (!isClient()) {
        header('Location: /DetectNUM/admin/dashboard.php');
        exit;
    }
}
