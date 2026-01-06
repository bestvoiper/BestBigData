<?php
/**
 * Controlador base
 * Proporciona métodos comunes para todos los controladores
 */

class Controller
{
    /**
     * Cargar un modelo
     */
    protected function model($model)
    {
        require_once APP_ROOT . '/app/models/' . $model . '.php';
        return new $model();
    }

    /**
     * Cargar una vista
     */
    protected function view($view, $data = [])
    {
        extract($data);
        
        // Verificar si existe la vista
        $viewFile = APP_ROOT . '/app/views/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("Vista no encontrada: " . $view);
        }
    }

    /**
     * Redireccionar
     */
    protected function redirect($url)
    {
        header('Location: ' . BASE_URL . '/' . $url);
        exit;
    }

    /**
     * Verificar si es una petición POST
     */
    protected function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Verificar si es una petición AJAX
     */
    protected function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Respuesta JSON
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Obtener datos POST sanitizados
     */
    protected function getPost($key = null, $default = null)
    {
        if ($key === null) {
            return array_map([$this, 'sanitize'], $_POST);
        }
        return isset($_POST[$key]) ? $this->sanitize($_POST[$key]) : $default;
    }

    /**
     * Obtener datos GET sanitizados
     */
    protected function getQuery($key = null, $default = null)
    {
        if ($key === null) {
            return array_map([$this, 'sanitize'], $_GET);
        }
        return isset($_GET[$key]) ? $this->sanitize($_GET[$key]) : $default;
    }

    /**
     * Sanitizar entrada
     */
    protected function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Establecer mensaje flash
     */
    protected function setFlash($type, $message)
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Obtener mensaje flash
     */
    protected function getFlash()
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}
