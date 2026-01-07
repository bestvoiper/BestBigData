<?php 
require_once APP_ROOT . '/app/views/helpers.php';
$activePage = 'searches';
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
    <link href="<?= url('assets/css/admin.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include APP_ROOT . '/app/views/partials/admin-sidebar.php'; ?>
    
    <div class="main-content">
        <?php 
        $title = 'Búsquedas';
        $subtitle = 'Historial de consultas realizadas';
        include APP_ROOT . '/app/views/partials/topbar.php'; 
        ?>
        
        <?= showFlash($flash ?? null) ?>
        
        <div class="card">
            <div class="card-header">
                <i class="bi bi-search"></i> Historial de Búsquedas
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="searchesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Teléfono</th>
                                <th>Resultados</th>
                                <th>Costo</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($searches as $search): ?>
                            <tr>
                                <td><?= $search['id'] ?></td>
                                <td><?= e($search['user_name']) ?></td>
                                <td><code><?= e($search['phone_number']) ?></code></td>
                                <td><span class="badge bg-primary"><?= $search['results_found'] ?></span></td>
                                <td><?= formatMoney($search['cost']) ?></td>
                                <td><?= formatDate($search['created_at']) ?></td>
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
            $('#searchesTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                order: [[0, 'desc']]
            });
        });
    </script>
</body>
</html>
