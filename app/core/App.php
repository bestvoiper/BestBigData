<?php
/**
 * Clase principal de la aplicación
 * Maneja el enrutamiento y carga de controladores
 */

class App
{
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];

    public function __construct()
    {
        $url = $this->parseUrl();

        // Verificar si existe el controlador
        if (isset($url[0]) && file_exists(APP_ROOT . '/app/controllers/' . ucfirst($url[0]) . 'Controller.php')) {
            $this->controller = ucfirst($url[0]) . 'Controller';
            unset($url[0]);
        }

        require_once APP_ROOT . '/app/controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller;

        // Verificar si existe el método
        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            }
        }

        // Obtener parámetros restantes
        $this->params = $url ? array_values($url) : [];

        // Llamar al controlador con el método y parámetros
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    /**
     * Parsear la URL
     */
    protected function parseUrl()
    {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return [];
    }
}
