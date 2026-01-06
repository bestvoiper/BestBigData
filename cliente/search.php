<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireClient();

$pageTitle = 'Buscar Número - Cliente';
$user = getUserById($_SESSION['user_id']);
$pdo = getMainConnection();

// Obtener configuración
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$costPerResult = floatval($settings['cost_per_result'] ?? 1);
$maxResults = intval($settings['max_results_per_search'] ?? 1000);

$results = [];
$searchPhone = '';
$error = '';
$searchPerformed = false;
$totalCost = 0;

// Procesar búsqueda
if (isset($_GET['phone']) && !empty($_GET['phone'])) {
    $searchPhone = sanitize($_GET['phone']);
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $searchPerformed = true;
    
    // Verificar saldo mínimo
    if ($user['balance'] < $costPerResult) {
        $error = 'Saldo insuficiente para realizar la búsqueda. Tu saldo actual es ' . formatMoney($user['balance']);
    } else {
        // Realizar búsqueda
        $results = searchPhoneNumber($searchPhone, $startDate, $endDate);
        
        // Limitar resultados
        $results = array_slice($results, 0, $maxResults);
        
        $resultsCount = count($results);
        $totalCost = $resultsCount * $costPerResult;
        
        // Verificar si hay suficiente saldo para todos los resultados
        if ($totalCost > $user['balance']) {
            // Calcular cuántos resultados podemos mostrar
            $affordableResults = floor($user['balance'] / $costPerResult);
            $results = array_slice($results, 0, $affordableResults);
            $resultsCount = count($results);
            $totalCost = $resultsCount * $costPerResult;
            $error = "Solo se muestran {$resultsCount} resultados debido a saldo insuficiente.";
        }
        
        if ($resultsCount > 0) {
            // Descontar saldo
            $balanceBefore = $user['balance'];
            if (updateUserBalance($user['id'], $totalCost, 'subtract')) {
                $balanceAfter = $balanceBefore - $totalCost;
                
                // Registrar transacción
                $stmt = $pdo->prepare("
                    INSERT INTO transactions (user_id, type, amount, description, search_query, results_count, balance_before, balance_after) 
                    VALUES (?, 'search', ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user['id'], 
                    $totalCost, 
                    "Búsqueda de número: {$searchPhone}", 
                    $searchPhone, 
                    $resultsCount,
                    $balanceBefore,
                    $balanceAfter
                ]);
                
                // Registrar búsqueda
                logSearch($user['id'], $searchPhone, $resultsCount, $totalCost);
                
                // Actualizar usuario en memoria
                $user['balance'] = $balanceAfter;
            } else {
                $error = 'Error al procesar el cobro. Intente nuevamente.';
                $results = [];
            }
        }
    }
}

include '../includes/header.php';
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <i class="bi bi-telephone-fill"></i>
        <h4>DetectNUM</h4>
        <small class="text-white-50">Panel Cliente</small>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link" href="dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link active" href="search.php">
            <i class="bi bi-search"></i> Buscar Número
        </a>
        <a class="nav-link" href="history.php">
            <i class="bi bi-clock-history"></i> Historial
        </a>
        <a class="nav-link" href="profile.php">
            <i class="bi bi-person"></i> Mi Perfil
        </a>
        <a class="nav-link text-danger" href="../logout.php">
            <i class="bi bi-box-arrow-left"></i> Cerrar Sesión
        </a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="top-bar">
        <div>
            <h4 class="mb-0">Buscar Número Telefónico</h4>
            <small class="text-muted">Consulta en todas las bases de datos CDR</small>
        </div>
        <div class="user-info">
            <div class="text-end">
                <span class="text-muted">Mi Saldo:</span><br>
                <span class="balance-display <?= $user['balance'] < ($settings['min_balance_alert'] ?? 10) ? 'text-danger' : '' ?>">
                    <?= formatMoney($user['balance']) ?>
                </span>
            </div>
        </div>
    </div>
    
    <?= showAlert() ?>
    
    <?php if ($error): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
    </div>
    <?php endif; ?>
    
    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label"><i class="bi bi-telephone"></i> Número de Teléfono</label>
                    <input type="text" name="phone" class="form-control form-control-lg" 
                           value="<?= sanitize($searchPhone) ?>"
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
                Resultados para: <code class="fs-5"><?= sanitize($searchPhone) ?></code>
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
                            <th>Origen (Caller)</th>
                            <th>Destino (Callee)</th>
                            <th>Duración</th>
                            <th>Estado</th>
                            <th>Coincidencia</th>
                            <th>Base de Datos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $index => $r): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= formatDate($r['call_date'], 'd/m/Y') ?></strong>
                                <br><small class="text-muted"><?= formatDate($r['call_date'], 'H:i:s') ?></small>
                            </td>
                            <td>
                                <code class="<?= $r['match_type'] === 'caller' ? 'text-success fw-bold' : '' ?>">
                                    <?= sanitize($r['caller']) ?>
                                </code>
                            </td>
                            <td>
                                <code class="<?= $r['match_type'] === 'callee' ? 'text-info fw-bold' : '' ?>">
                                    <?= sanitize($r['callee']) ?>
                                </code>
                            </td>
                            <td>
                                <?php
                                $duration = intval($r['duration']);
                                $minutes = floor($duration / 60);
                                $seconds = $duration % 60;
                                echo $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
                                ?>
                            </td>
                            <td>
                                <?php
                                $dispClass = match($r['disposition']) {
                                    'ANSWERED' => 'success',
                                    'NO ANSWER' => 'warning',
                                    'BUSY' => 'danger',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $dispClass ?>"><?= sanitize($r['disposition']) ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $r['match_type'] ?>">
                                    <?= $r['match_type'] === 'caller' ? 'Origen' : 'Destino' ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted"><?= sanitize($r['database']) ?></small>
                                <br><small class="text-muted"><?= sanitize($r['table']) ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Export button -->
            <div class="mt-3 text-end">
                <button onclick="exportToCSV()" class="btn btn-outline-primary">
                    <i class="bi bi-download"></i> Exportar CSV
                </button>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-search display-1 text-muted"></i>
                <h4 class="mt-3 text-muted">No se encontraron resultados</h4>
                <p class="text-muted">El número <?= sanitize($searchPhone) ?> no aparece en ningún registro.</p>
                <p><strong>No se realizó ningún cobro.</strong></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function exportToCSV() {
    const table = document.getElementById('resultsTable');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            let text = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + text + '"');
        }
        
        csv.push(row.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'resultados_<?= sanitize($searchPhone) ?>_<?= date('Ymd_His') ?>.csv';
    link.click();
}
</script>

<?php include '../includes/footer.php'; ?>
