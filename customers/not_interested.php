<?php
// customers/not_interested.php — Not Interested Customers list

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$pageTitle    = 'Not Interested Customers';
$validPerPage = [10, 25, 50, 100];
$requestedPer = (int)($_GET['per_page'] ?? 25);
$perPage      = in_array($requestedPer, $validPerPage) ? $requestedPer : 25;
$page         = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($page - 1) * $perPage;

// Filters
$group   = trim($_GET['group']    ?? '');
$regFrom = $_GET['reg_from']      ?? '';
$regTo   = $_GET['reg_to']        ?? '';
$jsFrom  = $_GET['js_from']       ?? '';  // updated_at (when marked not-interested)
$jsTo    = $_GET['js_to']         ?? '';

$where  = "WHERE c.status = 'not_interested'";
$params = [];

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
if ($jsFrom !== '') {
    $where   .= " AND DATE(c.updated_at) >= ?";
    $params[] = $jsFrom;
}
if ($jsTo !== '') {
    $where   .= " AND DATE(c.updated_at) <= ?";
    $params[] = $jsTo;
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
        ORDER BY c.updated_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    $customers = [];
    error_log("Not-interested query failed: " . $e->getMessage());
}

// All distinct groups (from not-interested customers for dropdown)
try {
    $allGroups = $pdo->query(
        "SELECT DISTINCT group_name FROM customers WHERE status='not_interested' AND group_name != '' ORDER BY group_name"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $allGroups = [];
}

include __DIR__ . '/../views/partials/header.php';
include __DIR__ . '/../views/customers/not_interested.php';
include __DIR__ . '/../views/partials/footer.php';
