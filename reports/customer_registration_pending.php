<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$validPerPage = [10, 25, 50, 100];
$perPage = in_array((int)($_GET['per_page'] ?? 10), $validPerPage) ? (int)($_GET['per_page'] ?? 10) : 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');
$regFrom = trim($_GET['reg_from'] ?? '');
$regTo = trim($_GET['reg_to'] ?? '');
$jsFrom = trim($_GET['js_from'] ?? '');
$jsTo = trim($_GET['js_to'] ?? '');
$group = trim($_GET['group'] ?? '');

$params = [];
$where = "WHERE c.status = 'pending'";

if ($search !== '') {
    $where .= " AND (c.name LIKE ? OR c.mobile LIKE ? OR c.group_name LIKE ? OR c.operator_name LIKE ? OR c.electricity_id LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like, $like);
}
if ($regFrom !== '') { $where .= " AND DATE(c.created_at) >= ?"; $params[] = $regFrom; }
if ($regTo !== '') { $where .= " AND DATE(c.created_at) <= ?"; $params[] = $regTo; }
if ($jsFrom !== '') { $where .= " AND DATE(c.updated_at) >= ?"; $params[] = $jsFrom; }
if ($jsTo !== '') { $where .= " AND DATE(c.updated_at) <= ?"; $params[] = $jsTo; }
if ($group !== '') { $where .= " AND c.group_name = ?"; $params[] = $group; }

try {
    $allGroups = $pdo->query("SELECT DISTINCT group_name FROM customers WHERE group_name != '' ORDER BY group_name")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) { $allGroups = []; }

try {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM customers c LEFT JOIN payments p ON c.id = p.customer_id $where");
    $cnt->execute($params);
    $totalRecords = (int)$cnt->fetchColumn();
} catch (PDOException $e) { $totalRecords = 0; }

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
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $rows = []; }

$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$startRecord = $totalRecords > 0 ? $offset + 1 : 0;
$endRecord = min($page * $perPage, $totalRecords);

