<?php
// customers/current_status.php — Customer Status list (with installation details)

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

// ── Ensure extra columns exist in installations ──────────────────────────────
$installCols = [];
try {
    $installCols = $pdo->query("SHOW COLUMNS FROM installations")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
}

$addCols = [
    'installer_name' => "VARCHAR(150) DEFAULT NULL",
    'online_installer_name' => "VARCHAR(150) DEFAULT NULL",
    'meter_installation' => "ENUM('Yes','No') DEFAULT NULL",
];
foreach ($addCols as $col => $def) {
    if (!in_array($col, $installCols)) {
        try {
            $pdo->exec("ALTER TABLE installations ADD COLUMN `$col` $def");
        } catch (PDOException $e) {
            // ignore: already exists or incompatible
        }
    }
}

$pageTitle = 'Customer Status';
$validPerPage = [10, 25, 50, 100];
$requestedPer = (int) ($_GET['per_page'] ?? 10);
$perPage = in_array($requestedPer, $validPerPage) ? $requestedPer : 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Filters
$group = trim($_GET['group'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = "WHERE c.status NOT IN ('not_interested')";
$params = [];

if ($group !== '') {
    $where .= " AND c.group_name = ?";
    $params[] = $group;
}
if ($search !== '') {
    $where .= " AND (c.name LIKE ? OR c.mobile LIKE ? OR c.electricity_id LIKE ? OR c.group_name LIKE ? OR i.invoice_no LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

// Total count
try {
    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM customers c
        LEFT JOIN installations i ON c.id = i.customer_id
        $where
    ");
    $countStmt->execute($params);
    $totalRecords = (int) $countStmt->fetchColumn();
} catch (PDOException $e) {
    $totalRecords = 0;
}
$totalPages = max(1, (int) ceil($totalRecords / $perPage));
$page = min($page, $totalPages);

// Paginated data — select only columns that actually exist in installations
$queryError = null;
try {
    // Refresh column list after potential ALTER TABLE
    $liveInstallCols = $pdo->query("SHOW COLUMNS FROM installations")->fetchAll(PDO::FETCH_COLUMN);

    // Build dynamic select for optional columns
    $optCols = [
        'installer_name',
        'online_installer_name',
        'meter_installation',
        'installation_date',
        'net_metering_date',
        'online_date',
        'subsidy_1st_date',
        'subsidy_2nd_date',
        'jan_samarth_date',
        'remarks',
        'updated_at'
    ];
    $iSelects = ['i.id AS install_id', 'i.invoice_no'];
    foreach ($optCols as $oc) {
        if (in_array($oc, $liveInstallCols)) {
            $alias = match ($oc) {
                'remarks' => 'i.remarks AS install_remarks',
                'updated_at' => 'i.updated_at AS last_updated',
                default => "i.$oc",
            };
            $iSelects[] = $alias;
        } else {
            // Provide NULL fallback with correct alias
            $alias = match ($oc) {
                'remarks' => "NULL AS install_remarks",
                'updated_at' => "NULL AS last_updated",
                default => "NULL AS $oc",
            };
            $iSelects[] = $alias;
        }
    }
    $iSelectStr = implode(",\n               ", $iSelects);

    $sql = "
        SELECT c.id, c.group_name, c.name, c.mobile, c.electricity_id, c.kw, c.status AS customer_status,
               $iSelectStr
        FROM customers c
        LEFT JOIN installations i ON c.id = i.customer_id
        $where
        ORDER BY c.id DESC
        LIMIT " . (int) $perPage . " OFFSET " . (int) $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $customers = [];
    $queryError = $e->getMessage();
}

// Groups dropdown
try {
    $allGroups = $pdo->query("SELECT DISTINCT group_name FROM customers WHERE group_name != '' ORDER BY group_name")
        ->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $allGroups = [];
}

include __DIR__ . '/../views/partials/header.php';
include __DIR__ . '/../views/customers/customer_status.php';
include __DIR__ . '/../views/partials/footer.php';
