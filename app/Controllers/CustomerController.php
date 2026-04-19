<?php
// app/Controllers/CustomerController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Helpers/FlashHelper.php';

class CustomerController {

    // LIST - Customer Registration
    public function index(): void {
        requireLogin();
        global $pdo;

        $customers = $pdo->query("
            SELECT c.*, p.total_amount, p.due_amount
            FROM customers c
            LEFT JOIN payments p ON c.id = p.customer_id
            ORDER BY c.id DESC
        ")->fetchAll();

        require_once __DIR__ . '/../../views/customers/index.php';
    }

    // CREATE FORM
    public function create(): void {
        requireLogin();
        require_once __DIR__ . '/../../views/customers/create.php';
    }

    // STORE NEW CUSTOMER
    public function store(): void {
        requireLogin();
        global $pdo;

        $fields = ['operator_name','group_name','name','email','mobile','ifsc_code','electricity_id','kw','account_number'];
        $data = [];
        foreach ($fields as $f) { $data[$f] = trim($_POST[$f] ?? ''); }

        $sql = "INSERT INTO customers (operator_name,group_name,name,email,mobile,ifsc_code,electricity_id,kw,account_number)
                VALUES (:operator_name,:group_name,:name,:email,:mobile,:ifsc_code,:electricity_id,:kw,:account_number)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        setFlash('success', 'Customer registered successfully.');
        header("Location: /APN-Solar/customers.php");
        exit;
    }

    // EDIT FORM
    public function edit(int $id): void {
        requireLogin();
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();
        require_once __DIR__ . '/../../views/customers/edit.php';
    }

    // UPDATE CUSTOMER
    public function update(int $id): void {
        requireLogin();
        global $pdo;

        $fields = ['operator_name','group_name','name','email','mobile','ifsc_code','electricity_id','kw','account_number'];
        $data = [];
        foreach ($fields as $f) { $data[$f] = trim($_POST[$f] ?? ''); }
        $data['id'] = $id;

        $sql = "UPDATE customers SET operator_name=:operator_name, group_name=:group_name, name=:name,
                email=:email, mobile=:mobile, ifsc_code=:ifsc_code, electricity_id=:electricity_id,
                kw=:kw, account_number=:account_number WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        setFlash('success', 'Customer updated successfully.');
        header("Location: /APN-Solar/customers.php");
        exit;
    }

    // DELETE CUSTOMER
    public function destroy(int $id): void {
        requireLogin();
        global $pdo;
        $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
        setFlash('success', 'Customer deleted.');
        header("Location: /APN-Solar/customers.php");
        exit;
    }

    // NOT INTERESTED
    public function notInterested(): void {
        requireLogin();
        global $pdo;
        $customers = $pdo->query("SELECT * FROM customers WHERE status='not_interested' ORDER BY id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/customers/not_interested.php';
    }

    // PENDING LIST
    public function pendingList(): void {
        requireLogin();
        global $pdo;
        $customers = $pdo->query("SELECT * FROM customers WHERE status='pending' ORDER BY id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/customers/pending_list.php';
    }

    // PAYMENT DUE LIST
    public function paymentDueList(): void {
        requireLogin();
        global $pdo;
        $customers = $pdo->query("
            SELECT c.*, p.due_amount FROM customers c
            LEFT JOIN payments p ON c.id = p.customer_id
            WHERE p.due_amount > 0 ORDER BY c.id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/customers/payment_due.php';
    }

    // PAYMENT RECEIVED LIST
    public function paymentReceivedList(): void {
        requireLogin();
        global $pdo;
        $customers = $pdo->query("
            SELECT c.*, p.payment_received FROM customers c
            LEFT JOIN payments p ON c.id = p.customer_id
            WHERE p.payment_received > 0 ORDER BY c.id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/customers/payment_received.php';
    }
}
