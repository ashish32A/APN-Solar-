<?php
// customers/index.php — Customer Registration with pagination

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$pageTitle      = 'Customer Registration';
$validPerPage   = [10, 25, 50, 100];
$requestedPer   = (int)($_GET['per_page'] ?? 25);          // evaluate ONCE — no double-access
$perPage        = in_array($requestedPer, $validPerPage) ? $requestedPer : 25;
$page           = max(1, (int)($_GET['page'] ?? 1));
$offset         = ($page - 1) * $perPage;

// Build WHERE clause from filters
$search    = trim($_GET['search'] ?? '');
$group     = trim($_GET['group'] ?? '');
$regFrom   = $_GET['reg_from'] ?? '';
$regTo     = $_GET['reg_to'] ?? '';

$where  = "WHERE 1=1";
$params = [];

if ($search !== '') {
    $where   .= " AND (c.name LIKE ? OR c.mobile LIKE ? OR c.emai l LIKE ? OR c.operator_name LIKE ?)";
    $like     = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($group !== '') {
    $where   .= " AND c.group_name = ?";
    $params[] = $group;
}
if ($regFrom !== '') {
    $where   .= " AND DATE(c.created_at) >= ?";
    $params[] = $regFrom;
}
if ($regTo !== '') {
    $where   .= " AND DATE(c.created_at) <= ?";
    $params[] = $regTo;
}

// Total count
try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM customers c $where");
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();
} catch (PDOException $e) {
    $totalRecords = 0;
}
$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page       = min($page, $totalPages);

// Paginated data
try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.total_amount, p.due_amount
        FROM customers c
        LEFT JOIN payments p ON c.id = p.customer_id
        $where
        ORDER BY c.id DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    $customers = [];
    error_log("Customer query failed: " . $e->getMessage());
}

// All distinct groups for dropdown
try {
    $allGroups = $pdo->query("SELECT DISTINCT group_name FROM customers WHERE group_name != '' ORDER BY group_name")
                     ->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $allGroups = [];
}

include __DIR__ . '/../views/partials/header.php';
include __DIR__ . '/../views/customers/index.php';
include __DIR__ . '/../views/partials/footer.php';
