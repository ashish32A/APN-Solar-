<?php
// config/database.php
$host = 'localhost';
$dbname = 'arogya_solar';
$username = 'root';
// NOTE TO USER: If your local MySQL setup has a password, enter it here.
$password = 'allinone@2552';

echo "Database Connection successful";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}
?>