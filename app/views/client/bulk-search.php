<?php 
require_once APP_ROOT . '/app/views/helpers.php';
$activePage = 'bulk-search';
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
        $title = 'Búsqueda Masiva';
        $subtitle = 'Buscar múltiples números desde archivo CSV o TXT';
        include APP_ROOT . '/app/views/partials/topbar.php'; 
        ?>
        
        <?= showFlash($flash ?? null) ?>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?= e($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <i class="bi bi-info-circle"></i> <?= e($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Upload Form -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-file-earmark-arrow-up"></i> Subir Archivo de Números
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label"><i class="bi bi-file-earmark-text"></i> Archivo CSV o TXT</label>
                        <input type="file" name="phone_file" class="form-control form-control-lg" 
                               accept=".csv,.txt" required>
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> Formatos aceptados: .csv, .txt (máx. 100MB)
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><i class="bi bi-calendar"></i> Desde</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="<?= $_POST['start_date'] ?? '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><i class="bi bi-calendar"></i> Hasta</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="<?= $_POST['end_date'] ?? '' ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-accent btn-lg w-100">
                            <i class="bi bi-search"></i> Procesar y Buscar
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="bi bi-file-earmark-spreadsheet text-success"></i> Formato CSV</h6>
                        <p class="text-muted small mb-2">Un número por columna o separados por comas:</p>
                        <pre class="bg-dark text-light p-2 rounded small">5551234567,5559876543
5551111111,5552222222
5553333333</pre>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-file-text text-primary"></i> Formato TXT</h6>
                        <p class="text-muted small mb-2">Un número por línea o separados por espacios/comas:</p>
                        <pre class="bg-dark text-light p-2 rounded small">5551234567
5559876543
5551111111</pre>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3 mb-0">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Información de cobro:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Se cobrará <strong><?= formatMoney($costPerResult) ?></strong> por cada resultado encontrado</li>
                                <li>Tamaño máximo de archivo: <strong>100MB</strong></li>
                                <li><strong>Sin límite</strong> de números por archivo</li>
                                <li>Los números deben tener al menos 7 dígitos</li>
                            </ul>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="fs-5">Tu saldo: <strong class="text-success"><?= formatMoney($user['balance']) ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($searchPerformed): ?>
        
        <!-- Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-telephone fs-1"></i>
                        <h3 class="mt-2"><?= $numbersSearched ?></h3>
                        <small>Números Buscados</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle fs-1"></i>
                        <h3 class="mt-2"><?= count($results) ?></h3>
                        <small>Resultados Encontrados</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <i class="bi bi-currency-dollar fs-1"></i>
                        <h3 class="mt-2"><?= formatMoney($totalCost) ?></h3>
                        <small>Costo Total</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-wallet2 fs-1"></i>
                        <h3 class="mt-2"><?= formatMoney($user['balance']) ?></h3>
                        <small>Saldo Restante</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Summary by Number -->
        <?php if (!empty($summary)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-list-check"></i> Resumen por Número
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>#</th>
                                <th>Número</th>
                                <th>Resultados</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $idx = 0; foreach ($summary as $phone => $info): $idx++; ?>
                            <tr>
                                <td><?= $idx ?></td>
                                <td><code><?= e($info['phone']) ?></code></td>
                                <td>
                                    <span class="badge bg-<?= $info['count'] > 0 ? 'success' : 'secondary' ?>">
                                        <?= $info['count'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($info['count'] > 0): ?>
                                        <span class="text-success"><i class="bi bi-check"></i> Encontrado</span>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="bi bi-dash"></i> Sin resultados</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Detailed Results -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-list-ul"></i> 
                    Resultados Detallados
                </span>
                <?php if (count($results) > 0): ?>
                <button class="btn btn-sm btn-outline-success" onclick="exportResults()">
                    <i class="bi bi-download"></i> Exportar CSV
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (count($results) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover data-table" id="resultsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Número Buscado</th>
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
                                <td><code class="text-primary"><?= e($row['searched_number'] ?? '') ?></code></td>
                                <td><?= formatDate($row['starttime']) ?></td>
                                <td><code><?= e($row['callere164']) ?></code></td>
                                <td><code><?= e($row['calleee164']) ?></code></td>
                                <td><?= gmdate('H:i:s', $row['callduration'] ?? 0) ?></td>
                                <td>
                                    <?php 
                                    $disposition = $row['disposition'] ?? '';
                                    $badgeClass = $disposition == 'ANSWERED' ? 'success' : 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $badgeClass ?>"><?= e($disposition) ?></span>
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
                    <p class="text-muted">Ninguno de los números del archivo tiene registros en las bases de datos.</p>
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
            if ($('#resultsTable').length && $('#resultsTable tbody tr').length > 0) {
                $('#resultsTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                    },
                    pageLength: 50,
                    order: [[2, 'desc']]
                });
            }
        });
        
        function exportResults() {
            var table = document.getElementById('resultsTable');
            var rows = table.querySelectorAll('tr');
            var csv = [];
            
            rows.forEach(function(row) {
                var cols = row.querySelectorAll('th, td');
                var rowData = [];
                cols.forEach(function(col) {
                    var text = col.innerText.replace(/"/g, '""');
                    rowData.push('"' + text + '"');
                });
                csv.push(rowData.join(','));
            });
            
            var csvContent = csv.join('\n');
            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'resultados_busqueda_' + new Date().toISOString().slice(0,10) + '.csv';
            link.click();
        }
    </script>
</body>
</html>
