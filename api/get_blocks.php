<?php
// api/get_blocks.php — Returns JSON blocks for a given district name
header('Content-Type: application/json');
$district = trim($_GET['district'] ?? '');
if (!$district) { echo json_encode([]); exit; }
$data = require __DIR__ . '/../data/up_districts_blocks.php';
$key = '';
foreach ($data as $d => $blocks) {
    if (strtolower($d) === strtolower($district)) { $key = $d; break; }
}
echo json_encode($key ? $data[$key] : []);
