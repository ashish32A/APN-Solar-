<?php
require 'c:/xampp/htdocs/APN-Solar/config/database.php';

$cols = $pdo->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_COLUMN);

$extra = [
    'state' => "VARCHAR(100) DEFAULT NULL",
    'block' => "VARCHAR(100) DEFAULT NULL",
    'gram_panchayat' => "VARCHAR(150) DEFAULT NULL",
    'village' => "VARCHAR(150) DEFAULT NULL",
    'division_name' => "VARCHAR(150) DEFAULT NULL",
    'app_ref_no' => "VARCHAR(100) DEFAULT NULL",
    'registration_date' => "DATE DEFAULT NULL",
    'js_cash_ecofy' => "VARCHAR(100) DEFAULT NULL",
    'js_bank_name' => "VARCHAR(150) DEFAULT NULL",
    'js_bank_branch' => "VARCHAR(150) DEFAULT NULL",
    'js_ifsc_code' => "VARCHAR(50) DEFAULT NULL",
    'js_date' => "DATE DEFAULT NULL",
    'doc_submission_date' => "DATE DEFAULT NULL",
    'pincode' => "VARCHAR(10) DEFAULT NULL",
    'address' => "TEXT DEFAULT NULL",
    'jan_samarth' => "VARCHAR(100) DEFAULT NULL",
    'model_number' => "VARCHAR(100) DEFAULT NULL"
];

foreach ($extra as $c => $def) {
    if (!in_array($c, $cols)) {
        try {
            $pdo->exec("ALTER TABLE customers ADD COLUMN `$c` $def");
            echo "Added $c\n";
        } catch(PDOException $e) {
            echo "Error adding $c: " . $e->getMessage() . "\n";
        }
    }
}
echo "Migration done.";
