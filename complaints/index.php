<?php
// complaints/index.php — Customer Complaints list

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

// ── Auto-add missing columns to complaints ────────────────────────────────────
try {
    $cmpCols = $pdo->query("SHOW COLUMNS FROM complaints")->fetchAll(PDO::FETCH_COLUMN);
    $addCols = [
        'complaint_no'          => "VARCHAR(20) DEFAULT NULL",
        'operator_name'         => "VARCHAR(100) DEFAULT NULL",
        'address'               => "TEXT DEFAULT NULL",
        'district'              => "VARCHAR(100) DEFAULT NULL",
        'electricity_account_no'=> "VARCHAR(50) DEFAULT NULL",
        'kw'                    => "DECIMAL(5,2) DEFAULT NULL",
        'division'              => "VARCHAR(100) DEFAULT NULL",
        'issue'                 => "VARCHAR(100) DEFAULT NULL",
        'remarks'               => "TEXT DEFAULT NULL",
    ];
    foreach ($addCols as $col => $def) {
        if (!in_array($col, $cmpCols)) {
            $pdo->exec("ALTER TABLE complaints ADD COLUMN `$col` $def");
        }
    }
    // Expand status ENUM for complaints
    $pdo->exec("ALTER TABLE complaints MODIFY COLUMN status
        ENUM('open','pending','in_progress','resolved','closed') DEFAULT 'open'");

    // Create complaint_followups table
    $pdo->exec("CREATE TABLE IF NOT EXISTS complaint_followups (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        complaint_id INT NOT NULL,
        remarks      TEXT,
        status       ENUM('Pending','In Progress','Resolved','Re Open','Closed') DEFAULT 'Pending',
        created_by   INT DEFAULT NULL,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE
    )");

    // Auto-number complaint_no for existing rows
    $pdo->exec("UPDATE complaints SET complaint_no = CONCAT('CPD-', LPAD(id, 4, '0')) WHERE complaint_no IS NULL OR complaint_no = ''");
} catch (PDOException $e) {}

$pageTitle    = 'Customer Complaints';
$validPerPage = [10, 25, 50, 100];
$requestedPer = (int)($_GET['per_page'] ?? 10);
$perPage      = in_array($requestedPer, $validPerPage) ? $requestedPer : 10;
$page         = max(1, (int)($_GET['page'] ?? 1));
$search       = trim($_GET['search'] ?? '');

$where  = "WHERE 1=1";
$params = [];
if ($search !== '') {
    $where .= " AND (c.complaint_no LIKE ? OR cu.name LIKE ? OR c.district LIKE ? OR c.issue LIKE ? OR c.operator_name LIKE ?)";
    $like   = "%$search%";
    $params = [$like, $like, $like, $like, $like];
}

try {
    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints c LEFT JOIN customers cu ON c.customer_id = cu.id $where");
    $cStmt->execute($params);
    $totalRecords = (int)$cStmt->fetchColumn();
} catch (PDOException $e) { $totalRecords = 0; }

$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

try {
    $stmt = $pdo->prepare("
        SELECT c.*, cu.name AS customer_name, cu.mobile AS customer_mobile,
               cu.kw AS customer_kw, cu.electricity_id, cu.district_name
        FROM complaints c
        LEFT JOIN customers cu ON c.customer_id = cu.id
        $where
        ORDER BY c.id DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $complaints = $stmt->fetchAll();
} catch (PDOException $e) { $complaints = []; }

$flash = null;
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

include __DIR__ . '/../views/partials/header.php';

function cmpUrl(array $ov = []): string {
    $p = array_merge(['search' => $_GET['search'] ?? '', 'page' => $_GET['page'] ?? 1, 'per_page' => $_GET['per_page'] ?? 10], $ov);
    $q = http_build_query(array_filter($p, fn($v) => $v !== ''));
    return '/APN-Solar/complaints/index.php' . ($q ? '?' . $q : '');
}
?>

<style>
.cm-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:10px; }
.cm-header h2 { font-size:1.15rem;font-weight:700;color:#1e293b; }
.btn { display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:7px;font-size:.83rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;white-space:nowrap; }
.btn-primary  { background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff; }
.btn-excel    { background:#1d6f42;color:#fff; }
.btn-pdf      { background:#dc2626;color:#fff; }
.btn-print    { background:#374151;color:#fff; }
.btn-warning  { background:#f59e0b;color:#fff; }
.btn-danger   { background:#ef4444;color:#fff; }
.btn-info     { background:#0891b2;color:#fff; }
.btn-secondary{ background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0; }
.btn-sm       { padding:4px 10px;font-size:.74rem; }
.btn:hover    { opacity:.87;transform:translateY(-1px); }
.top-btns { display:flex;gap:7px;margin-bottom:12px;flex-wrap:wrap; }
.action-stack { display:flex;flex-direction:column;gap:3px;min-width:70px; }

.ctrl-bar { display:flex;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:8px;margin-bottom:10px; }
.search-wrap { display:flex;align-items:center;gap:6px;font-size:.82rem;color:#64748b; }
.search-wrap input { padding:6px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.82rem;font-family:inherit;outline:none;width:210px; }
.search-wrap input:focus { border-color:#3b82f6; }

.table-wrap { background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden; }
.table-responsive { overflow-x:auto;-webkit-overflow-scrolling:touch; }
.table { width:100%;border-collapse:collapse;font-size:.78rem;color:#1e293b; }
.table thead th { background:#f0f4f8;padding:8px 9px;font-weight:700;font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;border-bottom:2px solid #e2e8f0;white-space:nowrap;text-align:left; }
.table thead th.sortable { cursor:pointer;user-select:none; }
.table thead th.sortable::after { content:' ⇅';opacity:.3;font-size:.6rem; }
.table thead th.sort-asc::after { content:' ↑';opacity:1; }
.table thead th.sort-desc::after{ content:' ↓';opacity:1; }
.table tbody td { padding:8px 9px;border-bottom:1px solid #f1f5f9;vertical-align:top; }
.table tbody tr:hover { background:#f8fbff; }
.table tbody tr:last-child td { border-bottom:none; }
.table tbody tr.hidden-row { display:none; }

/* Status badges */
.badge { display:inline-block;border-radius:5px;padding:2px 9px;font-size:.68rem;font-weight:700;white-space:nowrap; }
.badge-closed      { background:#1e293b;color:#fff; }
.badge-pending     { background:#f59e0b;color:#fff; }
.badge-open        { background:#e2e8f0;color:#475569; }
.badge-in_progress { background:#3b82f6;color:#fff; }
.badge-resolved    { background:#22c55e;color:#fff; }

.flash-alert   { display:flex;align-items:center;gap:9px;padding:11px 15px;border-radius:8px;font-size:.875rem;font-weight:500;margin-bottom:12px; }
.flash-success { background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a; }
.flash-error   { background:#fef2f2;border:1px solid #fecaca;color:#dc2626; }

.pagination-bar { display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:1px solid #e2e8f0;flex-wrap:wrap;gap:8px;font-size:.8rem;color:#64748b; }
.pagination { display:flex;gap:4px;flex-wrap:wrap; }
.pg-btn { display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border:1.5px solid #e2e8f0;border-radius:6px;background:#fff;color:#475569;font-size:.78rem;font-weight:600;cursor:pointer;text-decoration:none;transition:all .15s; }
.pg-btn:hover    { background:#f1f5f9; }
.pg-btn.active   { background:#3b82f6;border-color:#3b82f6;color:#fff; }
.pg-btn.disabled { opacity:.4;pointer-events:none; }

/* Delete confirm modal */
.modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9998;align-items:center;justify-content:center; }
.modal-overlay.open { display:flex; }
.modal-box { background:#fff;border-radius:12px;padding:32px 36px;max-width:420px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25); }
.modal-icon { font-size:2.8rem;color:#ef4444;margin-bottom:14px; }
.modal-box h3 { font-size:1.1rem;font-weight:700;color:#1e293b;margin-bottom:8px; }
.modal-box p  { color:#64748b;font-size:.88rem;margin-bottom:18px; }
.modal-actions-row { display:flex;gap:10px;justify-content:center; }
</style>

<?php if ($flash): ?>
<div class="flash-alert flash-<?php echo $flash['type']==='success'?'success':'error'; ?>">
    <i class="fas fa-<?php echo $flash['type']==='success'?'check-circle':'exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($flash['message']); ?>
</div>
<?php endif; ?>

<div class="cm-header">
    <h2>
        <i class="fas fa-exclamation-circle" style="color:#3b82f6;margin-right:7px;"></i>
        Customer Complaints
        <span style="font-weight:400;color:#94a3b8;margin-left:4px;">(<?php echo number_format($totalRecords); ?>)</span>
    </h2>
    <a href="/APN-Solar/complaints/create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Create Complaint
    </a>
</div>

<div class="top-btns">
    <button class="btn btn-excel btn-sm" onclick="cmpExport('excel')"><i class="fas fa-file-excel"></i> Excel</button>
    <button class="btn btn-pdf btn-sm"   onclick="cmpExport('pdf')"><i class="fas fa-file-pdf"></i> PDF</button>
    <button class="btn btn-print btn-sm" onclick="cmpPrint()"><i class="fas fa-print"></i> Print</button>
</div>

<div class="ctrl-bar">
    <div class="search-wrap">
        <label>Search:</label>
        <input type="text" id="cmpSearch" placeholder="Complaint no., customer, district..."
               oninput="cmpFilter(this.value)" value="<?php echo htmlspecialchars($search); ?>">
    </div>
</div>

<div class="table-wrap">
    <div class="table-responsive">
        <table class="table" id="cmpTable">
            <thead>
                <tr>
                    <th class="sortable" onclick="cmpSort(0)" style="width:45px;">Sr No.</th>
                    <th class="sortable" onclick="cmpSort(1)">Complain<br>No.</th>
                    <th class="sortable" onclick="cmpSort(2)">Customer</th>
                    <th class="sortable" onclick="cmpSort(3)">Mobile<br>no</th>
                    <th class="sortable" onclick="cmpSort(4)">Operator<br>Name no</th>
                    <th class="sortable" onclick="cmpSort(5)">Address</th>
                    <th class="sortable" onclick="cmpSort(6)">District</th>
                    <th class="sortable" onclick="cmpSort(7)">Electricity<br>Account No</th>
                    <th class="sortable" onclick="cmpSort(8)">KW</th>
                    <th class="sortable" onclick="cmpSort(9)">Division</th>
                    <th class="sortable" onclick="cmpSort(10)">Issue</th>
                    <th class="sortable" onclick="cmpSort(11)">Status</th>
                    <th class="sortable" onclick="cmpSort(12)">Remarks</th>
                    <th style="width:80px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($complaints):
                $rowNum = ($page-1)*$perPage+1;
                foreach ($complaints as $c): ?>
                <tr>
                    <td><?php echo $rowNum++; ?></td>
                    <td><strong><?php echo htmlspecialchars($c['complaint_no'] ?? ''); ?></strong></td>
                    <td><?php echo htmlspecialchars($c['customer_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($c['customer_mobile'] ?? $c['mobile'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($c['operator_name'] ?? ''); ?></td>
                    <td style="max-width:180px;word-wrap:break-word;font-size:.74rem;"><?php echo htmlspecialchars($c['address'] ?? $c['description'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($c['district'] ?? $c['district_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($c['electricity_account_no'] ?? $c['electricity_id'] ?? ''); ?></td>
                    <td><?php echo !empty($c['kw']) ? number_format((float)$c['kw'], 2) : (!empty($c['customer_kw']) ? number_format((float)$c['customer_kw'], 2) : ''); ?></td>
                    <td><?php echo htmlspecialchars($c['division'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($c['issue'] ?? $c['complaint_type'] ?? ''); ?></td>
                    <td>
                        <?php
                        $st = $c['status'] ?? 'open';
                        $label = match($st) {
                            'closed'      => 'Closed',
                            'pending'     => 'Pending',
                            'in_progress' => 'In Progress',
                            'resolved'    => 'Resolved',
                            default       => 'Open',
                        };
                        echo "<span class=\"badge badge-$st\">$label</span>";
                        ?>
                    </td>
                    <td style="max-width:130px;word-wrap:break-word;font-size:.74rem;"><?php echo htmlspecialchars($c['remarks'] ?? $c['resolved_note'] ?? ''); ?></td>
                    <td>
                        <div class="action-stack">
                            <a href="/APN-Solar/complaints/edit.php?id=<?php echo (int)$c['id']; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </a>
                            <button class="btn btn-danger btn-sm"
                                    onclick="cmpConfirmDelete(<?php echo (int)$c['id']; ?>,'<?php echo addslashes(htmlspecialchars($c['complaint_no']??'this complaint')); ?>')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                            <a href="/APN-Solar/complaints/status.php?id=<?php echo (int)$c['id']; ?>" class="btn btn-info btn-sm">
                                <i class="fas fa-tasks"></i> Show Status
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="14" style="text-align:center;padding:40px;color:#94a3b8;">
                    <i class="fas fa-exclamation-circle" style="font-size:2rem;display:block;margin-bottom:10px;color:#bfdbfe;"></i>
                    No complaints found<?php echo $search ? ' matching your search.' : '.'; ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="pagination-bar">
        <span>Showing <strong><?php echo $totalRecords>0?number_format(($page-1)*$perPage+1):0; ?>–<?php echo number_format(min($page*$perPage,$totalRecords)); ?></strong> of <strong><?php echo number_format($totalRecords); ?></strong> entries</span>
        <div class="pagination">
            <?php if($page>1):?><a class="pg-btn" href="<?php echo cmpUrl(['page'=>$page-1]);?>">Previous</a><?php else:?><span class="pg-btn disabled">Previous</span><?php endif;?>
            <?php $ws=max(1,$page-2);$we=min($totalPages,$page+2);
            if($ws>1){echo '<a class="pg-btn" href="'.cmpUrl(['page'=>1]).'">1</a>';if($ws>2)echo '<span class="pg-btn disabled">…</span>';}
            for($p=$ws;$p<=$we;$p++) echo '<a class="pg-btn '.($p===$page?'active':'').'" href="'.cmpUrl(['page'=>$p]).'">'.$p.'</a>';
            if($we<$totalPages){if($we<$totalPages-1)echo '<span class="pg-btn disabled">…</span>';echo '<a class="pg-btn" href="'.cmpUrl(['page'=>$totalPages]).'">'.$totalPages.'</a>';}
            ?>
            <?php if($page<$totalPages):?><a class="pg-btn" href="<?php echo cmpUrl(['page'=>$page+1]);?>">Next</a><?php else:?><span class="pg-btn disabled">Next</span><?php endif;?>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon"><i class="fas fa-trash-alt"></i></div>
        <h3>Delete Complaint?</h3>
        <p id="deleteMsg">This will permanently delete the complaint and all its followups.</p>
        <div class="modal-actions-row">
            <form id="deleteForm" method="POST" action="/APN-Solar/complaints/delete.php">
                <input type="hidden" name="id" id="deleteId">
                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> Yes, Delete</button>
            </form>
            <button class="btn btn-secondary btn-sm" onclick="document.getElementById('deleteModal').classList.remove('open')">Cancel</button>
        </div>
    </div>
</div>

<script>
function cmpFilter(q) {
    q=q.toLowerCase();
    document.querySelectorAll('#cmpTable tbody tr').forEach(r=>{r.classList.toggle('hidden-row',q!==''&&!r.innerText.toLowerCase().includes(q));});
}
let cSortCol=-1,cSortAsc=true;
function cmpSort(col) {
    const ths=document.querySelectorAll('#cmpTable thead th');
    ths.forEach(t=>t.classList.remove('sort-asc','sort-desc'));
    if(cSortCol===col){cSortAsc=!cSortAsc;}else{cSortCol=col;cSortAsc=true;}
    ths[col].classList.add(cSortAsc?'sort-asc':'sort-desc');
    const tbody=document.querySelector('#cmpTable tbody');
    const rows=[...tbody.querySelectorAll('tr')].filter(r=>!r.querySelector('[colspan]'));
    rows.sort((a,b)=>{const va=a.cells[col]?.innerText.trim()||'',vb=b.cells[col]?.innerText.trim()||'';const na=parseFloat(va),nb=parseFloat(vb);const c=(!isNaN(na)&&!isNaN(nb))?na-nb:va.localeCompare(vb);return cSortAsc?c:-c;});
    rows.forEach(r=>tbody.appendChild(r));
}
function cmpConfirmDelete(id,no) {
    document.getElementById('deleteId').value=id;
    document.getElementById('deleteMsg').textContent='Delete complaint "'+no+'" and all its followups?';
    document.getElementById('deleteModal').classList.add('open');
}
document.getElementById('deleteModal').addEventListener('click',e=>{if(e.target===document.getElementById('deleteModal'))document.getElementById('deleteModal').classList.remove('open');});

function cmpExport(type) {
    const rows=document.querySelectorAll('#cmpTable tr');
    let html='<table border="1"><thead>';let inBody=false;
    rows.forEach(row=>{const isHead=row.closest('thead');if(isHead&&!inBody)html+='<tr>';else if(!isHead&&!inBody){html+='</thead><tbody><tr>';inBody=true;}else html+='<tr>';
    row.querySelectorAll('th,td').forEach((c,i)=>{if(i===13)return;const tag=isHead?'th':'td';html+=`<${tag}>${c.innerText.trim()}</${tag}>`;});html+='</tr>';});
    html+='</tbody></table>';
    const blob=new Blob([html],{type:'application/vnd.ms-excel;charset=utf-8;'});
    const a=Object.assign(document.createElement('a'),{href:URL.createObjectURL(blob),download:'complaints_'+new Date().toISOString().slice(0,10)+'.xls'});
    a.click();URL.revokeObjectURL(a.href);
}
function cmpPrint() {
    const pw=window.open('','','width=1200,height=800');
    const ths=[...document.querySelectorAll('#cmpTable thead th')].filter((_,i)=>i!==13).map(t=>`<th>${t.innerText}</th>`).join('');
    const trs=[...document.querySelectorAll('#cmpTable tbody tr')].map(r=>{const tds=[...r.querySelectorAll('td')].filter((_,i)=>i!==13).map(c=>`<td>${c.innerText}</td>`).join('');return `<tr>${tds}</tr>`;}).join('');
    pw.document.write(`<!DOCTYPE html><html><head><title>Complaints</title><style>body{font-family:Arial,sans-serif;font-size:9px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ccc;padding:3px 5px;}th{background:#f1f5f9;}</style></head><body><h3>Customer Complaints</h3><table><thead><tr>${ths}</tr></thead><tbody>${trs}</tbody></table></body></html>`);
    pw.document.close();pw.focus();setTimeout(()=>{pw.print();pw.close();},400);
}
</script>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
