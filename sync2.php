<?php
require 'config/database.php';

// 1. Manually fix the renamed groups
$pdo->query("UPDATE customers SET group_name = 'Arogya SOLAR' WHERE group_name = 'CPD SOLAR'");
$pdo->query("UPDATE customers SET group_name = 'Arogya Surya Mitr' WHERE group_name = 'CPD Surya Mitr'");

// 2. Insert missing groups into `groups` table
$customersGroups = $pdo->query("SELECT DISTINCT group_name FROM customers WHERE group_name != '' AND group_name IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$existingGroups = $pdo->query("SELECT name FROM `groups`")->fetchAll(PDO::FETCH_COLUMN);

foreach ($customersGroups as $cg) {
    if (!in_array($cg, $existingGroups)) {
        $stmt = $pdo->prepare("INSERT INTO `groups` (name) VALUES (?)");
        $stmt->execute([$cg]);
        echo "Inserted missing group: $cg\n";
    }
}

// 3. Update customers.group_id based on name matching
$pdo->query("UPDATE customers c JOIN `groups` g ON c.group_name = g.name SET c.group_id = g.id");

echo "Sync complete.\n";
