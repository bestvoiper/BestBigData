<?php
/**
 * Configuración de bases de datos
 * Sistema bestbigdata - Conexión a múltiples bases de datos CDR
 * 
 * NOTA: Los parámetros de conexión principal (DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET)
 * están centralizados en app/config/config.php según el entorno (production/development)
 */

// Asegurar que la configuración principal esté cargada
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../app/config/config.php';
}

// Configuración de las 4 bases de datos CDR
$cdr_databases = [
    'sw1' => [
        'host' => 'sw1.bestvoiper.com',
        'name' => 'vos3000',
        'user' => 'developers',
        'pass' => 'Luisda0806*++',
        'prefix' => 'e_cdr_'
    ],
    'sw2' => [
        'host' => 'sw2.bestvoiper.com',
        'name' => 'vos3000',
        'user' => 'developers',
        'pass' => 'Luisda0806*++',
        'prefix' => 'e_cdr_'
    ],
    'sw3' => [
        'host' => 'sw3.bestvoiper.com',
        'name' => 'vos3000',
        'user' => 'developers',
        'pass' => 'Luisda0806*++',
        'prefix' => 'e_cdr_'
    ]
];

// Costo por consulta encontrada (en pesos)
define('COST_PER_RESULT', 1);

// Función para obtener conexión PDO principal
function getMainConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión principal: " . $e->getMessage());
    }
}

// Función para obtener conexiones a bases de datos CDR
function getCDRConnections() {
    global $cdr_databases;
    $connections = [];
    
    foreach ($cdr_databases as $key => $config) {
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['user'], $config['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $connections[$key] = [
                'connection' => $pdo,
                'prefix' => $config['prefix'],
                'name' => $config['name']
            ];
        } catch (PDOException $e) {
            // Log error pero continúa con las demás conexiones
            error_log("Error conectando a {$config['name']}: " . $e->getMessage());
        }
    }
    
    return $connections;
}
