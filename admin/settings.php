<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireAdmin();

$pageTitle = 'Configuración - Admin';
$user = getUserById($_SESSION['user_id']);
$pdo = getMainConnection();

// Procesar actualización de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $costPerResult = floatval($_POST['cost_per_result'] ?? 1);
    $minBalanceAlert = floatval($_POST['min_balance_alert'] ?? 10);
    $maxResults = intval($_POST['max_results_per_search'] ?? 1000);
    $dateRange = intval($_POST['search_date_range_days'] ?? 365);
    
    $settings = [
        'cost_per_result' => $costPerResult,
        'min_balance_alert' => $minBalanceAlert,
        'max_results_per_search' => $maxResults,
        'search_date_range_days' => $dateRange
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    
    setAlert('success', 'Configuración actualizada correctamente.');
    header('Location: settings.php');
    exit;
}

// Obtener configuración actual
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include '../includes/header.php';
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <i class="bi bi-telephone-fill"></i>
        <h4>DetectNUM</h4>
        <small class="text-white-50">Panel Admin</small>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link" href="dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link" href="users.php">
            <i class="bi bi-people"></i> Usuarios
        </a>
        <a class="nav-link" href="transactions.php">
            <i class="bi bi-currency-dollar"></i> Transacciones
        </a>
        <a class="nav-link" href="searches.php">
            <i class="bi bi-search"></i> Búsquedas
        </a>
        <a class="nav-link active" href="settings.php">
            <i class="bi bi-gear"></i> Configuración
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
            <h4 class="mb-0">Configuración del Sistema</h4>
            <small class="text-muted">Ajustes generales</small>
        </div>
    </div>
    
    <?= showAlert() ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-sliders"></i> Parámetros de Búsqueda
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-cash-coin text-success"></i>
                                    Costo por Resultado (Pesos)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="cost_per_result" class="form-control" 
                                           value="<?= $settings['cost_per_result'] ?? 1 ?>" 
                                           step="0.01" min="0" required>
                                </div>
                                <small class="text-muted">Monto a descontar por cada registro encontrado</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-exclamation-triangle text-warning"></i>
                                    Alerta de Saldo Mínimo
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="min_balance_alert" class="form-control" 
                                           value="<?= $settings['min_balance_alert'] ?? 10 ?>" 
                                           step="0.01" min="0" required>
                                </div>
                                <small class="text-muted">Mostrar alerta cuando el saldo sea menor a este valor</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-list-ol text-info"></i>
                                    Máximo de Resultados por Búsqueda
                                </label>
                                <input type="number" name="max_results_per_search" class="form-control" 
                                       value="<?= $settings['max_results_per_search'] ?? 1000 ?>" 
                                       min="1" max="10000" required>
                                <small class="text-muted">Límite de registros retornados por consulta</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="bi bi-calendar-range text-primary"></i>
                                    Rango Máximo de Búsqueda (Días)
                                </label>
                                <input type="number" name="search_date_range_days" class="form-control" 
                                       value="<?= $settings['search_date_range_days'] ?? 365 ?>" 
                                       min="1" max="3650" required>
                                <small class="text-muted">Máximo de días hacia atrás para buscar</small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Guardar Configuración
                        </button>
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
                    <p class="text-muted">Conexiones configuradas:</p>
                    <?php
                    global $cdr_databases;
                    require_once '../config/database.php';
                    foreach ($cdr_databases as $key => $config):
                    ?>
                    <div class="d-flex align-items-center mb-2 p-2 bg-light rounded">
                        <i class="bi bi-database-fill text-primary me-2"></i>
                        <div class="flex-grow-1">
                            <strong><?= $config['name'] ?></strong>
                            <br><small class="text-muted"><?= $config['host'] ?></small>
                        </div>
                        <span class="badge bg-success">Activa</span>
                    </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> 
                        Edite el archivo <code>config/database.php</code> para modificar las conexiones CDR.
                    </small>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Información del Sistema
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>PHP Version</td>
                            <td><code><?= phpversion() ?></code></td>
                        </tr>
                        <tr>
                            <td>Servidor</td>
                            <td><code><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></code></td>
                        </tr>
                        <tr>
                            <td>Fecha Servidor</td>
                            <td><code><?= date('Y-m-d H:i:s') ?></code></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
