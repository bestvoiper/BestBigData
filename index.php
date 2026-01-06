<?php
/**
 * Punto de entrada principal de la aplicación
 * DetectNUM - Arquitectura MVC
 */

// Definir constante de acceso
define('APP_ROOT', __DIR__);

// Cargar configuración
require_once 'app/config/config.php';

// Cargar clases del core
require_once 'app/core/Controller.php';
require_once 'app/core/Model.php';
require_once 'app/core/Session.php';
require_once 'app/core/App.php';

// Iniciar la aplicación
$app = new App();
