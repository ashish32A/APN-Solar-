<?php
// customers/index.php — Customer Registration (proper location)

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$pageTitle = 'Customer Registration';

try {
    $stmt = $pdo->query("
        SELECT c.*, p.total_amount, p.due_amount
        FROM customers c
        LEFT JOIN payments p ON c.id = p.customer_id
        ORDER BY c.id DESC
    ");
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    $customers = [];
    error_log("Customer query failed: " . $e->getMessage());
}

include __DIR__ . '/../views/partials/header.php';
include __DIR__ . '/../views/customers/index.php';
include __DIR__ . '/../views/partials/footer.php';
