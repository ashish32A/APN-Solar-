<?php
// masters/payments/index.php — Payments Master

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle    = 'Payments Master';
$validPerPage = [10, 25, 50, 100];
$requestedPer = (int)($_GET['per_page'] ?? 10);
$perPage      = in_array($requestedPer, $validPerPage) ? $requestedPer : 10;
$page         = max(1, (int)($_GET['page'] ?? 1));
$search       = trim($_GET['search'] ?? '');
$filterMode   = trim($_GET['filter'] ?? 'all'); // all | due | paid

$where  = "WHERE 1=1";
$params = [];
if ($search !== '') {
    $where   .= " AND (cu.name LIKE ? OR cu.mobile LIKE ? OR p.transaction_no LIKE ? OR p.payment_mode LIKE ?)";
    $like     = "%$search%";
    $params   = [$like, $like, $like, $like];
}
if ($filterMode === 'due') {
    $where .= " AND p.due_amount > 0";
} elseif ($filterMode === 'paid') {
    $where .= " AND p.due_amount <= 0";
}

try {
    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM payments p LEFT JOIN customers cu ON p.customer_id = cu.id $where");
    $cStmt->execute($params);
    $totalRecords = (int)$cStmt->fetchColumn();
} catch (PDOException $e) { $totalRecords = 0; }

$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

