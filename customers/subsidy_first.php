<?php
// customers/subsidy_first.php — Customers who received First Subsidy

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

// ── Filters ────────────────────────────────────────────────────────────────────
$regFrom  = trim($_GET['reg_from']  ?? '');
$regTo    = trim($_GET['reg_to']    ?? '');
$group    = trim($_GET['group']     ?? '');
$district = trim($_GET['district']  ?? '');
$search   = trim($_GET['search']    ?? '');

$validPerPage = [10, 25, 50, 100];
$reqPerPage   = (int)($_GET['per_page'] ?? 25);
$perPage      = in_array($reqPerPage, $validPerPage) ? $reqPerPage : 25;
$page         = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($page - 1) * $perPage;

$where  = "WHERE i.subsidy_1st_status = 'Yes'";
$params = [];

if ($regFrom  !== '') { $where .= " AND DATE(c.created_at) >= ?";  $params[] = $regFrom; }
if ($regTo    !== '') { $where .= " AND DATE(c.created_at) <= ?";  $params[] = $regTo; }
if ($group    !== '') { $where .= " AND c.group_name = ?";         $params[] = $group; }
if ($district !== '') { $where .= " AND c.district_name LIKE ?";   $params[] = "%$district%"; }
if ($search   !== '') {
    $where .= " AND (c.name LIKE ? OR c.mobile LIKE ? OR c.electricity_id LIKE ? OR c.operator_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}

// Total count
try {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM customers c INNER JOIN installations i ON c.id = i.customer_id $where");
    $cnt->execute($params);
    $totalRecords = (int)$cnt->fetchColumn();
} catch (PDOException $e) { $totalRecords = 0; }

$totalPages  = max(1, (int)ceil($totalRecords / $perPage));
$page        = min($page, $totalPages);
$startRecord = $totalRecords > 0 ? ($page - 1) * $perPage + 1 : 0;
$endRecord   = min($page * $perPage, $totalRecords);

// Summary
try {
    $totStmt = $pdo->prepare("
        SELECT SUM(p.total_amount) AS total, SUM(p.payment_received) AS received, SUM(p.due_amount) AS due
        FROM customers c
        INNER JOIN installations i ON c.id = i.customer_id
        LEFT JOIN payments p ON c.id = p.customer_id
        $where
    ");
    $totStmt->execute($params);
    $totals = $totStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $totals = ['total'=>0,'received'=>0,'due'=>0]; }

// Data
try {
    $sql = "
        SELECT c.id, c.operator_name, c.group_name, c.name, c.email, c.mobile,
               c.district_name, c.electricity_id, c.kw, c.updated_at,
               p.total_amount, p.due_amount,
               i.remarks AS install_remarks, i.updated_at AS install_updated
        FROM customers c
        INNER JOIN installations i ON c.id = i.customer_id
        LEFT JOIN payments p ON c.id = p.customer_id
        $where
        ORDER BY i.updated_at DESC, c.id DESC
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $customers = []; }

// Groups dropdown
$allGroups = $pdo->query("SELECT DISTINCT group_name FROM customers WHERE group_name != '' ORDER BY group_name")
                 ->fetchAll(PDO::FETCH_COLUMN);

// Flash
$flash = null;
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

function sf1Url(array $ov = []): string {
    $p = array_merge([
        'reg_from' => $_GET['reg_from'] ?? '', 'reg_to'   => $_GET['reg_to']   ?? '',
        'group'    => $_GET['group']    ?? '', 'district' => $_GET['district'] ?? '',
        'search'   => $_GET['search']   ?? '', 'per_page' => $_GET['per_page'] ?? 25,
        'page'     => $_GET['page']     ?? 1,
    ], $ov);
    return '/APN-Solar/customers/subsidy_first.php?' . http_build_query(array_filter($p, fn($v) => $v !== ''));
}

$pageTitle = 'Received Subsidy First';
include __DIR__ . '/../views/partials/header.php';
?>

<style>
.pl-filters{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;margin-bottom:12px;}
.pl-filters form{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;}
.pl-fg{display:flex;flex-direction:column;gap:3px;}
.pl-fg label{font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;}
.pl-fg input,.pl-fg select{padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:.82rem;font-family:inherit;color:#1e293b;}
.pl-fg input[type="date"]{width:135px;}.pl-fg input[type="text"]{width:150px;}.pl-fg select{min-width:145px;}
.btn{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:6px;font-size:.8rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:opacity .15s;text-decoration:none;white-space:nowrap;}
.btn-filter{background:#3b82f6;color:#fff;}.btn-reset{background:#64748b;color:#fff;}.btn-excel{background:#1d6f42;color:#fff;}.btn-edit{background:#f59e0b;color:#fff;}.btn-sm{padding:3px 8px;font-size:.72rem;}
.btn:hover{opacity:.85;}
.dt-bar{display:flex;gap:6px;margin-bottom:8px;}
.ctrl-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;margin-bottom:6px;}
.search-box{display:flex;align-items:center;gap:5px;font-size:.82rem;}
.search-box input{padding:5px 10px;border:1px solid #e2e8f0;border-radius:6px;width:220px;font-size:.82rem;font-family:inherit;}

/* Summary cards */
.sum-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px;}
.sum-card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;display:flex;align-items:center;gap:10px;}
.sc-icon{width:38px;height:38px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;}
.sc-lbl{font-size:.63rem;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:2px;}
.sc-val{font-size:.95rem;font-weight:800;color:#1e293b;}
.ic-tot{background:#fef3c7;color:#d97706;}.ic-recv{background:#dcfce7;color:#15803d;}.ic-due{background:#fee2e2;color:#dc2626;}

/* Flash */
.flash{display:flex;align-items:center;gap:8px;padding:10px 14px;border-radius:6px;font-size:.875rem;font-weight:500;margin-bottom:10px;}
.flash-success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;}
.flash-error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;}

/* Table */
.table-wrap{background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;}
.table-responsive{overflow-x:auto;-webkit-overflow-scrolling:touch;}
.table{width:100%;border-collapse:collapse;font-size:.78rem;color:#1e293b;}
.table thead th{background:#f0f4f8;padding:7px 8px;font-weight:700;font-size:.67rem;text-transform:uppercase;letter-spacing:.03em;color:#64748b;border-bottom:2px solid #e2e8f0;white-space:nowrap;text-align:left;cursor:pointer;user-select:none;position:sticky;top:0;z-index:1;}
.table thead th::after{content:' ⇅';opacity:.2;font-size:.5rem;}
.table thead th.sort-asc::after{content:' ↑';opacity:1;}
.table thead th.sort-desc::after{content:' ↓';opacity:1;}
.table tbody td{padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top;}
.table tbody tr:hover{background:#f8fbff;}
.table tbody tr:last-child td{border-bottom:none;}
.table tbody tr.hidden-row{display:none;}
.text-right{text-align:right;}

/* Subsidy badge */
.sub-badge{display:inline-block;background:#dcfce7;color:#15803d;border-radius:20px;padding:2px 10px;font-size:.68rem;font-weight:700;}

/* Pagination */
.pag-bar{display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border-top:1px solid #e2e8f0;flex-wrap:wrap;gap:5px;font-size:.8rem;color:#64748b;}
.pagination{display:flex;gap:2px;}
.pg-btn{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:28px;padding:0 6px;border:1px solid #e2e8f0;border-radius:4px;background:#fff;color:#3b82f6;font-size:.76rem;font-weight:600;cursor:pointer;text-decoration:none;}
.pg-btn:hover{background:#f1f5f9;}.pg-btn.active{background:#3b82f6;border-color:#3b82f6;color:#fff;}.pg-btn.disabled{opacity:.45;pointer-events:none;color:#64748b;}
</style>

<?php if ($flash): ?>
<div class="flash flash-<?php echo $flash['type']==='success'?'success':'error'; ?>">
    <i class="fas fa-<?php echo $flash['type']==='success'?'check-circle':'exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($flash['message']); ?>
</div>
<?php endif; ?>

<!-- Title -->
<h2 style="font-size:1.1rem;font-weight:700;margin-bottom:12px;color:#1e293b;">
    <i class="fas fa-hand-holding-usd" style="color:#22c55e;margin-right:7px;"></i>
    Customer List who received First Subsidy:-
    <span style="font-weight:400;color:#64748b;">(<?php echo number_format($totalRecords); ?>)</span>
</h2>

<!-- Summary Cards -->
<div class="sum-cards">
    <div class="sum-card"><div class="sc-icon ic-tot"><i class="fas fa-wallet"></i></div><div><div class="sc-lbl">Total Amount</div><div class="sc-val">₹<?php echo number_format((float)($totals['total']??0),2); ?></div></div></div>
    <div class="sum-card"><div class="sc-icon ic-recv"><i class="fas fa-check-circle"></i></div><div><div class="sc-lbl">Received</div><div class="sc-val" style="color:#15803d;">₹<?php echo number_format((float)($totals['received']??0),2); ?></div></div></div>
    <div class="sum-card"><div class="sc-icon ic-due"><i class="fas fa-exclamation-circle"></i></div><div><div class="sc-lbl">Due Amount</div><div class="sc-val" style="color:#dc2626;">₹<?php echo number_format((float)($totals['due']??0),2); ?></div></div></div>
</div>

<!-- Filters -->
<div class="pl-filters">
    <form method="GET" action="/APN-Solar/customers/subsidy_first.php">
        <div class="pl-fg"><label>Reg From:</label><input type="date" name="reg_from" value="<?php echo htmlspecialchars($regFrom); ?>"></div>
        <div class="pl-fg"><label>Reg To:</label><input type="date" name="reg_to" value="<?php echo htmlspecialchars($regTo); ?>"></div>
        <div class="pl-fg"><label>Group:</label><select name="group"><option value="">All Groups</option><?php foreach($allGroups as $g): ?><option value="<?php echo htmlspecialchars($g); ?>" <?php echo $group===$g?'selected':''; ?>><?php echo htmlspecialchars($g); ?></option><?php endforeach; ?></select></div>
        <div class="pl-fg"><label>District:</label><input type="text" name="district" placeholder="District..." value="<?php echo htmlspecialchars($district); ?>"></div>
        <div style="display:flex;gap:6px;align-items:flex-end;">
            <button type="submit" class="btn btn-filter"><i class="fas fa-filter"></i> Filter</button>
            <a href="/APN-Solar/customers/subsidy_first.php" class="btn btn-reset"><i class="fas fa-undo"></i> Reset</a>
        </div>
    </form>
</div>

<!-- Export -->
<div class="dt-bar">
    <button class="btn btn-excel" onclick="sf1Excel()"><i class="fas fa-file-excel"></i> Excel</button>
</div>

<!-- Controls -->
<div class="ctrl-bar">
    <div style="display:flex;align-items:center;gap:6px;font-size:.82rem;">
        <form method="GET" action="/APN-Solar/customers/subsidy_first.php" style="display:flex;align-items:center;gap:5px;">
            <?php foreach(['reg_from'=>$regFrom,'reg_to'=>$regTo,'group'=>$group,'district'=>$district,'search'=>$search] as $k=>$v): if($v): ?><input type="hidden" name="<?php echo $k;?>" value="<?php echo htmlspecialchars($v);?>"><?php endif; endforeach; ?>
            Show <select name="per_page" onchange="this.form.submit()"><?php foreach([10,25,50,100] as $n): ?><option value="<?php echo $n;?>" <?php echo $perPage==$n?'selected':'';?>><?php echo $n;?></option><?php endforeach; ?></select> entries
        </form>
    </div>
    <div class="search-box"><span>Search:</span><input type="text" id="sf1Search" placeholder="Name, mobile, ID..." oninput="sf1Filter(this.value)" value="<?php echo htmlspecialchars($search); ?>"></div>
</div>

<!-- Table -->
<div class="table-wrap">
    <div class="table-responsive">
        <table class="table" id="sf1Table">
            <thead>
                <tr>
                    <th onclick="sf1Sort(0)">Sr<br>No.</th>
                    <th onclick="sf1Sort(1)">Operator<br>Name</th>
                    <th onclick="sf1Sort(2)">Group Name</th>
                    <th onclick="sf1Sort(3)">Name</th>
                    <th onclick="sf1Sort(4)">Email</th>
                    <th onclick="sf1Sort(5)">Mobile</th>
                    <th onclick="sf1Sort(6)">District</th>
                    <th onclick="sf1Sort(7)">Electricity<br>Id</th>
                    <th onclick="sf1Sort(8)">Kw</th>
                    <th onclick="sf1Sort(9)" class="text-right">Total<br>Amount</th>
                    <th onclick="sf1Sort(10)" class="text-right">Due<br>Amount</th>
                    <th onclick="sf1Sort(11)">Subsidy<br>1st Status</th>
                    <th onclick="sf1Sort(12)">Remarks</th>
                    <th onclick="sf1Sort(13)">Last Updated<br>on</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($customers):
                $n = $startRecord;
                foreach ($customers as $r): ?>
                <tr>
                    <td><?php echo $n++; ?></td>
                    <td style="min-width:90px;"><?php echo htmlspecialchars($r['operator_name']??''); ?></td>
                    <td style="min-width:120px;"><?php echo htmlspecialchars($r['group_name']??''); ?></td>
                    <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                    <td style="font-size:.72rem;"><?php echo htmlspecialchars($r['email']??''); ?></td>
                    <td><?php echo htmlspecialchars($r['mobile']??''); ?></td>
                    <td><?php echo htmlspecialchars($r['district_name']??''); ?></td>
                    <td><?php echo htmlspecialchars($r['electricity_id']??''); ?></td>
                    <td><?php echo htmlspecialchars($r['kw']??''); ?></td>
                    <td class="text-right">₹<?php echo number_format((float)($r['total_amount']??0),2); ?></td>
                    <td class="text-right" style="<?php echo (float)($r['due_amount']??0)>0?'color:#dc2626;font-weight:600;':''; ?>">
                        ₹<?php echo number_format((float)($r['due_amount']??0),2); ?>
                    </td>
                    <td><span class="sub-badge"><i class="fas fa-check" style="font-size:.6rem;"></i> Yes</span></td>
                    <td style="min-width:150px;max-width:220px;word-wrap:break-word;font-size:.72rem;"><?php echo htmlspecialchars($r['install_remarks']??''); ?></td>
                    <td style="white-space:nowrap;font-size:.71rem;">
                        <?php $upd=$r['install_updated']??$r['updated_at']??''; echo $upd?htmlspecialchars(substr($upd,0,16)):'—'; ?>
                    </td>
                    <td>
                        <a href="/APN-Solar/customers/customer_status_update.php?id=<?php echo (int)$r['id']; ?>&back=subsidy_first"
                           class="btn btn-edit btn-sm"><i class="fas fa-pencil-alt"></i> Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="15" style="text-align:center;padding:40px;color:#94a3b8;">
                    <i class="fas fa-hand-holding-usd" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                    No customers found<?php echo ($group||$district||$search||$regFrom||$regTo)?' matching your filters.':'.'; ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="pag-bar">
        <span>Showing <strong><?php echo number_format($startRecord); ?>–<?php echo number_format($endRecord); ?></strong> of <strong><?php echo number_format($totalRecords); ?></strong> entries</span>
        <div class="pagination">
            <?php if($page>1): ?><a class="pg-btn" href="<?php echo sf1Url(['page'=>$page-1]); ?>">Prev</a><?php else: ?><span class="pg-btn disabled">Prev</span><?php endif; ?>
            <?php $ps=max(1,$page-3);$pe=min($totalPages,$page+3);
            if($ps>1){echo '<a class="pg-btn" href="'.sf1Url(['page'=>1]).'">1</a>';if($ps>2)echo '<span class="pg-btn disabled">…</span>';}
            for($p=$ps;$p<=$pe;$p++) echo '<a class="pg-btn '.($p===$page?'active':'').'" href="'.sf1Url(['page'=>$p]).'">'.$p.'</a>';
            if($pe<$totalPages){if($pe<$totalPages-1)echo '<span class="pg-btn disabled">…</span>';echo '<a class="pg-btn" href="'.sf1Url(['page'=>$totalPages]).'">'.$totalPages.'</a>';}?>
            <?php if($page<$totalPages): ?><a class="pg-btn" href="<?php echo sf1Url(['page'=>$page+1]); ?>">Next</a><?php else: ?><span class="pg-btn disabled">Next</span><?php endif; ?>
        </div>
    </div>
</div>

<script>
function sf1Filter(q){q=q.toLowerCase();document.querySelectorAll('#sf1Table tbody tr').forEach(r=>{r.classList.toggle('hidden-row',q!==''&&!r.innerText.toLowerCase().includes(q));});}
let sf1SC=-1,sf1SA=true;
function sf1Sort(c){const ths=document.querySelectorAll('#sf1Table thead th');ths.forEach(t=>t.classList.remove('sort-asc','sort-desc'));if(sf1SC===c){sf1SA=!sf1SA;}else{sf1SC=c;sf1SA=true;}ths[c].classList.add(sf1SA?'sort-asc':'sort-desc');const tb=document.querySelector('#sf1Table tbody');const rows=[...tb.querySelectorAll('tr')].filter(r=>!r.querySelector('[colspan]'));rows.sort((a,b)=>{const va=a.cells[c]?.innerText.replace(/[₹,]/g,'').trim()||'',vb=b.cells[c]?.innerText.replace(/[₹,]/g,'').trim()||'';const na=parseFloat(va),nb=parseFloat(vb);const cm=(!isNaN(na)&&!isNaN(nb))?na-nb:va.localeCompare(vb);return sf1SA?cm:-cm;});rows.forEach(r=>tb.appendChild(r));}
function sf1Excel(){const rows=document.querySelectorAll('#sf1Table tr');let html='<table border="1"><thead>';let inB=false;rows.forEach(row=>{const isH=row.closest('thead');if(isH&&!inB)html+='<tr>';else if(!isH&&!inB){html+='</thead><tbody><tr>';inB=true;}else html+='<tr>';row.querySelectorAll('th,td').forEach((c,i)=>{if(i===14)return;html+=`<${isH?'th':'td'}>${c.innerText.trim()}</${isH?'th':'td'}>`;});html+='</tr>';});html+='</tbody></table>';const b=new Blob([html],{type:'application/vnd.ms-excel;charset=utf-8;'});const a=Object.assign(document.createElement('a'),{href:URL.createObjectURL(b),download:'subsidy_first_'+new Date().toISOString().slice(0,10)+'.xls'});a.click();URL.revokeObjectURL(a.href);}
</script>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
