<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireAdmin();

$pageTitle = 'Historial de Búsquedas - Admin';
$user = getUserById($_SESSION['user_id']);
$pdo = getMainConnection();

// Filtros
$filterUser = $_GET['user_id'] ?? '';
$filterPhone = $_GET['phone'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

// Construir query con filtros
$where = [];
$params = [];

if ($filterUser) {
    $where[] = "sh.user_id = ?";
    $params[] = $filterUser;
}
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

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("
    SELECT sh.*, u.name as user_name, u.email as user_email
    FROM search_history sh
    JOIN users u ON sh.user_id = u.id
    {$whereClause}
    ORDER BY sh.created_at DESC
    LIMIT 500
");
$stmt->execute($params);
$searches = $stmt->fetchAll();

// Obtener lista de usuarios para el filtro
$stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'cliente' ORDER BY name");
$clients = $stmt->fetchAll();

// Estadísticas
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_searches,
        SUM(results_found) as total_results,
        SUM(cost) as total_revenue
    FROM search_history
");
$stats = $stmt->fetch();

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
        <a class="nav-link active" href="searches.php">
            <i class="bi bi-search"></i> Búsquedas
        </a>
        <a class="nav-link" href="settings.php">
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
            <h4 class="mb-0">Historial de Búsquedas</h4>
            <small class="text-muted">Todas las consultas realizadas</small>
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
            <div class="stat-card success">
                <i class="bi bi-cash-stack fs-4"></i>
                <h3><?= formatMoney($stats['total_revenue'] ?? 0) ?></h3>
                <p>Ingresos Totales</p>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Usuario</label>
                    <select name="user_id" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filterUser == $c['id'] ? 'selected' : '' ?>>
                            <?= sanitize($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="phone" class="form-control" placeholder="Buscar..." value="<?= sanitize($filterPhone) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="date_from" class="form-control" value="<?= $filterDateFrom ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="date_to" class="form-control" value="<?= $filterDateTo ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-filter"></i> Filtrar
                    </button>
                    <a href="searches.php" class="btn btn-outline-secondary">
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
                            <th>Usuario</th>
                            <th>Teléfono Buscado</th>
                            <th>Resultados</th>
                            <th>Costo</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($searches as $s): ?>
                        <tr>
                            <td><?= $s['id'] ?></td>
                            <td>
                                <strong><?= sanitize($s['user_name']) ?></strong>
                                <br><small class="text-muted"><?= sanitize($s['user_email']) ?></small>
                            </td>
                            <td><code class="fs-6"><?= sanitize($s['phone_number']) ?></code></td>
                            <td>
                                <span class="badge bg-<?= $s['results_found'] > 0 ? 'success' : 'secondary' ?> fs-6">
                                    <?= $s['results_found'] ?>
                                </span>
                            </td>
                            <td class="fw-bold"><?= formatMoney($s['cost']) ?></td>
                            <td><?= formatDate($s['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
