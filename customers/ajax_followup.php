<?php
// customers/ajax_followup.php — Save followup note via AJAX

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
requireLogin();

$id   = (int)($_POST['id'] ?? 0);
$note = trim($_POST['note'] ?? '');

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid customer ID.']);
    exit;
}

try {
    // Update the followup_remarks on the customer record
    $pdo->prepare("UPDATE customers SET followup_remarks = ? WHERE id = ?")->execute([$note, $id]);

    // Also insert into followups table
    $pdo->prepare("INSERT INTO followups (customer_id, followup_by, notes, status) VALUES (?,?,?,'pending')")
        ->execute([$id, $_SESSION['admin_id'] ?? null, $note]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
