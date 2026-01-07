<?php 
require_once APP_ROOT . '/app/views/helpers.php';
$activePage = 'search';
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
        $title = 'Buscar Número Telefónico';
        $subtitle = 'Consulta en todas las bases de datos CDR';
        include APP_ROOT . '/app/views/partials/topbar.php'; 
        ?>
        
        <?= showFlash($flash ?? null) ?>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> <?= e($error) ?>
        </div>
        <?php endif; ?>
        
        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label"><i class="bi bi-telephone"></i> Número de Teléfono</label>
                        <input type="text" name="phone" class="form-control form-control-lg" 
                               value="<?= e($searchPhone) ?>"
                               placeholder="Ej: 5551234567" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><i class="bi bi-calendar"></i> Desde</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="<?= $_GET['start_date'] ?? '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><i class="bi bi-calendar"></i> Hasta</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="<?= $_GET['end_date'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-accent btn-lg w-100">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </form>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> 
                        Se cobrará <strong><?= formatMoney($costPerResult) ?></strong> por cada resultado encontrado.
                        La búsqueda se realiza en las columnas <code>callere164</code> y <code>calleee164</code>.
                    </small>
                </div>
            </div>
        </div>
        
        <?php if ($searchPerformed): ?>
        <!-- Results -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-list-ul"></i> 
                    Resultados para: <code class="fs-5"><?= e($searchPhone) ?></code>
                </span>
                <div>
                    <span class="badge bg-primary fs-6"><?= count($results) ?> registros</span>
                    <span class="badge bg-warning fs-6">Costo: <?= formatMoney($totalCost) ?></span>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($results) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover data-table" id="resultsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Fecha Llamada</th>
                                <th>Origen</th>
                                <th>Destino</th>
                                <th>Duración</th>
                                <th>Estado</th>
                                <th>Servidor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $index => $row): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= formatDate($row['starttime']) ?></td>
                                <td><code><?= e($row['callere164']) ?></code></td>
                                <td><code><?= e($row['calleee164']) ?></code></td>
                                <td><?= gmdate('H:i:s', $row['holdtime'] ?? 0) ?></td>
                                <td>
                                    <?php 
                                    $endreason = $row['endreason'] ?? '';
                                    $badgeClass = $endreason == 'ANSWERED' ? 'success' : 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $badgeClass ?>"><?= e($endreason) ?></span>
                                </td>
                                <td><span class="badge bg-info"><?= e($row['source_db']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-search fs-1 text-muted"></i>
                    <h5 class="mt-3">No se encontraron resultados</h5>
                    <p class="text-muted">No hay registros para el número <code><?= e($searchPhone) ?></code></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            if ($('#resultsTable').length) {
                $('#resultsTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                    },
                    pageLength: 25,
                    order: [[1, 'desc']]
                });
            }
        });
    </script>
</body>
</html>
