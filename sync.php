<?php
require 'config/database.php';
$pdo->query("UPDATE customers c JOIN `groups` g ON c.group_id = g.id SET c.group_name = g.name");
echo "Sync complete.";
