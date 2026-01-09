#!/usr/bin/env php
<?php
/**
 * Sincronización CDR → Elasticsearch (CLI)
 * 
 * Uso:
 *   php sync_cli.php --days=7
 *   php sync_cli.php --start=2025-01-01 --end=2025-01-09
 *   php sync_cli.php --server=sw1 --days=3
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde CLI\n");
}

set_time_limit(0);
ini_set('memory_limit', '512M');

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/config/config.php';
require_once APP_ROOT . '/app/models/Conexion.php';
require_once APP_ROOT . '/app/services/ElasticSearch.php';

// Parsear argumentos
$options = getopt('', ['days:', 'start:', 'end:', 'server:', 'batch:', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
Sincronización CDR → Elasticsearch

Uso:
  php sync_cli.php [opciones]

Opciones:
  --days=N        Sincronizar últimos N días (default: 7)
  --start=FECHA   Fecha inicio (YYYY-MM-DD)
  --end=FECHA     Fecha fin (YYYY-MM-DD)
  --server=NAME   Solo sincronizar servidor específico (sw1, sw2, sw3, sw4)
  --batch=N       Tamaño de batch (default: 1000)
  --help          Mostrar esta ayuda

Ejemplos:
  php sync_cli.php --days=7
  php sync_cli.php --start=2025-01-01 --end=2025-01-09
  php sync_cli.php --server=sw1 --days=3

HELP;
    exit(0);
}

$days = (int)($options['days'] ?? 7);
$startDate = $options['start'] ?? date('Y-m-d', strtotime("-{$days} days"));
$endDate = $options['end'] ?? date('Y-m-d');
$serverFilter = $options['server'] ?? null;
$batchSize = (int)($options['batch'] ?? 1000);

function cliLog($msg, $type = 'info') {
    $prefix = match($type) {
        'error' => '❌',
        'success' => '✅',
        'warn' => '⚠️',
        default => '📋'
    };
    echo date('H:i:s') . " {$prefix} {$msg}\n";
}

cliLog("=== Sincronización CDR → Elasticsearch ===", 'info');
cliLog("Rango: {$startDate} a {$endDate}");

$es = ElasticSearch::getInstance();

if (!$es->isAvailable()) {
    cliLog("Elasticsearch no disponible", 'error');
    exit(1);
}

$initialCount = $es->count();
cliLog("Documentos actuales en ES: " . number_format($initialCount));

$servers = $serverFilter 
    ? [$serverFilter] 
    : Conexion::getCDRServers();

$startYmd = str_replace('-', '', $startDate);
$endYmd = str_replace('-', '', $endDate);

$totalIndexed = 0;
$errors = 0;

foreach ($servers as $srv) {
    cliLog("--- Procesando servidor: {$srv} ---");
    
    $cdrConn = Conexion::getCDR($srv);
    if (!$cdrConn) {
        cliLog("No se pudo conectar a {$srv}", 'error');
        continue;
    }
    
    $conn = $cdrConn['connection'];
    $prefix = $cdrConn['prefix'];
    
    // Obtener tablas en rango
    $stmt = $conn->query("SHOW TABLES LIKE '{$prefix}%'");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $tables = [];
    foreach ($allTables as $table) {
        if (preg_match('/e_cdr_(\d{8})/', $table, $m)) {
            if ($m[1] >= $startYmd && $m[1] <= $endYmd) {
                $tables[] = $table;
            }
        }
    }
    
    cliLog("Tablas encontradas: " . count($tables));
    
    foreach ($tables as $table) {
        $offset = 0;
        $tableIndexed = 0;
        
        while (true) {
            try {
                $sql = "SELECT callere164, calleee164, starttime, stoptime, 
                               callerip, calleeip, holdtime, endreason
                        FROM {$table} 
                        LIMIT {$batchSize} OFFSET {$offset}";
                
                $stmt = $conn->query($sql);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($rows)) break;
                
                // Preparar documentos
                $docs = [];
                foreach ($rows as $row) {
                    $docs[] = ElasticSearch::prepareCDRDocument($row, $srv, $table);
                }
                
                // Indexar en bulk
                $result = $es->bulkIndex($docs);
                
                if (isset($result['errors']) && $result['errors']) {
                    $errors++;
                }
                
                $tableIndexed += count($docs);
                $totalIndexed += count($docs);
                $offset += $batchSize;
                
                // Progreso cada 5000 registros
                if ($tableIndexed % 5000 === 0) {
                    cliLog("{$table}: " . number_format($tableIndexed) . " registros...");
                }
                
            } catch (Exception $e) {
                cliLog("Error en {$table}: " . $e->getMessage(), 'error');
                $errors++;
                break;
            }
        }
        
        if ($tableIndexed > 0) {
            cliLog("{$table}: " . number_format($tableIndexed) . " indexados", 'success');
        }
    }
}

// Refrescar índice
$es->refresh();

$finalCount = $es->count();
$newDocs = $finalCount - $initialCount;

cliLog("=================================");
cliLog("Sincronización completada", 'success');
cliLog("Total indexados en esta sesión: " . number_format($totalIndexed));
cliLog("Nuevos documentos netos: " . number_format($newDocs));
cliLog("Total documentos en ES: " . number_format($finalCount));
cliLog("Errores: {$errors}", $errors > 0 ? 'warn' : 'success');

exit($errors > 0 ? 1 : 0);
