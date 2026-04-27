<?php
// masters/users/toggle.php — Toggle is_active status
require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();
$id   = (int)($_GET['id'] ?? 0);
$self = (int)($_SESSION['admin_id'] ?? 0);
if ($id && $id !== $self) {
    try {
        $pdo->prepare("UPDATE users SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
        setFlash('success','User status updated.');
    } catch (PDOException $e) { setFlash('error','Error: '.$e->getMessage()); }
} elseif ($id === $self) {
    setFlash('error','You cannot deactivate your own account.');
}
header("Location: /APN-Solar/masters/users/index.php"); exit;
