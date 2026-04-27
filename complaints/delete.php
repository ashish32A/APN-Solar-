<?php
// complaints/delete.php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();
$id = (int)($_POST['id'] ?? 0);
if ($id) {
    try { $pdo->prepare("DELETE FROM complaints WHERE id=?")->execute([$id]); setFlash('success','Complaint deleted.'); }
    catch (PDOException $e) { setFlash('error','Delete failed: '.$e->getMessage()); }
}
header("Location: /APN-Solar/complaints/index.php"); exit;
