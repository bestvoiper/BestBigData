-- =====================================================
-- Script para crear datos de prueba en bases de datos CDR
-- Ejecutar después de crear las bases de datos CDR
-- =====================================================

-- Base de datos CDR 1
CREATE DATABASE IF NOT EXISTS cdr_database_1 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cdr_database_1;

-- Crear tablas de ejemplo para diferentes días
CREATE TABLE IF NOT EXISTS e_cdr_20250101 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calldate DATETIME NOT NULL,
    callere164 VARCHAR(50),
    calleee164 VARCHAR(50),
    duration INT DEFAULT 0,
    billsec INT DEFAULT 0,
    disposition VARCHAR(50) DEFAULT 'ANSWERED',
    uniqueid VARCHAR(100),
    INDEX idx_caller (callere164),
    INDEX idx_callee (calleee164),
    INDEX idx_calldate (calldate)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS e_cdr_20250102 LIKE e_cdr_20250101;
CREATE TABLE IF NOT EXISTS e_cdr_20250103 LIKE e_cdr_20250101;

-- Insertar datos de prueba
INSERT INTO e_cdr_20250101 (calldate, callere164, calleee164, duration, disposition) VALUES
('2025-01-01 08:30:00', '5551234567', '5559876543', 120, 'ANSWERED'),
('2025-01-01 09:15:00', '5559876543', '5551112222', 60, 'ANSWERED'),
('2025-01-01 10:00:00', '5553334444', '5551234567', 180, 'ANSWERED'),
('2025-01-01 14:30:00', '5551234567', '5555556666', 45, 'ANSWERED');

INSERT INTO e_cdr_20250102 (calldate, callere164, calleee164, duration, disposition) VALUES
('2025-01-02 07:00:00', '5551234567', '5557778888', 90, 'ANSWERED'),
('2025-01-02 11:30:00', '5559999000', '5551234567', 200, 'ANSWERED'),
('2025-01-02 16:45:00', '5551234567', '5551112222', 30, 'NO ANSWER');

INSERT INTO e_cdr_20250103 (calldate, callere164, calleee164, duration, disposition) VALUES
('2025-01-03 09:00:00', '5551234567', '5552223333', 150, 'ANSWERED'),
('2025-01-03 13:00:00', '5554445555', '5551234567', 75, 'ANSWERED');

-- Base de datos CDR 2
CREATE DATABASE IF NOT EXISTS cdr_database_2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cdr_database_2;

CREATE TABLE IF NOT EXISTS e_cdr_20250101 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calldate DATETIME NOT NULL,
    callere164 VARCHAR(50),
    calleee164 VARCHAR(50),
    duration INT DEFAULT 0,
    billsec INT DEFAULT 0,
    disposition VARCHAR(50) DEFAULT 'ANSWERED',
    uniqueid VARCHAR(100),
    INDEX idx_caller (callere164),
    INDEX idx_callee (calleee164),
    INDEX idx_calldate (calldate)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS e_cdr_20250102 LIKE e_cdr_20250101;
CREATE TABLE IF NOT EXISTS e_cdr_20250104 LIKE e_cdr_20250101;

INSERT INTO e_cdr_20250101 (calldate, callere164, calleee164, duration, disposition) VALUES
('2025-01-01 12:00:00', '5551234567', '5556667777', 100, 'ANSWERED'),
('2025-01-01 18:30:00', '5558889999', '5551234567', 250, 'ANSWERED');

INSERT INTO e_cdr_20250102 (calldate, callere164, calleee164, duration, disposition) VALUES
('2025-01-02 08:15:00', '5551234567', '5550001111', 80, 'ANSWERED');

INSERT INTO e_cdr_20250104 (calldate, callere164, calleee164, duration, disposition) VALUES
('2025-01-04 10:30:00', '5551234567', '5552223344', 190, 'ANSWERED'),
('2025-01-04 15:00:00', '5553334455', '5551234567', 110, 'ANSWERED');

-- Base de datos CDR 3
CREATE DATABASE IF NOT EXISTS cdr_database_3 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cdr_database_3;

CREATE TABLE IF NOT EXISTS e_cdr_20250101 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calldate DATETIME NOT NULL,
    callere164 VARCHAR(50),
    calleee164 VARCHAR(50),
    duration INT DEFAULT 0,
    billsec INT DEFAULT 0,
    disposition VARCHAR(50) DEFAULT 'ANSWERED',
    uniqueid VARCHAR(100),
    INDEX idx_caller (callere164),
    INDEX idx_callee (calleee164),
    INDEX idx_calldate (calldate)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS e_cdr_20250105 LIKE e_cdr_20250101;

INSERT INTO e_cdr_20250101 (calldate, callere164, calleee164, duration, disposition) VALUES
('2025-01-01 20:00:00', '5551234567', '5557778899', 60, 'ANSWERED');

INSERT INTO e_cdr_20250105 (calldate, callere164, calleee164, duration, disposition) VALUES
('2025-01-05 09:30:00', '5551234567', '5550009999', 140, 'ANSWERED'),
('2025-01-05 14:45:00', '5556667788', '5551234567', 200, 'ANSWERED');

-- Base de datos CDR 4
CREATE DATABASE IF NOT EXISTS cdr_database_4 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cdr_database_4;

CREATE TABLE IF NOT EXISTS e_cdr_20250102 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calldate DATETIME NOT NULL,
    callere164 VARCHAR(50),
    calleee164 VARCHAR(50),
    duration INT DEFAULT 0,
    billsec INT DEFAULT 0,
    disposition VARCHAR(50) DEFAULT 'ANSWERED',
    uniqueid VARCHAR(100),
    INDEX idx_caller (callere164),
    INDEX idx_callee (calleee164),
    INDEX idx_calldate (calldate)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS e_cdr_20250106 LIKE e_cdr_20250102;

INSERT INTO e_cdr_20250102 (calldate, callere164, calleee164, duration, disposition) VALUES
('2025-01-02 19:00:00', '5551234567', '5554443322', 170, 'ANSWERED'),
('2025-01-02 21:30:00', '5552221100', '5551234567', 90, 'ANSWERED');

INSERT INTO e_cdr_20250106 (calldate, callere164, calleee164, duration, disposition) VALUES
('2025-01-06 08:00:00', '5551234567', '5558887766', 220, 'ANSWERED'),
('2025-01-06 12:15:00', '5559998877', '5551234567', 45, 'NO ANSWER'),
('2025-01-06 17:30:00', '5551234567', '5550001122', 180, 'ANSWERED');
