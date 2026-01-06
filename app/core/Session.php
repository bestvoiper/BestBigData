<?php
/**
 * Clase para manejo de sesiones
 */

class Session
{
    /**
     * Iniciar sesión
     */
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Establecer valor en sesión
     */
    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Obtener valor de sesión
     */
    public static function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Verificar si existe clave en sesión
     */
    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Eliminar valor de sesión
     */
    public static function remove($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Destruir sesión
     */
    public static function destroy()
    {
        session_unset();
        session_destroy();
    }

    /**
     * Establecer mensaje flash
     */
    public static function setFlash($type, $message)
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Obtener y eliminar mensaje flash
     */
    public static function getFlash()
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    /**
     * Verificar si el usuario está logueado
     */
    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }

    /**
     * Verificar si el usuario es admin
     */
    public static function isAdmin()
    {
        return self::isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    /**
     * Verificar si el usuario es cliente
     */
    public static function isClient()
    {
        return self::isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'cliente';
    }

    /**
     * Obtener ID del usuario actual
     */
    public static function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Login del usuario
     */
    public static function login($userId, $role, $name)
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = $role;
        $_SESSION['user_name'] = $name;
        session_regenerate_id(true);
    }

    /**
     * Logout del usuario
     */
    public static function logout()
    {
        self::destroy();
    }
}
