<?php
/**
 * Controlador de inicio
 * Maneja la página principal y redirecciones
 */

class HomeController extends Controller
{
    public function __construct()
    {
        Session::start();
    }

    public function index()
    {
        // Redirigir según el estado de la sesión
        if (Session::isLoggedIn()) {
            if (Session::isAdmin()) {
                $this->redirect('admin');
            } else {
                $this->redirect('client');
            }
        } else {
            $this->redirect('auth/login');
        }
    }
}
