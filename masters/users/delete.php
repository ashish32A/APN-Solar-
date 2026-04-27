<?php
// masters/users/delete.php
require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();
$id = (int)($_POST['id'] ?? 0);
// Prevent deleting yourself
$self = (int)($_SESSION['admin_id'] ?? 0);
if ($id && $id !== $self) {
    try { $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]); setFlash('success','User deleted.'); }
    catch (PDOException $e) { setFlash('error','Delete failed: '.$e->getMessage()); }
} elseif ($id === $self) {
    setFlash('error', 'You cannot delete your own account.');
}
header("Location: /APN-Solar/masters/users/index.php"); exit;