try {
    $stmt = $pdo->prepare("
        SELECT p.*, cu.name AS customer_name, cu.mobile AS customer_mobile, cu.group_name
        FROM payments p
        LEFT JOIN customers cu ON p.customer_id = cu.id
        $where
        ORDER BY p.id DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
} catch (PDOException $e) { $payments = []; }

// Totals
try {
    $totStmt = $pdo->prepare("SELECT SUM(p.total_amount) AS total, SUM(p.payment_received) AS received, SUM(p.due_amount) AS due FROM payments p LEFT JOIN customers cu ON p.customer_id = cu.id $where");
    $totStmt->execute($params);
    $totals = $totStmt->fetch();
} catch (PDOException $e) { $totals = ['total'=>0,'received'=>0,'due'=>0]; }

$flash = null;
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

include __DIR__ . '/../../views/partials/header.php';

function payUrl(array $ov = []): string {
    $p = array_merge([
        'search'   => $_GET['search']   ?? '',
        'filter'   => $_GET['filter']   ?? 'all',
        'page'     => $_GET['page']     ?? 1,
        'per_page' => $_GET['per_page'] ?? 10,
    ], $ov);
    $q = http_build_query(array_filter($p, fn($v) => $v !== '' && $v !== 'all'));
    return '/APN-Solar/masters/payments/index.php' . ($q ? '?' . $q : '');
}
?>

<style>
.pm-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px; }
.pm-header h2 { font-size:1.15rem;font-weight:700;color:#1e293b; }
.btn { display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:7px;font-size:.83rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;white-space:nowrap; }
.btn-primary  { background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff; }
.btn-excel    { background:#1d6f42;color:#fff; }
.btn-warning  { background:#f59e0b;color:#fff; }
.btn-danger   { background:#ef4444;color:#fff; }
.btn-secondary{ background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0; }
.btn-sm       { padding:4px 11px;font-size:.75rem; }
.btn:hover    { opacity:.87;transform:translateY(-1px); }
.action-btns  { display:flex;gap:4px; }
.top-btns     { display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;align-items:center; }

/* Summary cards */
.summary-cards { display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px; }
.s-card { background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 18px;display:flex;align-items:center;gap:12px; }
.s-card .s-icon { width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0; }
.s-card .s-lbl  { font-size:.68rem;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:2px; }
.s-card .s-val  { font-size:1rem;font-weight:800;color:#1e293b; }
.icon-total    { background:#dbeafe;color:#2563eb; }
.icon-received { background:#dcfce7;color:#15803d; }
.icon-due      { background:#fee2e2;color:#dc2626; }

/* Filter tabs */
.filter-tabs { display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap; }
.filter-tab  { padding:5px 14px;border-radius:20px;font-size:.78rem;font-weight:600;cursor:pointer;text-decoration:none;border:1.5px solid #e2e8f0;color:#64748b;transition:all .15s; }
.filter-tab:hover   { background:#f1f5f9; }
.filter-tab.active  { background:#3b82f6;border-color:#3b82f6;color:#fff; }

.ctrl-bar { display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px; }
.show-wrap { display:flex;align-items:center;gap:6px;font-size:.82rem;color:#64748b; }
.show-wrap select { padding:4px 8px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.82rem; }
.search-wrap { display:flex;align-items:center;gap:6px;font-size:.82rem;color:#64748b; }
.search-wrap input { padding:6px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.82rem;font-family:inherit;outline:none;width:210px; }
.search-wrap input:focus { border-color:#3b82f6; }

.table-wrap { background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden; }
.table-responsive { overflow-x:auto;-webkit-overflow-scrolling:touch; }
.table { width:100%;border-collapse:collapse;font-size:.8rem;color:#1e293b; }
.table thead th { background:#f0f4f8;padding:9px 11px;font-weight:700;font-size:.7rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;border-bottom:2px solid #e2e8f0;white-space:nowrap;text-align:left; }
.table thead th.sortable { cursor:pointer;user-select:none; }
.table thead th.sortable::after { content:' ⇅';opacity:.3;font-size:.6rem; }
.table thead th.sort-asc::after { content:' ↑';opacity:1; }
.table thead th.sort-desc::after{ content:' ↓';opacity:1; }
.table tbody td { padding:9px 11px;border-bottom:1px solid #f1f5f9;vertical-align:middle; }
.table tbody tr:hover { background:#f8fbff; }
.table tbody tr:last-child td { border-bottom:none; }
.table tbody tr.hidden-row { display:none; }
.text-right { text-align:right; }
.text-success { color:#15803d;font-weight:600; }
.text-danger  { color:#dc2626;font-weight:600; }

.badge-mode { display:inline-block;border-radius:20px;padding:2px 9px;font-size:.68rem;font-weight:700; }
.badge-cash    { background:#fef3c7;color:#92400e; }
.badge-online  { background:#dbeafe;color:#1e40af; }
.badge-cheque  { background:#e0e7ff;color:#3730a3; }
.badge-neft    { background:#dcfce7;color:#15803d; }

.flash-alert   { display:flex;align-items:center;gap:9px;padding:11px 15px;border-radius:8px;font-size:.875rem;font-weight:500;margin-bottom:12px; }
.flash-success { background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a; }
.flash-error   { background:#fef2f2;border:1px solid #fecaca;color:#dc2626; }

.pagination-bar { display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:1px solid #e2e8f0;flex-wrap:wrap;gap:8px;font-size:.8rem;color:#64748b; }
.pagination { display:flex;gap:4px;flex-wrap:wrap; }
.pg-btn { display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border:1.5px solid #e2e8f0;border-radius:6px;background:#fff;color:#475569;font-size:.78rem;font-weight:600;cursor:pointer;text-decoration:none;transition:all .15s; }
.pg-btn:hover    { background:#f1f5f9; }
.pg-btn.active   { background:#3b82f6;border-color:#3b82f6;color:#fff; }
.pg-btn.disabled { opacity:.4;pointer-events:none; }

/* Delete modal */
.modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9998;align-items:center;justify-content:center; }
.modal-overlay.open { display:flex; }
.modal-box { background:#fff;border-radius:12px;padding:32px 36px;max-width:420px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25); }
.modal-icon { font-size:2.8rem;color:#ef4444;margin-bottom:14px; }
.modal-box h3 { font-size:1.1rem;font-weight:700;color:#1e293b;margin-bottom:8px; }
.modal-box p  { color:#64748b;font-size:.88rem;margin-bottom:18px; }
.modal-actions { display:flex;gap:10px;justify-content:center; }
</style>

<?php if ($flash): ?>
<div class="flash-alert flash-<?php echo $flash['type']==='success'?'success':'error'; ?>">
    <i class="fas fa-<?php echo $flash['type']==='success'?'check-circle':'exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($flash['message']); ?>
</div>
<?php endif; ?>

<div class="pm-header">
    <h2><i class="fas fa-rupee-sign" style="color:#3b82f6;margin-right:7px;"></i>Payments Master</h2>
    <a href="/APN-Solar/masters/payments/create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Payment
    </a>
</div>

<!-- Summary Cards -->
<div class="summary-cards">
    <div class="s-card">
        <div class="s-icon icon-total"><i class="fas fa-wallet"></i></div>
        <div>
            <div class="s-lbl">Total Amount</div>
            <div class="s-val">₹<?php echo number_format((float)($totals['total'] ?? 0), 2); ?></div>
        </div>
    </div>
    <div class="s-card">
        <div class="s-icon icon-received"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="s-lbl">Received</div>
            <div class="s-val">₹<?php echo number_format((float)($totals['received'] ?? 0), 2); ?></div>
        </div>
    </div>
    <div class="s-card">
        <div class="s-icon icon-due"><i class="fas fa-exclamation-circle"></i></div>
        <div>
            <div class="s-lbl">Due Amount</div>
            <div class="s-val">₹<?php echo number_format((float)($totals['due'] ?? 0), 2); ?></div>
        </div>
    </div>
</div>

<!-- Filter tabs + Excel -->
<div class="top-btns">
    <div class="filter-tabs">
        <a href="<?php echo payUrl(['filter'=>'all','page'=>1]); ?>" class="filter-tab <?php echo $filterMode==='all'?'active':''; ?>">All</a>
        <a href="<?php echo payUrl(['filter'=>'due','page'=>1]); ?>" class="filter-tab <?php echo $filterMode==='due'?'active':''; ?>">Due</a>
        <a href="<?php echo payUrl(['filter'=>'paid','page'=>1]); ?>" class="filter-tab <?php echo $filterMode==='paid'?'active':''; ?>">Paid</a>
    </div>
    <button class="btn btn-excel btn-sm" onclick="payExportExcel()"><i class="fas fa-file-excel"></i> Excel</button>
</div>

<div class="ctrl-bar">
    <form method="GET" action="/APN-Solar/masters/payments/index.php" class="show-wrap">
        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filterMode); ?>">
        <?php if ($search): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
        <label>Show</label>
        <select name="per_page" onchange="this.form.submit()">
            <?php foreach ($validPerPage as $n): ?>
                <option value="<?php echo $n; ?>" <?php echo $perPage==$n?'selected':''; ?>><?php echo $n; ?></option>
            <?php endforeach; ?>
        </select>
        <label>entries</label>
    </form>
    <div class="search-wrap">
        <label>Search:</label>
        <input type="text" id="paySearch" placeholder="Customer, mode, transaction no..."
               oninput="payFilter(this.value)" value="<?php echo htmlspecialchars($search); ?>">
    </div>
</div>

<div class="table-wrap">
    <div class="table-responsive">
        <table class="table" id="payTable">
            <thead>
                <tr>
                    <th class="sortable" onclick="paySort(0)" style="width:50px;">Sr No.</th>
                    <th class="sortable" onclick="paySort(1)">Customer</th>
                    <th class="sortable" onclick="paySort(2)">Group</th>
                    <th class="sortable" onclick="paySort(3)">Mobile</th>
                    <th class="sortable text-right" onclick="paySort(4)">Total Amt</th>
                    <th class="sortable text-right" onclick="paySort(5)">Received</th>
                    <th class="sortable text-right" onclick="paySort(6)">Due Amt</th>
                    <th class="sortable" onclick="paySort(7)">Mode</th>
                    <th class="sortable" onclick="paySort(8)">Transaction No.</th>
                    <th class="sortable" onclick="paySort(9)">Pay Date</th>
                    <th class="sortable" onclick="paySort(10)">Notes</th>
                    <th style="width:110px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($payments):
                $rowNum = ($page-1)*$perPage+1;
                foreach ($payments as $pm): ?>
                <tr>
                    <td><?php echo $rowNum++; ?></td>
                    <td><strong><?php echo htmlspecialchars($pm['customer_name'] ?? ''); ?></strong></td>
                    <td><?php echo htmlspecialchars($pm['group_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($pm['customer_mobile'] ?? ''); ?></td>
                    <td class="text-right">₹<?php echo number_format((float)($pm['total_amount'] ?? 0), 2); ?></td>
                    <td class="text-right text-success">₹<?php echo number_format((float)($pm['payment_received'] ?? 0), 2); ?></td>
                    <td class="text-right <?php echo (float)($pm['due_amount'] ?? 0) > 0 ? 'text-danger' : ''; ?>">
                        ₹<?php echo number_format((float)($pm['due_amount'] ?? 0), 2); ?>
                    </td>
                    <td>
                        <?php $mode = $pm['payment_mode'] ?? ''; ?>
                        <?php if ($mode): ?><span class="badge-mode badge-<?php echo htmlspecialchars($mode); ?>"><?php echo strtoupper($mode); ?></span><?php endif; ?>
                    </td>
                    <td style="font-size:.76rem;"><?php echo htmlspecialchars($pm['transaction_no'] ?? '—'); ?></td>
                    <td style="white-space:nowrap;font-size:.76rem;"><?php echo htmlspecialchars($pm['payment_date'] ?? ''); ?></td>
                    <td style="max-width:120px;word-wrap:break-word;font-size:.76rem;"><?php echo htmlspecialchars($pm['notes'] ?? ''); ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="/APN-Solar/masters/payments/edit.php?id=<?php echo (int)$pm['id']; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </a>
                            <button class="btn btn-danger btn-sm"
                                    onclick="payConfirmDelete(<?php echo (int)$pm['id']; ?>,'<?php echo addslashes(htmlspecialchars($pm['customer_name']??'')); ?>')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="12" style="text-align:center;padding:40px;color:#94a3b8;">
                    <i class="fas fa-rupee-sign" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                    No payments found<?php echo $search ? ' matching your search.' : '.'; ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="pagination-bar">
        <span>Showing <strong><?php echo $totalRecords>0?number_format(($page-1)*$perPage+1):0; ?>–<?php echo number_format(min($page*$perPage,$totalRecords)); ?></strong> of <strong><?php echo number_format($totalRecords); ?></strong> entries</span>
        <div class="pagination">
            <?php if($page>1):?><a class="pg-btn" href="<?php echo payUrl(['page'=>$page-1]);?>">Previous</a><?php else:?><span class="pg-btn disabled">Previous</span><?php endif;?>
            <?php $ws=max(1,$page-2);$we=min($totalPages,$page+2);
            if($ws>1){echo '<a class="pg-btn" href="'.payUrl(['page'=>1]).'">1</a>';if($ws>2)echo '<span class="pg-btn disabled">…</span>';}
            for($pp=$ws;$pp<=$we;$pp++) echo '<a class="pg-btn '.($pp===$page?'active':'').'" href="'.payUrl(['page'=>$pp]).'">'.$pp.'</a>';
            if($we<$totalPages){if($we<$totalPages-1)echo '<span class="pg-btn disabled">…</span>';echo '<a class="pg-btn" href="'.payUrl(['page'=>$totalPages]).'">'.$totalPages.'</a>';}
            ?>
            <?php if($page<$totalPages):?><a class="pg-btn" href="<?php echo payUrl(['page'=>$page+1]);?>">Next</a><?php else:?><span class="pg-btn disabled">Next</span><?php endif;?>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon"><i class="fas fa-trash-alt"></i></div>
        <h3>Delete Payment?</h3>
        <p id="deleteMsg">This will permanently delete this payment record.</p>
        <div class="modal-actions">
            <form id="deleteForm" method="POST" action="/APN-Solar/masters/payments/delete.php">
                <input type="hidden" name="id" id="deleteId">
                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> Yes, Delete</button>
            </form>
            <button class="btn btn-secondary btn-sm" onclick="document.getElementById('deleteModal').classList.remove('open')">Cancel</button>
        </div>
    </div>
</div>

<script>
function payFilter(q) {
    q=q.toLowerCase();
    document.querySelectorAll('#payTable tbody tr').forEach(r=>{r.classList.toggle('hidden-row',q!==''&&!r.innerText.toLowerCase().includes(q));});
}
let pySortCol=-1,pySortAsc=true;
function paySort(col) {
    const ths=document.querySelectorAll('#payTable thead th');
    ths.forEach(t=>t.classList.remove('sort-asc','sort-desc'));
    if(pySortCol===col){pySortAsc=!pySortAsc;}else{pySortCol=col;pySortAsc=true;}
    ths[col].classList.add(pySortAsc?'sort-asc':'sort-desc');
    const tbody=document.querySelector('#payTable tbody');
    const rows=[...tbody.querySelectorAll('tr')].filter(r=>!r.querySelector('[colspan]'));
    rows.sort((a,b)=>{const va=a.cells[col]?.innerText.replace(/[₹,]/g,'').trim()||'',vb=b.cells[col]?.innerText.replace(/[₹,]/g,'').trim()||'';const na=parseFloat(va),nb=parseFloat(vb);const c=(!isNaN(na)&&!isNaN(nb))?na-nb:va.localeCompare(vb);return pySortAsc?c:-c;});
    rows.forEach(r=>tbody.appendChild(r));
}
function payConfirmDelete(id,name) {
    document.getElementById('deleteId').value=id;
    document.getElementById('deleteMsg').textContent='Delete payment record for "'+name+'"?';
    document.getElementById('deleteModal').classList.add('open');
}
document.getElementById('deleteModal').addEventListener('click',e=>{if(e.target===document.getElementById('deleteModal'))document.getElementById('deleteModal').classList.remove('open');});

function payExportExcel() {
    const rows=document.querySelectorAll('#payTable tr');
    let html='<table border="1"><thead>';let inBody=false;
    rows.forEach(row=>{const isHead=row.closest('thead');if(isHead&&!inBody)html+='<tr>';else if(!isHead&&!inBody){html+='</thead><tbody><tr>';inBody=true;}else html+='<tr>';
    row.querySelectorAll('th,td').forEach((c,i)=>{if(i===11)return;const tag=isHead?'th':'td';html+=`<${tag}>${c.innerText.trim()}</${tag}>`;});html+='</tr>';});
    html+='</tbody></table>';
    const blob=new Blob([html],{type:'application/vnd.ms-excel;charset=utf-8;'});
    const a=Object.assign(document.createElement('a'),{href:URL.createObjectURL(blob),download:'payments_'+new Date().toISOString().slice(0,10)+'.xls'});
    a.click();URL.revokeObjectURL(a.href);
}
</script>

<?php include __DIR__ . '/../../views/partials/footer.php'; ?>
