<?php
// app/Controllers/AuthController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Helpers/FlashHelper.php';

class AuthController {

    public function showLogin(): void {
        require_once __DIR__ . '/../../views/auth/login.php';
    }

    public function login(): void {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            setFlash('error', 'Email and password are required.');
            header("Location: /APN-Solar/solaradmin/public/all/login/");
            exit;
        }

        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $user['id'];
            $_SESSION['admin_name']      = $user['name'];
            $_SESSION['admin_email']     = $user['email'];
            $_SESSION['admin_role']      = $user['role'];

            // Remember Me - set cookie for 30 days
            if (!empty($_POST['remember'])) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
            }

            header("Location: /APN-Solar/index.php");
            exit;
        }

        setFlash('error', 'Invalid email or password.');
        header("Location: /APN-Solar/solaradmin/public/all/login/");
        exit;
    }

    public function logout(): void {
        $_SESSION = [];
        session_destroy();
        setcookie('remember_token', '', time() - 3600, '/');
        header("Location: /APN-Solar/solaradmin/public/all/login/");
        exit;
    }
}
