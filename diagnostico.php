<?php
/**
 * Script de diagnóstico para verificar conexiones a bases de datos
 * Solo valida conexiones, no trae datos
 */

// Definir APP_ROOT
define('APP_ROOT', __DIR__);

// Cargar configuración
require_once APP_ROOT . '/app/config/config.php';
require_once APP_ROOT . '/app/models/Conexion.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Conexiones - BestBigData</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .status-card { transition: all 0.3s; }
        .status-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex align-items-center mb-4">
        <i class="bi bi-database-check fs-1 text-primary me-3"></i>
        <div>
            <h1 class="mb-0">Diagnóstico de Conexiones</h1>
            <small class="text-muted">BestBigData v<?= APP_VERSION ?></small>
        </div>
    </div>
    
    <div class="row">
        <!-- Base de datos principal -->
        <div class="col-md-4 mb-4">
            <div class="card status-card h-100">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-database"></i> Base de Datos Principal
                </div>
                <div class="card-body">
                    <?php
                    $startTime = microtime(true);
                    try {
                        $mainDb = Conexion::getMain();
                        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                        
                        // Solo verificar que la conexión funciona
                        $stmt = $mainDb->query("SELECT 1");
                        
                        echo "<div class='text-center py-3'>";
                        echo "<i class='bi bi-check-circle-fill text-success' style='font-size: 3rem;'></i>";
                        echo "<h5 class='mt-3 text-success'>Conexión Exitosa</h5>";
                        echo "</div>";
                        
                        echo "<ul class='list-group list-group-flush'>";
                        echo "<li class='list-group-item d-flex justify-content-between'>";
                        echo "<span><i class='bi bi-hdd'></i> Host</span>";
                        echo "<strong>" . DB_HOST . "</strong>";
                        echo "</li>";
                        echo "<li class='list-group-item d-flex justify-content-between'>";
                        echo "<span><i class='bi bi-archive'></i> Base de datos</span>";
                        echo "<strong>" . DB_NAME . "</strong>";
                        echo "</li>";
                        echo "<li class='list-group-item d-flex justify-content-between'>";
                        echo "<span><i class='bi bi-speedometer2'></i> Tiempo respuesta</span>";
                        echo "<strong>{$responseTime} ms</strong>";
                        echo "</li>";
                        echo "</ul>";
                        
                    } catch (Exception $e) {
                        echo "<div class='text-center py-3'>";
                        echo "<i class='bi bi-x-circle-fill text-danger' style='font-size: 3rem;'></i>";
                        echo "<h5 class='mt-3 text-danger'>Error de Conexión</h5>";
                        echo "<p class='text-muted small'>" . htmlspecialchars($e->getMessage()) . "</p>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Bases de datos CDR -->
        <?php
        $cdrServers = Conexion::getCDRServers();
        foreach ($cdrServers as $serverKey):
        ?>
        <div class="col-md-4 mb-4">
            <div class="card status-card h-100">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-server"></i> CDR: <?= strtoupper($serverKey) ?>
                </div>
                <div class="card-body">
                    <?php
                    $startTime = microtime(true);
                    $cdrConn = Conexion::getCDR($serverKey);
                    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    if ($cdrConn !== null):
                        $connection = $cdrConn['connection'];
                        $prefix = $cdrConn['prefix'];
                        
                        // Contar tablas CDR (sin traer datos)
                        try {
                            $stmt = $connection->query("SHOW TABLES LIKE '{$prefix}%'");
                            $tableCount = $stmt->rowCount();
                        } catch (Exception $e) {
                            $tableCount = 0;
                        }
                    ?>
                        <div class="text-center py-3">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 text-success">Conexión Exitosa</h5>
                        </div>
                        
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span><i class="bi bi-hdd"></i> Host</span>
                                <strong><?= $serverKey ?>.bestvoiper.com</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><i class="bi bi-table"></i> Tablas CDR</span>
                                <strong><?= $tableCount ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><i class="bi bi-speedometer2"></i> Tiempo respuesta</span>
                                <strong><?= $responseTime ?> ms</strong>
                            </li>
                        </ul>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="bi bi-x-circle-fill text-danger" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 text-danger">Error de Conexión</h5>
                            <p class="text-muted small">No se pudo conectar al servidor <?= $serverKey ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Resumen -->
    <div class="card mt-4">
        <div class="card-header">
            <i class="bi bi-clipboard-check"></i> Resumen del Diagnóstico
        </div>
        <div class="card-body">
            <?php
            $totalServers = count($cdrServers) + 1; // +1 por la base principal
            $connectedServers = 0;
            
            // Verificar principal
            try {
                Conexion::getMain();
                $connectedServers++;
            } catch (Exception $e) {}
            
            // Verificar CDRs
            foreach ($cdrServers as $key) {
                if (Conexion::isCDRAvailable($key)) {
                    $connectedServers++;
                }
            }
            
            $percentage = round(($connectedServers / $totalServers) * 100);
            $statusClass = $percentage == 100 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');
            ?>
            
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-<?= $statusClass ?>" style="width: <?= $percentage ?>%;">
                            <?= $connectedServers ?> / <?= $totalServers ?> conexiones activas
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <?php if ($percentage == 100): ?>
                        <span class="badge bg-success fs-6"><i class="bi bi-check-lg"></i> Todo funcionando</span>
                    <?php elseif ($percentage >= 50): ?>
                        <span class="badge bg-warning fs-6"><i class="bi bi-exclamation-triangle"></i> Parcialmente operativo</span>
                    <?php else: ?>
                        <span class="badge bg-danger fs-6"><i class="bi bi-x-lg"></i> Problemas críticos</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <hr>
            
            <div class="row text-center">
                <div class="col-md-4">
                    <i class="bi bi-clock text-muted"></i>
                    <small class="text-muted d-block">Fecha del diagnóstico</small>
                    <strong><?= date('d/m/Y H:i:s') ?></strong>
                </div>
                <div class="col-md-4">
                    <i class="bi bi-globe text-muted"></i>
                    <small class="text-muted d-block">Servidor</small>
                    <strong><?= $_SERVER['SERVER_NAME'] ?? 'localhost' ?></strong>
                </div>
                <div class="col-md-4">
                    <i class="bi bi-filetype-php text-muted"></i>
                    <small class="text-muted d-block">Versión PHP</small>
                    <strong><?= PHP_VERSION ?></strong>
                </div>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-4">
        <a href="<?= BASE_URL ?>" class="btn btn-primary">
            <i class="bi bi-house"></i> Ir al Sistema
        </a>
        <button onclick="location.reload()" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-clockwise"></i> Actualizar
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
