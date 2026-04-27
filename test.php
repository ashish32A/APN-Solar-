<?php
require_once __DIR__ . '/config/database.php';
try {
    $stmt = $pdo->query("SELECT p.payment_mode FROM payments p LIMIT 1");
    echo "Success\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
