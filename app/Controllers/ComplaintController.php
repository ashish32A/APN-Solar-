<?php
// app/Controllers/ComplaintController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Helpers/FlashHelper.php';

class ComplaintController {

    public function index(): void {
        requireLogin();
        global $pdo;
        $complaints = $pdo->query("
            SELECT comp.*, c.name AS customer_name, c.mobile
            FROM complaints comp
            LEFT JOIN customers c ON c.id = comp.customer_id
            ORDER BY comp.id DESC
        ")->fetchAll();
        require_once __DIR__ . '/../../views/complaints/index.php';
    }

    public function create(): void {
        requireLogin();
        global $pdo;
        $customers = $pdo->query("SELECT id, name FROM customers ORDER BY name ASC")->fetchAll();
        require_once __DIR__ . '/../../views/complaints/create.php';
    }

    public function store(): void {
        requireLogin();
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO complaints (customer_id, complaint_type, description, status) VALUES (?,?,?,?)");
        $stmt->execute([
            $_POST['customer_id'] ?? null,
            $_POST['complaint_type'] ?? '',
            $_POST['description'] ?? '',
            'open'
        ]);
        setFlash('success', 'Complaint registered.');
        header("Location: /APN-Solar/complaints/");
        exit;
    }
}
