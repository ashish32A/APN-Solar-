<?php
$reports = [
    [
        'file' => 'pending_installation.php',
        'title' => 'Pending Installation',
        'where' => "WHERE i.material_dispatch_1st IS NOT NULL AND (i.installation_date IS NULL OR i.installation_date = '')",
        'cols' => "i.material_dispatch_1st, i.material_dispatch_2nd, i.installer_name, i.installation_date, i.remarks AS install_remarks, i.updated_at AS install_updated",
        'th' => '<th>Material Dispatch 1st</th><th>Material Dispatch 2nd</th><th>Installer Name</th><th>Installation Date</th><th>Remarks</th><th>Last Updated on</th>',
        'td' => '<td><?= $r["material_dispatch_1st"] ?></td><td><?= $r["material_dispatch_2nd"] ?></td><td><?= $r["installer_name"] ?></td><td><?= $r["installation_date"] ?></td><td><?= $r["install_remarks"] ?></td><td><?= substr($r["install_updated"] ?? "", 0, 10) ?></td>',
        'edit' => 'customer_status_update.php'
    ],
    [
        'file' => 'pending_net_metering.php',
        'title' => 'Pending Netmetering',
        'where' => "WHERE i.meter_installation IS NULL OR i.meter_installation = 'No'",
        'cols' => "i.invoice_no, i.installation_date, i.meter_installation, i.meter_configuration, i.remarks AS install_remarks, i.updated_at AS install_updated",
        'th' => '<th>Invoice No.</th><th>Installation Date</th><th>Meter Installation</th><th>Meter Configuration</th><th>Remarks</th><th>Last Updated on</th>',
        'td' => '<td><?= $r["invoice_no"] ?></td><td><?= $r["installation_date"] ?></td><td><?= $r["meter_installation"] ?></td><td><?= $r["meter_configuration"] ?></td><td><?= $r["install_remarks"] ?></td><td><?= substr($r["install_updated"] ?? "", 0, 10) ?></td>',
        'edit' => 'customer_status_update.php'
    ],
    [
        'file' => 'pending_online_installation.php',
        'title' => 'Pending Online Installations',
        'where' => "WHERE i.online_installer_name IS NULL OR i.online_installer_name = ''",
        'cols' => "i.invoice_no, i.installer_name, i.installation_date, i.meter_installation, i.meter_configuration, i.dcr_certificate, i.online_installer_name, i.remarks AS install_remarks, i.updated_at AS install_updated",
        'th' => '<th>Invoice No.</th><th>Installer Name</th><th>Installation Date</th><th>Meter Installation</th><th>Meter Configuration</th><th>Dcr Certificate</th><th>Online Installer</th><th>Last Updated on</th><th>Remarks</th>',
        'td' => '<td><?= $r["invoice_no"] ?></td><td><?= $r["installer_name"] ?></td><td><?= $r["installation_date"] ?></td><td><?= $r["meter_installation"] ?></td><td><?= $r["meter_configuration"] ?></td><td><?= $r["dcr_certificate"] ?></td><td><?= $r["online_installer_name"] ?></td><td><?= substr($r["install_updated"] ?? "", 0, 10) ?></td><td><?= $r["install_remarks"] ?></td>',
        'edit' => 'customer_status_update.php'
    ],
    [
        'file' => 'pending_subsidy_first.php',
        'title' => 'Pending Customer First Subsidy',
        'where' => "WHERE i.subsidy_1st_status IS NULL OR i.subsidy_1st_status = 'Pending' OR i.subsidy_1st_status = ''",
        'cols' => "p.total_amount, p.due_amount, i.remarks AS install_remarks, i.updated_at AS install_updated",
        'th' => '<th>Total Amount</th><th>Due Amount</th><th>Remarks</th><th>Updated date</th>',
        'td' => '<td><?= number_format((float)$r["total_amount"], 2) ?></td><td><?= number_format((float)$r["due_amount"], 2) ?></td><td><?= $r["install_remarks"] ?></td><td><?= substr($r["install_updated"] ?? "", 0, 10) ?></td>',
        'edit' => 'customer_status_update.php'
    ],
    [
        'file' => 'pending_subsidy_second.php',
        'title' => 'Pending Customer Second Subsidy',
        'where' => "WHERE i.subsidy_2nd_status IS NULL OR i.subsidy_2nd_status = 'Pending' OR i.subsidy_2nd_status = ''",
        'cols' => "p.total_amount, p.due_amount, i.remarks AS install_remarks, i.updated_at AS install_updated",
        'th' => '<th>Total Amount</th><th>Due Amount</th><th>Remarks</th><th>Updated date</th>',
        'td' => '<td><?= number_format((float)$r["total_amount"], 2) ?></td><td><?= number_format((float)$r["due_amount"], 2) ?></td><td><?= $r["install_remarks"] ?></td><td><?= substr($r["install_updated"] ?? "", 0, 10) ?></td>',
        'edit' => 'customer_status_update.php'
    ],
    [
        'file' => 'pending_account_number.php',
        'title' => 'Pending account number',
        'where' => "WHERE c.account_number IS NULL OR c.account_number = ''",
        'cols' => "c.email, c.account_number, c.ifsc_code, c.jan_samarth, c.remarks, c.updated_at AS c_updated_at",
        'th' => '<th>Email</th><th>Bank name</th><th>Ifsc code</th><th>Branch name</th><th>J S date</th><th>Remarks</th><th>Updated date</th>',
        'td' => '<td><?= $r["email"] ?></td><td><?= $r["account_number"] ?></td><td><?= $r["ifsc_code"] ?></td><td></td><td><?= $r["jan_samarth"] ?></td><td><?= $r["remarks"] ?></td><td><?= substr($r["c_updated_at"] ?? "", 0, 10) ?></td>',
        'edit' => 'edit.php'
    ],
    [
        'file' => 'first_materials_dispatched.php',
        'title' => 'Material Dispatch One Customer List',
        'where' => "WHERE i.material_dispatch_1st IS NULL OR i.material_dispatch_1st = ''",
        'cols' => "c.address, p.payment_received, i.invoice_no, i.material_dispatch_1st, i.material_dispatch_2nd, i.installer_name, i.installation_date, i.remarks AS install_remarks, i.updated_at AS install_updated",
        'th' => '<th>Address</th><th>Payment Received</th><th>Invoice No.</th><th>Material Dispatch 1st</th><th>Material Dispatch 2nd</th><th>Installer Name</th><th>Installation Date</th><th>Remarks</th><th>Last Updated on</th>',
        'td' => '<td><?= $r["address"] ?></td><td><?= number_format((float)$r["payment_received"], 2) ?></td><td><?= $r["invoice_no"] ?></td><td><?= $r["material_dispatch_1st"] ?></td><td><?= $r["material_dispatch_2nd"] ?></td><td><?= $r["installer_name"] ?></td><td><?= $r["installation_date"] ?></td><td><?= $r["install_remarks"] ?></td><td><?= substr($r["install_updated"] ?? "", 0, 10) ?></td>',
        'edit' => 'customer_status_update.php'
    ],
    [
        'file' => 'second_materials_dispatched.php',
        'title' => 'Material Dispatch Two Customer List',
        'where' => "WHERE i.material_dispatch_1st IS NOT NULL AND i.material_dispatch_1st != '' AND (i.material_dispatch_2nd IS NULL OR i.material_dispatch_2nd = '')",
        'cols' => "c.address, p.payment_received, i.invoice_no, i.material_dispatch_1st, i.material_dispatch_2nd, i.installer_name, i.installation_date, i.remarks AS install_remarks, i.updated_at AS install_updated",
        'th' => '<th>Address</th><th>Payment Received</th><th>Invoice No.</th><th>Material Dispatch 1st</th><th>Material Dispatch 2nd</th><th>Installer Name</th><th>Installation Date</th><th>Remarks</th><th>Last Updated on</th>',
        'td' => '<td><?= $r["address"] ?></td><td><?= number_format((float)$r["payment_received"], 2) ?></td><td><?= $r["invoice_no"] ?></td><td><?= $r["material_dispatch_1st"] ?></td><td><?= $r["material_dispatch_2nd"] ?></td><td><?= $r["installer_name"] ?></td><td><?= $r["installation_date"] ?></td><td><?= $r["install_remarks"] ?></td><td><?= substr($r["install_updated"] ?? "", 0, 10) ?></td>',
        'edit' => 'customer_status_update.php'
    ],
    [
        'file' => 'customer_registration_pending.php',
        'title' => 'Customer Registration Pending',
        'where' => "WHERE c.status = 'pending'",
        'cols' => "c.status, c.remarks, c.updated_at AS c_updated_at",
        'th' => '<th>Status</th><th>Remarks</th><th>Updated date</th>',
        'td' => '<td><?= strtoupper($r["status"]) ?></td><td><?= $r["remarks"] ?></td><td><?= substr($r["c_updated_at"] ?? "", 0, 10) ?></td>',
        'edit' => 'edit.php'
    ],
    [
        'file' => 'customer_jan_samarth_pending.php',
        'title' => 'Customer Jan Samarth Pending',
        'where' => "WHERE c.jan_samarth IS NULL OR c.jan_samarth = 'Pending' OR c.jan_samarth = 'No' OR c.jan_samarth = ''",
        'cols' => "c.jan_samarth, c.updated_at AS c_updated_at",
        'th' => '<th>J S Date / Status</th><th>Updated date</th>',
        'td' => '<td><?= $r["jan_samarth"] ?></td><td><?= substr($r["c_updated_at"] ?? "", 0, 10) ?></td>',
        'edit' => 'edit.php'
    ]
];

