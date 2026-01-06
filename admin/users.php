<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireAdmin();

$pageTitle = 'Gestión de Usuarios - Admin';
$user = getUserById($_SESSION['user_id']);
$pdo = getMainConnection();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Crear usuario
    if ($action === 'create') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'cliente';
        $balance = floatval($_POST['balance'] ?? 0);
        
        if (empty($name) || empty($email) || empty($password)) {
            setAlert('error', 'Todos los campos son obligatorios.');
        } else {
            // Verificar si el email ya existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                setAlert('error', 'El email ya está registrado.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, balance, status) VALUES (?, ?, ?, ?, ?, 'active')");
                if ($stmt->execute([$name, $email, hashPassword($password), $role, $balance])) {
                    setAlert('success', 'Usuario creado exitosamente.');
                } else {
                    setAlert('error', 'Error al crear el usuario.');
                }
            }
        }
    }
    
    // Actualizar usuario
    if ($action === 'update') {
        $userId = intval($_POST['user_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'cliente';
        $status = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';
        
        if ($password) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $status, hashPassword($password), $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $status, $userId]);
        }
        setAlert('success', 'Usuario actualizado.');
    }
    
    // Cargar saldo
    if ($action === 'add_balance') {
        $userId = intval($_POST['user_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $description = sanitize($_POST['description'] ?? 'Carga de saldo');
        
        if ($amount > 0) {
            $targetUser = getUserById($userId);
            $balanceBefore = $targetUser['balance'];
            
            updateUserBalance($userId, $amount, 'add');
            
            $balanceAfter = $balanceBefore + $amount;
            
            // Registrar transacción
            $stmt = $pdo->prepare("
                INSERT INTO transactions (user_id, type, amount, description, balance_before, balance_after, created_by) 
                VALUES (?, 'deposit', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $amount, $description, $balanceBefore, $balanceAfter, $_SESSION['user_id']]);
            
            setAlert('success', 'Saldo cargado exitosamente.');
        } else {
            setAlert('error', 'El monto debe ser mayor a 0.');
        }
    }
    
    // Eliminar usuario
    if ($action === 'delete') {
        $userId = intval($_POST['user_id'] ?? 0);
        if ($userId != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            setAlert('success', 'Usuario eliminado.');
        } else {
            setAlert('error', 'No puede eliminarse a sí mismo.');
        }
    }
    
    header('Location: users.php');
    exit;
}

// Obtener usuarios
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

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
        <a class="nav-link active" href="users.php">
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
            <h4 class="mb-0">Gestión de Usuarios</h4>
            <small class="text-muted">Administrar usuarios del sistema</small>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="bi bi-plus-lg"></i> Nuevo Usuario
        </button>
    </div>
    
    <?= showAlert() ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Saldo</th>
                            <th>Estado</th>
                            <th>Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-2" style="width:35px;height:35px;font-size:0.8rem;">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <?= sanitize($u['name']) ?>
                                </div>
                            </td>
                            <td><?= sanitize($u['email']) ?></td>
                            <td>
                                <span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'info' ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td class="fw-bold"><?= formatMoney($u['balance']) ?></td>
                            <td>
                                <span class="badge bg-<?= $u['status'] === 'active' ? 'success' : ($u['status'] === 'suspended' ? 'warning' : 'secondary') ?>">
                                    <?= ucfirst($u['status']) ?>
                                </span>
                            </td>
                            <td><?= formatDate($u['created_at'], 'd/m/Y') ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-success" 
                                            onclick="openBalanceModal(<?= $u['id'] ?>, '<?= sanitize($u['name']) ?>', <?= $u['balance'] ?>)"
                                            title="Cargar Saldo">
                                        <i class="bi bi-cash-coin"></i>
                                    </button>
                                    <button class="btn btn-outline-primary" 
                                            onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)"
                                            title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-outline-danger" 
                                            onclick="confirmDelete(<?= $u['id'] ?>, '<?= sanitize($u['name']) ?>')"
                                            title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rol</label>
                            <select name="role" class="form-select">
                                <option value="cliente">Cliente</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Saldo Inicial</label>
                            <input type="number" name="balance" class="form-control" value="0" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña (dejar vacío para mantener)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rol</label>
                            <select name="role" id="edit_role" class="form-select">
                                <option value="cliente">Cliente</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                                <option value="suspended">Suspendido</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cargar Saldo -->
<div class="modal fade" id="balanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_balance">
                <input type="hidden" name="user_id" id="balance_user_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Cargar Saldo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Usuario:</strong> <span id="balance_user_name"></span><br>
                        <strong>Saldo Actual:</strong> <span id="balance_current"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto a Cargar</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="amount" class="form-control" required step="0.01" min="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <input type="text" name="description" class="form-control" value="Carga de saldo">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Cargar Saldo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="delete_user_id">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar al usuario <strong id="delete_user_name"></strong>?</p>
                    <p class="text-muted">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_status').value = user.status;
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function openBalanceModal(userId, userName, currentBalance) {
    document.getElementById('balance_user_id').value = userId;
    document.getElementById('balance_user_name').textContent = userName;
    document.getElementById('balance_current').textContent = '$' + parseFloat(currentBalance).toFixed(2);
    new bootstrap.Modal(document.getElementById('balanceModal')).show();
}

function confirmDelete(userId, userName) {
    document.getElementById('delete_user_id').value = userId;
    document.getElementById('delete_user_name').textContent = userName;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
