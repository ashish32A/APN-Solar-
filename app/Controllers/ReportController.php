<?php
// app/Controllers/ReportController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

class ReportController {

    private function getInstallations(string $status): array {
        global $pdo;
        return $pdo->query("SELECT c.*, i.* FROM installations i
                            JOIN customers c ON c.id = i.customer_id
                            WHERE i.status = '$status' ORDER BY c.id DESC")->fetchAll();
    }

    public function pendingInstallation(): void {
        requireLogin();
        $records = $this->getInstallations('pending');
        require_once __DIR__ . '/../../views/reports/pending_installation.php';
    }

    public function pendingNetMetering(): void {
        requireLogin();
        global $pdo;
        $records = $pdo->query("SELECT * FROM customers ORDER BY id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/reports/pending_net_metering.php';
    }

    public function pendingOnlineInstallation(): void {
        requireLogin();
        global $pdo;
        $records = $pdo->query("SELECT * FROM customers ORDER BY id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/reports/pending_online_installation.php';
    }

    public function pendingSubsidyFirst(): void {
        requireLogin();
        global $pdo;
        $records = $pdo->query("SELECT * FROM customers ORDER BY id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/reports/pending_subsidy_first.php';
    }

    public function pendingSubsidySecond(): void {
        requireLogin();
        global $pdo;
        $records = $pdo->query("SELECT * FROM customers ORDER BY id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/reports/pending_subsidy_second.php';
    }

    public function pendingAccountNumber(): void {
        requireLogin();
        global $pdo;
        $records = $pdo->query("SELECT * FROM customers WHERE account_number IS NULL OR account_number = '' ORDER BY id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/reports/pending_account_number.php';
    }

    public function firstMaterialsDispatched(): void {
        requireLogin();
        global $pdo;
        $records = $pdo->query("SELECT c.*, i.material_dispatch_1st FROM installations i JOIN customers c ON c.id=i.customer_id WHERE i.material_dispatch_1st IS NOT NULL ORDER BY c.id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/reports/first_materials_dispatched.php';
    }

    public function secondMaterialsDispatched(): void {
        requireLogin();
        global $pdo;
        $records = $pdo->query("SELECT c.*, i.material_dispatch_2nd FROM installations i JOIN customers c ON c.id=i.customer_id WHERE i.material_dispatch_2nd IS NOT NULL ORDER BY c.id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/reports/second_materials_dispatched.php';
    }

    public function customerRegistrationPending(): void {
        requireLogin();
        global $pdo;
        $records = $pdo->query("SELECT * FROM customers WHERE created_at >= NOW() - INTERVAL 30 DAY ORDER BY id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/reports/customer_registration_pending.php';
    }

    public function customerJanSamarthPending(): void {
        requireLogin();
        global $pdo;
        $records = $pdo->query("SELECT * FROM customers ORDER BY id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/reports/customer_jan_samarth_pending.php';
    }

    public function centralizedReport(): void {
        requireLogin();
        global $pdo;
        $records = $pdo->query("SELECT c.*, p.total_amount, p.due_amount, p.payment_received FROM customers c LEFT JOIN payments p ON c.id = p.customer_id ORDER BY c.id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/reports/centralized_report.php';
    }

    public function paymentReport(): void {
        requireLogin();
        global $pdo;
        $records = $pdo->query("SELECT c.name, c.mobile, p.* FROM payments p JOIN customers c ON c.id = p.customer_id ORDER BY p.id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/reports/payment_report.php';
    }

    public function groupWiseReport(): void {
        requireLogin();
        global $pdo;
        $records = $pdo->query("SELECT group_name, COUNT(*) as total FROM customers GROUP BY group_name ORDER BY total DESC")->fetchAll();
        require_once __DIR__ . '/../../views/reports/group_wise_report.php';
    }
}