$template = <<<'HTML'
<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$validPerPage = [10, 25, 50, 100];
$perPage = in_array((int)($_GET['per_page'] ?? 10), $validPerPage) ? (int)$_GET['per_page'] : 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');
$params = [];
$where = "{{WHERE}}";

if ($search !== '') {
    $where .= " AND (c.name LIKE ? OR c.mobile LIKE ? OR c.group_name LIKE ? OR c.operator_name LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}

try {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM customers c LEFT JOIN installations i ON c.id = i.customer_id LEFT JOIN payments p ON c.id = p.customer_id $where");
    $cnt->execute($params);
    $totalRecords = (int)$cnt->fetchColumn();
} catch (PDOException $e) { $totalRecords = 0; }

try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.operator_name, c.group_name, c.name, c.mobile, c.district_name, c.kw, c.electricity_id,
        {{COLS}}
        FROM customers c
        LEFT JOIN installations i ON c.id = i.customer_id
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

$pageTitle = '{{TITLE}}';
include __DIR__ . '/../views/partials/header.php';
?>

<div style="padding: 15px;">
    <h2 style="font-size: 1.5rem; margin-bottom: 15px; font-weight: normal;">
        {{TITLE}}:-<?= $totalRecords > 0 ? "($totalRecords)" : "" ?>
    </h2>
    
    <div style="display: flex; justify-content: space-between; margin-bottom: 15px; align-items: center;">
        <button class="btn btn-success" onclick="exportToExcel()">Excel</button>
        <div style="display: flex; align-items: center; gap: 10px;">
            <label style="margin: 0;">Search:</label>
            <form method="GET" style="margin: 0; display: inline-flex;">
                <input type="text" name="search" class="form-control" style="width: 200px; display: inline-block;" value="<?= htmlspecialchars($search) ?>" placeholder="">
            </form>
        </div>
    </div>

    <div class="table-responsive" style="background: #fff;">
        <table class="table table-bordered table-striped" style="font-size: 0.9rem;">
            <thead style="background-color: #f8f9fa;">
                <tr>
                    <th style="width: 60px;">Sr No. <i class="fas fa-sort" style="color: #ccc; margin-left: 5px;"></i></th>
                    <?php if ("{{TITLE}}" !== "Pending Online Installations"): ?>
                    <th>Operator Name <i class="fas fa-sort" style="color: #ccc; margin-left: 5px;"></i></th>
                    <?php endif; ?>
                    <th>Group Name <i class="fas fa-sort" style="color: #ccc; margin-left: 5px;"></i></th>
                    <th>Name <i class="fas fa-sort" style="color: #ccc; margin-left: 5px;"></i></th>
                    <?php if (strpos("{{TH}}", "<th>Email</th>") === false): ?>
                    <th>Mobile <i class="fas fa-sort" style="color: #ccc; margin-left: 5px;"></i></th>
                    <?php endif; ?>
                    <th>District <i class="fas fa-sort" style="color: #ccc; margin-left: 5px;"></i></th>
                    <th>Electricity Id <i class="fas fa-sort" style="color: #ccc; margin-left: 5px;"></i></th>
                    <th>Kw <i class="fas fa-sort" style="color: #ccc; margin-left: 5px;"></i></th>
                    {{TH}}
                    <th style="width: 80px;">Action <i class="fas fa-sort" style="color: #ccc; margin-left: 5px;"></i></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows): $n = $startRecord; foreach ($rows as $r): ?>
                <tr>
                    <td><?= $n++ ?></td>
                    <?php if ("{{TITLE}}" !== "Pending Online Installations"): ?>
                    <td><?= htmlspecialchars($r['operator_name'] ?? '') ?></td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars($r['group_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <?php if (strpos("{{TH}}", "<th>Email</th>") === false): ?>
                    <td><?= htmlspecialchars($r['mobile'] ?? '') ?></td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars($r['district_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['electricity_id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['kw'] ?? '') ?></td>
                    {{TD}}
                    <td>
                        <a href="/APN-Solar/customers/{{EDIT}}?id=<?= $r['id'] ?>&back=<?= str_replace('.php', '', '{{FILE}}') ?>" class="btn btn-warning btn-sm" style="background-color: #ffc107; border-color: #ffc107; color: #212529;">
                            Edit
                        </a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="20" style="text-align: center; padding: 20px;">No matching records found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
        <div>
            Showing <?= $startRecord ?> to <?= $endRecord ?> of <?= $totalRecords ?> entries
        </div>
        <div style="display: flex; gap: 5px;">
            <a href="?page=<?= max(1, $page - 1) ?>&search=<?= urlencode($search) ?>" class="btn btn-light" style="border: 1px solid #ddd; <?= $page <= 1 ? 'pointer-events: none; color: #aaa;' : '' ?>">Previous</a>
            <a href="?page=<?= min($totalPages, $page + 1) ?>&search=<?= urlencode($search) ?>" class="btn btn-light" style="border: 1px solid #ddd; <?= $page >= $totalPages ? 'pointer-events: none; color: #aaa;' : '' ?>">Next</a>
        </div>
    </div>
</div>

<script>
function exportToExcel() {
    let html = '<table border="1">';
    const rows = document.querySelectorAll('table tr');
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
    a.download = '{{TITLE}}.xls';
    a.click();
}
</script>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
HTML;

foreach ($reports as $r) {
    $c = $template;
    $c = str_replace('{{TITLE}}', $r['title'], $c);
    $c = str_replace('{{WHERE}}', $r['where'], $c);
    $c = str_replace('{{COLS}}', $r['cols'], $c);
    $c = str_replace('{{TH}}', $r['th'], $c);
    $c = str_replace('{{TD}}', $r['td'], $c);
    $c = str_replace('{{FILE}}', $r['file'], $c);
    $c = str_replace('{{EDIT}}', $r['edit'], $c);
    file_put_contents('c:/xampp/htdocs/APN-Solar/reports/' . $r['file'], $c);
}
echo "Done.";
