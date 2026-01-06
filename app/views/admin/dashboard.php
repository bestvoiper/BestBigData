<?php 
require_once APP_ROOT . '/app/views/helpers.php';
$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - DetectNUM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/main.css') ?>" rel="stylesheet">
    <link href="<?= url('assets/css/admin.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include APP_ROOT . '/app/views/partials/admin-sidebar.php'; ?>
    
    <div class="main-content">
        <?php 
        $title = 'Dashboard';
        $subtitle = 'Bienvenido, ' . e($user['name']);
        include APP_ROOT . '/app/views/partials/topbar.php'; 
        ?>
        
        <?= showFlash($flash ?? null) ?>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-people fs-3"></i>
                    <h3><?= number_format($totalClients) ?></h3>
                    <p>Total Clientes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <i class="bi bi-person-check fs-3"></i>
                    <h3><?= number_format($activeClients) ?></h3>
                    <p>Clientes Activos</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card accent">
                    <i class="bi bi-search fs-3"></i>
                    <h3><?= number_format($searchesToday) ?></h3>
                    <p>Búsquedas Hoy</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <i class="bi bi-cash-stack fs-3"></i>
                    <h3><?= formatMoney($revenueToday) ?></h3>
                    <p>Ingresos Hoy</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Recent Searches -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-search"></i> Búsquedas Recientes</span>
                        <a href="<?= url('admin/searches') ?>" class="btn btn-sm btn-outline-primary">Ver Todas</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Teléfono</th>
                                        <th>Resultados</th>
                                        <th>Costo</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentSearches as $search): ?>
                                    <tr>
                                        <td><?= e($search['user_name']) ?></td>
                                        <td><code><?= e($search['phone_number']) ?></code></td>
                                        <td><span class="badge bg-primary"><?= $search['results_found'] ?></span></td>
                                        <td><?= formatMoney($search['cost']) ?></td>
                                        <td><?= formatDate($search['created_at']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($recentSearches)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No hay búsquedas recientes</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-people"></i> Nuevos Usuarios</span>
                        <a href="<?= url('admin/users') ?>" class="btn btn-sm btn-outline-primary">Ver Todos</a>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recentUsers as $client): ?>
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                            <div class="avatar me-3" style="width:40px;height:40px;font-size:0.9rem;">
                                <?= strtoupper(substr($client['name'], 0, 1)) ?>
                            </div>
                            <div class="flex-grow-1">
                                <strong><?= e($client['name']) ?></strong>
                                <br><small class="text-muted"><?= formatMoney($client['balance']) ?></small>
                            </div>
                            <span class="badge bg-<?= $client['status'] === 'active' ? 'success' : 'secondary' ?>">
                                <?= $client['status'] ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($recentUsers)): ?>
                        <p class="text-muted text-center">No hay usuarios recientes</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
