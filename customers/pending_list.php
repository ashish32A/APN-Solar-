<?php
// customers/pending_list.php — Pending Customer List

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

// ── Ensure extra columns exist ────────────────────────────────────────────────
$cols = $pdo->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_COLUMN);
$extra = ['jan_samarth' => "VARCHAR(100) DEFAULT NULL", 'model_number' => "VARCHAR(100) DEFAULT NULL", 'pincode' => "VARCHAR(10) DEFAULT NULL", 'address' => "TEXT DEFAULT NULL"];
foreach ($extra as $c => $def) {
    if (!in_array($c, $cols)) {
        try { $pdo->exec("ALTER TABLE customers ADD COLUMN `$c` $def"); } catch(PDOException $e) {}
    }
}

// ── Filters ───────────────────────────────────────────────────────────────────
$regFrom = trim($_GET['reg_from']  ?? '');
$regTo   = trim($_GET['reg_to']    ?? '');
$jsFrom  = trim($_GET['js_from']   ?? '');
$jsTo    = trim($_GET['js_to']     ?? '');
$group   = trim($_GET['group']     ?? '');
$district= trim($_GET['district']  ?? '');
$search  = trim($_GET['search']    ?? '');

$validPerPage = [10, 25, 50, 100];
$reqPerPage   = (int)($_GET['per_page'] ?? 25);
$perPage      = in_array($reqPerPage, $validPerPage) ? $reqPerPage : 25;
$page         = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($page - 1) * $perPage;

$where  = "WHERE c.status NOT IN ('not_interested')";
$params = [];

