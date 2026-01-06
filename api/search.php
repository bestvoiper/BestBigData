<?php
/**
 * API para búsqueda de números telefónicos
 * Devuelve resultados en formato JSON
 */

header('Content-Type: application/json');

require_once '../config/session.php';
require_once '../includes/functions.php';

// Verificar autenticación
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$user = getUserById($_SESSION['user_id']);
$pdo = getMainConnection();

// Obtener configuración
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$costPerResult = floatval($settings['cost_per_result'] ?? 1);
$maxResults = intval($settings['max_results_per_search'] ?? 1000);

// Validar parámetros
$phone = $_GET['phone'] ?? $_POST['phone'] ?? '';
$startDate = $_GET['start_date'] ?? $_POST['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? $_POST['end_date'] ?? null;
$preview = isset($_GET['preview']) || isset($_POST['preview']); // Solo contar, no cobrar

if (empty($phone)) {
    http_response_code(400);
    echo json_encode(['error' => 'Número de teléfono requerido']);
    exit;
}

// Verificar saldo
if ($user['balance'] < $costPerResult && !$preview) {
    http_response_code(402);
    echo json_encode([
        'error' => 'Saldo insuficiente',
        'balance' => $user['balance'],
        'cost_per_result' => $costPerResult
    ]);
    exit;
}

// Realizar búsqueda
$results = searchPhoneNumber($phone, $startDate, $endDate);
$totalResults = count($results);

// Si es preview, solo devolver conteo
if ($preview) {
    echo json_encode([
        'success' => true,
        'preview' => true,
        'phone' => $phone,
        'total_results' => $totalResults,
        'estimated_cost' => min($totalResults, $maxResults) * $costPerResult,
        'user_balance' => $user['balance']
    ]);
    exit;
}

// Limitar resultados
$results = array_slice($results, 0, $maxResults);
$resultsCount = count($results);

// Verificar si hay suficiente saldo
if ($resultsCount * $costPerResult > $user['balance']) {
    $affordableResults = floor($user['balance'] / $costPerResult);
    $results = array_slice($results, 0, $affordableResults);
    $resultsCount = count($results);
}

$totalCost = $resultsCount * $costPerResult;

// Procesar cobro si hay resultados
if ($resultsCount > 0) {
    $balanceBefore = $user['balance'];
    
    if (updateUserBalance($user['id'], $totalCost, 'subtract')) {
        $balanceAfter = $balanceBefore - $totalCost;
        
        // Registrar transacción
        $stmt = $pdo->prepare("
            INSERT INTO transactions (user_id, type, amount, description, search_query, results_count, balance_before, balance_after) 
            VALUES (?, 'search', ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'], 
            $totalCost, 
            "API - Búsqueda de número: {$phone}", 
            $phone, 
            $resultsCount,
            $balanceBefore,
            $balanceAfter
        ]);
        
        // Registrar búsqueda
        logSearch($user['id'], $phone, $resultsCount, $totalCost);
        
        echo json_encode([
            'success' => true,
            'phone' => $phone,
            'results' => $results,
            'total_results' => $resultsCount,
            'cost' => $totalCost,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al procesar el cobro']);
    }
} else {
    // Sin resultados, sin cobro
    logSearch($user['id'], $phone, 0, 0);
    
    echo json_encode([
        'success' => true,
        'phone' => $phone,
        'results' => [],
        'total_results' => 0,
        'cost' => 0,
        'message' => 'No se encontraron resultados'
    ]);
}
