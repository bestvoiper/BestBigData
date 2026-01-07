-- =====================================================
-- Base de datos principal BestBigData
-- =====================================================

CREATE DATABASE IF NOT EXISTS bestbigdata CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bestbigdata;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
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
) ENGINE=InnoDB;

-- Tabla de transacciones (cargas de saldo y consumos)
CREATE TABLE IF NOT EXISTS transactions (
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
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Historial de búsquedas
CREATE TABLE IF NOT EXISTS search_history (
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
) ENGINE=InnoDB;

-- Detalle de resultados de búsqueda
CREATE TABLE IF NOT EXISTS search_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    search_id INT NOT NULL,
    database_name VARCHAR(100),
    table_name VARCHAR(100),
    caller_number VARCHAR(50),
    callee_number VARCHAR(50),
    call_date DATETIME,
    duration INT DEFAULT 0,
    disposition VARCHAR(50),
    match_type ENUM('caller', 'callee') DEFAULT 'caller',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (search_id) REFERENCES search_history(id) ON DELETE CASCADE,
    INDEX idx_search_id (search_id)
) ENGINE=InnoDB;

-- Configuración del sistema
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insertar configuraciones por defecto
INSERT INTO settings (setting_key, setting_value, description) VALUES
('cost_per_result', '1', 'Costo en pesos por cada resultado encontrado'),
('min_balance_alert', '10', 'Saldo mínimo para mostrar alerta'),
('max_results_per_search', '1000', 'Máximo de resultados por búsqueda'),
('search_date_range_days', '365', 'Rango máximo de días para búsqueda');

-- Crear usuario administrador por defecto
-- Password: admin123 (cambiar en producción)
INSERT INTO users (name, email, password, role, balance, status) VALUES
('Administrador', 'admin@bestbigdata.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 999999.00, 'active');

-- Usuario de prueba cliente
-- Password: cliente123
INSERT INTO users (name, email, password, role, balance, status) VALUES
('Cliente Demo', 'cliente@bestbigdata.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'cliente', 100.00, 'active');


-- =====================================================
-- Ejemplo de estructura para bases de datos CDR
-- (Crear estas tablas en cada una de las 4 bases de datos CDR)
-- =====================================================

-- CREATE DATABASE IF NOT EXISTS cdr_database_1;
-- USE cdr_database_1;

-- Ejemplo de tabla CDR para un día específico
-- CREATE TABLE IF NOT EXISTS e_cdr_20250121 (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     calldate DATETIME NOT NULL,
--     callere164 VARCHAR(50),
--     calleee164 VARCHAR(50),
--     duration INT DEFAULT 0,
--     billsec INT DEFAULT 0,
--     disposition VARCHAR(50),
--     uniqueid VARCHAR(100),
--     INDEX idx_caller (callere164),
--     INDEX idx_callee (calleee164),
--     INDEX idx_calldate (calldate)
-- ) ENGINE=InnoDB;