$pageTitle = 'Pending Registration Customer';
include __DIR__ . '/../views/partials/header.php';
?>
<style>
.filter-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; font-size: 0.85rem; font-weight: bold; }
.filter-bar input[type="date"], .filter-bar select { border: 1px solid #ccc; border-radius: 4px; padding: 4px 8px; font-weight: normal; }
.dt-buttons .btn { background-color: #6c757d; color: white; border: none; padding: 5px 12px; margin-right: 2px; border-radius: 3px; font-size: 0.85rem; }
.table th { background-color: #f4f6f9; color: #333; font-size: 0.8rem; vertical-align: middle; }
.table td { font-size: 0.8rem; vertical-align: middle; }
</style>
<div style="padding: 15px;">
    <h4 style="margin-bottom: 15px; font-weight: normal;">
        Pending Registration Customer:-<?= $totalRecords > 0 ? "($totalRecords)" : "(0)" ?>
    </h4>
    
    <form method="GET" class="filter-bar">
        <label>Reg From: <input type="date" name="reg_from" value="<?= htmlspecialchars($regFrom) ?>"></label>
        <label>Reg To: <input type="date" name="reg_to" value="<?= htmlspecialchars($regTo) ?>"></label>
        <label>J S From: <input type="date" name="js_from" value="<?= htmlspecialchars($jsFrom) ?>"></label>
        <label>J S To: <input type="date" name="js_to" value="<?= htmlspecialchars($jsTo) ?>"></label>
        <label>Group: 
            <select name="group">
                <option value="">Select a Group</option>
                <?php foreach ($allGroups as $g): ?>
                    <option value="<?= htmlspecialchars($g) ?>" <?= $group === $g ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-primary btn-sm" style="background-color: #007bff; border: none; padding: 5px 15px;">Filter</button>
        <a href="<?= basename(__FILE__) ?>" class="btn btn-secondary btn-sm" style="background-color: #6c757d; border: none; padding: 5px 15px;">Reset</a>
    </form>

    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; align-items: center;">
        <div class="dt-buttons">
            <button class="btn" onclick="exportToExcel()">Excel</button>
            <button class="btn">PDF</button>
            <button class="btn" onclick="window.print()">Print</button>
        </div>
        <div style="display: flex; align-items: center; gap: 5px;">
            <label style="margin: 0; font-size: 0.85rem;">Search:</label>
            <form method="GET" style="margin: 0;">
                <?php foreach(['reg_from'=>$regFrom,'reg_to'=>$regTo,'js_from'=>$jsFrom,'js_to'=>$jsTo,'group'=>$group] as $k=>$v): if($v): ?>
                <input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>">
                <?php endif; endforeach; ?>
                <input type="text" name="search" style="border: 1px solid #ccc; border-radius: 4px; padding: 4px 8px; width: 180px;" value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>
    </div>

    <div class="table-responsive" style="background: #fff;">
        <table class="table table-bordered table-striped" id="myTable">
            <thead>
                <tr>
                    <th style="width: 40px;">Sr<br>No. <i class="fas fa-sort" style="color: #ccc; margin-left: 2px;"></i></th>
                    <th>Operator<br>Name <i class="fas fa-sort" style="color: #ccc; margin-left: 2px;"></i></th>
                    <th>Group Name <i class="fas fa-sort" style="color: #ccc; margin-left: 2px;"></i></th>
                    <th>Name <i class="fas fa-sort" style="color: #ccc; margin-left: 2px;"></i></th>
                    <th>Email <i class="fas fa-sort" style="color: #ccc; margin-left: 2px;"></i></th>
                    <th>Mobile <i class="fas fa-sort" style="color: #ccc; margin-left: 2px;"></i></th>
                    <th>IFSC<br>Code <i class="fas fa-sort" style="color: #ccc; margin-left: 2px;"></i></th>
                    <th>Electricity<br>Id <i class="fas fa-sort" style="color: #ccc; margin-left: 2px;"></i></th>
                    <th>Kw <i class="fas fa-sort" style="color: #ccc; margin-left: 2px;"></i></th>
                    <th>Account<br>Number <i class="fas fa-sort" style="color: #ccc; margin-left: 2px;"></i></th>
                    <th>Total<br>Amount <i class="fas fa-sort" style="color: #ccc; margin-left: 2px;"></i></th>
                    <th>Due<br>Amount <i class="fas fa-sort" style="color: #ccc; margin-left: 2px;"></i></th>
                    <th>Remarks <i class="fas fa-sort" style="color: #ccc; margin-left: 2px;"></i></th>
                    <th>Created<br>Date <i class="fas fa-sort" style="color: #ccc; margin-left: 2px;"></i></th>
                    <th>Updated<br>Date <i class="fas fa-sort" style="color: #ccc; margin-left: 2px;"></i></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows): $n = $startRecord; foreach ($rows as $r): ?>
                <tr>
                    <td><?= $n++ ?></td>
                    <td><?= htmlspecialchars($r['operator_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['group_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['mobile'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['ifsc_code'] ?? 'NA') ?: 'NA' ?></td>
                    <td><?= htmlspecialchars($r['electricity_id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['kw'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['account_number'] ?? 'NA') ?: 'NA' ?></td>
                    <td><?= number_format((float)$r['total_amount'], 2) ?></td>
                    <td><?= number_format((float)$r['due_amount'], 2) ?></td>
                    <td style="max-width: 200px; word-wrap: break-word;"><?= htmlspecialchars($r['remarks'] ?? '') ?></td>
                    <td><?= $r['created_at'] ?></td>
                    <td><?= substr($r['updated_at'] ?? "", 0, 16) ?></td>
                    <td>
                        <a href="/APN-Solar/customers/edit.php?id=<?= $r['id'] ?>&back=<?= str_replace('.php', '', 'customer_registration_pending.php') ?>" class="btn btn-warning btn-sm" style="background-color: #ffc107; border-color: #ffc107; color: #212529; font-weight: bold; padding: 2px 10px;">
                            Edit
                        </a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="16" style="text-align: center; padding: 20px;">No matching records found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportToExcel() {
    let html = '<table border="1">';
    const rows = document.querySelectorAll('#myTable tr');
    rows.forEach(r => {
        let cols = r.querySelectorAll('th,td');
        html += '<tr>';
        cols.forEach((c, i) => { if (i !== cols.length - 1) html += '<td>' + c.innerText + '</td>'; });
        html += '</tr>';
    });
    html += '</table>';
    const blob = new Blob([html], {type: 'application/vnd.ms-excel'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'Pending Registration Customer.xls';
    a.click();
}
</script>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>