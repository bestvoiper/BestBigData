<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireClient();

$pageTitle = 'Mi Perfil - Cliente';
$user = getUserById($_SESSION['user_id']);
$pdo = getMainConnection();

$error = '';
$success = '';

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        
        if (empty($name) || empty($email)) {
            $error = 'Nombre y email son obligatorios.';
        } else {
            // Verificar si el email ya existe (excepto el actual)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            
            if ($stmt->fetch()) {
                $error = 'El email ya está en uso por otro usuario.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                if ($stmt->execute([$name, $email, $user['id']])) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $success = 'Perfil actualizado correctamente.';
                    $user = getUserById($user['id']); // Recargar datos
                } else {
                    $error = 'Error al actualizar el perfil.';
                }
            }
        }
    }
    
    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'Todos los campos de contraseña son obligatorios.';
        } elseif (!verifyPassword($currentPassword, $user['password'])) {
            $error = 'La contraseña actual es incorrecta.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([hashPassword($newPassword), $user['id']])) {
                $success = 'Contraseña actualizada correctamente.';
            } else {
                $error = 'Error al cambiar la contraseña.';
            }
        }
    }
}

// Obtener transacciones recientes
$stmt = $pdo->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll();

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
        <a class="nav-link" href="dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link" href="search.php">
            <i class="bi bi-search"></i> Buscar Número
        </a>
        <a class="nav-link" href="history.php">
            <i class="bi bi-clock-history"></i> Historial
        </a>
        <a class="nav-link active" href="profile.php">
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
            <h4 class="mb-0">Mi Perfil</h4>
            <small class="text-muted">Administra tu cuenta</small>
        </div>
        <div class="user-info">
            <div class="text-end">
                <span class="text-muted">Mi Saldo:</span><br>
                <span class="balance-display"><?= formatMoney($user['balance']) ?></span>
            </div>
            <div class="avatar" style="width:60px;height:60px;font-size:1.5rem;">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
        </div>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= $success ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Profile Info -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-person-circle"></i> Información Personal
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?= sanitize($user['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= sanitize($user['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Miembro desde</label>
                            <input type="text" class="form-control" 
                                   value="<?= formatDate($user['created_at'], 'd/m/Y') ?>" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Guardar Cambios
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-key"></i> Cambiar Contraseña
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3">
                            <label class="form-label">Contraseña Actual</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nueva Contraseña</label>
                            <input type="password" name="new_password" class="form-control" 
                                   minlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" name="confirm_password" class="form-control" 
                                   minlength="6" required>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key"></i> Cambiar Contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Account Summary & Transactions -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-wallet2"></i> Resumen de Cuenta
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h2 class="text-primary"><?= formatMoney($user['balance']) ?></h2>
                            <p class="text-muted mb-0">Saldo Actual</p>
                        </div>
                        <div class="col-6">
                            <h2 class="text-success">
                                <?php
                                $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'deposit'");
                                $stmt->execute([$user['id']]);
                                echo formatMoney($stmt->fetch()['total'] ?? 0);
                                ?>
                            </h2>
                            <p class="text-muted mb-0">Total Recargado</p>
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted text-center mb-0">
                        <i class="bi bi-info-circle"></i> 
                        Para recargar saldo, contacta al administrador.
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Últimos Movimientos
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($transactions as $t): ?>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <span class="badge bg-<?= $t['type'] === 'deposit' ? 'success' : 'info' ?>">
                                <?= $t['type'] === 'deposit' ? 'Depósito' : 'Búsqueda' ?>
                            </span>
                            <br>
                            <small class="text-muted"><?= formatDate($t['created_at'], 'd/m/Y H:i') ?></small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold <?= $t['type'] === 'deposit' ? 'text-success' : 'text-danger' ?>">
                                <?= $t['type'] === 'deposit' ? '+' : '-' ?><?= formatMoney($t['amount']) ?>
                            </span>
                            <br>
                            <small class="text-muted">Saldo: <?= formatMoney($t['balance_after']) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($transactions)): ?>
                    <p class="text-muted text-center py-3">No hay movimientos</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
