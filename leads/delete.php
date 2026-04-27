<?php
// leads/delete.php — Delete Customer (POST confirmation)

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$id) {
    header('Location: /APN-Solar/leads/');
    exit;
}

// Verify customer exists
$stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Customer not found.');
    header('Location: /APN-Solar/leads/');
    exit;
}

// On POST confirmation — delete with cascade in FK order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        $pdo->beginTransaction();

        $pdo->prepare("DELETE FROM followups    WHERE customer_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM complaints   WHERE customer_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM dispatches   WHERE customer_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM payments     WHERE customer_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM installations WHERE customer_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM customers    WHERE id = ?")->execute([$id]);

        $pdo->commit();
        setFlash('success', 'Customer "' . htmlspecialchars($customer['name']) . '" deleted successfully.');
    } catch (PDOException $e) {
        $pdo->rollBack();
        setFlash('error', 'Delete failed: ' . $e->getMessage());
    }
    header('Location: /APN-Solar/leads/');
    exit;
}
?>
