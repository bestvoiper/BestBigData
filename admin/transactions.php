<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireAdmin();

$pageTitle = 'Transacciones - Admin';
$user = getUserById($_SESSION['user_id']);
$pdo = getMainConnection();

// Filtros
$filterUser = $_GET['user_id'] ?? '';
$filterType = $_GET['type'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

// Construir query con filtros
$where = [];
$params = [];

if ($filterUser) {
    $where[] = "t.user_id = ?";
    $params[] = $filterUser;
}
if ($filterType) {
    $where[] = "t.type = ?";
    $params[] = $filterType;
}
if ($filterDateFrom) {
    $where[] = "DATE(t.created_at) >= ?";
    $params[] = $filterDateFrom;
}
if ($filterDateTo) {
    $where[] = "DATE(t.created_at) <= ?";
    $params[] = $filterDateTo;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("
    SELECT t.*, u.name as user_name, u.email as user_email, 
           a.name as admin_name
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN users a ON t.created_by = a.id
    {$whereClause}
    ORDER BY t.created_at DESC
    LIMIT 500
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Obtener lista de usuarios para el filtro
$stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'cliente' ORDER BY name");
$clients = $stmt->fetchAll();

include '../includes/header.php';
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <i class="bi bi-telephone-fill"></i>
        <h4>BestBigData</h4>
        <small class="text-white-50">Panel Admin</small>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link" href="dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link" href="users.php">
            <i class="bi bi-people"></i> Usuarios
        </a>
        <a class="nav-link active" href="transactions.php">
            <i class="bi bi-currency-dollar"></i> Transacciones
        </a>
        <a class="nav-link" href="searches.php">
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
            <h4 class="mb-0">Transacciones</h4>
            <small class="text-muted">Historial de movimientos de saldo</small>
        </div>
    </div>
    
    <?= showAlert() ?>
    
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
                    <label class="form-label">Tipo</label>
                    <select name="type" class="form-select">
                        <option value="">Todos</option>
                        <option value="deposit" <?= $filterType === 'deposit' ? 'selected' : '' ?>>Depósito</option>
                        <option value="search" <?= $filterType === 'search' ? 'selected' : '' ?>>Búsqueda</option>
                        <option value="adjustment" <?= $filterType === 'adjustment' ? 'selected' : '' ?>>Ajuste</option>
                    </select>
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
                    <a href="transactions.php" class="btn btn-outline-secondary">
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
                            <th>Tipo</th>
                            <th>Monto</th>
                            <th>Descripción</th>
                            <th>Saldo Anterior</th>
                            <th>Saldo Posterior</th>
                            <th>Realizado por</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?= $t['id'] ?></td>
                            <td>
                                <strong><?= sanitize($t['user_name']) ?></strong>
                                <br><small class="text-muted"><?= sanitize($t['user_email']) ?></small>
                            </td>
                            <td>
                                <?php
                                $typeColors = [
                                    'deposit' => 'success',
                                    'search' => 'info',
                                    'withdrawal' => 'danger',
                                    'adjustment' => 'warning'
                                ];
                                $typeNames = [
                                    'deposit' => 'Depósito',
                                    'search' => 'Búsqueda',
                                    'withdrawal' => 'Retiro',
                                    'adjustment' => 'Ajuste'
                                ];
                                ?>
                                <span class="badge bg-<?= $typeColors[$t['type']] ?? 'secondary' ?>">
                                    <?= $typeNames[$t['type']] ?? $t['type'] ?>
                                </span>
                            </td>
                            <td class="fw-bold <?= $t['type'] === 'deposit' ? 'text-success' : 'text-danger' ?>">
                                <?= $t['type'] === 'deposit' ? '+' : '-' ?><?= formatMoney($t['amount']) ?>
                            </td>
                            <td><?= sanitize($t['description']) ?></td>
                            <td><?= formatMoney($t['balance_before']) ?></td>
                            <td><?= formatMoney($t['balance_after']) ?></td>
                            <td><?= $t['admin_name'] ? sanitize($t['admin_name']) : 'Sistema' ?></td>
                            <td><?= formatDate($t['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
