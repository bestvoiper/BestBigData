<?php 
require_once APP_ROOT . '/app/views/helpers.php';
$activePage = 'login';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - DetectNUM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/auth.css') ?>" rel="stylesheet">
</head>
<body>
    <div class="login-card">
        <div class="logo">
            <i class="bi bi-telephone-fill"></i>
            <h1>DetectNUM</h1>
            <p class="text-muted">Sistema de Consulta Telefónica</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle"></i> <?= e($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= url('auth/login') ?>">
            <div class="mb-3">
                <label class="form-label">Correo Electrónico</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" 
                           placeholder="correo@ejemplo.com" 
                           value="<?= e($email ?? '') ?>" required>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" 
                           placeholder="••••••••" required>
                </div>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-login btn-lg text-white">
                    <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                </button>
            </div>
        </form>
        
        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="bi bi-shield-lock"></i> Conexión segura
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
