<?php
// app/Controllers/LeadController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Helpers/FlashHelper.php';

class LeadController {

    public function index(): void {
        requireLogin();
        global $pdo;
        $leads = $pdo->query("SELECT * FROM leads ORDER BY id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/leads/index.php';
    }

    public function create(): void {
        requireLogin();
        require_once __DIR__ . '/../../views/leads/create.php';
    }

    public function store(): void {
        requireLogin();
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO leads (name, mobile, email, address, status) VALUES (?,?,?,?,?)");
        $stmt->execute([
            $_POST['name'] ?? '',
            $_POST['mobile'] ?? '',
            $_POST['email'] ?? '',
            $_POST['address'] ?? '',
            'new'
        ]);
        setFlash('success', 'Lead added successfully.');
        header("Location: /APN-Solar/leads/");
        exit;
    }
}
