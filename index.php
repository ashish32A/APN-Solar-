<?php
// index.php — Dashboard bootstrap (entry point)

require_once __DIR__ . '/app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/config/database.php';

requireLogin();

$pageTitle = 'Dashboard';

// Live stats - Top Cards
$totalCustomers            = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$paymentReceived           = (float)$pdo->query("SELECT COALESCE(SUM(payment_received),0) FROM payments")->fetchColumn();
$totalDue                  = (float)$pdo->query("SELECT COALESCE(SUM(due_amount),0) FROM payments")->fetchColumn();
$totalPendingInstallations = (int)$pdo->query("SELECT COUNT(*) FROM installations i WHERE i.material_dispatch_1st IS NOT NULL AND (i.installation_date IS NULL OR i.installation_date = '')")->fetchColumn();
$pendingDispatch1st        = (int)$pdo->query("SELECT COUNT(*) FROM customers c LEFT JOIN installations i ON c.id=i.customer_id WHERE i.material_dispatch_1st IS NULL OR i.material_dispatch_1st = ''")->fetchColumn();
$pendingDispatch2nd        = (int)$pdo->query("SELECT COUNT(*) FROM installations i WHERE i.material_dispatch_1st IS NOT NULL AND (i.material_dispatch_2nd IS NULL OR i.material_dispatch_2nd = '')")->fetchColumn();
$pendingNetMetering        = (int)$pdo->query("SELECT COUNT(*) FROM installations i WHERE i.meter_installation IS NULL OR i.meter_installation = 'No'")->fetchColumn();
$pendingOnline             = (int)$pdo->query("SELECT COUNT(*) FROM installations i WHERE i.online_installer_name IS NULL OR i.online_installer_name = ''")->fetchColumn();
$pendingJanSamarth         = (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE jan_samarth IS NULL OR jan_samarth = 'Pending' OR jan_samarth = ''")->fetchColumn();
$pendingRegistration       = (int)$pdo->query("SELECT COUNT(*) FROM customers WHERE status = 'pending'")->fetchColumn();
$pendingComplaints         = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status NOT IN ('resolved','closed')")->fetchColumn();

// Data for Charts
$chartDistricts = $pdo->query("SELECT COALESCE(district_name, 'Unknown') as label, COUNT(*) as val FROM customers GROUP BY district_name ORDER BY val DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
$chartGroups = $pdo->query("SELECT COALESCE(group_name, 'No Group') as label, COUNT(*) as val FROM customers GROUP BY group_name ORDER BY val DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);

$chartOperators = $pdo->query("SELECT COALESCE(operator_name, 'No Operator') as label, COUNT(*) as val FROM customers GROUP BY operator_name ORDER BY val DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);

$chartVillages = $pdo->query("
    SELECT COALESCE(v.name, 'Unknown') as label, COUNT(c.id) as val 
    FROM customers c 
    LEFT JOIN villages v ON c.village_id = v.id 
    GROUP BY v.name 
    ORDER BY val DESC LIMIT 7
")->fetchAll(PDO::FETCH_ASSOC);

$chartGramPanchayats = $pdo->query("
    SELECT COALESCE(gp.name, 'Unknown') as label, COUNT(c.id) as val 
    FROM customers c 
    LEFT JOIN gram_panchayats gp ON c.gram_panchayat_id = gp.id 
    GROUP BY gp.name 
    ORDER BY val DESC LIMIT 7
")->fetchAll(PDO::FETCH_ASSOC);

$chartUsers = $pdo->query("SELECT role as label, COUNT(*) as val FROM users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);

// Payment Overview Chart
$paymentOverview = [
    ['label' => 'Total Amount', 'val' => $paymentReceived + $totalDue], // Total expected
    ['label' => 'Received', 'val' => $paymentReceived],
    ['label' => 'Due', 'val' => $totalDue]
];


include __DIR__ . '/views/partials/header.php';
include __DIR__ . '/views/dashboard/index.php';
include __DIR__ . '/views/partials/footer.php';
