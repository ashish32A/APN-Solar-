<?php
// masters/group/save.php — Create or update a Group

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /APN-Solar/masters/group/index.php");
    exit;
}

$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);
$name   = trim($_POST['name'] ?? '');

if ($name === '') {
    setFlash('error', 'Group name cannot be empty.');
    header("Location: /APN-Solar/masters/group/index.php");
    exit;
}

try {
    if ($action === 'edit' && $id) {
        $pdo->prepare("UPDATE `groups` SET name = ? WHERE id = ?")->execute([$name, $id]);
        // Sync the redundant group_name column in the customers table
        $pdo->prepare("UPDATE customers SET group_name = ? WHERE group_id = ?")->execute([$name, $id]);
        setFlash('success', 'Group updated successfully.');
    } else {
        $pdo->prepare("INSERT INTO `groups` (name) VALUES (?)")->execute([$name]);
        setFlash('success', 'Group "' . htmlspecialchars($name) . '" created successfully.');
    }
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        setFlash('error', 'Group name "' . htmlspecialchars($name) . '" already exists.');
    } else {
        setFlash('error', 'Database error: ' . $e->getMessage());
    }
}

header("Location: /APN-Solar/masters/group/index.php");
exit;
