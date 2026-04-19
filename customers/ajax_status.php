<?php
// customers/ajax_status.php — Update customer status via AJAX

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
requireLogin();

$id     = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$allowed = ['active', 'pending', 'not_interested', 'completed'];

if (!$id || !in_array($status, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

try {
    $pdo->prepare("UPDATE customers SET status = ? WHERE id = ?")->execute([$status, $id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
