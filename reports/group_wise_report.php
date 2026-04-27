<?php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$regFrom = trim($_GET['reg_from'] ?? '');
$regTo   = trim($_GET['reg_to']   ?? '');
$search  = trim($_GET['search']   ?? '');

$where  = "WHERE 1=1";
$params = [];
if ($regFrom !== '') { $where .= " AND DATE(c.created_at) >= ?"; $params[] = $regFrom; }
if ($regTo   !== '') { $where .= " AND DATE(c.created_at) <= ?"; $params[] = $regTo; }

// Group-wise summary
try {
    $stmt = $pdo->prepare("
        SELECT
            COALESCE(c.group_name,'(No Group)') AS group_name,
            COUNT(DISTINCT c.id)                AS total_customers,
            SUM(p.total_amount)                 AS grand_total,
            SUM(p.payment_received)             AS grand_recv,
            SUM(p.due_amount)                   AS grand_due,
            SUM(CASE WHEN p.due_amount <= 0 AND p.payment_received > 0 THEN 1 ELSE 0 END) AS fully_paid,
            SUM(CASE WHEN p.due_amount > 0 AND p.payment_received > 0 THEN 1 ELSE 0 END)  AS partial,
            SUM(CASE WHEN p.payment_received = 0 OR p.payment_received IS NULL THEN 1 ELSE 0 END) AS not_paid
        FROM customers c
        LEFT JOIN payments p ON c.id = p.customer_id
        $where
        GROUP BY COALESCE(c.group_name,'(No Group)')
        ORDER BY total_customers DESC
    ");
    $stmt->execute($params);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $groups = []; }

// Grand totals
$grandTotal   = array_sum(array_column($groups, 'grand_total'));
$grandRecv    = array_sum(array_column($groups, 'grand_recv'));
$grandDue     = array_sum(array_column($groups, 'grand_due'));
$grandCust    = array_sum(array_column($groups, 'total_customers'));

// Per-group customer detail (for drill-down)
$groupName = trim($_GET['group'] ?? '');
$detailRows = [];
if ($groupName !== '') {
    $dWhere  = $where . " AND c.group_name = ?";
    $dParams = array_merge($params, [$groupName]);
    try {
        $ds = $pdo->prepare("
            SELECT c.id, c.operator_name, c.name, c.mobile, c.district_name,
                   c.electricity_id, c.kw, c.status, c.created_at,
                   p.total_amount, p.payment_received, p.due_amount
            FROM customers c
            LEFT JOIN payments p ON c.id = p.customer_id
            $dWhere ORDER BY c.id DESC
        ");
        $ds->execute($dParams);
        $detailRows = $ds->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $detailRows = []; }
}

// Search filter on groups
if ($search !== '') {
    $groups = array_filter($groups, fn($g) => stripos($g['group_name'], $search) !== false);
}

$pageTitle = 'Group Wise Report';
include __DIR__ . '/../views/partials/header.php';
?>
<style>
.rpt-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px;}
.rpt-card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;display:flex;align-items:center;gap:10px;}
.rc-icon{width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;}
.rc-lbl{font-size:.65rem;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:2px;}
.rc-val{font-size:.98rem;font-weight:800;color:#1e293b;}
.ic-cust{background:#dbeafe;color:#2563eb;}.ic-tot{background:#fef3c7;color:#d97706;}.ic-recv{background:#dcfce7;color:#15803d;}.ic-due{background:#fee2e2;color:#dc2626;}

.rpt-filters{background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:10px 14px;margin-bottom:10px;}
.rpt-filters form{display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;}
.rf-fg{display:flex;flex-direction:column;gap:2px;}
.rf-fg label{font-size:.7rem;font-weight:700;color:#64748b;}
.rf-fg input{padding:5px 8px;border:1px solid #ced4da;border-radius:4px;font-size:.8rem;font-family:inherit;width:130px;}

.btn{display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border-radius:4px;font-size:.78rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:opacity .15s;text-decoration:none;white-space:nowrap;}
.btn-filter{background:#007bff;color:#fff;}.btn-reset{background:#6c757d;color:#fff;}.btn-excel{background:#1d6f42;color:#fff;}
.btn:hover{opacity:.85;}
.dt-bar{display:flex;gap:6px;margin-bottom:10px;}
.search-box{display:flex;align-items:center;gap:5px;font-size:.8rem;margin-bottom:8px;}
.search-box input{padding:5px 9px;border:1px solid #ced4da;border-radius:4px;width:200px;font-size:.8rem;font-family:inherit;}

/* Group cards grid */
.group-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:12px;margin-bottom:20px;}
.group-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:16px 18px;transition:box-shadow .15s;}
.group-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.08);}
.gc-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
.gc-name{font-size:.95rem;font-weight:700;color:#1e293b;}
.gc-count{background:#dbeafe;color:#1e40af;border-radius:20px;padding:2px 10px;font-size:.72rem;font-weight:700;}
.gc-row{display:flex;justify-content:space-between;font-size:.78rem;padding:4px 0;border-bottom:1px solid #f1f5f9;}
.gc-row:last-child{border-bottom:none;}
.gc-label{color:#64748b;}
.gc-val{font-weight:700;color:#1e293b;}
.gc-footer{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;}
.prog-bar{height:6px;border-radius:3px;background:#f1f5f9;margin-top:6px;overflow:hidden;}
.prog-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,#22c55e,#16a34a);}

/* Detail table */
.table-wrap{background:#fff;border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;margin-top:10px;}
.table-responsive{overflow-x:auto;}
.table{width:100%;border-collapse:collapse;font-size:.77rem;color:#1e293b;}
.table thead th{background:#f0f4f8;padding:7px 8px;font-weight:700;font-size:.67rem;text-transform:uppercase;color:#64748b;border-bottom:2px solid #e2e8f0;white-space:nowrap;text-align:left;}
.table tbody td{padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.table tbody tr:hover{background:#f8fbff;}
.table tbody tr:last-child td{border-bottom:none;}
.text-right{text-align:right;}
.text-danger{color:#dc2626;font-weight:600;}.text-success{color:#15803d;font-weight:600;}
.due-badge{background:#fee2e2;color:#dc2626;border-radius:20px;padding:1px 8px;font-size:.67rem;font-weight:700;display:inline-block;}
.recv-badge{background:#dcfce7;color:#15803d;border-radius:20px;padding:1px 8px;font-size:.67rem;font-weight:700;display:inline-block;}

.back-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:4px;background:#495057;color:#fff;font-size:.8rem;font-weight:600;text-decoration:none;margin-bottom:10px;}
.back-btn:hover{opacity:.85;}
</style>

<h2 style="font-size:1.1rem;font-weight:700;margin-bottom:12px;color:#1e293b;">
    <i class="fas fa-layer-group" style="color:#3b82f6;margin-right:7px;"></i>
    Group Wise Report
    <span style="font-weight:400;color:#64748b;font-size:.9rem;">(<?php echo count($groups); ?> groups)</span>
</h2>

<!-- Summary Cards -->
<div class="rpt-cards">
    <div class="rpt-card"><div class="rc-icon ic-cust"><i class="fas fa-users"></i></div><div><div class="rc-lbl">Total Customers</div><div class="rc-val"><?php echo number_format($grandCust); ?></div></div></div>
    <div class="rpt-card"><div class="rc-icon ic-tot"><i class="fas fa-wallet"></i></div><div><div class="rc-lbl">Total Amount</div><div class="rc-val">₹<?php echo number_format($grandTotal,2); ?></div></div></div>
    <div class="rpt-card"><div class="rc-icon ic-recv"><i class="fas fa-check-circle"></i></div><div><div class="rc-lbl">Received</div><div class="rc-val" style="color:#15803d;">₹<?php echo number_format($grandRecv,2); ?></div></div></div>
    <div class="rpt-card"><div class="rc-icon ic-due"><i class="fas fa-exclamation-circle"></i></div><div><div class="rc-lbl">Due</div><div class="rc-val" style="color:#dc2626;">₹<?php echo number_format($grandDue,2); ?></div></div></div>
</div>

<!-- Filters -->
<div class="rpt-filters">
    <form method="GET" action="/APN-Solar/reports/group_wise_report.php">
        <div class="rf-fg"><label>Reg From:</label><input type="date" name="reg_from" value="<?php echo htmlspecialchars($regFrom); ?>"></div>
        <div class="rf-fg"><label>Reg To:</label><input type="date" name="reg_to" value="<?php echo htmlspecialchars($regTo); ?>"></div>
        <div style="display:flex;gap:5px;align-items:flex-end;">
            <button type="submit" class="btn btn-filter"><i class="fas fa-filter"></i> Filter</button>
            <a href="/APN-Solar/reports/group_wise_report.php" class="btn btn-reset"><i class="fas fa-undo"></i> Reset</a>
        </div>
    </form>
</div>

<div class="dt-bar">
    <button class="btn btn-excel" onclick="gwExcel()"><i class="fas fa-file-excel"></i> Export Excel</button>
</div>

<div class="search-box">
    <span>Search Group:</span>
    <input type="text" id="gwSearch" placeholder="Group name..." oninput="gwFilter(this.value)" value="<?php echo htmlspecialchars($search); ?>">
</div>

<?php if ($groupName !== ''): ?>
<!-- Detail drill-down -->
<a href="/APN-Solar/reports/group_wise_report.php?<?php echo http_build_query(array_filter(['reg_from'=>$regFrom,'reg_to'=>$regTo],fn($v)=>$v!=='')); ?>" class="back-btn">
    <i class="fas fa-arrow-left"></i> Back to All Groups
</a>
<h3 style="font-size:.95rem;font-weight:700;color:#1e293b;margin-bottom:8px;">
    <i class="fas fa-users" style="color:#3b82f6;margin-right:5px;"></i>
    <?php echo htmlspecialchars($groupName); ?> — <?php echo count($detailRows); ?> customers
</h3>
<div class="table-wrap">
    <div class="table-responsive">
        <table class="table" id="detailTable">
            <thead>
                <tr>
                    <th>Sr</th><th>Operator</th><th>Name</th><th>Mobile</th>
                    <th>District</th><th>Elec. ID</th><th>KW</th>
                    <th class="text-right">Total Amt</th>
                    <th class="text-right">Received</th>
                    <th class="text-right">Due Amt</th>
                    <th>Reg Date</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($detailRows): $n=1; foreach ($detailRows as $r):
                $due=(float)($r['due_amount']??0); $recv=(float)($r['payment_received']??0); ?>
                <tr>
                    <td><?php echo $n++; ?></td>
                    <td><?php echo htmlspecialchars($r['operator_name']??''); ?></td>
                    <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($r['mobile']??''); ?></td>
                    <td><?php echo htmlspecialchars($r['district_name']??''); ?></td>
                    <td><?php echo htmlspecialchars($r['electricity_id']??''); ?></td>
                    <td><?php echo htmlspecialchars($r['kw']??''); ?></td>
                    <td class="text-right">₹<?php echo number_format((float)($r['total_amount']??0),2); ?></td>
                    <td class="text-right"><span class="recv-badge">₹<?php echo number_format($recv,2); ?></span></td>
                    <td class="text-right"><?php if($due>0): ?><span class="due-badge">₹<?php echo number_format($due,2); ?></span><?php else: ?>₹0.00<?php endif; ?></td>
                    <td style="white-space:nowrap;font-size:.71rem;"><?php echo htmlspecialchars(substr($r['created_at']??'',0,10)); ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="11" style="text-align:center;padding:30px;color:#94a3b8;">No customers found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<!-- Group cards -->
<div class="group-grid" id="gwGrid">
    <?php foreach ($groups as $g):
        $total  = (float)($g['grand_total']??0);
        $recv   = (float)($g['grand_recv']??0);
        $due    = (float)($g['grand_due']??0);
        $cust   = (int)$g['total_customers'];
        $pct    = $total > 0 ? round(($recv/$total)*100) : 0;
        $qStr   = http_build_query(array_filter(['group'=>$g['group_name'],'reg_from'=>$regFrom,'reg_to'=>$regTo],fn($v)=>$v!==''));
    ?>
    <div class="group-card" data-name="<?php echo strtolower(htmlspecialchars($g['group_name'])); ?>">
        <div class="gc-head">
            <div class="gc-name"><i class="fas fa-layer-group" style="color:#3b82f6;margin-right:5px;"></i><?php echo htmlspecialchars($g['group_name']); ?></div>
            <span class="gc-count"><?php echo number_format($cust); ?> Customers</span>
        </div>
        <div class="gc-row"><span class="gc-label">Total Amount</span><span class="gc-val">₹<?php echo number_format($total,2); ?></span></div>
        <div class="gc-row"><span class="gc-label">Received</span><span class="gc-val" style="color:#15803d;">₹<?php echo number_format($recv,2); ?></span></div>
        <div class="gc-row"><span class="gc-label">Due Amount</span><span class="gc-val" style="color:<?php echo $due>0?'#dc2626':'#15803d'; ?>;">₹<?php echo number_format($due,2); ?></span></div>
        <div class="gc-row"><span class="gc-label">Fully Paid</span><span class="gc-val"><?php echo (int)$g['fully_paid']; ?></span></div>
        <div class="gc-row"><span class="gc-label">Partial Payment</span><span class="gc-val"><?php echo (int)$g['partial']; ?></span></div>
        <div class="gc-row"><span class="gc-label">Not Paid</span><span class="gc-val" style="color:#dc2626;"><?php echo (int)$g['not_paid']; ?></span></div>
        <div class="prog-bar"><div class="prog-fill" style="width:<?php echo $pct; ?>%;"></div></div>
        <div style="font-size:.68rem;color:#64748b;margin-top:3px;"><?php echo $pct; ?>% collected</div>
        <div class="gc-footer">
            <a href="/APN-Solar/reports/group_wise_report.php?<?php echo $qStr; ?>" class="btn btn-filter" style="font-size:.72rem;padding:4px 10px;">
                <i class="fas fa-eye"></i> View Customers
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($groups)): ?>
    <div style="grid-column:1/-1;text-align:center;padding:40px;color:#94a3b8;">
        <i class="fas fa-layer-group" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
        No group data found.
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
function gwFilter(q){q=q.toLowerCase();document.querySelectorAll('#gwGrid .group-card').forEach(c=>{c.style.display=q===''||c.dataset.name.includes(q)?'':'none';});}
function gwExcel(){
    let html='<table border="1"><thead><tr><th>Group</th><th>Customers</th><th>Total Amount</th><th>Received</th><th>Due</th><th>Fully Paid</th><th>Partial</th><th>Not Paid</th></tr></thead><tbody>';
    <?php foreach($groups as $g): ?>
    html+=`<tr><td><?php echo addslashes($g['group_name']); ?></td><td><?php echo $g['total_customers']; ?></td><td><?php echo number_format((float)($g['grand_total']??0),2); ?></td><td><?php echo number_format((float)($g['grand_recv']??0),2); ?></td><td><?php echo number_format((float)($g['grand_due']??0),2); ?></td><td><?php echo (int)$g['fully_paid']; ?></td><td><?php echo (int)$g['partial']; ?></td><td><?php echo (int)$g['not_paid']; ?></td></tr>`;
    <?php endforeach; ?>
    html+='<tr style="font-weight:bold"><td>TOTAL</td><td><?php echo $grandCust; ?></td><td><?php echo number_format($grandTotal,2); ?></td><td><?php echo number_format($grandRecv,2); ?></td><td><?php echo number_format($grandDue,2); ?></td><td></td><td></td><td></td></tr>';
    html+='</tbody></table>';
    const b=new Blob([html],{type:'application/vnd.ms-excel;charset=utf-8;'});
    const a=Object.assign(document.createElement('a'),{href:URL.createObjectURL(b),download:'group_wise_report_'+new Date().toISOString().slice(0,10)+'.xls'});
    a.click();URL.revokeObjectURL(a.href);
}
</script>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
