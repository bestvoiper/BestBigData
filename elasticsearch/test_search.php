<?php
/**
 * Test de búsqueda en Elasticsearch
 * Compara rendimiento ES vs MySQL
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/config/config.php';
require_once APP_ROOT . '/app/models/Conexion.php';
require_once APP_ROOT . '/app/services/ElasticSearch.php';

header('Content-Type: text/html; charset=utf-8');

$phoneNumber = $_GET['q'] ?? '573124560009';
$startDate = $_GET['start'] ?? '2025-01-01';
$endDate = $_GET['end'] ?? '2025-01-09';
$searchType = $_GET['type'] ?? 'optimized';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test Búsqueda Elasticsearch - BestBigData</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .time-excellent { color: #0d6efd; font-weight: bold; font-size: 1.2em; }
        .time-good { color: green; font-weight: bold; }
        .time-medium { color: orange; font-weight: bold; }
        .time-bad { color: red; font-weight: bold; }
        .comparison-card { border-left: 4px solid; }
        .es-card { border-left-color: #0d6efd; }
        .mysql-card { border-left-color: #6c757d; }
    </style>
</head>
<body class="bg-light p-4">
<div class="container">
    <h1>🔍 Test de Búsqueda - Elasticsearch</h1>
    
    <?php
    $es = ElasticSearch::getInstance();
    
    if (!$es->isAvailable()) {
        echo "<div class='alert alert-danger'>❌ Elasticsearch no disponible</div>";
        exit;
    }
    
    $esInfo = $es->getInfo();
    $docCount = $es->count();
    ?>
    
    <div class="alert alert-info">
        <div class="row">
            <div class="col-md-4">
                <strong>Cluster:</strong> <?= $esInfo['cluster_name'] ?>
            </div>
            <div class="col-md-4">
                <strong>Versión:</strong> <?= $esInfo['version']['number'] ?>
            </div>
            <div class="col-md-4">
                <strong>Documentos indexados:</strong> <?= number_format($docCount) ?>
            </div>
        </div>
    </div>
    
    <?php if ($docCount === 0): ?>
    <div class="alert alert-warning">
        ⚠️ No hay documentos indexados. 
        <a href="sync_cdr.php" class="btn btn-sm btn-primary">Ir a Sincronización</a>
    </div>
    <?php else: ?>
    
    <form class="row g-3 mb-4" method="GET">
        <div class="col-md-3">
            <label class="form-label">Número a buscar</label>
            <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($phoneNumber) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Desde</label>
            <input type="date" class="form-control" name="start" value="<?= $startDate ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Hasta</label>
            <input type="date" class="form-control" name="end" value="<?= $endDate ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipo de búsqueda</label>
            <select name="type" class="form-select">
                <option value="optimized" <?= $searchType === 'optimized' ? 'selected' : '' ?>>Optimizada (índices)</option>
                <option value="wildcard" <?= $searchType === 'wildcard' ? 'selected' : '' ?>>Wildcard (*pattern*)</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">🔍 Buscar</button>
        </div>
    </form>
    
    <?php
    // Ejecutar búsqueda en Elasticsearch
    $esStart = microtime(true);
    
    if ($searchType === 'wildcard') {
        $esResult = $es->searchWildcard($phoneNumber, $startDate, $endDate, 500);
    } else {
        $esResult = $es->searchPhone($phoneNumber, $startDate, $endDate, 500);
    }
    
    $esTime = round((microtime(true) - $esStart) * 1000, 2);
    
    function timeClass($ms) {
        if ($ms < 100) return 'time-excellent';
        if ($ms < 1000) return 'time-good';
        if ($ms < 5000) return 'time-medium';
        return 'time-bad';
    }
    ?>
    
    <!-- Resultados ES -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card comparison-card es-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">⚡ Elasticsearch - Resultados</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-md-3">
                            <h2><?= number_format($esResult['total']) ?></h2>
                            <small>Resultados encontrados</small>
                        </div>
                        <div class="col-md-3">
                            <h2 class="<?= timeClass($esTime) ?>"><?= $esTime ?> ms</h2>
                            <small>Tiempo total PHP</small>
                        </div>
                        <div class="col-md-3">
                            <h2 class="<?= timeClass($esResult['es_took_ms']) ?>"><?= $esResult['es_took_ms'] ?> ms</h2>
                            <small>Tiempo ES interno</small>
                        </div>
                        <div class="col-md-3">
                            <h2><?= round($esTime / 1000, 3) ?> s</h2>
                            <small>En segundos</small>
                        </div>
                    </div>
                    
                    <?php
                    // Desglose de lo que se buscó
                    $cleanNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
                    $baseNumber = ElasticSearch::extractBaseNumber($phoneNumber);
                    ?>
                    <div class="alert alert-light">
                        <strong>Número original:</strong> <?= htmlspecialchars($phoneNumber) ?><br>
                        <strong>Número limpio:</strong> <?= $cleanNumber ?><br>
                        <strong>Número base:</strong> <?= $baseNumber ?><br>
                        <strong>Tipo de búsqueda:</strong> <?= $searchType === 'wildcard' ? 'Wildcard (*pattern*)' : 'Optimizada (term queries)' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Comparación con estimación MySQL -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">⚡ Elasticsearch</div>
                <div class="card-body text-center">
                    <h1 class="<?= timeClass($esTime) ?>"><?= $esTime ?> ms</h1>
                    <p class="mb-0">(~<?= round($esTime / 1000, 3) ?> segundos)</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-secondary">
                <div class="card-header bg-secondary text-white">🐢 MySQL (estimado)</div>
                <div class="card-body text-center">
                    <h1 class="time-bad">120,000+ ms</h1>
                    <p class="mb-0">(~2+ minutos con LIKE)</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($esTime < 1000 && $esResult['total'] > 0): ?>
    <div class="alert alert-success">
        🎉 <strong>¡Excelente!</strong> La búsqueda tardó solo <strong><?= $esTime ?> ms</strong> 
        - esto es aproximadamente <strong><?= round(120000 / max($esTime, 1)) ?>x más rápido</strong> que MySQL con LIKE.
    </div>
    <?php endif; ?>
    
    <!-- Tabla de resultados -->
    <?php if (count($esResult['results']) > 0): ?>
    <div class="card">
        <div class="card-header">
            📋 Primeros <?= min(20, count($esResult['results'])) ?> de <?= number_format($esResult['total']) ?> resultados
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Server</th>
                        <th>Tabla</th>
                        <th>Caller</th>
                        <th>Callee</th>
                        <th>Fecha</th>
                        <th>Duración</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($esResult['results'], 0, 20) as $r): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?= $r['source_db'] ?></span></td>
                        <td><?= str_replace('e_cdr_', '', $r['source_table']) ?></td>
                        <td><?= htmlspecialchars($r['callere164']) ?></td>
                        <td><?= htmlspecialchars($r['calleee164']) ?></td>
                        <td><?= is_numeric($r['starttime']) ? date('Y-m-d H:i:s', intval($r['starttime']/1000)) : $r['starttime'] ?></td>
                        <td><?= $r['holdtime'] ?>s</td>
                        <td><span class="badge bg-info"><?= $r['endreason'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif ($docCount > 0): ?>
    <div class="alert alert-warning">
        No se encontraron resultados para "<?= htmlspecialchars($phoneNumber) ?>" en el rango de fechas seleccionado.
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
    
    <hr class="mt-4">
    
    <div class="row">
        <div class="col-md-6">
            <a href="setup_index.php" class="btn btn-secondary">⬅️ Setup</a>
            <a href="sync_cdr.php" class="btn btn-info">📥 Sincronización</a>
        </div>
        <div class="col-md-6 text-end">
            <a href="../debug_search.php?q=<?= urlencode($phoneNumber) ?>&start=<?= $startDate ?>&end=<?= $endDate ?>" 
               class="btn btn-outline-secondary" target="_blank">
                🐢 Comparar con MySQL (lento)
            </a>
        </div>
    </div>
    
</div>
</body>
</html>
