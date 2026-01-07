<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BestBigData - Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 420px;
            width: 100%;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #0f3460;
            font-weight: bold;
            font-size: 2.5rem;
        }
        .logo i {
            font-size: 3rem;
            color: #e94560;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
        }
        .form-control:focus {
            border-color: #0f3460;
            box-shadow: 0 0 0 0.2rem rgba(15, 52, 96, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #e94560 0%, #0f3460 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: bold;
            transition: transform 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(233, 69, 96, 0.4);
        }
        .input-group-text {
            border-radius: 10px 0 0 10px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-right: none;
        }
        .input-group .form-control {
            border-radius: 0 10px 10px 0;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">
            <i class="bi bi-telephone-fill"></i>
            <h1>BestBigData</h1>
            <p class="text-muted">Sistema de Consulta Telefónica</p>
        </div>
        
        <?php
        require_once 'config/session.php';
        require_once 'includes/functions.php';
        
        // Si ya está logueado, redirigir
        if (isLoggedIn()) {
            if (isAdmin()) {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: cliente/dashboard.php');
            }
            exit;
        }
        
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $error = 'Por favor complete todos los campos.';
            } else {
                $user = getUserByEmail($email);
                
                if ($user && verifyPassword($password, $user['password'])) {
                    if ($user['status'] !== 'active') {
                        $error = 'Su cuenta está ' . ($user['status'] === 'suspended' ? 'suspendida' : 'inactiva') . '.';
                    } else {
                        // Crear sesión
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['last_activity'] = time();
                        
                        // Actualizar último login
                        $pdo = getMainConnection();
                        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        $stmt->execute([$user['id']]);
                        
                        // Redirigir según el rol
                        if ($user['role'] === 'admin') {
                            header('Location: admin/dashboard.php');
                        } else {
                            header('Location: cliente/dashboard.php');
                        }
                        exit;
                    }
                } else {
                    $error = 'Credenciales incorrectas.';
                }
            }
        }
        
        if (isset($_GET['expired'])) {
            $error = 'Su sesión ha expirado. Por favor inicie sesión nuevamente.';
        }
        
        if (isset($_GET['logout'])) {
            echo '<div class="alert alert-success">Ha cerrado sesión correctamente.</div>';
        }
        ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Correo Electrónico</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com" required>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-login w-100">
                <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
            </button>
        </form>
        
        <div class="text-center mt-4">
            <small class="text-muted">
                BestBigData &copy; <?= date('Y') ?> - Todos los derechos reservados
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
