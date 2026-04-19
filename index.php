<?php
// index.php — Dashboard bootstrap (entry point)

require_once __DIR__ . '/app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/config/database.php';

requireLogin();

$pageTitle = 'Dashboard';

// Live stats
$totalCustomers            = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$totalPendingInstallations = (int)$pdo->query("SELECT COUNT(*) FROM installations WHERE status='pending'")->fetchColumn();
$paymentReceived           = (float)$pdo->query("SELECT COALESCE(SUM(payment_received),0) FROM payments")->fetchColumn();
$totalDue                  = (float)$pdo->query("SELECT COALESCE(SUM(due_amount),0) FROM payments")->fetchColumn();

include __DIR__ . '/views/partials/header.php';
include __DIR__ . '/views/dashboard/index.php';
include __DIR__ . '/views/partials/footer.php';