if ($regFrom !== '') { $where .= " AND DATE(c.created_at) >= ?"; $params[] = $regFrom; }
if ($regTo   !== '') { $where .= " AND DATE(c.created_at) <= ?"; $params[] = $regTo; }
if ($jsFrom  !== '') { $where .= " AND DATE(c.updated_at) >= ?"; $params[] = $jsFrom; }
if ($jsTo    !== '') { $where .= " AND DATE(c.updated_at) <= ?"; $params[] = $jsTo; }
if ($group   !== '') { $where .= " AND c.group_name = ?";        $params[] = $group; }
if ($district!== '') { $where .= " AND c.district_name LIKE ?";  $params[] = "%$district%"; }
if ($search  !== '') {
    $where .= " AND (c.name LIKE ? OR c.mobile LIKE ? OR c.electricity_id LIKE ? OR c.operator_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}

// Total count
try {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM customers c $where");
    $cnt->execute($params);
    $totalRecords = (int)$cnt->fetchColumn();
} catch (PDOException $e) { $totalRecords = 0; }

$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page       = min($page, $totalPages);
$startRecord = $totalRecords > 0 ? ($page - 1) * $perPage + 1 : 0;
$endRecord   = min($page * $perPage, $totalRecords);

// Data
try {
    $sql = "
        SELECT c.id, c.operator_name, c.group_name, c.name, c.email, c.mobile,
               c.district_name, c.electricity_id, c.kw, c.remarks, c.followup_remarks,
               c.jan_samarth, c.created_at, c.updated_at, c.ifsc_code,
               p.total_amount, p.due_amount
        FROM customers c
        LEFT JOIN payments p ON c.id = p.customer_id
        $where
        ORDER BY c.id DESC
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $customers = [];
}

// Groups dropdown
$allGroups = $pdo->query("SELECT DISTINCT group_name FROM customers WHERE group_name != '' ORDER BY group_name")->fetchAll(PDO::FETCH_COLUMN);

// Flash
$flash = null;
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

function plUrl(array $ov = []): string {
    $p = array_merge([
        'reg_from' => $_GET['reg_from'] ?? '', 'reg_to'   => $_GET['reg_to']   ?? '',
        'js_from'  => $_GET['js_from']  ?? '', 'js_to'    => $_GET['js_to']    ?? '',
        'group'    => $_GET['group']    ?? '', 'district' => $_GET['district'] ?? '',
        'search'   => $_GET['search']   ?? '', 'per_page' => $_GET['per_page'] ?? 25,
        'page'     => $_GET['page']     ?? 1,
    ], $ov);
    return '/APN-Solar/customers/pending_list.php?' . http_build_query(array_filter($p, fn($v) => $v !== ''));
}

$pageTitle = 'Pending Customer List';
include __DIR__ . '/../views/partials/header.php';
?>

<style>
/* ── Filters ── */
.pl-filters { background:#fff; border:1px solid #dee2e6; border-radius:4px; padding:10px 14px; margin-bottom:10px; }
.pl-filters form { display:flex; flex-wrap:wrap; gap:8px; align-items:flex-end; }
.pl-fg { display:flex; flex-direction:column; gap:2px; }
.pl-fg label { font-size:.72rem; font-weight:700; color:#6c757d; }
.pl-fg input, .pl-fg select { padding:5px 9px; border:1px solid #ced4da; border-radius:3px; font-size:.82rem; font-family:inherit; }
.pl-fg input[type="date"]   { width:135px; }
.pl-fg input[type="text"]   { width:140px; }
.pl-fg select               { min-width:130px; }

/* ── Buttons ── */
.btn { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:3px;
       font-size:.78rem; font-weight:600; cursor:pointer; border:none; font-family:inherit;
       transition:opacity .15s; text-decoration:none; white-space:nowrap; }
.btn-filter  { background:#007bff; color:#fff; }
.btn-reset   { background:#6c757d; color:#fff; }
.btn-excel   { background:#1d6f42; color:#fff; }
.btn-edit    { background:#ffc107; color:#212529; }
.btn-ni      { background:#6c757d; color:#fff; }
.btn-followup{ background:#fd7e14; color:#fff; }
.btn-status  { background:#28a745; color:#fff; }
.btn-sm      { padding:3px 8px; font-size:.73rem; }
.btn:hover   { opacity:.85; }

/* ── Export bar ── */
.dt-bar  { margin-bottom:8px; }

/* ── Ctrl bar ── */
.ctrl-bar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:6px; }
.search-box { display:flex; align-items:center; gap:6px; font-size:.82rem; }
.search-box input { padding:5px 10px; border:1px solid #ced4da; border-radius:3px; width:200px; font-size:.82rem; font-family:inherit; }

/* ── Table ── */
.table-wrap { background:#fff; border:1px solid #dee2e6; border-radius:3px; overflow:hidden; }
.table-responsive { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.table { width:100%; border-collapse:collapse; font-size:.78rem; color:#212529; }
.table thead th { background:#f8f9fa; padding:7px 8px; font-weight:700; font-size:.7rem;
                  text-transform:uppercase; letter-spacing:.03em; color:#6c757d;
                  border-bottom:2px solid #dee2e6; border-right:1px solid #dee2e6;
                  white-space:nowrap; text-align:left; cursor:pointer; user-select:none; position:sticky; top:0; z-index:1; }
.table thead th::after { content:' ⇅'; opacity:.2; font-size:.55rem; }
.table thead th.sort-asc::after  { content:' ↑'; opacity:1; }
.table thead th.sort-desc::after { content:' ↓'; opacity:1; }
.table tbody td { padding:6px 8px; border-bottom:1px solid #f1f3f5; border-right:1px solid #f0f0f0; vertical-align:top; }
.table tbody tr:hover { background:#fffbf0; }
.table tbody tr:last-child td { border-bottom:none; }
.table tbody tr.hidden-row { display:none; }

/* ── Action stack ── */
.action-col { display:flex; flex-direction:column; gap:3px; min-width:100px; }

/* ── Flash ── */
.flash { display:flex; align-items:center; gap:8px; padding:10px 14px; border-radius:5px; font-size:.875rem; font-weight:500; margin-bottom:10px; }
.flash-success { background:#d4edda; border:1px solid #c3e6cb; color:#155724; }
.flash-error   { background:#f8d7da; border:1px solid #f5c6cb; color:#721c24; }

/* ── Pagination ── */
.pag-bar { display:flex; align-items:center; justify-content:space-between; padding:8px 12px; border-top:1px solid #dee2e6; flex-wrap:wrap; gap:6px; font-size:.8rem; color:#6c757d; }
.pagination { display:flex; gap:2px; }
.pg-btn { display:inline-flex; align-items:center; justify-content:center; min-width:32px; height:30px; padding:0 6px;
          border:1px solid #dee2e6; border-radius:3px; background:#fff; color:#007bff; font-size:.78rem; font-weight:600; cursor:pointer; text-decoration:none; }
.pg-btn:hover    { background:#e9ecef; }
.pg-btn.active   { background:#007bff; border-color:#007bff; color:#fff; }
.pg-btn.disabled { opacity:.5; pointer-events:none; color:#6c757d; }

/* ── Delete / NI modal ── */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.open { display:flex; }
.modal-box { background:#fff; border-radius:6px; padding:28px 32px; max-width:400px; width:90%; text-align:center; box-shadow:0 10px 40px rgba(0,0,0,.25); }
.modal-icon { font-size:2.2rem; margin-bottom:10px; }
.modal-box h3 { font-size:1rem; font-weight:700; margin-bottom:6px; }
.modal-box p  { color:#6c757d; font-size:.875rem; margin-bottom:14px; }
.modal-actions { display:flex; gap:8px; justify-content:center; }
</style>

<?php if ($flash): ?>
<div class="flash flash-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($flash['message']); ?>
</div>
<?php endif; ?>

<!-- Title -->
<h2 style="font-size:1.1rem;font-weight:700;margin-bottom:10px;color:#212529;">
    Pending Customer List:-
    <span style="font-weight:400;color:#6c757d;">(<?php echo number_format($totalRecords); ?>)</span>
</h2>

<!-- Filters -->
<div class="pl-filters">
    <form method="GET" action="/APN-Solar/customers/pending_list.php">
        <div class="pl-fg">
            <label>Reg From:</label>
            <input type="date" name="reg_from" value="<?php echo htmlspecialchars($regFrom); ?>">
        </div>
        <div class="pl-fg">
            <label>Reg To:</label>
            <input type="date" name="reg_to" value="<?php echo htmlspecialchars($regTo); ?>">
        </div>
        <div class="pl-fg">
            <label>J S From:</label>
            <input type="date" name="js_from" value="<?php echo htmlspecialchars($jsFrom); ?>">
        </div>
        <div class="pl-fg">
            <label>J S To:</label>
            <input type="date" name="js_to" value="<?php echo htmlspecialchars($jsTo); ?>">
        </div>
        <div class="pl-fg">
            <label>Group:</label>
            <select name="group">
                <option value="">All Groups</option>
                <?php foreach ($allGroups as $g): ?>
                    <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $group === $g ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($g); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="pl-fg">
            <label>District:</label>
            <input type="text" name="district" placeholder="Enter district" value="<?php echo htmlspecialchars($district); ?>">
        </div>
        <div style="display:flex;gap:6px;align-items:flex-end;">
            <button type="submit" class="btn btn-filter"><i class="fas fa-filter"></i> Filter</button>
            <a href="/APN-Solar/customers/pending_list.php" class="btn btn-reset"><i class="fas fa-undo"></i> Reset</a>
        </div>
    </form>
</div>

<!-- Export -->
<div class="dt-bar">
    <button class="btn btn-excel" onclick="plExportExcel()"><i class="fas fa-file-excel"></i> Excel</button>
</div>

<!-- Controls -->
<div class="ctrl-bar">
    <div style="display:flex;align-items:center;gap:8px;font-size:.82rem;">
        <form method="GET" action="/APN-Solar/customers/pending_list.php" style="display:flex;align-items:center;gap:6px;">
            <?php foreach (['reg_from'=>$regFrom,'reg_to'=>$regTo,'js_from'=>$jsFrom,'js_to'=>$jsTo,'group'=>$group,'district'=>$district,'search'=>$search] as $k=>$v): if($v): ?>
            <input type="hidden" name="<?php echo $k; ?>" value="<?php echo htmlspecialchars($v); ?>">
            <?php endif; endforeach; ?>
            Show
            <select name="per_page" onchange="this.form.submit()">
                <?php foreach ([10,25,50,100] as $n): ?>
                    <option value="<?php echo $n; ?>" <?php echo $perPage == $n ? 'selected' : ''; ?>><?php echo $n; ?></option>
                <?php endforeach; ?>
            </select>
            entries
        </form>
    </div>
    <div class="search-box">
        <span>Search:</span>
        <input type="text" id="plSearch" placeholder="Name, mobile, ID..."
               oninput="plFilter(this.value)" value="<?php echo htmlspecialchars($search); ?>">
    </div>
</div>

<!-- Table -->
<div class="table-wrap">
    <div class="table-responsive">
        <table class="table" id="plTable">
            <thead>
                <tr>
                    <th onclick="plSort(0)">Sr<br>No.</th>
                    <th onclick="plSort(1)">Operator<br>Name</th>
                    <th onclick="plSort(2)">Group<br>Name</th>
                    <th onclick="plSort(3)">Name</th>
                    <th onclick="plSort(4)">Email</th>
                    <th onclick="plSort(5)">Mobile</th>
                    <th onclick="plSort(6)">District</th>
                    <th onclick="plSort(7)">Bank<br>Name</th>
                    <th onclick="plSort(8)">Bank<br>Branch</th>
                    <th onclick="plSort(9)">IFSC<br>Code</th>
                    <th onclick="plSort(10)">J S<br>date</th>
                    <th onclick="plSort(11)">Electricity<br>Id</th>
                    <th onclick="plSort(12)">Kw</th>
                    <th onclick="plSort(13)">Total<br>Amount</th>
                    <th onclick="plSort(14)">Due<br>Amount</th>
                    <th onclick="plSort(15)">Remarks</th>
                    <th onclick="plSort(16)">Followup<br>Remarks</th>
                    <th onclick="plSort(17)">created<br>date</th>
                    <th onclick="plSort(18)">Updated<br>date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($customers) > 0):
                $rowNum = ($page - 1) * $perPage + 1;
                foreach ($customers as $row): ?>
                <tr>
                    <td><?php echo $rowNum++; ?></td>
                    <td><?php echo htmlspecialchars($row['operator_name'] ?? ''); ?></td>
                    <td style="min-width:110px;"><?php echo htmlspecialchars($row['group_name'] ?? ''); ?></td>
                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                    <td style="min-width:130px;"><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['mobile'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['district_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['bank_name'] ?? 'NA'); ?></td>
                    <td><?php echo htmlspecialchars($row['bank_branch'] ?? 'NA'); ?></td>
                    <td><?php echo htmlspecialchars($row['ifsc_code'] ?? ''); ?></td>
                    <td style="white-space:nowrap;"><?php echo htmlspecialchars(substr($row['jan_samarth'] ?? '', 0, 10)); ?></td>
                    <td><?php echo htmlspecialchars($row['electricity_id'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['kw'] ?? ''); ?></td>
                    <td><?php echo !empty($row['total_amount']) ? number_format($row['total_amount'], 2) : ''; ?></td>
                    <td><?php echo !empty($row['due_amount']) ? number_format($row['due_amount'], 2) : ''; ?></td>
                    <td style="min-width:160px;max-width:200px;word-wrap:break-word;"><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></td>
                    <td style="min-width:130px;max-width:180px;word-wrap:break-word;"><?php echo htmlspecialchars($row['followup_remarks'] ?? ''); ?></td>
                    <td style="white-space:nowrap;font-size:.73rem;"><?php echo htmlspecialchars(substr($row['created_at'] ?? '', 0, 16)); ?></td>
                    <td style="white-space:nowrap;font-size:.73rem;"><?php echo htmlspecialchars(substr($row['updated_at'] ?? '', 0, 16)); ?></td>
                    <td>
                        <div class="action-col">
                            <a href="/APN-Solar/customers/edit.php?id=<?php echo (int)$row['id']; ?>&back=pending"
                               class="btn btn-edit btn-sm"><i class="fas fa-pencil-alt"></i> Edit</a>
                            <button class="btn btn-ni btn-sm"
                                    onclick="plNI(<?php echo (int)$row['id']; ?>, '<?php echo addslashes(htmlspecialchars($row['name'])); ?>')">
                                <i class="fas fa-ban"></i> NotInterested
                            </button>
                            <a href="/APN-Solar/customers/followup.php?id=<?php echo (int)$row['id']; ?>&back=pending"
                               class="btn btn-followup btn-sm"><i class="fas fa-phone-alt"></i> Followup</a>
                            <a href="/APN-Solar/customers/customer_status_view.php?id=<?php echo (int)$row['id']; ?>&back=pending"
                               class="btn btn-status btn-sm"><i class="fas fa-eye"></i> Show Status</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="20" style="text-align:center;padding:40px;color:#94a3b8;">
                    <i class="fas fa-users" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                    No pending customers found<?php echo ($group || $district || $search || $regFrom || $regTo) ? ' matching your filters.' : '.'; ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pag-bar">
        <span>
            Showing <strong><?php echo number_format($startRecord); ?>–<?php echo number_format($endRecord); ?></strong>
            of <strong><?php echo number_format($totalRecords); ?></strong> entries
        </span>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a class="pg-btn" href="<?php echo plUrl(['page' => $page - 1]); ?>">Previous</a>
            <?php else: ?>
                <span class="pg-btn disabled">Previous</span>
            <?php endif; ?>

            <?php
            $win = 3; $ps = max(1,$page-$win); $pe = min($totalPages,$page+$win);
            if ($ps > 1): ?>
                <a class="pg-btn" href="<?php echo plUrl(['page'=>1]); ?>">1</a>
                <?php if ($ps > 2): ?><span class="pg-btn disabled">…</span><?php endif;
            endif;
            for ($p=$ps; $p<=$pe; $p++): ?>
                <a class="pg-btn <?php echo $p===$page ? 'active':''; ?>"
                   href="<?php echo plUrl(['page'=>$p]); ?>"><?php echo $p; ?></a>
            <?php endfor;
            if ($pe < $totalPages):
                if ($pe < $totalPages-1): ?><span class="pg-btn disabled">…</span><?php endif; ?>
                <a class="pg-btn" href="<?php echo plUrl(['page'=>$totalPages]); ?>"><?php echo $totalPages; ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a class="pg-btn" href="<?php echo plUrl(['page' => $page + 1]); ?>">Next</a>
            <?php else: ?>
                <span class="pg-btn disabled">Next</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Not Interested Modal -->
<div class="modal-overlay" id="niModal">
    <div class="modal-box">
        <div class="modal-icon" style="color:#6c757d;"><i class="fas fa-ban"></i></div>
        <h3>Mark as Not Interested?</h3>
        <p id="niModalMsg">This will mark the customer as Not Interested.</p>
        <div class="modal-actions">
            <button class="btn btn-ni" id="niConfirmBtn"><i class="fas fa-check"></i> Confirm</button>
            <button class="btn btn-reset" onclick="document.getElementById('niModal').classList.remove('open')">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </div>
</div>

<script>
/* ── Client-side search ── */
function plFilter(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#plTable tbody tr').forEach(row => {
        const text = row.innerText.toLowerCase();
        row.classList.toggle('hidden-row', q !== '' && !text.includes(q));
    });
}

/* ── Column sort ── */
let plSortCol = -1, plSortAsc = true;
function plSort(col) {
    const ths = document.querySelectorAll('#plTable thead th');
    ths.forEach(th => th.classList.remove('sort-asc','sort-desc'));
    if (plSortCol === col) { plSortAsc = !plSortAsc; } else { plSortCol = col; plSortAsc = true; }
    ths[col].classList.add(plSortAsc ? 'sort-asc' : 'sort-desc');
    const tbody = document.querySelector('#plTable tbody');
    const rows  = [...tbody.querySelectorAll('tr')].filter(r => !r.querySelector('[colspan]'));
    rows.sort((a, b) => {
        const va = a.cells[col]?.innerText.trim() ?? '';
        const vb = b.cells[col]?.innerText.trim() ?? '';
        const na = parseFloat(va.replace(/[^0-9.\-]/g,''));
        const nb = parseFloat(vb.replace(/[^0-9.\-]/g,''));
        const cmp = (!isNaN(na) && !isNaN(nb)) ? na - nb : va.localeCompare(vb);
        return plSortAsc ? cmp : -cmp;
    });
    rows.forEach(r => tbody.appendChild(r));
}

/* ── Not Interested ── */
let niTargetId = null;
function plNI(id, name) {
    niTargetId = id;
    document.getElementById('niModalMsg').textContent = 'Mark "' + name + '" as Not Interested?';
    document.getElementById('niModal').classList.add('open');
}
document.getElementById('niModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
});
document.getElementById('niConfirmBtn').addEventListener('click', function() {
    if (!niTargetId) return;
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    fetch('/APN-Solar/customers/ajax_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + niTargetId + '&status=not_interested'
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) { location.reload(); }
        else { alert('Error: ' + (d.message || 'Unknown error')); this.disabled = false; this.innerHTML = '<i class="fas fa-check"></i> Confirm'; }
    })
    .catch(() => { alert('Request failed'); this.disabled = false; this.innerHTML = '<i class="fas fa-check"></i> Confirm'; });
});

/* ── Export Excel ── */
function plExportExcel() {
    const rows = document.querySelectorAll('#plTable tr');
    let html = '<table border="1"><thead>';
    let inBody = false;
    rows.forEach(row => {
        const isHead = row.closest('thead');
        if (isHead && !inBody) html += '<tr>';
        else if (!isHead && !inBody) { html += '</thead><tbody><tr>'; inBody = true; }
        else html += '<tr>';
        row.querySelectorAll('th,td').forEach((cell, i) => {
            if (i === 19) return; // skip Actions
            const tag = isHead ? 'th' : 'td';
            html += `<${tag}>${cell.innerText.trim()}</${tag}>`;
        });
        html += '</tr>';
    });
    html += '</tbody></table>';
    const blob = new Blob([html], {type:'application/vnd.ms-excel;charset=utf-8;'});
    const a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(blob),
        download: 'pending_customers_' + new Date().toISOString().slice(0,10) + '.xls'
    });
    a.click(); URL.revokeObjectURL(a.href);
}
</script>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
