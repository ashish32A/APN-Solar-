<?php
require 'config/database.php';
$res = $pdo->query("SELECT DISTINCT group_id, group_name FROM customers")->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
