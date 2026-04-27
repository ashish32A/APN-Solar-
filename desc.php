<?php
require 'c:/xampp/htdocs/APN-Solar/config/database.php';
$tables = ['customers', 'installations', 'payments'];
foreach ($tables as $t) {
    echo "\nTable: $t\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM $t");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
}
