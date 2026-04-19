<?php
// app/Controllers/DashboardController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

class DashboardController {

    public function index(): void {
        requireLogin();

        global $pdo;

        // Aggregate stats
        $stats = [
            'total_customers'         => (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
            'pending_installations'   => (int)$pdo->query("SELECT COUNT(*) FROM installations WHERE status='pending'")->fetchColumn(),
            'payment_received'        => (float)$pdo->query("SELECT COALESCE(SUM(payment_received),0) FROM payments")->fetchColumn(),
            'total_due'               => (float)$pdo->query("SELECT COALESCE(SUM(due_amount),0) FROM payments")->fetchColumn(),
        ];

        require_once __DIR__ . '/../../views/dashboard/index.php';
    }
}
