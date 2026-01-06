<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireClient();

$pageTitle = 'Historial de Búsquedas - Cliente';
$user = getUserById($_SESSION['user_id']);
$pdo = getMainConnection();

// Filtros
$filterPhone = $_GET['phone'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

// Construir query con filtros
$where = ["sh.user_id = ?"];
$params = [$user['id']];

if ($filterPhone) {
    $where[] = "sh.phone_number LIKE ?";
    $params[] = "%{$filterPhone}%";
}
if ($filterDateFrom) {
    $where[] = "DATE(sh.created_at) >= ?";
    $params[] = $filterDateFrom;
}
if ($filterDateTo) {
    $where[] = "DATE(sh.created_at) <= ?";
    $params[] = $filterDateTo;
}

$whereClause = "WHERE " . implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT * FROM search_history sh
    {$whereClause}
    ORDER BY sh.created_at DESC
    LIMIT 500
");
$stmt->execute($params);
$searches = $stmt->fetchAll();

// Estadísticas
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_searches,
        SUM(results_found) as total_results,
        SUM(cost) as total_spent
    FROM search_history
    WHERE user_id = ?
");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();

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
        <a class="nav-link" href="search.php">
            <i class="bi bi-search"></i> Buscar Número
        </a>
        <a class="nav-link active" href="history.php">
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
            <h4 class="mb-0">Historial de Búsquedas</h4>
            <small class="text-muted">Todas tus consultas realizadas</small>
        </div>
        <div class="user-info">
            <div class="text-end">
                <span class="text-muted">Mi Saldo:</span><br>
                <span class="balance-display"><?= formatMoney($user['balance']) ?></span>
            </div>
        </div>
    </div>
    
    <?= showAlert() ?>
    
    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <i class="bi bi-search fs-4"></i>
                <h3><?= number_format($stats['total_searches']) ?></h3>
                <p>Total Búsquedas</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card accent">
                <i class="bi bi-telephone fs-4"></i>
                <h3><?= number_format($stats['total_results'] ?? 0) ?></h3>
                <p>Resultados Encontrados</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card warning">
                <i class="bi bi-cash-stack fs-4"></i>
                <h3><?= formatMoney($stats['total_spent'] ?? 0) ?></h3>
                <p>Total Gastado</p>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="phone" class="form-control" placeholder="Buscar..." value="<?= sanitize($filterPhone) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Desde</label>
                    <input type="date" name="date_from" class="form-control" value="<?= $filterDateFrom ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="date_to" class="form-control" value="<?= $filterDateTo ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-filter"></i> Filtrar
                    </button>
                    <a href="history.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Teléfono Buscado</th>
                            <th>Resultados</th>
                            <th>Costo</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($searches as $s): ?>
                        <tr>
                            <td><?= $s['id'] ?></td>
                            <td><code class="fs-6"><?= sanitize($s['phone_number']) ?></code></td>
                            <td>
                                <span class="badge bg-<?= $s['results_found'] > 0 ? 'success' : 'secondary' ?> fs-6">
                                    <?= $s['results_found'] ?>
                                </span>
                            </td>
                            <td class="fw-bold"><?= formatMoney($s['cost']) ?></td>
                            <td><?= formatDate($s['created_at']) ?></td>
                            <td>
                                <a href="search.php?phone=<?= urlencode($s['phone_number']) ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Buscar de nuevo">
                                    <i class="bi bi-arrow-repeat"></i> Repetir
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($searches)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-clock-history fs-1 d-block mb-2"></i>
                                No hay búsquedas en tu historial
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
