<?php
/**
 * Script de diagnóstico para verificar conexiones a bases de datos
 * Actualización en tiempo real sin recargar la página
 */

define('APP_ROOT', __DIR__);
require_once APP_ROOT . '/app/config/config.php';

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
        .refresh-indicator { 
            position: fixed; 
            top: 10px; 
            right: 10px; 
            background: rgba(0,0,0,0.7); 
            color: white; 
            padding: 8px 15px; 
            border-radius: 20px; 
            font-size: 0.85rem;
            z-index: 1000;
        }
        .updating { opacity: 0.6; }
        .pulse { animation: pulse 0.5s ease-in-out; }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body class="bg-light">
<div class="refresh-indicator">
    <i class="bi bi-arrow-clockwise" id="refreshIcon"></i> 
    <span id="statusText">Auto-refresh: <span id="countdown">5</span>s</span>
</div>
<div class="container py-4">
    <div class="d-flex align-items-center mb-4">
        <i class="bi bi-database-check fs-1 text-primary me-3"></i>
        <div>
            <h1 class="mb-0">Diagnóstico de Conexiones</h1>
            <small class="text-muted">BestBigData v<?= APP_VERSION ?> | Entorno: <span id="environment">-</span></small>
        </div>
    </div>
    
    <div class="row" id="serversContainer">
        <!-- Se llena dinámicamente -->
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-3">Verificando conexiones...</p>
        </div>
    </div>
    
    <!-- Resumen -->
    <div class="card mt-4">
        <div class="card-header">
            <i class="bi bi-clipboard-check"></i> Resumen del Diagnóstico
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar" id="progressBar" style="width: 0%;">
                            <span id="progressText">0 / 0 conexiones</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge fs-6" id="statusBadge">Verificando...</span>
                </div>
            </div>
            
            <hr>
            
            <div class="row text-center">
                <div class="col-md-4">
                    <i class="bi bi-clock text-muted"></i>
                    <small class="text-muted d-block">Última actualización</small>
                    <strong id="lastUpdate">-</strong>
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
        <button onclick="fetchData()" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-clockwise"></i> Actualizar Ahora
        </button>
        <button onclick="toggleAutoRefresh()" class="btn btn-outline-info" id="toggleBtn">
            <i class="bi bi-pause-fill"></i> Pausar
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const REFRESH_INTERVAL = 5; // segundos
    let countdown = REFRESH_INTERVAL;
    let autoRefresh = true;
    let isUpdating = false;
    
    const elements = {
        countdown: document.getElementById('countdown'),
        toggleBtn: document.getElementById('toggleBtn'),
        serversContainer: document.getElementById('serversContainer'),
        progressBar: document.getElementById('progressBar'),
        progressText: document.getElementById('progressText'),
        statusBadge: document.getElementById('statusBadge'),
        lastUpdate: document.getElementById('lastUpdate'),
        environment: document.getElementById('environment'),
        refreshIcon: document.getElementById('refreshIcon'),
        statusText: document.getElementById('statusText')
    };
    
    function renderServerCard(key, data, isMain = false) {
        const isOk = data.status === 'ok';
        const headerClass = isMain ? 'bg-primary' : 'bg-secondary';
        const title = isMain ? 'Base de Datos Principal' : `CDR: ${key.toUpperCase()}`;
        const icon = isMain ? 'bi-database' : 'bi-server';
        
        let content = '';
        if (isOk) {
            content = `
                <div class="text-center py-3">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-success">Conexión Exitosa</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-hdd"></i> Host</span>
                        <strong>${data.host}</strong>
                    </li>
                    ${isMain ? `
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-archive"></i> Base de datos</span>
                        <strong>${data.database}</strong>
                    </li>` : `
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-table"></i> Tablas CDR</span>
                        <strong>${data.tables}</strong>
                    </li>`}
                    <li class="list-group-item d-flex justify-content-between">
                        <span><i class="bi bi-speedometer2"></i> Tiempo respuesta</span>
                        <strong>${data.responseTime} ms</strong>
                    </li>
                </ul>`;
        } else {
            content = `
                <div class="text-center py-3">
                    <i class="bi bi-x-circle-fill text-danger" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-danger">Error de Conexión</h5>
                    <p class="text-muted small">${data.error || 'No se pudo conectar'}</p>
                </div>`;
        }
        
        return `
            <div class="col-md-4 mb-4">
                <div class="card status-card h-100 pulse">
                    <div class="card-header ${headerClass} text-white">
                        <i class="bi ${icon}"></i> ${title}
                    </div>
                    <div class="card-body">${content}</div>
                </div>
            </div>`;
    }
    
    async function fetchData() {
        if (isUpdating) return;
        isUpdating = true;
        
        elements.refreshIcon.classList.add('spin');
        elements.statusText.innerHTML = 'Actualizando...';
        
        try {
            const response = await fetch('<?= BASE_URL ?>/api/diagnostico.php?' + Date.now());
            const data = await response.json();
            
            // Renderizar servidores
            let html = renderServerCard('main', data.main, true);
            for (const [key, cdr] of Object.entries(data.cdr)) {
                html += renderServerCard(key, cdr, false);
            }
            elements.serversContainer.innerHTML = html;
            
            // Actualizar resumen
            const { connected, total, percentage } = data.summary;
            elements.progressBar.style.width = percentage + '%';
            elements.progressText.textContent = `${connected} / ${total} conexiones activas`;
            
            // Color del progress bar
            let barClass = 'bg-success';
            let badgeClass = 'bg-success';
            let badgeText = '<i class="bi bi-check-lg"></i> Todo funcionando';
            
            if (percentage < 100) {
                barClass = percentage >= 50 ? 'bg-warning' : 'bg-danger';
                badgeClass = percentage >= 50 ? 'bg-warning' : 'bg-danger';
                badgeText = percentage >= 50 ? 
                    '<i class="bi bi-exclamation-triangle"></i> Parcialmente operativo' : 
                    '<i class="bi bi-x-lg"></i> Problemas críticos';
            }
            
            elements.progressBar.className = `progress-bar ${barClass}`;
            elements.statusBadge.className = `badge fs-6 ${badgeClass}`;
            elements.statusBadge.innerHTML = badgeText;
            
            // Actualizar metadata
            elements.lastUpdate.textContent = data.timestamp;
            elements.environment.textContent = data.environment;
            
        } catch (error) {
            console.error('Error fetching data:', error);
            elements.statusBadge.className = 'badge fs-6 bg-danger';
            elements.statusBadge.innerHTML = '<i class="bi bi-wifi-off"></i> Error de conexión';
        }
        
        elements.refreshIcon.classList.remove('spin');
        countdown = REFRESH_INTERVAL;
        isUpdating = false;
    }
    
    function toggleAutoRefresh() {
        autoRefresh = !autoRefresh;
        if (autoRefresh) {
            elements.toggleBtn.innerHTML = '<i class="bi bi-pause-fill"></i> Pausar';
            elements.toggleBtn.classList.remove('btn-success');
            elements.toggleBtn.classList.add('btn-outline-info');
        } else {
            elements.toggleBtn.innerHTML = '<i class="bi bi-play-fill"></i> Reanudar';
            elements.toggleBtn.classList.remove('btn-outline-info');
            elements.toggleBtn.classList.add('btn-success');
        }
    }
    
    // Timer
    setInterval(() => {
        if (autoRefresh && !isUpdating) {
            countdown--;
            elements.countdown.textContent = countdown;
            elements.statusText.innerHTML = `Auto-refresh: <span id="countdown">${countdown}</span>s`;
            if (countdown <= 0) {
                fetchData();
            }
        }
    }, 1000);
    
    // Cargar datos iniciales
    fetchData();
</script>
<style>
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>
</body>
</html>
