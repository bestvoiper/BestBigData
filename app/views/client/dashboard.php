<?php 
require_once APP_ROOT . '/app/views/helpers.php';
$activePage = 'dashboard';
$showBalance = true;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - BestBigData</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/main.css') ?>" rel="stylesheet">
    <link href="<?= url('assets/css/client.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include APP_ROOT . '/app/views/partials/client-sidebar.php'; ?>
    
    <div class="main-content">
        <?php 
        $title = 'Bienvenido, ' . e($user['name']);
        $subtitle = 'Panel de Control';
        include APP_ROOT . '/app/views/partials/topbar.php'; 
        ?>
        
        <?= showFlash($flash ?? null) ?>
        
        <?php if ($user['balance'] < ($settings['min_balance_alert'] ?? 10)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>¡Atención!</strong> Tu saldo es bajo. Contacta al administrador para recargar.
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <i class="bi bi-wallet2 fs-3"></i>
                    <h3><?= formatMoney($user['balance']) ?></h3>
                    <p>Saldo Disponible</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card accent">
                    <i class="bi bi-search fs-3"></i>
                    <h3><?= number_format($totalSearches) ?></h3>
                    <p>Búsquedas Realizadas</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card success">
                    <i class="bi bi-telephone fs-3"></i>
                    <h3><?= number_format($totalResults) ?></h3>
                    <p>Resultados Encontrados</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Quick Search -->
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-search"></i> Búsqueda Rápida
                    </div>
                    <div class="card-body">
                        <form action="<?= url('client/search') ?>" method="GET">
                            <div class="mb-3">
                                <label class="form-label">Número de Teléfono</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" name="phone" class="form-control" 
                                           placeholder="Ej: 5551234567" required>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-accent btn-lg">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                            </div>
                        </form>
                        <hr>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            Costo: <strong><?= formatMoney($settings['cost_per_result'] ?? 1) ?></strong> por cada resultado encontrado.
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Recent Searches -->
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clock-history"></i> Búsquedas Recientes</span>
                        <a href="<?= url('client/history') ?>" class="btn btn-sm btn-outline-primary">Ver Todo</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Teléfono</th>
                                        <th>Resultados</th>
                                        <th>Costo</th>
                                        <th>Fecha</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentSearches as $search): ?>
                                    <tr>
                                        <td><code><?= e($search['phone_number']) ?></code></td>
                                        <td><span class="badge bg-primary"><?= $search['results_found'] ?></span></td>
                                        <td><?= formatMoney($search['cost']) ?></td>
                                        <td><?= formatDate($search['created_at'], 'd/m H:i') ?></td>
                                        <td>
                                            <a href="<?= url('client/search?phone=' . urlencode($search['phone_number'])) ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Buscar de nuevo">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($recentSearches)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="bi bi-search fs-1 d-block mb-2"></i>
                                            No has realizado búsquedas aún
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
