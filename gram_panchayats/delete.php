<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();
$id = (int)($_POST['id'] ?? 0);
if ($id) {
    try { $pdo->prepare("DELETE FROM gram_panchayats WHERE id=?")->execute([$id]); setFlash('success','Gram Panchayat deleted.'); }
    catch (PDOException $e) { setFlash('error','Delete failed.'); }
}
header("Location: /APN-Solar/gram_panchayats/index.php"); exit;
