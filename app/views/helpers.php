<?php
/**
 * Funciones helper para las vistas
 */

/**
 * Formatear dinero
 */
function formatMoney($amount)
{
    return '$' . number_format(floatval($amount), 2);
}

/**
 * Formatear fecha
 * Soporta timestamps Unix y strings de fecha
 */
function formatDate($date, $format = 'd/m/Y H:i')
{
    if (empty($date)) {
        return '-';
    }
    
    // Si es un número (timestamp Unix), usarlo directamente
    if (is_numeric($date)) {
        return date($format, intval($date));
    }
    
    // Si es string, convertir con strtotime
    return date($format, strtotime($date));
}

/**
 * Sanitizar salida
 */
function e($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Mostrar alerta flash
 */
function showFlash($flash)
{
    if (!$flash) return '';
    
    $type = $flash['type'];
    $message = $flash['message'];
    
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show">
        ' . e($message) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

/**
 * Verificar si la página está activa
 */
function isActive($page, $current)
{
    return $page === $current ? 'active' : '';
}

/**
 * Generar URL
 */
function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}
