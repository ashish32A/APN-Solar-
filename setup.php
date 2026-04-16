<?php
$host = 'localhost';
$username = 'root';
$password = 'allinone@2552'; // Set this if you use one

try {
    // First connect to mysql without DB to create it
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read the sql file
    $sql = file_get_contents('database.sql');
    
    // Execute
    $pdo->exec($sql);
    
    echo "<h1>Database setup successful!</h1>";
    echo "<p><a href='index.php'>Go to Dashboard</a></p>";
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage() . "<br>Please check your password in setup.php if it's not empty.");
}
?>
