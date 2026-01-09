<?php
/**
 * Sincronización de CDR a Elasticsearch
 * Importa registros desde los servidores MySQL a Elasticsearch
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/config/config.php';
require_once APP_ROOT . '/app/models/Conexion.php';
require_once APP_ROOT . '/app/services/ElasticSearch.php';

// Para ejecución CLI sin límite de tiempo
if (php_sapi_name() === 'cli') {
    set_time_limit(0);
}

header('Content-Type: text/html; charset=utf-8');

// Parámetros
$action = $_GET['action'] ?? '';
$server = $_GET['server'] ?? '';
$startDate = $_GET['start'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['end'] ?? date('Y-m-d');
$batchSize = (int)($_GET['batch'] ?? 1000);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sincronización CDR → Elasticsearch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .log-container { 
            background: #1e1e1e; 
            color: #0f0; 
            padding: 15px; 
            border-radius: 5px;
            font-family: monospace;
            max-height: 500px;
            overflow-y: auto;
        }
        .log-error { color: #f55; }
        .log-success { color: #5f5; }
        .log-info { color: #5af; }
        .log-warn { color: #fa0; }
        .progress-bar { transition: width 0.3s; }
    </style>
</head>
<body class="bg-light p-4">
<div class="container-fluid">
    <h1>📥 Sincronización CDR → Elasticsearch</h1>
    
    <?php
    $es = ElasticSearch::getInstance();
    
    if (!$es->isAvailable()) {
        echo "<div class='alert alert-danger'>❌ Elasticsearch no disponible</div>";
        exit;
    }
    
    $docCount = $es->count();
    echo "<div class='alert alert-info'>
        📊 <strong>Documentos actuales en Elasticsearch:</strong> " . number_format($docCount) . "
    </div>";
    ?>
    
    <form class="row g-3 mb-4" method="GET">
        <div class="col-md-2">
            <label class="form-label">Servidor</label>
            <select name="server" class="form-select">
                <option value="">Todos</option>
                <?php foreach (Conexion::getCDRServers() as $srv): ?>
                <option value="<?= $srv ?>" <?= $server === $srv ? 'selected' : '' ?>><?= $srv ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Desde</label>
            <input type="date" class="form-control" name="start" value="<?= $startDate ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Hasta</label>
            <input type="date" class="form-control" name="end" value="<?= $endDate ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Batch size</label>
            <select name="batch" class="form-select">
                <option value="500" <?= $batchSize === 500 ? 'selected' : '' ?>>500</option>
                <option value="1000" <?= $batchSize === 1000 ? 'selected' : '' ?>>1,000</option>
                <option value="2000" <?= $batchSize === 2000 ? 'selected' : '' ?>>2,000</option>
                <option value="5000" <?= $batchSize === 5000 ? 'selected' : '' ?>>5,000</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" name="action" value="sync" class="btn btn-success w-100">
                🚀 Iniciar Sincronización
            </button>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" name="action" value="count" class="btn btn-info w-100">
                📊 Solo Contar
            </button>
        </div>
    </form>
    
    <?php if ($action === 'count' || $action === 'sync'): ?>
    
    <div id="progress-container" class="mb-3">
        <div class="progress" style="height: 30px;">
            <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" style="width: 0%">0%</div>
        </div>
        <small id="progress-text" class="text-muted"></small>
    </div>
    
    <div id="log" class="log-container"></div>
    
    <script>
    const logEl = document.getElementById('log');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    
    function log(msg, type = '') {
        const line = document.createElement('div');
        line.className = type ? 'log-' + type : '';
        line.textContent = new Date().toLocaleTimeString() + ' ' + msg;
        logEl.appendChild(line);
        logEl.scrollTop = logEl.scrollHeight;
    }
    
    function updateProgress(current, total, text) {
        const pct = total > 0 ? Math.round(current / total * 100) : 0;
        progressBar.style.width = pct + '%';
        progressBar.textContent = pct + '%';
        progressText.textContent = text;
    }
    </script>
    
    <?php
    // Flush inicial
    ob_implicit_flush(true);
    if (ob_get_level()) ob_end_flush();
    
    function jsLog($msg, $type = '') {
        echo "<script>log(" . json_encode($msg) . ", " . json_encode($type) . ");</script>\n";
        flush();
    }
    
    function jsProgress($current, $total, $text) {
        echo "<script>updateProgress({$current}, {$total}, " . json_encode($text) . ");</script>\n";
        flush();
    }
    
    $servers = $server ? [$server] : Conexion::getCDRServers();
    $totalRecords = 0;
    $totalIndexed = 0;
    $errors = 0;
    
    $startYmd = str_replace('-', '', $startDate);
    $endYmd = str_replace('-', '', $endDate);
    
    jsLog("🔍 Analizando servidores: " . implode(', ', $servers), 'info');
    jsLog("📅 Rango de fechas: {$startDate} a {$endDate}", 'info');
    
    // Fase 1: Contar registros
    jsLog("--- Fase 1: Contando registros ---", 'info');
    $serverStats = [];
    
    foreach ($servers as $srv) {
        $cdrConn = Conexion::getCDR($srv);
        if (!$cdrConn) {
            jsLog("❌ No se pudo conectar a {$srv}", 'error');
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
        
        $serverCount = 0;
        foreach ($tables as $table) {
            try {
                $stmt = $conn->query("SELECT COUNT(*) FROM {$table}");
                $count = (int)$stmt->fetchColumn();
                $serverCount += $count;
            } catch (Exception $e) {
                // Tabla puede no existir
            }
        }
        
        $serverStats[$srv] = [
            'tables' => $tables,
            'count' => $serverCount,
            'connection' => $conn,
            'prefix' => $prefix
        ];
        
        $totalRecords += $serverCount;
        jsLog("📦 {$srv}: " . count($tables) . " tablas, " . number_format($serverCount) . " registros");
    }
    
    jsLog("📊 Total a procesar: " . number_format($totalRecords) . " registros", 'success');
    
    if ($action === 'count') {
        jsLog("--- Solo conteo (no se indexará) ---", 'warn');
        jsProgress($totalRecords, $totalRecords, "Conteo completado");
    }
    
    // Fase 2: Indexar (solo si action=sync)
    if ($action === 'sync') {
        jsLog("--- Fase 2: Indexando en Elasticsearch ---", 'info');
        
        $processed = 0;
        
        foreach ($serverStats as $srv => $stats) {
            if (empty($stats['tables'])) continue;
            
            $conn = $stats['connection'];
            
            foreach ($stats['tables'] as $table) {
                jsLog("📥 Procesando {$srv}/{$table}...");
                
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
                            jsLog("⚠️ Algunos errores en batch", 'warn');
                        }
                        
                        $indexed = count($docs);
                        $tableIndexed += $indexed;
                        $totalIndexed += $indexed;
                        $processed += $indexed;
                        $offset += $batchSize;
                        
                        jsProgress($processed, $totalRecords, 
                            "{$srv}/{$table}: " . number_format($tableIndexed) . " indexados");
                        
                    } catch (Exception $e) {
                        jsLog("❌ Error: " . $e->getMessage(), 'error');
                        $errors++;
                        break;
                    }
                }
                
                jsLog("✅ {$table}: " . number_format($tableIndexed) . " registros indexados", 'success');
            }
        }
        
        // Refrescar índice
        $es->refresh();
        
        jsLog("=================================", 'info');
        jsLog("✅ SINCRONIZACIÓN COMPLETADA", 'success');
        jsLog("📊 Total indexados: " . number_format($totalIndexed), 'success');
        jsLog("❌ Errores: {$errors}", $errors > 0 ? 'error' : 'success');
        jsLog("📈 Documentos en ES: " . number_format($es->count()), 'info');
        jsProgress($totalRecords, $totalRecords, "¡Completado!");
    }
    ?>
    
    <?php endif; ?>
    
    <hr class="mt-4">
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">🔄 Sincronización Automática (Cron)</div>
                <div class="card-body">
                    <p>Para mantener Elasticsearch actualizado, agrega este cron job:</p>
                    <pre class="bg-dark text-light p-2 rounded"># Cada hora, sincronizar últimos 2 días
0 * * * * php <?= APP_ROOT ?>/elasticsearch/sync_cli.php --days=2

# Cada noche, sincronización completa de la semana
0 3 * * * php <?= APP_ROOT ?>/elasticsearch/sync_cli.php --days=7</pre>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">📋 Comandos CLI</div>
                <div class="card-body">
                    <pre class="bg-dark text-light p-2 rounded"># Sincronizar últimos 7 días
php sync_cli.php --days=7

# Sincronizar rango específico  
php sync_cli.php --start=2025-01-01 --end=2025-01-09

# Solo un servidor
php sync_cli.php --server=sw1 --days=3</pre>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-3">
        <a href="setup_index.php" class="btn btn-secondary">⬅️ Volver a Setup</a>
        <a href="test_search.php" class="btn btn-success">🔍 Probar Búsqueda</a>
    </div>
    
</div>
</body>
</html>
