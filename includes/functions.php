<?php
/**
 * Funciones generales del sistema
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Sanitizar entrada
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generar hash de contraseña
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verificar contraseña
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Obtener usuario por ID
 */
function getUserById($userId) {
    $pdo = getMainConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Obtener usuario por email
 */
function getUserByEmail($email) {
    $pdo = getMainConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Actualizar saldo del usuario
 */
function updateUserBalance($userId, $amount, $operation = 'subtract') {
    $pdo = getMainConnection();
    
    if ($operation === 'add') {
        $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
        $stmt->execute([$amount, $userId, $amount]);
        return $stmt->rowCount() > 0;
    }
    
    $stmt->execute([$amount, $userId]);
    return $stmt->rowCount() > 0;
}

/**
 * Obtener saldo del usuario
 */
function getUserBalance($userId) {
    $user = getUserById($userId);
    return $user ? floatval($user['balance']) : 0;
}

/**
 * Registrar transacción
 */
function logTransaction($userId, $type, $amount, $description, $searchQuery = null, $resultsCount = 0) {
    $pdo = getMainConnection();
    $stmt = $pdo->prepare("
        INSERT INTO transactions (user_id, type, amount, description, search_query, results_count, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    return $stmt->execute([$userId, $type, $amount, $description, $searchQuery, $resultsCount]);
}

/**
 * Registrar búsqueda
 */
function logSearch($userId, $phoneNumber, $resultsFound, $cost) {
    $pdo = getMainConnection();
    $stmt = $pdo->prepare("
        INSERT INTO search_history (user_id, phone_number, results_found, cost, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    return $stmt->execute([$userId, $phoneNumber, $resultsFound, $cost]);
}

/**
 * Obtener tablas CDR disponibles en un rango de fechas
 */
function getCDRTables($connection, $prefix, $startDate = null, $endDate = null) {
    $tables = [];
    
    try {
        $stmt = $connection->query("SHOW TABLES LIKE '{$prefix}%'");
        $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($allTables as $table) {
            // Extraer la fecha del nombre de la tabla (e_cdr_20250121)
            if (preg_match('/e_cdr_(\d{8})/', $table, $matches)) {
                $tableDate = $matches[1];
                
                // Filtrar por rango de fechas si se especifica
                if ($startDate && $endDate) {
                    $start = str_replace('-', '', $startDate);
                    $end = str_replace('-', '', $endDate);
                    
                    if ($tableDate >= $start && $tableDate <= $end) {
                        $tables[] = $table;
                    }
                } else {
                    $tables[] = $table;
                }
            }
        }
        
        // Ordenar tablas de más reciente a más antigua
        rsort($tables);
        
    } catch (PDOException $e) {
        error_log("Error obteniendo tablas CDR: " . $e->getMessage());
    }
    
    return $tables;
}

/**
 * Buscar número en las bases de datos CDR
 */
function searchPhoneNumber($phoneNumber, $startDate = null, $endDate = null) {
    $results = [];
    $cdrConnections = getCDRConnections();
    
    // Limpiar el número (solo dígitos)
    $cleanNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
    
    foreach ($cdrConnections as $dbKey => $dbInfo) {
        $connection = $dbInfo['connection'];
        $prefix = $dbInfo['prefix'];
        $dbName = $dbInfo['name'];
        
        $tables = getCDRTables($connection, $prefix, $startDate, $endDate);
        
        foreach ($tables as $table) {
            try {
                // Buscar en callere164 y calleee164
                $sql = "SELECT 
                            callere164, 
                            calleee164, 
                            starttime,
                            holdtime
                        FROM {$table} 
                        WHERE callere164 LIKE ? OR calleee164 LIKE ?
                        ORDER BY starttime DESC";
                
                $stmt = $connection->prepare($sql);
                $searchPattern = "%{$cleanNumber}%";
                $stmt->execute([$searchPattern, $searchPattern]);
                
                while ($row = $stmt->fetch()) {
                    // Extraer fecha de la tabla
                    preg_match('/e_cdr_(\d{4})(\d{2})(\d{2})/', $table, $dateMatch);
                    $tableDate = "{$dateMatch[1]}-{$dateMatch[2]}-{$dateMatch[3]}";
                    
                    // Convertir timestamp a fecha
                    $callDateTime = isset($row['starttime']) ? date('Y-m-d H:i:s', $row['starttime']) : $tableDate;
                    
                    $results[] = [
                        'database' => $dbKey,
                        'table' => $table,
                        'table_date' => $tableDate,
                        'caller' => $row['callere164'],
                        'callee' => $row['calleee164'],
                        'call_date' => $callDateTime,
                        'duration' => $row['holdtime'] ?? 0,
                        'match_type' => ($row['callere164'] == $cleanNumber || strpos($row['callere164'], $cleanNumber) !== false) ? 'caller' : 'callee'
                    ];
                }
                
            } catch (PDOException $e) {
                error_log("Error buscando en {$table}: " . $e->getMessage());
            }
        }
    }
    
    // Ordenar resultados por fecha (más reciente primero)
    usort($results, function($a, $b) {
        return strtotime($b['call_date']) - strtotime($a['call_date']);
    });
    
    return $results;
}

/**
 * Formatear número de teléfono
 */
function formatPhoneNumber($number) {
    $clean = preg_replace('/[^0-9]/', '', $number);
    if (strlen($clean) == 10) {
        return sprintf("(%s) %s-%s", 
            substr($clean, 0, 3),
            substr($clean, 3, 3),
            substr($clean, 6, 4)
        );
    }
    return $number;
}

/**
 * Formatear moneda
 */
function formatMoney($amount) {
    return '$' . number_format($amount, 2, '.', ',');
}

/**
 * Formatear fecha
 */
function formatDate($date, $format = 'd/m/Y H:i:s') {
    return date($format, strtotime($date));
}

/**
 * Generar mensaje de alerta
 */
function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Mostrar alerta
 */
function showAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        
        $class = match($alert['type']) {
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            default => 'alert-info'
        };
        
        return "<div class='alert {$class} alert-dismissible fade show' role='alert'>
                    {$alert['message']}
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
    return '';
}
