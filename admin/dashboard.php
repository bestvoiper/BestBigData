<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireAdmin();

$pageTitle = 'Dashboard - Admin';
$user = getUserById($_SESSION['user_id']);

// Obtener estadísticas
$pdo = getMainConnection();

// Total de usuarios
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'cliente'");
$totalClients = $stmt->fetch()['total'];

// Usuarios activos
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'cliente' AND status = 'active'");
$activeClients = $stmt->fetch()['total'];

// Total de búsquedas hoy
$stmt = $pdo->query("SELECT COUNT(*) as total FROM search_history WHERE DATE(created_at) = CURDATE()");
$searchesToday = $stmt->fetch()['total'];

// Ingresos hoy
$stmt = $pdo->query("SELECT COALESCE(SUM(cost), 0) as total FROM search_history WHERE DATE(created_at) = CURDATE()");
$revenueToday = $stmt->fetch()['total'];

// Últimas búsquedas
$stmt = $pdo->query("
    SELECT sh.*, u.name as user_name 
    FROM search_history sh 
    JOIN users u ON sh.user_id = u.id 
    ORDER BY sh.created_at DESC 
    LIMIT 10
");
$recentSearches = $stmt->fetchAll();

// Últimos usuarios registrados
$stmt = $pdo->query("
    SELECT * FROM users 
    WHERE role = 'cliente' 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recentUsers = $stmt->fetchAll();

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
        <a class="nav-link active" href="dashboard.php">
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
            <h4 class="mb-0">Dashboard</h4>
            <small class="text-muted">Bienvenido, <?= sanitize($user['name']) ?></small>
        </div>
        <div class="user-info">
            <div>
                <strong><?= sanitize($user['name']) ?></strong>
                <br><small class="text-muted">Administrador</small>
            </div>
            <div class="avatar">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
        </div>
    </div>
    
    <?= showAlert() ?>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <i class="bi bi-people fs-3"></i>
                <h3><?= $totalClients ?></h3>
                <p>Total Clientes</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <i class="bi bi-person-check fs-3"></i>
                <h3><?= $activeClients ?></h3>
                <p>Clientes Activos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card accent">
                <i class="bi bi-search fs-3"></i>
                <h3><?= $searchesToday ?></h3>
                <p>Búsquedas Hoy</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <i class="bi bi-cash-stack fs-3"></i>
                <h3><?= formatMoney($revenueToday) ?></h3>
                <p>Ingresos Hoy</p>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Searches -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-search"></i> Búsquedas Recientes</span>
                    <a href="searches.php" class="btn btn-sm btn-outline-primary">Ver Todas</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Teléfono</th>
                                    <th>Resultados</th>
                                    <th>Costo</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSearches as $search): ?>
                                <tr>
                                    <td><?= sanitize($search['user_name']) ?></td>
                                    <td><code><?= sanitize($search['phone_number']) ?></code></td>
                                    <td><span class="badge bg-primary"><?= $search['results_found'] ?></span></td>
                                    <td><?= formatMoney($search['cost']) ?></td>
                                    <td><?= formatDate($search['created_at']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentSearches)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No hay búsquedas recientes</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Users -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people"></i> Nuevos Usuarios</span>
                    <a href="users.php" class="btn btn-sm btn-outline-primary">Ver Todos</a>
                </div>
                <div class="card-body">
                    <?php foreach ($recentUsers as $client): ?>
                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                        <div class="avatar me-3" style="width:40px;height:40px;font-size:0.9rem;">
                            <?= strtoupper(substr($client['name'], 0, 1)) ?>
                        </div>
                        <div class="flex-grow-1">
                            <strong><?= sanitize($client['name']) ?></strong>
                            <br><small class="text-muted"><?= formatMoney($client['balance']) ?></small>
                        </div>
                        <span class="badge bg-<?= $client['status'] === 'active' ? 'success' : 'secondary' ?>">
                            <?= $client['status'] ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($recentUsers)): ?>
                    <p class="text-muted text-center">No hay usuarios recientes</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
