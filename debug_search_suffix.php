<?php
/**
 * Debug de búsqueda - VERSION CON %numero (sufijo)
 * Compara tiempos vs la versión con variantes
 */

define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/app/config/config.php';
require_once APP_ROOT . '/app/models/Conexion.php';
require_once APP_ROOT . '/app/core/Model.php';
require_once APP_ROOT . '/app/models/Search.php';

header('Content-Type: text/html; charset=utf-8');

$phoneNumber = $_GET['q'] ?? '573124560009';
$startDate = $_GET['start'] ?? '2025-12-01';
$endDate = $_GET['end'] ?? '2025-12-31';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Debug Búsqueda SUFIJO - BestBigData</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .time-good { color: green; font-weight: bold; }
        .time-medium { color: orange; font-weight: bold; }
        .time-bad { color: red; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 300px; overflow: auto; }
    </style>
</head>
<body class="bg-light p-4">
<div class="container">
    <h1>🔍 Debug de Búsqueda <span class="badge bg-warning">%NUMERO (sufijo)</span></h1>
    
    <div class="alert alert-warning">
        <strong>⚠️ Esta versión usa <code>%numero</code></strong> - NO usa índices, puede ser lenta.<br>
        <a href="debug_search.php?q=<?= urlencode($phoneNumber) ?>&start=<?= $startDate ?>&end=<?= $endDate ?>" class="btn btn-sm btn-primary mt-2">
            Ver versión con variantes (rápida)
        </a>
    </div>
    
    <form class="row g-3 mb-4" method="GET">
        <div class="col-md-4">
            <label class="form-label">Número</label>
            <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($phoneNumber) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Desde</label>
            <input type="date" class="form-control" name="start" value="<?= htmlspecialchars($startDate) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Hasta</label>
            <input type="date" class="form-control" name="end" value="<?= htmlspecialchars($endDate) ?>">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-warning w-100">Buscar</button>
        </div>
    </form>
    
    <?php
    // Función para colorear tiempos
    function timeClass($ms) {
        if ($ms < 1000) return 'time-good';
        if ($ms < 5000) return 'time-medium';
        return 'time-bad';
    }
    
    // Extraer número base (misma lógica que Search.php)
    function extractBase($number) {
        $clean = preg_replace('/[^0-9]/', '', $number);
        if (strlen($clean) > 10 && substr($clean, 0, 3) === '011') {
            $clean = substr($clean, 3);
        }
        if (strlen($clean) > 10 && substr($clean, 0, 2) === '57') {
            $clean = substr($clean, 2);
        }
        return $clean;
    }
    
    $baseNumber = extractBase($phoneNumber);
    $searchPattern = '%' . $baseNumber;
    
    echo "<div class='alert alert-info'>";
    echo "<strong>Número original:</strong> {$phoneNumber}<br>";
    echo "<strong>Número base (sin prefijos):</strong> {$baseNumber}<br>";
    echo "<strong>Patrón de búsqueda:</strong> <code>{$searchPattern}</code> (busca números que TERMINEN con {$baseNumber})";
    echo "</div>";
    
    $totalStart = microtime(true);
    $cdrServers = Conexion::getCDRServers();
    $allResults = [];
    $debugInfo = [];
    
    foreach ($cdrServers as $serverKey):
        $serverStart = microtime(true);
        
        echo "<div class='card mb-3'>";
        echo "<div class='card-header bg-secondary text-white'><strong>Servidor: {$serverKey}</strong></div>";
        echo "<div class='card-body'>";
        
        // Conectar
        $connStart = microtime(true);
        $cdrConn = Conexion::getCDR($serverKey);
        $connTime = round((microtime(true) - $connStart) * 1000, 2);
        
        if ($cdrConn === null) {
            echo "<div class='alert alert-danger'>❌ No se pudo conectar</div>";
            echo "</div></div>";
            continue;
        }
        
        echo "<p>⏱️ Tiempo conexión: <span class='" . timeClass($connTime) . "'>{$connTime} ms</span></p>";
        
        $connection = $cdrConn['connection'];
        $prefix = $cdrConn['prefix'];
        
        // Obtener tablas
        $tablesStart = microtime(true);
        $stmt = $connection->query("SHOW TABLES LIKE '{$prefix}%'");
        $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Filtrar por fecha
        $tables = [];
        $start = str_replace('-', '', $startDate);
        $end = str_replace('-', '', $endDate);
        
        foreach ($allTables as $table) {
            if (preg_match('/e_cdr_(\d{8})/', $table, $matches)) {
                $tableDate = $matches[1];
                if ($tableDate >= $start && $tableDate <= $end) {
                    $tables[] = $table;
                }
            }
        }
        rsort($tables);
        $tables = array_slice($tables, 0, 31); // Limitar
        
        $tablesTime = round((microtime(true) - $tablesStart) * 1000, 2);
        
        echo "<p>📋 Total tablas: " . count($allTables) . " | Filtradas: " . count($tables) . " | Tiempo: <span class='" . timeClass($tablesTime) . "'>{$tablesTime} ms</span></p>";
        
        if (empty($tables)) {
            echo "<div class='alert alert-warning'>No hay tablas en el rango de fechas</div>";
            echo "</div></div>";
            continue;
        }
        
        // Buscar en batches de 5 tablas con UNION ALL
        $serverResults = [];
        $batchSize = 5;
        $tableBatches = array_chunk($tables, $batchSize);
        
        echo "<h6>Búsqueda por batches (UNION ALL de {$batchSize} tablas) - SUFIJO %numero:</h6>";
        echo "<table class='table table-sm table-bordered'>";
        echo "<thead><tr><th>Batch</th><th>Tablas</th><th>Resultados</th><th>Tiempo</th></tr></thead><tbody>";
        
        foreach ($tableBatches as $batchIndex => $batch) {
            $batchStart = microtime(true);
            
            // Construir UNION ALL con %numero
            $unions = [];
            $params = [];
            foreach ($batch as $table) {
                $unions[] = "(SELECT 
                                callere164, calleee164, starttime, stoptime,
                                callerip, calleeip, holdtime, endreason,
                                '{$table}' as source_table
                             FROM {$table}
                             WHERE callere164 LIKE ? OR calleee164 LIKE ?
                             LIMIT 30)";
                $params[] = $searchPattern;
                $params[] = $searchPattern;
            }
            
            $sql = implode(" UNION ALL ", $unions) . " LIMIT 150";
            
            try {
                $stmt = $connection->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll();
                
                foreach ($rows as $row) {
                    $row['source_db'] = $serverKey;
                    $serverResults[] = $row;
                    $allResults[] = $row;
                }
                
                $batchTime = round((microtime(true) - $batchStart) * 1000, 2);
                $batchNum = $batchIndex + 1;
                $tablesStr = implode(', ', array_map(fn($t) => str_replace('e_cdr_', '', $t), $batch));
                
                echo "<tr>";
                echo "<td>#{$batchNum}</td>";
                echo "<td><small>{$tablesStr}</small></td>";
                echo "<td>" . count($rows) . "</td>";
                echo "<td class='" . timeClass($batchTime) . "'>{$batchTime} ms</td>";
                echo "</tr>";
                
            } catch (PDOException $e) {
                $batchTime = round((microtime(true) - $batchStart) * 1000, 2);
                echo "<tr class='table-danger'>";
                echo "<td>#{$batchNum}</td>";
                echo "<td colspan='2'>Error: " . htmlspecialchars($e->getMessage()) . "</td>";
                echo "<td>{$batchTime} ms</td>";
                echo "</tr>";
            }
        }
        
        echo "</tbody></table>";
        
        $serverTime = round((microtime(true) - $serverStart) * 1000, 2);
        echo "<p><strong>Total servidor:</strong> " . count($serverResults) . " resultados en <span class='" . timeClass($serverTime) . "'>{$serverTime} ms</span></p>";
        
        echo "</div></div>";
        
        $debugInfo[$serverKey] = [
            'connection' => $connTime,
            'tables' => $tablesTime,
            'total' => $serverTime,
            'results' => count($serverResults)
        ];
    endforeach;
    
    $totalTime = round((microtime(true) - $totalStart) * 1000, 2);
    ?>
    
    <!-- Resumen -->
    <div class="card bg-dark text-white">
        <div class="card-header"><strong>📊 Resumen Final - SUFIJO %numero</strong></div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <h3><?= count($allResults) ?></h3>
                    <small>Resultados totales</small>
                </div>
                <div class="col-md-3">
                    <h3><?= count($cdrServers) ?></h3>
                    <small>Servidores consultados</small>
                </div>
                <div class="col-md-3">
                    <h3 class="<?= timeClass($totalTime) ?>"><?= number_format($totalTime, 2) ?> ms</h3>
                    <small>Tiempo total</small>
                </div>
                <div class="col-md-3">
                    <h3><?= round($totalTime / 1000, 2) ?> s</h3>
                    <small>En segundos</small>
                </div>
            </div>
            
            <hr>
            
            <h6>Desglose por servidor:</h6>
            <table class="table table-dark table-sm">
                <thead><tr><th>Servidor</th><th>Conexión</th><th>Tablas</th><th>Total</th><th>Resultados</th></tr></thead>
                <tbody>
                <?php foreach ($debugInfo as $server => $info): ?>
                <tr>
                    <td><?= $server ?></td>
                    <td><?= $info['connection'] ?> ms</td>
                    <td><?= $info['tables'] ?> ms</td>
                    <td class="<?= timeClass($info['total']) ?>"><?= $info['total'] ?> ms</td>
                    <td><?= $info['results'] ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Primeros resultados -->
    <?php if (count($allResults) > 0): ?>
    <div class="card mt-3">
        <div class="card-header">Primeros 10 resultados</div>
        <div class="card-body">
            <table class="table table-sm table-striped">
                <thead><tr><th>Server</th><th>Tabla</th><th>Caller</th><th>Callee</th><th>Fecha</th><th>Duración</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($allResults, 0, 10) as $r): ?>
                <tr>
                    <td><?= $r['source_db'] ?></td>
                    <td><?= str_replace('e_cdr_', '', $r['source_table']) ?></td>
                    <td><?= $r['callere164'] ?></td>
                    <td><?= $r['calleee164'] ?></td>
                    <td><?= is_numeric($r['starttime']) ? date('Y-m-d H:i:s', intval($r['starttime']/1000)) : $r['starttime'] ?></td>
                    <td><?= $r['holdtime'] ?? '-' ?>s</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
</div>
</body>
</html>
