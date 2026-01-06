<?php
/**
 * API para obtener información del usuario
 */

header('Content-Type: application/json');

require_once '../config/session.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$user = getUserById($_SESSION['user_id']);

echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'balance' => floatval($user['balance']),
        'status' => $user['status'],
        'created_at' => $user['created_at'],
        'last_login' => $user['last_login']
    ]
]);
