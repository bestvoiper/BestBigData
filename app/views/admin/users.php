<?php 
require_once APP_ROOT . '/app/views/helpers.php';
$activePage = 'users';
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
    <link href="<?= url('assets/css/admin.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include APP_ROOT . '/app/views/partials/admin-sidebar.php'; ?>
    
    <div class="main-content">
        <?php 
        $title = 'Gestión de Usuarios';
        $subtitle = 'Administrar clientes del sistema';
        include APP_ROOT . '/app/views/partials/topbar.php'; 
        ?>
        
        <?= showFlash($flash ?? null) ?>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people"></i> Lista de Usuarios</span>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="bi bi-plus-lg"></i> Nuevo Usuario
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Saldo</th>
                                <th>Estado</th>
                                <th>Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><?= $client['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-2" style="width:35px;height:35px;font-size:0.8rem;">
                                            <?= strtoupper(substr($client['name'], 0, 1)) ?>
                                        </div>
                                        <?= e($client['name']) ?>
                                    </div>
                                </td>
                                <td><?= e($client['email']) ?></td>
                                <td><strong><?= formatMoney($client['balance']) ?></strong></td>
                                <td>
                                    <span class="badge bg-<?= $client['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= $client['status'] === 'active' ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td><?= formatDate($client['created_at'], 'd/m/Y') ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-success" data-bs-toggle="modal" 
                                                data-bs-target="#rechargeModal"
                                                data-id="<?= $client['id'] ?>"
                                                data-name="<?= e($client['name']) ?>"
                                                data-balance="<?= $client['balance'] ?>">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                        <button class="btn btn-primary" data-bs-toggle="modal" 
                                                data-bs-target="#editUserModal"
                                                data-id="<?= $client['id'] ?>"
                                                data-name="<?= e($client['name']) ?>"
                                                data-email="<?= e($client['email']) ?>"
                                                data-status="<?= $client['status'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
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
    
    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="<?= url('admin/createUser') ?>" method="POST">
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
                        <div class="mb-3">
                            <label class="form-label">Saldo Inicial</label>
                            <input type="number" name="balance" class="form-control" value="0" min="0" step="0.01">
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
    
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editUserForm" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="editEmail" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nueva Contraseña (dejar vacío para no cambiar)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select name="status" id="editStatus" class="form-select">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                            </select>
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
    
    <!-- Recharge Modal -->
    <div class="modal fade" id="rechargeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="rechargeForm" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-cash-stack"></i> Recargar Saldo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Usuario: <strong id="rechargeName"></strong></p>
                        <p>Saldo Actual: <strong id="rechargeCurrentBalance"></strong></p>
                        <div class="mb-3">
                            <label class="form-label">Monto a Recargar</label>
                            <input type="number" name="amount" class="form-control" min="1" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <input type="text" name="description" class="form-control" value="Recarga de saldo">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Recargar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#usersTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                order: [[0, 'desc']]
            });
            
            // Edit User Modal
            $('#editUserModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var name = button.data('name');
                var email = button.data('email');
                var status = button.data('status');
                
                $('#editUserForm').attr('action', '<?= url('admin/updateUser/') ?>' + id);
                $('#editName').val(name);
                $('#editEmail').val(email);
                $('#editStatus').val(status);
            });
            
            // Recharge Modal
            $('#rechargeModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var name = button.data('name');
                var balance = button.data('balance');
                
                $('#rechargeForm').attr('action', '<?= url('admin/recharge/') ?>' + id);
                $('#rechargeName').text(name);
                $('#rechargeCurrentBalance').text('$' + parseFloat(balance).toFixed(2));
            });
        });
    </script>
</body>
</html>
