<?php
// seed_flow_data.php
require_once __DIR__ . '/config/database.php';

// Clear old test data
$pdo->exec("DELETE FROM payments WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'Test Flow%')");
$pdo->exec("DELETE FROM installations WHERE customer_id IN (SELECT id FROM customers WHERE name LIKE 'Test Flow%')");
$pdo->exec("DELETE FROM customers WHERE name LIKE 'Test Flow%'");

// 5 Test Customers at different stages
$customers = [
    [
        'name' => 'Test Flow 1 - Registered',
        'mobile' => '9999000001',
        'operator_name' => 'Operator A',
        'group_name' => 'Group Alpha',
        'district_name' => 'Varanasi',
        'electricity_id' => 'ELEC10001',
        'kw' => '2',
        'status' => 'pending',
    ],
    [
        'name' => 'Test Flow 2 - Dispatched',
        'mobile' => '9999000002',
        'operator_name' => 'Operator B',
        'group_name' => 'Group Alpha',
        'district_name' => 'Prayagraj',
        'electricity_id' => 'ELEC10002',
        'kw' => '3',
        'status' => 'approved',
    ],
    [
        'name' => 'Test Flow 3 - Installed & Partial Pay',
        'mobile' => '9999000003',
        'operator_name' => 'Operator A',
        'group_name' => 'Group Beta',
        'district_name' => 'Lucknow',
        'electricity_id' => 'ELEC10003',
        'kw' => '5',
        'status' => 'approved',
    ],
    [
        'name' => 'Test Flow 4 - Subsidy 1st',
        'mobile' => '9999000004',
        'operator_name' => 'Operator C',
        'group_name' => 'Group Beta',
        'district_name' => 'Varanasi',
        'electricity_id' => 'ELEC10004',
        'kw' => '4',
        'status' => 'approved',
    ],
    [
        'name' => 'Test Flow 5 - Completed (Subsidy 2nd)',
        'mobile' => '9999000005',
        'operator_name' => 'Operator B',
        'group_name' => 'Group Gamma',
        'district_name' => 'Kanpur',
        'electricity_id' => 'ELEC10005',
        'kw' => '10',
        'status' => 'approved',
    ]
];

foreach ($customers as $i => $c) {
    // Insert Customer
    $stmt = $pdo->prepare("INSERT INTO customers (name, mobile, operator_name, group_name, district_name, electricity_id, kw, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$c['name'], $c['mobile'], $c['operator_name'], $c['group_name'], $c['district_name'], $c['electricity_id'], $c['kw'], $c['status']]);
    $cid = $pdo->lastInsertId();

    $idx = $i + 1;

    // Stage variables
    $install = null;
    $payment = null;

    if ($idx == 1) {
        // Just registered, no installation, no payment.
        // Actually let's create empty installation record as per app flow
        $install = ['invoice_no'=>null, 'disp1'=>null, 'disp2'=>null, 'inst_date'=>null, 'sub1'=>'No', 'sub2'=>'No'];
        $payment = ['tot'=>100000, 'recv'=>0, 'due'=>100000];
    } elseif ($idx == 2) {
        // Dispatched but not installed
        $install = ['invoice_no'=>'INV-TEST-002', 'disp1'=>'2026-04-01', 'disp2'=>'2026-04-05', 'inst_date'=>null, 'sub1'=>'No', 'sub2'=>'No'];
        $payment = ['tot'=>150000, 'recv'=>50000, 'due'=>100000];
    } elseif ($idx == 3) {
        // Installed, partial pay
        $install = ['invoice_no'=>'INV-TEST-003', 'disp1'=>'2026-03-01', 'disp2'=>'2026-03-10', 'inst_date'=>'2026-03-15', 'sub1'=>'No', 'sub2'=>'No'];
        $payment = ['tot'=>250000, 'recv'=>200000, 'due'=>50000];
    } elseif ($idx == 4) {
        // First subsidy
        $install = ['invoice_no'=>'INV-TEST-004', 'disp1'=>'2026-02-01', 'disp2'=>'2026-02-10', 'inst_date'=>'2026-02-20', 'sub1'=>'Yes', 'sub2'=>'No'];
        $payment = ['tot'=>200000, 'recv'=>200000, 'due'=>0];
    } elseif ($idx == 5) {
        // Second subsidy (Completed)
        $install = ['invoice_no'=>'INV-TEST-005', 'disp1'=>'2026-01-01', 'disp2'=>'2026-01-10', 'inst_date'=>'2026-01-20', 'sub1'=>'Yes', 'sub2'=>'Yes'];
        $payment = ['tot'=>500000, 'recv'=>500000, 'due'=>0];
    }

    // Insert Installation
    if ($install) {
        $pdo->prepare("INSERT INTO installations (customer_id, invoice_no, material_dispatch_1st, material_dispatch_2nd, installation_date, subsidy_1st_status, subsidy_2nd_status) VALUES (?,?,?,?,?,?,?)")
            ->execute([$cid, $install['invoice_no'], $install['disp1'], $install['disp2'], $install['inst_date'], $install['sub1'], $install['sub2']]);
    }

    // Insert Payment
    if ($payment) {
        $pdo->prepare("INSERT INTO payments (customer_id, total_amount, payment_received, due_amount) VALUES (?,?,?,?)")
            ->execute([$cid, $payment['tot'], $payment['recv'], $payment['due']]);
    }
}

echo "5 Test Flow Records Added Successfully!";
