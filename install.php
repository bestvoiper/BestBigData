<?php
/**
 * Script de instalación - DetectNUM
 * Ejecutar una sola vez para crear las tablas y usuarios
 */

// Configuración de base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Conexión sin base de datos para crearla
        $pdo = new PDO("mysql:host={$host};charset={$charset}", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Crear base de datos
        $pdo->exec("CREATE DATABASE IF NOT EXISTS detectnum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE detectnum");
        $messages[] = "✓ Base de datos 'detectnum' creada/verificada";
        
        // Crear tabla users
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'cliente') DEFAULT 'cliente',
            balance DECIMAL(10,2) DEFAULT 0.00,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_status (status)
        ) ENGINE=InnoDB");
        $messages[] = "✓ Tabla 'users' creada";
        
        // Crear tabla transactions
        $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('deposit', 'withdrawal', 'search', 'adjustment') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            description TEXT,
            search_query VARCHAR(50) NULL,
            results_count INT DEFAULT 0,
            balance_before DECIMAL(10,2) DEFAULT 0,
            balance_after DECIMAL(10,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_by INT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_type (type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB");
        $messages[] = "✓ Tabla 'transactions' creada";
        
        // Crear tabla search_history
        $pdo->exec("CREATE TABLE IF NOT EXISTS search_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            phone_number VARCHAR(50) NOT NULL,
            results_found INT DEFAULT 0,
            cost DECIMAL(10,2) DEFAULT 0,
            search_params JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_phone (phone_number),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB");
        $messages[] = "✓ Tabla 'search_history' creada";
        
        // Crear tabla settings
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        $messages[] = "✓ Tabla 'settings' creada";
        
        // Insertar configuraciones
        $pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES
            ('cost_per_result', '1', 'Costo en pesos por cada resultado encontrado'),
            ('min_balance_alert', '10', 'Saldo mínimo para mostrar alerta'),
            ('max_results_per_search', '1000', 'Máximo de resultados por búsqueda'),
            ('search_date_range_days', '365', 'Rango máximo de días para búsqueda')");
        $messages[] = "✓ Configuraciones insertadas";
        
        // Crear usuarios con contraseñas hasheadas correctamente
        $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $clientePassword = password_hash('cliente123', PASSWORD_BCRYPT);
        
        // Eliminar usuarios existentes si los hay
        $pdo->exec("DELETE FROM users WHERE email IN ('admin@detectnum.com', 'cliente@detectnum.com')");
        
        // Insertar admin
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, balance, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['Administrador', 'admin@detectnum.com', $adminPassword, 'admin', 999999.00, 'active']);
        $messages[] = "✓ Usuario Admin creado (admin@detectnum.com / admin123)";
        
        // Insertar cliente
        $stmt->execute(['Cliente Demo', 'cliente@detectnum.com', $clientePassword, 'cliente', 100.00, 'active']);
        $messages[] = "✓ Usuario Cliente creado (cliente@detectnum.com / cliente123)";
        
        $messages[] = "";
        $messages[] = "🎉 ¡Instalación completada exitosamente!";
        
    } catch (PDOException $e) {
        $errors[] = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - DetectNUM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .install-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        .logo { color: #0f3460; }
        .logo i { color: #e94560; }
    </style>
</head>
<body>
    <div class="install-card">
        <div class="text-center mb-4">
            <h1 class="logo"><i class="bi bi-telephone-fill"></i> DetectNUM</h1>
            <p class="text-muted">Instalación del Sistema</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p class="mb-0"><?= $error ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($messages)): ?>
            <div class="alert alert-success">
                <?php foreach ($messages as $msg): ?>
                    <p class="mb-1"><?= $msg ?></p>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="login.php" class="btn btn-primary btn-lg">Ir al Login</a>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <h5>Este script creará:</h5>
                <ul class="mb-0">
                    <li>Base de datos <code>detectnum</code></li>
                    <li>Tablas: users, transactions, search_history, settings</li>
                    <li>Usuario Admin: <code>admin@detectnum.com</code> / <code>admin123</code></li>
                    <li>Usuario Cliente: <code>cliente@detectnum.com</code> / <code>cliente123</code></li>
                </ul>
            </div>
            
            <form method="POST">
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Instalar Sistema
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html>
