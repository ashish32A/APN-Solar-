<?php
// customers/ajax_restore.php — Restore a not-interested customer to active

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
requireLogin();

$id = (int)($_POST['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid customer ID.']);
    exit;
}

try {
    $pdo->prepare("UPDATE customers SET status = 'active', updated_at = NOW() WHERE id = ? AND status = 'not_interested'")
        ->execute([$id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
