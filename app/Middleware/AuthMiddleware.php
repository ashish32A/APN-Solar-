<?php
// app/Middleware/AuthMiddleware.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: /APN-Solar/solaradmin/public/all/login/");
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if (($_SESSION['admin_role'] ?? '') !== $role) {
        header("Location: /APN-Solar/index.php");
        exit;
    }
}

function getAdminName(): string {
    return htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');
}
