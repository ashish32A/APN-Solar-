<?php
// masters/products/save.php — Create / Edit / Delete product

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /APN-Solar/masters/products/index.php");
    exit;
}

$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);
$name   = trim($_POST['name'] ?? '');
$desc   = trim($_POST['description'] ?? '');
$qty    = (int)($_POST['quantity'] ?? 0);
$stock  = (int)($_POST['stock'] ?? 0);

if ($action === 'delete') {
    if ($id) {
        try {
            $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
            setFlash('success', 'Product deleted.');
        } catch (PDOException $e) {
            setFlash('error', 'Delete failed: ' . $e->getMessage());
        }
    }
    header("Location: /APN-Solar/masters/products/index.php");
    exit;
}

if ($name === '') {
    setFlash('error', 'Product name is required.');
    header("Location: /APN-Solar/masters/products/index.php");
    exit;
}

try {
    if ($action === 'edit' && $id) {
        $pdo->prepare("UPDATE products SET name=?,description=?,quantity=?,stock=? WHERE id=?")
            ->execute([$name, $desc, $qty, $stock, $id]);
        setFlash('success', 'Product updated successfully.');
    } else {
        $pdo->prepare("INSERT INTO products (name,description,quantity,stock) VALUES (?,?,?,?)")
            ->execute([$name, $desc, $qty, $stock]);
        setFlash('success', 'Product "'.htmlspecialchars($name).'" created.');
    }
} catch (PDOException $e) {
    setFlash('error', 'Error: ' . $e->getMessage());
}

header("Location: /APN-Solar/masters/products/index.php");
exit;
