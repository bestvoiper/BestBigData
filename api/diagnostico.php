<?php
/**
 * API para diagnóstico de conexiones
 * Devuelve estado de conexiones en JSON
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/config/config.php';
require_once APP_ROOT . '/app/models/Conexion.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache');

$result = [
    'timestamp' => date('d/m/Y H:i:s'),
    'environment' => ENVIRONMENT,
    'main' => null,
    'cdr' => [],
    'summary' => [
        'total' => 0,
        'connected' => 0,
        'percentage' => 0
    ]
];

// Verificar base de datos principal
$startTime = microtime(true);
try {
    $mainDb = Conexion::getMain();
    $mainDb->query("SELECT 1");
    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
    
    $result['main'] = [
        'status' => 'ok',
        'host' => DB_HOST,
        'database' => DB_NAME,
        'responseTime' => $responseTime
    ];
    $result['summary']['connected']++;
} catch (Exception $e) {
    $result['main'] = [
        'status' => 'error',
        'host' => DB_HOST,
        'database' => DB_NAME,
        'error' => $e->getMessage()
    ];
}
$result['summary']['total']++;

// Verificar servidores CDR
$cdrServers = Conexion::getCDRServers();
foreach ($cdrServers as $serverKey) {
    $startTime = microtime(true);
    $cdrConn = Conexion::getCDR($serverKey);
    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($cdrConn !== null) {
        $connection = $cdrConn['connection'];
        $prefix = $cdrConn['prefix'];
        
        try {
            $stmt = $connection->query("SHOW TABLES LIKE '{$prefix}%'");
            $tableCount = $stmt->rowCount();
        } catch (Exception $e) {
            $tableCount = 0;
        }
        
        $result['cdr'][$serverKey] = [
            'status' => 'ok',
            'host' => "{$serverKey}.bestvoiper.com",
            'tables' => $tableCount,
            'responseTime' => $responseTime
        ];
        $result['summary']['connected']++;
    } else {
        $result['cdr'][$serverKey] = [
            'status' => 'error',
            'host' => "{$serverKey}.bestvoiper.com",
            'tables' => 0,
            'responseTime' => 0
        ];
    }
    $result['summary']['total']++;
}

$result['summary']['percentage'] = round(($result['summary']['connected'] / $result['summary']['total']) * 100);

echo json_encode($result);
