<?php
// api/get_gram_panchayats.php — Returns JSON GPs for a given district+block
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
$district = trim($_GET['district'] ?? '');
$block    = trim($_GET['block']    ?? '');
if (!$district || !$block) { echo json_encode([]); exit; }
try {
    $stmt = $pdo->prepare("SELECT id, name FROM gram_panchayats WHERE LOWER(district)=LOWER(?) AND LOWER(block)=LOWER(?) AND status='active' ORDER BY name ASC");
    $stmt->execute([$district, $block]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) { echo json_encode([]); }
