<?php 
require_once APP_ROOT . '/app/views/helpers.php';
$activePage = 'transactions';
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
        $title = 'Transacciones';
        $subtitle = 'Historial de movimientos';
        include APP_ROOT . '/app/views/partials/topbar.php'; 
        ?>
        
        <?= showFlash($flash ?? null) ?>
        
        <div class="card">
            <div class="card-header">
                <i class="bi bi-currency-dollar"></i> Historial de Transacciones
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="transactionsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Tipo</th>
                                <th>Monto</th>
                                <th>Descripción</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td><?= $tx['id'] ?></td>
                                <td><?= e($tx['user_name']) ?></td>
                                <td>
                                    <?php if ($tx['type'] === 'recharge'): ?>
                                        <span class="badge bg-success"><i class="bi bi-plus-lg"></i> Recarga</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary"><i class="bi bi-search"></i> Búsqueda</span>
                                    <?php endif; ?>
                                </td>
                                <td class="<?= $tx['type'] === 'recharge' ? 'text-success' : 'text-danger' ?>">
                                    <?= $tx['type'] === 'recharge' ? '+' : '-' ?><?= formatMoney($tx['amount']) ?>
                                </td>
                                <td><?= e($tx['description']) ?></td>
                                <td><?= formatDate($tx['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#transactionsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                order: [[0, 'desc']]
            });
        });
    </script>
</body>
</html>
