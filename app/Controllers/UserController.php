<?php
// app/Controllers/UserController.php - User Master (admin users)

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Helpers/FlashHelper.php';

class UserController {

    public function index(): void {
        requireLogin();
        global $pdo;
        $users = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY id DESC")->fetchAll();
        require_once __DIR__ . '/../../views/masters/users/index.php';
    }

    public function create(): void {
        requireLogin();
        require_once __DIR__ . '/../../views/masters/users/create.php';
    }

    public function store(): void {
        requireLogin();
        global $pdo;
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
        $role     = $_POST['role'] ?? 'admin';

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)");
        $stmt->execute([$name, $email, $password, $role]);

        setFlash('success', 'User created successfully.');
        header("Location: /APN-Solar/masters/users/");
        exit;
    }

    public function edit(int $id): void {
        requireLogin();
        global $pdo;
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        require_once __DIR__ . '/../../views/masters/users/edit.php';
    }

    public function update(int $id): void {
        requireLogin();
        global $pdo;
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = $_POST['role'] ?? 'admin';

        $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
        $stmt->execute([$name, $email, $role, $id]);

        if (!empty($_POST['password'])) {
            $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hashed, $id]);
        }

        setFlash('success', 'User updated successfully.');
        header("Location: /APN-Solar/masters/users/");
        exit;
    }

    public function destroy(int $id): void {
        requireLogin();
        global $pdo;
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        setFlash('success', 'User deleted.');
        header("Location: /APN-Solar/masters/users/");
        exit;
    }
}
