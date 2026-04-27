<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();
$id = (int)($_POST['id'] ?? 0);
if ($id) {
    try { $pdo->prepare("DELETE FROM villages WHERE id=?")->execute([$id]); setFlash('success','Village deleted.'); }
    catch (PDOException $e) { setFlash('error','Delete failed.'); }
}
header("Location: /APN-Solar/villages/index.php"); exit;
