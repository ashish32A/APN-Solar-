<?php
// reports/payment_report.php — Detailed Payment Report

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

// ── Filters ────────────────────────────────────────────────────────────────────
$regFrom  = trim($_GET['reg_from']  ?? '');
$regTo    = trim($_GET['reg_to']    ?? '');
$group    = trim($_GET['group']     ?? '');
$district = trim($_GET['district']  ?? '');
$mode     = trim($_GET['mode']      ?? 'all'); // all | due | paid | partial
$search   = trim($_GET['search']    ?? '');

$validPerPage = [10, 25, 50, 100];
$reqPerPage   = (int)($_GET['per_page'] ?? 25);
$perPage      = in_array($reqPerPage, $validPerPage) ? $reqPerPage : 25;
$page         = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($page - 1) * $perPage;

$where  = "WHERE 1=1";
$params = [];

if ($regFrom  !== '') { $where .= " AND DATE(c.created_at) >= ?"; $params[] = $regFrom; }
if ($regTo    !== '') { $where .= " AND DATE(c.created_at) <= ?"; $params[] = $regTo; }
if ($group    !== '') { $where .= " AND c.group_name = ?";        $params[] = $group; }
if ($district !== '') { $where .= " AND c.district_name LIKE ?";  $params[] = "%$district%"; }
if ($mode === 'due')     { $where .= " AND p.due_amount > 0"; }
elseif ($mode === 'paid'){ $where .= " AND p.due_amount <= 0 AND p.payment_received > 0"; }
elseif ($mode === 'partial'){ $where .= " AND p.payment_received > 0 AND p.due_amount > 0"; }
if ($search !== '') {
    $where .= " AND (c.name LIKE ? OR c.mobile LIKE ? OR c.operator_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}

// Summary
try {
    $ts = $pdo->prepare("SELECT COUNT(DISTINCT c.id) AS total, SUM(p.total_amount) AS grand_total, SUM(p.payment_received) AS grand_recv, SUM(p.due_amount) AS grand_due FROM customers c LEFT JOIN payments p ON c.id=p.customer_id $where");
    $ts->execute($params);
    $summary = $ts->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $summary = []; }

// Count
try {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM customers c LEFT JOIN payments p ON c.id=p.customer_id $where");
    $cnt->execute($params);
    $totalRecords = (int)$cnt->fetchColumn();
} catch (PDOException $e) { $totalRecords = 0; }

$totalPages  = max(1, (int)ceil($totalRecords / $perPage));
$page        = min($page, $totalPages);
$startRecord = $totalRecords > 0 ? ($page - 1) * $perPage + 1 : 0;
$endRecord   = min($page * $perPage, $totalRecords);

// Data
try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.operator_name, c.group_name, c.name, c.mobile,
               c.district_name, c.electricity_id, c.kw,
               c.account_number, c.ifsc_code, c.status, c.created_at,
               p.total_amount, p.payment_received, p.due_amount, p.updated_at AS pay_date
        FROM customers c
        LEFT JOIN payments p ON c.id = p.customer_id
        $where
        ORDER BY p.due_amount DESC, c.id DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $rows = []; }

// Groups
$allGroups = $pdo->query("SELECT DISTINCT group_name FROM customers WHERE group_name != '' ORDER BY group_name")->fetchAll(PDO::FETCH_COLUMN);

function prUrl(array $ov = []): string {
    $p = array_merge([
        'reg_from'=>$_GET['reg_from']??'','reg_to'=>$_GET['reg_to']??'',
        'group'=>$_GET['group']??'','district'=>$_GET['district']??'',
        'mode'=>$_GET['mode']??'all','search'=>$_GET['search']??'',
        'per_page'=>$_GET['per_page']??25,'page'=>$_GET['page']??1,
    ], $ov);
    return '/APN-Solar/reports/payment_report.php?'.http_build_query(array_filter($p, fn($v)=>$v!==''&&$v!=='all'));
}

$pageTitle = 'Payment Report';
include __DIR__ . '/../views/partials/header.php';
?>

