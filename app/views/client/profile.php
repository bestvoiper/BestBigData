<?php 
require_once APP_ROOT . '/app/views/helpers.php';
$activePage = 'profile';
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
    <link href="<?= url('assets/css/main.css') ?>" rel="stylesheet">
    <link href="<?= url('assets/css/client.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include APP_ROOT . '/app/views/partials/client-sidebar.php'; ?>
    
    <div class="main-content">
        <?php 
        $title = 'Mi Perfil';
        $subtitle = 'Información de tu cuenta';
        include APP_ROOT . '/app/views/partials/topbar.php'; 
        ?>
        
        <?= showFlash($flash ?? null) ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-person"></i> Información Personal
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= url('client/profile') ?>">
                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= e($user['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" 
                                       value="<?= e($user['email']) ?>" disabled>
                                <small class="text-muted">El email no puede ser modificado</small>
                            </div>
                            <hr>
                            <h6>Cambiar Contraseña</h6>
                            <div class="mb-3">
                                <label class="form-label">Contraseña Actual</label>
                                <input type="password" name="current_password" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nueva Contraseña</label>
                                <input type="password" name="new_password" class="form-control">
                                <small class="text-muted">Mínimo 6 caracteres. Dejar vacío para no cambiar.</small>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-wallet2"></i> Mi Saldo
                    </div>
                    <div class="card-body text-center">
                        <h1 class="display-4 <?= $user['balance'] < ($settings['min_balance_alert'] ?? 10) ? 'text-danger' : 'text-success' ?>">
                            <?= formatMoney($user['balance']) ?>
                        </h1>
                        <p class="text-muted">Saldo disponible para búsquedas</p>
                        <hr>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            Para recargar saldo, contacta al administrador.
                        </small>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-clock-history"></i> Últimos Movimientos
                    </div>
                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($transactions as $tx): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <div>
                                <small class="text-muted"><?= formatDate($tx['created_at'], 'd/m H:i') ?></small>
                                <br>
                                <?php if ($tx['type'] === 'recharge'): ?>
                                    <span class="text-success"><i class="bi bi-plus-circle"></i> Recarga</span>
                                <?php else: ?>
                                    <span class="text-primary"><i class="bi bi-search"></i> Búsqueda</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <strong class="<?= $tx['type'] === 'recharge' ? 'text-success' : 'text-danger' ?>">
                                    <?= $tx['type'] === 'recharge' ? '+' : '-' ?><?= formatMoney($tx['amount']) ?>
                                </strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($transactions)): ?>
                        <p class="text-muted text-center">No hay movimientos</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
