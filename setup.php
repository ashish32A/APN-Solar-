<?php
// setup.php — Run this once to create/upgrade the full database
$host     = 'localhost';
$username = 'root';
$password = '';   // <-- set your MySQL root password here if any

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS arogya_solar");
    $pdo->exec("USE arogya_solar");

    // 2. Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('superadmin','admin','operator') DEFAULT 'admin',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // 3. Groups
    $pdo->exec("CREATE TABLE IF NOT EXISTS `groups` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 4. Villages
    $pdo->exec("CREATE TABLE IF NOT EXISTS villages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        gram_panchayat VARCHAR(150),
        district VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 5. Gram Panchayats
    $pdo->exec("CREATE TABLE IF NOT EXISTS gram_panchayats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        district VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 6. Products
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        brand VARCHAR(100),
        category VARCHAR(100),
        unit VARCHAR(50),
        price DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 7. Customers (create if not exists, then add new columns safely)
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        operator_name VARCHAR(100),
        group_id INT,
        group_name VARCHAR(100),
        name VARCHAR(150) NOT NULL,
        email VARCHAR(150),
        mobile VARCHAR(15),
        ifsc_code VARCHAR(20),
        electricity_id VARCHAR(50),
        kw DECIMAL(5,2),
        account_number VARCHAR(50),
        village_id INT,
        gram_panchayat_id INT,
        district_name VARCHAR(100),
        status ENUM('active','pending','not_interested','completed') DEFAULT 'active',
        remarks TEXT,
        followup_remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Safely add new columns to existing customers table
    $existingCols = $pdo->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_COLUMN);
    $newCols = [
        'group_id'          => "INT DEFAULT NULL",
        'village_id'        => "INT DEFAULT NULL",
        'gram_panchayat_id' => "INT DEFAULT NULL",
        'district_name'     => "VARCHAR(100) DEFAULT NULL",
        'status'            => "ENUM('active','pending','not_interested','completed') DEFAULT 'active'",
        'remarks'           => "TEXT",
        'followup_remarks'  => "TEXT",
        'updated_at'        => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    ];
    foreach ($newCols as $col => $def) {
        if (!in_array($col, $existingCols)) {
            $pdo->exec("ALTER TABLE customers ADD COLUMN `$col` $def");
        }
    }

    // 8. Installations
    $pdo->exec("CREATE TABLE IF NOT EXISTS installations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        address TEXT,
        district_name VARCHAR(100),
        invoice_no VARCHAR(50),
        material_dispatch_1st DATE,
        material_dispatch_2nd DATE,
        installation_date DATE,
        net_metering_date DATE,
        online_date DATE,
        subsidy_1st_date DATE,
        subsidy_2nd_date DATE,
        jan_samarth_date DATE,
        status ENUM('pending','completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    )");

    // 9. Payments
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        total_amount DECIMAL(12,2) DEFAULT 0.00,
        due_amount DECIMAL(12,2) DEFAULT 0.00,
        payment_received DECIMAL(12,2) DEFAULT 0.00,
        payment_date DATE,
        payment_mode ENUM('cash','online','cheque','neft') DEFAULT 'online',
        transaction_no VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    )");

    // 10. Dispatches
    $pdo->exec("CREATE TABLE IF NOT EXISTS dispatches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        dispatch_no VARCHAR(50),
        dispatch_date DATE,
        type ENUM('first','second') NOT NULL,
        product_id INT,
        quantity INT DEFAULT 1,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    )");

    // 11. Complaints
    $pdo->exec("CREATE TABLE IF NOT EXISTS complaints (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        complaint_type VARCHAR(100),
        description TEXT,
        status ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
        resolved_note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    )");

    // 12. Leads
    $pdo->exec("CREATE TABLE IF NOT EXISTS leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        mobile VARCHAR(15),
        email VARCHAR(150),
        address TEXT,
        kw_interest DECIMAL(5,2),
        source VARCHAR(100),
        status ENUM('new','contacted','interested','converted','lost') DEFAULT 'new',
        notes TEXT,
        assigned_to INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // 13. Follow Ups
    $pdo->exec("CREATE TABLE IF NOT EXISTS followups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        followup_by INT,
        notes TEXT,
        next_date DATE,
        status ENUM('pending','done') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    )");

    // 14. Access Permissions
    $pdo->exec("CREATE TABLE IF NOT EXISTS access_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role ENUM('superadmin','admin','operator') NOT NULL,
        module VARCHAR(100) NOT NULL,
        can_view TINYINT(1) DEFAULT 1,
        can_create TINYINT(1) DEFAULT 0,
        can_edit TINYINT(1) DEFAULT 0,
        can_delete TINYINT(1) DEFAULT 0,
        UNIQUE KEY role_module (role, module)
    )");

    // --- SEED DATA ---

    // Admin users (password = admin123)
    $pdo->exec("INSERT IGNORE INTO users (name, email, password, role) VALUES
        ('Admin One',   'admin1@arogya.com', '\$2y\$10\$/FKykMTBH7RK9gkx6U.kNuOak.QBBfC6plDZ0gQpuyRpVhzOie.tS', 'superadmin'),
        ('Admin Two',   'admin2@arogya.com', '\$2y\$10\$/FKykMTBH7RK9gkx6U.kNuOak.QBBfC6plDZ0gQpuyRpVhzOie.tS', 'admin'),
        ('Admin Three', 'admin3@arogya.com', '\$2y\$10\$/FKykMTBH7RK9gkx6U.kNuOak.QBBfC6plDZ0gQpuyRpVhzOie.tS', 'admin')
    ");

    // Groups
    $groupsStmt = $pdo->prepare("INSERT IGNORE INTO `groups` (name) VALUES (?)");
    foreach (['CPD Surya Mitr', 'CPD SOLAR', 'RESCO BHARIYA'] as $g) {
        $groupsStmt->execute([$g]);
    }


    echo "<div style='font-family:sans-serif;max-width:600px;margin:40px auto;padding:30px;border:1px solid #d1fae5;border-radius:12px;background:#f0fdf4'>";
    echo "<h2 style='color:#065f46'>✅ Database setup successful!</h2>";
    echo "<p style='color:#374151'>All tables created / upgraded with new production schema.</p>";
    echo "<ul style='color:#374151'>";
    echo "<li>14 tables created</li>";
    echo "<li>3 admin users seeded (password: <code>admin123</code>)</li>";
    echo "<li>3 groups seeded</li>";
    echo "</ul>";
    echo "<a href='index.php' style='display:inline-block;margin-top:16px;padding:10px 24px;background:#059669;color:#fff;border-radius:8px;text-decoration:none'>→ Go to Dashboard</a>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<pre style='color:red;font-family:monospace;padding:20px'>Database Error: " . $e->getMessage() . "</pre>";
}
