<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireClient();

$pageTitle = 'Dashboard - Cliente';
$user = getUserById($_SESSION['user_id']);
$pdo = getMainConnection();

// Obtener configuración
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Estadísticas del usuario
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM search_history WHERE user_id = ?");
$stmt->execute([$user['id']]);
$totalSearches = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT SUM(results_found) as total FROM search_history WHERE user_id = ?");
$stmt->execute([$user['id']]);
$totalResults = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare("SELECT SUM(cost) as total FROM search_history WHERE user_id = ?");
$stmt->execute([$user['id']]);
$totalSpent = $stmt->fetch()['total'] ?? 0;

// Últimas búsquedas
$stmt = $pdo->prepare("
    SELECT * FROM search_history 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$user['id']]);
$recentSearches = $stmt->fetchAll();

include '../includes/header.php';
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <i class="bi bi-telephone-fill"></i>
        <h4>BestBigData</h4>
        <small class="text-white-50">Panel Cliente</small>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link active" href="dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link" href="search.php">
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
            <h4 class="mb-0">Bienvenido, <?= sanitize($user['name']) ?></h4>
            <small class="text-muted">Panel de Control</small>
        </div>
        <div class="user-info">
            <div class="text-end">
                <span class="text-muted">Mi Saldo:</span><br>
                <span class="balance-display <?= $user['balance'] < ($settings['min_balance_alert'] ?? 10) ? 'text-danger' : '' ?>">
                    <?= formatMoney($user['balance']) ?>
                </span>
            </div>
            <div class="avatar">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
        </div>
    </div>
    
    <?= showAlert() ?>
    
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
                    <form action="search.php" method="GET">
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
                    <a href="history.php" class="btn btn-sm btn-outline-primary">Ver Todo</a>
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
                                    <td><code><?= sanitize($search['phone_number']) ?></code></td>
                                    <td><span class="badge bg-primary"><?= $search['results_found'] ?></span></td>
                                    <td><?= formatMoney($search['cost']) ?></td>
                                    <td><?= formatDate($search['created_at'], 'd/m H:i') ?></td>
                                    <td>
                                        <a href="search.php?phone=<?= urlencode($search['phone_number']) ?>" 
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

<?php include '../includes/footer.php'; ?>
