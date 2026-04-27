<?php
// masters/dispatch/delete.php — Delete a dispatch record

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$id = (int)($_POST['id'] ?? 0);
if ($id) {
    try {
        $pdo->prepare("DELETE FROM dispatches WHERE id = ?")->execute([$id]);
        setFlash('success', 'Dispatch deleted successfully.');
    } catch (PDOException $e) {
        setFlash('error', 'Delete failed: ' . $e->getMessage());
    }
}
header("Location: /APN-Solar/masters/dispatch/index.php");
exit;