<style>
.rpt-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px;}
.rpt-card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;display:flex;align-items:center;gap:10px;}
.rpt-card .rc-icon{width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;}
.rpt-card .rc-lbl{font-size:.65rem;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:2px;}
.rpt-card .rc-val{font-size:.98rem;font-weight:800;color:#1e293b;}
.ic-cust{background:#dbeafe;color:#2563eb;}.ic-tot{background:#fef3c7;color:#d97706;}.ic-recv{background:#dcfce7;color:#15803d;}.ic-due{background:#fee2e2;color:#dc2626;}
.text-danger{color:#dc2626;font-weight:700;}.text-success{color:#15803d;font-weight:600;}

.rpt-filters{background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:10px 14px;margin-bottom:10px;}
.rpt-filters form{display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;}
.rf-fg{display:flex;flex-direction:column;gap:2px;}
.rf-fg label{font-size:.7rem;font-weight:700;color:#64748b;}
.rf-fg input,.rf-fg select{padding:5px 8px;border:1px solid #ced4da;border-radius:4px;font-size:.8rem;font-family:inherit;}
.rf-fg input[type="date"]{width:130px;}.rf-fg input[type="text"]{width:140px;}.rf-fg select{min-width:130px;}

.filter-tabs{display:flex;gap:6px;margin-bottom:10px;flex-wrap:wrap;}
.filter-tab{padding:4px 14px;border-radius:20px;font-size:.76rem;font-weight:600;cursor:pointer;text-decoration:none;border:1.5px solid #e2e8f0;color:#64748b;transition:all .15s;}
.filter-tab:hover{background:#f1f5f9;}
.filter-tab.active{background:#3b82f6;border-color:#3b82f6;color:#fff;}

.btn{display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border-radius:4px;font-size:.78rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:opacity .15s;text-decoration:none;white-space:nowrap;}
.btn-filter{background:#007bff;color:#fff;}.btn-reset{background:#6c757d;color:#fff;}.btn-excel{background:#1d6f42;color:#fff;}.btn-pdf{background:#c0392b;color:#fff;}.btn-print{background:#6c757d;color:#fff;}
.btn:hover{opacity:.85;}
.dt-bar{display:flex;gap:6px;margin-bottom:8px;}
.ctrl-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;margin-bottom:6px;}
.search-box{display:flex;align-items:center;gap:5px;font-size:.8rem;}
.search-box input{padding:5px 9px;border:1px solid #ced4da;border-radius:4px;width:200px;font-size:.8rem;font-family:inherit;}

.table-wrap{background:#fff;border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;}
.table-responsive{overflow-x:auto;-webkit-overflow-scrolling:touch;}
.table{width:100%;border-collapse:collapse;font-size:.77rem;color:#1e293b;}
.table thead th{background:#f0f4f8;padding:7px 8px;font-weight:700;font-size:.67rem;text-transform:uppercase;letter-spacing:.03em;color:#64748b;border-bottom:2px solid #e2e8f0;white-space:nowrap;text-align:left;cursor:pointer;user-select:none;position:sticky;top:0;z-index:1;}
.table thead th::after{content:' ⇅';opacity:.2;font-size:.5rem;}
.table thead th.sort-asc::after{content:' ↑';opacity:1;}
.table thead th.sort-desc::after{content:' ↓';opacity:1;}
.table tbody td{padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.table tbody tr:hover{background:#f8fbff;}
.table tbody tr:last-child td{border-bottom:none;}
.table tbody tr.hidden-row{display:none;}
.text-right{text-align:right;}

.due-badge{display:inline-block;background:#fee2e2;color:#dc2626;border-radius:20px;padding:1px 8px;font-size:.68rem;font-weight:700;}
.recv-badge{display:inline-block;background:#dcfce7;color:#15803d;border-radius:20px;padding:1px 8px;font-size:.68rem;font-weight:700;}
.paid-badge{display:inline-block;background:#dbeafe;color:#1e40af;border-radius:20px;padding:1px 8px;font-size:.68rem;font-weight:700;}

.pag-bar{display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border-top:1px solid #e2e8f0;flex-wrap:wrap;gap:5px;font-size:.78rem;color:#64748b;}
.pagination{display:flex;gap:2px;}
.pg-btn{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:28px;padding:0 6px;border:1px solid #e2e8f0;border-radius:4px;background:#fff;color:#007bff;font-size:.76rem;font-weight:600;cursor:pointer;text-decoration:none;}
.pg-btn:hover{background:#f1f5f9;}.pg-btn.active{background:#007bff;border-color:#007bff;color:#fff;}.pg-btn.disabled{opacity:.45;pointer-events:none;color:#64748b;}
</style>

<h2 style="font-size:1.1rem;font-weight:700;margin-bottom:12px;color:#1e293b;">
    <i class="fas fa-rupee-sign" style="color:#3b82f6;margin-right:7px;"></i>
    Payment Report
    <span style="font-weight:400;color:#64748b;font-size:.9rem;">(<?php echo number_format($totalRecords); ?> records)</span>
</h2>

<!-- Summary Cards -->
<div class="rpt-cards">
    <div class="rpt-card"><div class="rc-icon ic-cust"><i class="fas fa-users"></i></div><div><div class="rc-lbl">Customers</div><div class="rc-val"><?php echo number_format((int)($summary['total']??0)); ?></div></div></div>
    <div class="rpt-card"><div class="rc-icon ic-tot"><i class="fas fa-wallet"></i></div><div><div class="rc-lbl">Total Amount</div><div class="rc-val">₹<?php echo number_format((float)($summary['grand_total']??0),2); ?></div></div></div>
    <div class="rpt-card"><div class="rc-icon ic-recv"><i class="fas fa-check-circle"></i></div><div><div class="rc-lbl">Received</div><div class="rc-val" style="color:#15803d;">₹<?php echo number_format((float)($summary['grand_recv']??0),2); ?></div></div></div>
    <div class="rpt-card"><div class="rc-icon ic-due"><i class="fas fa-exclamation-circle"></i></div><div><div class="rc-lbl">Due</div><div class="rc-val" style="color:#dc2626;">₹<?php echo number_format((float)($summary['grand_due']??0),2); ?></div></div></div>
</div>

<!-- Filter Tabs -->
<div class="filter-tabs">
    <?php foreach(['all'=>'All','due'=>'Due Only','paid'=>'Fully Paid','partial'=>'Partial Payment'] as $k=>$label): ?>
    <a href="<?php echo prUrl(['mode'=>$k,'page'=>1]); ?>" class="filter-tab <?php echo $mode===$k?'active':''; ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="rpt-filters">
    <form method="GET" action="/APN-Solar/reports/payment_report.php">
        <input type="hidden" name="mode" value="<?php echo htmlspecialchars($mode); ?>">
        <div class="rf-fg"><label>Reg From:</label><input type="date" name="reg_from" value="<?php echo htmlspecialchars($regFrom); ?>"></div>
        <div class="rf-fg"><label>Reg To:</label><input type="date" name="reg_to" value="<?php echo htmlspecialchars($regTo); ?>"></div>
        <div class="rf-fg"><label>Group:</label><select name="group"><option value="">All Groups</option><?php foreach($allGroups as $g): ?><option value="<?php echo htmlspecialchars($g); ?>" <?php echo $group===$g?'selected':''; ?>><?php echo htmlspecialchars($g); ?></option><?php endforeach; ?></select></div>
        <div class="rf-fg"><label>District:</label><input type="text" name="district" placeholder="District" value="<?php echo htmlspecialchars($district); ?>"></div>
        <div style="display:flex;gap:5px;align-items:flex-end;">
            <button type="submit" class="btn btn-filter"><i class="fas fa-filter"></i> Filter</button>
            <a href="/APN-Solar/reports/payment_report.php" class="btn btn-reset"><i class="fas fa-undo"></i> Reset</a>
        </div>
    </form>
</div>

<div class="dt-bar">
    <button class="btn btn-excel" onclick="prExcel()"><i class="fas fa-file-excel"></i> Excel</button>
    <button class="btn btn-pdf"   onclick="prPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
    <button class="btn btn-print" onclick="prPDF()"><i class="fas fa-print"></i> Print</button>
</div>

<div class="ctrl-bar">
    <div style="display:flex;align-items:center;gap:6px;font-size:.8rem;">
        <form method="GET" action="/APN-Solar/reports/payment_report.php" style="display:flex;align-items:center;gap:5px;">
            <?php foreach(['reg_from'=>$regFrom,'reg_to'=>$regTo,'group'=>$group,'district'=>$district,'mode'=>$mode,'search'=>$search] as $k=>$v): if($v&&$v!=='all'): ?><input type="hidden" name="<?php echo $k;?>" value="<?php echo htmlspecialchars($v);?>"><?php endif; endforeach; ?>
            Show <select name="per_page" onchange="this.form.submit()"><?php foreach([10,25,50,100] as $n): ?><option value="<?php echo $n;?>" <?php echo $perPage==$n?'selected':'';?>><?php echo $n;?></option><?php endforeach; ?></select> entries
        </form>
    </div>
    <div class="search-box"><span>Search:</span><input type="text" id="prSearch" placeholder="Name, mobile..." oninput="prFilter(this.value)" value="<?php echo htmlspecialchars($search); ?>"></div>
</div>

<div class="table-wrap">
    <div class="table-responsive">
        <table class="table" id="prTable">
            <thead>
                <tr>
                    <th onclick="prSort(0)">Sr</th>
                    <th onclick="prSort(1)">Operator</th>
                    <th onclick="prSort(2)">Group</th>
                    <th onclick="prSort(3)">Name</th>
                    <th onclick="prSort(4)">Mobile</th>
                    <th onclick="prSort(5)">District</th>
                    <th onclick="prSort(6)">Elec. ID</th>
                    <th onclick="prSort(7)">KW</th>
                    <th onclick="prSort(8)">IFSC Code</th>
                    <th onclick="prSort(9)">Account No.</th>
                    <th onclick="prSort(10)" class="text-right">Total Amt</th>
                    <th onclick="prSort(11)" class="text-right">Received</th>
                    <th onclick="prSort(12)" class="text-right">Due Amt</th>
                    <th onclick="prSort(13)">Payment Status</th>
                    <th onclick="prSort(14)">Reg Date</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($rows):
                $n = $startRecord;
                foreach ($rows as $r):
                    $total = (float)($r['total_amount']??0);
                    $recv  = (float)($r['payment_received']??0);
                    $due   = (float)($r['due_amount']??0);
                    $payStatus = $due <= 0 && $recv > 0 ? 'paid' : ($recv > 0 ? 'partial' : 'due');
            ?>
                <tr>
                    <td><?php echo $n++; ?></td>
                    <td style="min-width:90px;"><?php echo htmlspecialchars($r['operator_name']??''); ?></td>
                    <td style="min-width:100px;"><?php echo htmlspecialchars($r['group_name']??''); ?></td>
                    <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($r['mobile']??''); ?></td>
                    <td><?php echo htmlspecialchars($r['district_name']??''); ?></td>
                    <td><?php echo htmlspecialchars($r['electricity_id']??''); ?></td>
                    <td><?php echo htmlspecialchars($r['kw']??''); ?></td>
                    <td><?php echo htmlspecialchars($r['ifsc_code']??''); ?></td>
                    <td><?php echo htmlspecialchars($r['account_number']??''); ?></td>
                    <td class="text-right">₹<?php echo number_format($total,2); ?></td>
                    <td class="text-right"><span class="recv-badge">₹<?php echo number_format($recv,2); ?></span></td>
                    <td class="text-right"><?php if($due>0): ?><span class="due-badge">₹<?php echo number_format($due,2); ?></span><?php else: ?>₹0.00<?php endif; ?></td>
                    <td><?php if($payStatus==='paid'): ?><span class="paid-badge">Fully Paid</span><?php elseif($payStatus==='partial'): ?><span style="background:#fef3c7;color:#92400e;border-radius:20px;padding:1px 8px;font-size:.68rem;font-weight:700;">Partial</span><?php else: ?><span class="due-badge">Due</span><?php endif; ?></td>
                    <td style="white-space:nowrap;font-size:.71rem;"><?php echo htmlspecialchars(substr($r['created_at']??'',0,10)); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="15" style="text-align:center;padding:40px;color:#94a3b8;"><i class="fas fa-rupee-sign" style="font-size:2rem;display:block;margin-bottom:8px;"></i>No records found<?php echo ($group||$district||$search||$regFrom||$regTo)?' matching your filters.':'.'; ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="pag-bar">
        <span>Showing <strong><?php echo number_format($startRecord); ?>–<?php echo number_format($endRecord); ?></strong> of <strong><?php echo number_format($totalRecords); ?></strong> entries</span>
        <div class="pagination">
            <?php if($page>1): ?><a class="pg-btn" href="<?php echo prUrl(['page'=>$page-1]); ?>">Prev</a><?php else: ?><span class="pg-btn disabled">Prev</span><?php endif; ?>
            <?php $ps=max(1,$page-3);$pe=min($totalPages,$page+3);
            if($ps>1){echo '<a class="pg-btn" href="'.prUrl(['page'=>1]).'">1</a>';if($ps>2)echo '<span class="pg-btn disabled">…</span>';}
            for($p=$ps;$p<=$pe;$p++) echo '<a class="pg-btn '.($p===$page?'active':'').'" href="'.prUrl(['page'=>$p]).'">'.$p.'</a>';
            if($pe<$totalPages){if($pe<$totalPages-1)echo '<span class="pg-btn disabled">…</span>';echo '<a class="pg-btn" href="'.prUrl(['page'=>$totalPages]).'">'.$totalPages.'</a>';}?>
            <?php if($page<$totalPages): ?><a class="pg-btn" href="<?php echo prUrl(['page'=>$page+1]); ?>">Next</a><?php else: ?><span class="pg-btn disabled">Next</span><?php endif; ?>
        </div>
    </div>
</div>

<script>
function prFilter(q){q=q.toLowerCase();document.querySelectorAll('#prTable tbody tr').forEach(r=>{r.classList.toggle('hidden-row',q!==''&&!r.innerText.toLowerCase().includes(q));});}
let prSC=-1,prSA=true;
function prSort(c){const ths=document.querySelectorAll('#prTable thead th');ths.forEach(t=>t.classList.remove('sort-asc','sort-desc'));if(prSC===c){prSA=!prSA;}else{prSC=c;prSA=true;}ths[c].classList.add(prSA?'sort-asc':'sort-desc');const tb=document.querySelector('#prTable tbody');const rows=[...tb.querySelectorAll('tr')].filter(r=>!r.querySelector('[colspan]'));rows.sort((a,b)=>{const va=a.cells[c]?.innerText.replace(/[₹,]/g,'').trim()||'',vb=b.cells[c]?.innerText.replace(/[₹,]/g,'').trim()||'';const na=parseFloat(va),nb=parseFloat(vb);const cm=(!isNaN(na)&&!isNaN(nb))?na-nb:va.localeCompare(vb);return prSA?cm:-cm;});rows.forEach(r=>tb.appendChild(r));}
function prExcel(){const rows=document.querySelectorAll('#prTable tr');let html='<table border="1"><thead>';let inB=false;rows.forEach(row=>{const isH=row.closest('thead');if(isH&&!inB)html+='<tr>';else if(!isH&&!inB){html+='</thead><tbody><tr>';inB=true;}else html+='<tr>';row.querySelectorAll('th,td').forEach(c=>{const t=isH?'th':'td';html+=`<${t}>${c.innerText.trim()}</${t}>`;});html+='</tr>';});html+='</tbody></table>';const b=new Blob([html],{type:'application/vnd.ms-excel;charset=utf-8;'});const a=Object.assign(document.createElement('a'),{href:URL.createObjectURL(b),download:'payment_report_'+new Date().toISOString().slice(0,10)+'.xls'});a.click();URL.revokeObjectURL(a.href);}
function prPDF(){const pw=window.open('','','width=1200,height=800');const ths=[...document.querySelectorAll('#prTable thead th')].map(t=>`<th>${t.innerText}</th>`).join('');const trs=[...document.querySelectorAll('#prTable tbody tr')].map(r=>`<tr>${[...r.querySelectorAll('td')].map(c=>`<td>${c.innerText}</td>`).join('')}</tr>`).join('');pw.document.write(`<!DOCTYPE html><html><head><title>Payment Report</title><style>body{font-family:Arial;font-size:7px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ccc;padding:2px 4px;}th{background:#f0f4f8;}</style></head><body><h3>Payment Report (<?php echo $totalRecords; ?> records) - ${new Date().toLocaleDateString()}</h3><table><thead><tr>${ths}</tr></thead><tbody>${trs}</tbody></table></body></html>`);pw.document.close();pw.focus();setTimeout(()=>{pw.print();pw.close();},500);}
</script>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
