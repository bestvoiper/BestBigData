<?php 
require_once APP_ROOT . '/app/views/helpers.php';
$activePage = 'settings';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - DetectNUM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/main.css') ?>" rel="stylesheet">
    <link href="<?= url('assets/css/admin.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include APP_ROOT . '/app/views/partials/admin-sidebar.php'; ?>
    
    <div class="main-content">
        <?php 
        $title = 'Configuración';
        $subtitle = 'Ajustes del sistema';
        include APP_ROOT . '/app/views/partials/topbar.php'; 
        ?>
        
        <?= showFlash($flash ?? null) ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-gear"></i> Configuración General
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= url('admin/settings') ?>">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Costo por Resultado</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="cost_per_result" class="form-control" 
                                                   value="<?= $settings['cost_per_result'] ?? 1 ?>" 
                                                   min="0.01" step="0.01" required>
                                        </div>
                                        <small class="text-muted">Monto cobrado por cada resultado encontrado</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Máximo de Resultados por Búsqueda</label>
                                        <input type="number" name="max_results_per_search" class="form-control" 
                                               value="<?= $settings['max_results_per_search'] ?? 1000 ?>" 
                                               min="1" required>
                                        <small class="text-muted">Límite máximo de registros a mostrar</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Alerta de Saldo Bajo</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="min_balance_alert" class="form-control" 
                                                   value="<?= $settings['min_balance_alert'] ?? 10 ?>" 
                                                   min="0" step="0.01" required>
                                        </div>
                                        <small class="text-muted">Mostrar alerta cuando el saldo sea menor a este valor</small>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Guardar Configuración
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-database"></i> Bases de Datos CDR
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>SW2</strong>
                            <br><small class="text-muted">sw2.bestvoiper.com</small>
                            <br><span class="badge bg-success">Conectado</span>
                        </div>
                        <div class="mb-3">
                            <strong>SW3</strong>
                            <br><small class="text-muted">sw3.bestvoiper.com</small>
                            <br><span class="badge bg-success">Conectado</span>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Las conexiones a las bases CDR se configuran en el archivo de configuración.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
