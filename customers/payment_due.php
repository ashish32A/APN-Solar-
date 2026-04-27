<?php
// customers/payment_due.php — Payment Due List

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

// ── Filters ───────────────────────────────────────────────────────────────────
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

$where  = "WHERE p.due_amount > 0";
$params = [];

if ($regFrom !== '') { $where .= " AND DATE(c.created_at) >= ?"; $params[] = $regFrom; }
if ($regTo   !== '') { $where .= " AND DATE(c.created_at) <= ?"; $params[] = $regTo; }
if ($group   !== '') { $where .= " AND c.group_name = ?";        $params[] = $group; }
if ($district!== '') { $where .= " AND c.district_name LIKE ?";  $params[] = "%$district%"; }
if ($search  !== '') {
    $where .= " AND (c.name LIKE ? OR c.mobile LIKE ? OR c.electricity_id LIKE ? OR c.operator_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}

// Total count
try {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM customers c INNER JOIN payments p ON c.id = p.customer_id $where");
    $cnt->execute($params);
    $totalRecords = (int)$cnt->fetchColumn();
} catch (PDOException $e) { $totalRecords = 0; }

$totalPages  = max(1, (int)ceil($totalRecords / $perPage));
$page        = min($page, $totalPages);
$startRecord = $totalRecords > 0 ? ($page - 1) * $perPage + 1 : 0;
$endRecord   = min($page * $perPage, $totalRecords);

// Summary totals
try {
    $totStmt = $pdo->prepare("
        SELECT SUM(p.total_amount) AS total, SUM(p.payment_received) AS received, SUM(p.due_amount) AS due
        FROM customers c INNER JOIN payments p ON c.id = p.customer_id $where
    ");
    $totStmt->execute($params);
    $totals = $totStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $totals = ['total'=>0,'received'=>0,'due'=>0]; }

// Data
try {
    $sql = "
        SELECT c.id, c.operator_name, c.group_name, c.name, c.mobile,
               c.district_name, c.electricity_id, c.kw, c.ifsc_code,
               c.remarks, c.created_at,
               p.total_amount, p.payment_received, p.due_amount, p.updated_at
        FROM customers c
        INNER JOIN payments p ON c.id = p.customer_id
        $where
        ORDER BY p.due_amount DESC, c.id DESC
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $customers = []; }

// Groups dropdown
$allGroups = $pdo->query("SELECT DISTINCT group_name FROM customers WHERE group_name != '' ORDER BY group_name")->fetchAll(PDO::FETCH_COLUMN);

// Flash
$flash = null;
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

function pdUrl(array $ov = []): string {
    $p = array_merge([
        'reg_from' => $_GET['reg_from'] ?? '', 'reg_to'   => $_GET['reg_to']   ?? '',
        'group'    => $_GET['group']    ?? '', 'district' => $_GET['district'] ?? '',
        'search'   => $_GET['search']   ?? '', 'per_page' => $_GET['per_page'] ?? 25,
        'page'     => $_GET['page']     ?? 1,
    ], $ov);
    return '/APN-Solar/customers/payment_due.php?' . http_build_query(array_filter($p, fn($v) => $v !== ''));
}

$pageTitle = 'Payment Due List';
include __DIR__ . '/../views/partials/header.php';
?>

<style>
/* ── Filters ── */
.pl-filters { background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:12px 16px; margin-bottom:12px; }
.pl-filters form { display:flex; flex-wrap:wrap; gap:8px; align-items:flex-end; }
.pl-fg { display:flex; flex-direction:column; gap:3px; }
.pl-fg label { font-size:.72rem; font-weight:700; color:#64748b; }
.pl-fg input, .pl-fg select { padding:5px 9px; border:1.5px solid #e2e8f0; border-radius:6px; font-size:.82rem; font-family:inherit; outline:none; }
.pl-fg input:focus, .pl-fg select:focus { border-color:#3b82f6; }
.pl-fg input[type="date"] { width:135px; }
.pl-fg input[type="text"] { width:140px; }
.pl-fg select              { min-width:130px; }

/* ── Summary Cards ── */
.summary-cards { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:14px; }
.s-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:14px 18px; display:flex; align-items:center; gap:12px; }
.s-card .s-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
.s-card .s-lbl  { font-size:.68rem; font-weight:700; text-transform:uppercase; color:#94a3b8; margin-bottom:2px; }
.s-card .s-val  { font-size:1.05rem; font-weight:800; color:#1e293b; }
.icon-total    { background:#dbeafe; color:#2563eb; }
.icon-received { background:#dcfce7; color:#15803d; }
.icon-due      { background:#fee2e2; color:#dc2626; }

/* ── Buttons ── */
.btn { display:inline-flex; align-items:center; gap:5px; padding:6px 14px; border-radius:6px;
       font-size:.8rem; font-weight:600; cursor:pointer; border:none; font-family:inherit;
       transition:all .15s; text-decoration:none; white-space:nowrap; }
.btn-filter   { background:linear-gradient(135deg,#3b82f6,#2563eb); color:#fff; }
.btn-reset    { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.btn-excel    { background:#1d6f42; color:#fff; }
.btn-view     { background:#0ea5e9; color:#fff; }
.btn-edit     { background:#f59e0b; color:#fff; }
.btn-sm       { padding:3px 9px; font-size:.73rem; }
.btn:hover    { opacity:.87; transform:translateY(-1px); }

/* ── Export / ctrl bar ── */
.dt-bar   { margin-bottom:10px; }
.ctrl-bar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:8px; }
.search-box { display:flex; align-items:center; gap:6px; font-size:.82rem; }
.search-box input { padding:5px 10px; border:1.5px solid #e2e8f0; border-radius:6px; width:200px; font-size:.82rem; font-family:inherit; outline:none; }
.search-box input:focus { border-color:#3b82f6; }

/* ── Table ── */
.table-wrap { background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
.table-responsive { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.table { width:100%; border-collapse:collapse; font-size:.79rem; color:#1e293b; }
.table thead th { background:#f0f4f8; padding:8px 10px; font-weight:700; font-size:.7rem;
                  text-transform:uppercase; letter-spacing:.04em; color:#64748b;
                  border-bottom:2px solid #e2e8f0; white-space:nowrap; text-align:left;
                  cursor:pointer; user-select:none; position:sticky; top:0; z-index:1; }
.table thead th::after { content:' ⇅'; opacity:.2; font-size:.55rem; }
.table thead th.sort-asc::after  { content:' ↑'; opacity:1; }
.table thead th.sort-desc::after { content:' ↓'; opacity:1; }
.table tbody td { padding:7px 10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.table tbody tr:hover { background:#fefce8; }
.table tbody tr:last-child td { border-bottom:none; }
.table tbody tr.hidden-row { display:none; }
.text-right   { text-align:right; }
.text-danger  { color:#dc2626; font-weight:700; }
.text-success { color:#15803d; font-weight:600; }

/* ── Due badge ── */
.due-badge { display:inline-block; background:#fee2e2; color:#dc2626; border-radius:20px; padding:2px 9px; font-size:.7rem; font-weight:700; }

/* ── Action stack ── */
.action-col { display:flex; flex-direction:column; gap:3px; min-width:90px; }

/* ── Badge mode ── */
.badge-mode { display:inline-block; border-radius:20px; padding:2px 9px; font-size:.68rem; font-weight:700; }
.badge-cash   { background:#fef3c7; color:#92400e; }
.badge-online { background:#dbeafe; color:#1e40af; }
.badge-cheque { background:#e0e7ff; color:#3730a3; }
.badge-neft   { background:#dcfce7; color:#15803d; }

/* ── Flash ── */
.flash { display:flex; align-items:center; gap:8px; padding:10px 14px; border-radius:8px; font-size:.875rem; font-weight:500; margin-bottom:10px; }
.flash-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#16a34a; }
.flash-error   { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }

/* ── Pagination ── */
.pag-bar { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border-top:1px solid #e2e8f0; flex-wrap:wrap; gap:6px; font-size:.8rem; color:#64748b; }
.pagination { display:flex; gap:3px; }
.pg-btn { display:inline-flex; align-items:center; justify-content:center; min-width:32px; height:30px; padding:0 8px;
          border:1.5px solid #e2e8f0; border-radius:6px; background:#fff; color:#3b82f6; font-size:.78rem; font-weight:600; cursor:pointer; text-decoration:none; transition:all .15s; }
.pg-btn:hover    { background:#f1f5f9; }
.pg-btn.active   { background:#3b82f6; border-color:#3b82f6; color:#fff; }
.pg-btn.disabled { opacity:.4; pointer-events:none; color:#64748b; }
</style>

<?php if ($flash): ?>
<div class="flash flash-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($flash['message']); ?>
</div>
<?php endif; ?>

<!-- Title -->
<h2 style="font-size:1.15rem;font-weight:700;margin-bottom:12px;color:#1e293b;">
    <i class="fas fa-exclamation-circle" style="color:#dc2626;margin-right:7px;"></i>
    Payment Due List
    <span style="font-weight:400;color:#64748b;">(<?php echo number_format($totalRecords); ?>)</span>
</h2>

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
            <div class="s-val" style="color:#dc2626;">₹<?php echo number_format((float)($totals['due'] ?? 0), 2); ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="pl-filters">
    <form method="GET" action="/APN-Solar/customers/payment_due.php">
        <div class="pl-fg">
            <label>Reg From:</label>
            <input type="date" name="reg_from" value="<?php echo htmlspecialchars($regFrom); ?>">
        </div>
        <div class="pl-fg">
            <label>Reg To:</label>
            <input type="date" name="reg_to" value="<?php echo htmlspecialchars($regTo); ?>">
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
            <a href="/APN-Solar/customers/payment_due.php" class="btn btn-reset"><i class="fas fa-undo"></i> Reset</a>
        </div>
    </form>
</div>

<!-- Export -->
<div class="dt-bar">
    <button class="btn btn-excel" onclick="pdExportExcel()"><i class="fas fa-file-excel"></i> Excel</button>
</div>

<!-- Controls -->
<div class="ctrl-bar">
    <div style="display:flex;align-items:center;gap:8px;font-size:.82rem;">
        <form method="GET" action="/APN-Solar/customers/payment_due.php" style="display:flex;align-items:center;gap:6px;">
            <?php foreach (['reg_from'=>$regFrom,'reg_to'=>$regTo,'group'=>$group,'district'=>$district,'search'=>$search] as $k=>$v): if($v): ?>
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
        <input type="text" id="pdSearch" placeholder="Name, mobile, ID..."
               oninput="pdFilter(this.value)" value="<?php echo htmlspecialchars($search); ?>">
    </div>
</div>

<!-- Table -->
<div class="table-wrap">
    <div class="table-responsive">
        <table class="table" id="pdTable">
            <thead>
                <tr>
                    <th onclick="pdSort(0)">Sr<br>No.</th>
                    <th onclick="pdSort(1)">Operator<br>Name</th>
                    <th onclick="pdSort(2)">Group<br>Name</th>
                    <th onclick="pdSort(3)">Name</th>
                    <th onclick="pdSort(4)">Mobile</th>
                    <th onclick="pdSort(5)">District</th>
                    <th onclick="pdSort(6)">Electricity<br>Id</th>
                    <th onclick="pdSort(7)">KW</th>
                    <th onclick="pdSort(8)" class="text-right">Total<br>Amount</th>
                    <th onclick="pdSort(9)" class="text-right">Received</th>
                    <th onclick="pdSort(10)" class="text-right">Due<br>Amount</th>
                    <th onclick="pdSort(11)">Remarks</th>
                    <th onclick="pdSort(12)">Reg<br>Date</th>
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
                    <td><?php echo htmlspecialchars($row['mobile'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['district_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['electricity_id'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['kw'] ?? ''); ?></td>
                    <td class="text-right">₹<?php echo number_format((float)($row['total_amount'] ?? 0), 2); ?></td>
                    <td class="text-right text-success">₹<?php echo number_format((float)($row['payment_received'] ?? 0), 2); ?></td>
                    <td class="text-right">
                        <span class="due-badge">₹<?php echo number_format((float)($row['due_amount'] ?? 0), 2); ?></span>
                    </td>
                    <td style="min-width:150px;max-width:200px;word-wrap:break-word;"><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></td>
                    <td style="white-space:nowrap;font-size:.73rem;"><?php echo htmlspecialchars(substr($row['created_at'] ?? '', 0, 10)); ?></td>
                    <td>
                        <div class="action-col">
                            <a href="/APN-Solar/customers/edit.php?id=<?php echo (int)$row['id']; ?>&back=payment_due"
                               class="btn btn-edit btn-sm"><i class="fas fa-pencil-alt"></i> Edit</a>
                            <a href="/APN-Solar/customers/customer_status_view.php?id=<?php echo (int)$row['id']; ?>&back=payment_due"
                               class="btn btn-view btn-sm"><i class="fas fa-eye"></i> View</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="14" style="text-align:center;padding:40px;color:#94a3b8;">
                    <i class="fas fa-check-circle" style="font-size:2rem;display:block;margin-bottom:8px;color:#15803d;"></i>
                    No payment due records found<?php echo ($group || $district || $search || $regFrom || $regTo) ? ' matching your filters.' : '.'; ?>
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
                <a class="pg-btn" href="<?php echo pdUrl(['page' => $page - 1]); ?>">Previous</a>
            <?php else: ?>
                <span class="pg-btn disabled">Previous</span>
            <?php endif; ?>

            <?php
            $win = 3; $ps = max(1,$page-$win); $pe = min($totalPages,$page+$win);
            if ($ps > 1): ?>
                <a class="pg-btn" href="<?php echo pdUrl(['page'=>1]); ?>">1</a>
                <?php if ($ps > 2): ?><span class="pg-btn disabled">…</span><?php endif;
            endif;
            for ($p=$ps; $p<=$pe; $p++): ?>
                <a class="pg-btn <?php echo $p===$page ? 'active':''; ?>"
                   href="<?php echo pdUrl(['page'=>$p]); ?>"><?php echo $p; ?></a>
            <?php endfor;
            if ($pe < $totalPages):
                if ($pe < $totalPages-1): ?><span class="pg-btn disabled">…</span><?php endif; ?>
                <a class="pg-btn" href="<?php echo pdUrl(['page'=>$totalPages]); ?>"><?php echo $totalPages; ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a class="pg-btn" href="<?php echo pdUrl(['page' => $page + 1]); ?>">Next</a>
            <?php else: ?>
                <span class="pg-btn disabled">Next</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
/* ── Client-side search ── */
function pdFilter(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#pdTable tbody tr').forEach(row => {
        const text = row.innerText.toLowerCase();
        row.classList.toggle('hidden-row', q !== '' && !text.includes(q));
    });
}

/* ── Column sort ── */
let pdSortCol = -1, pdSortAsc = true;
function pdSort(col) {
    const ths = document.querySelectorAll('#pdTable thead th');
    ths.forEach(th => th.classList.remove('sort-asc','sort-desc'));
    if (pdSortCol === col) { pdSortAsc = !pdSortAsc; } else { pdSortCol = col; pdSortAsc = true; }
    ths[col].classList.add(pdSortAsc ? 'sort-asc' : 'sort-desc');
    const tbody = document.querySelector('#pdTable tbody');
    const rows  = [...tbody.querySelectorAll('tr')].filter(r => !r.querySelector('[colspan]'));
    rows.sort((a, b) => {
        const va = a.cells[col]?.innerText.replace(/[₹,]/g,'').trim() ?? '';
        const vb = b.cells[col]?.innerText.replace(/[₹,]/g,'').trim() ?? '';
        const na = parseFloat(va), nb = parseFloat(vb);
        const cmp = (!isNaN(na) && !isNaN(nb)) ? na - nb : va.localeCompare(vb);
        return pdSortAsc ? cmp : -cmp;
    });
    rows.forEach(r => tbody.appendChild(r));
}

/* ── Export Excel ── */
function pdExportExcel() {
    const rows = document.querySelectorAll('#pdTable tr');
    let html = '<table border="1"><thead>'; let inBody = false;
    rows.forEach(row => {
        const isHead = row.closest('thead');
        if (isHead && !inBody) html += '<tr>';
        else if (!isHead && !inBody) { html += '</thead><tbody><tr>'; inBody = true; }
        else html += '<tr>';
        row.querySelectorAll('th,td').forEach((cell, i) => {
            if (i === 16) return; // skip Actions
            const tag = isHead ? 'th' : 'td';
            html += `<${tag}>${cell.innerText.trim()}</${tag}>`;
        });
        html += '</tr>';
    });
    html += '</tbody></table>';
    const blob = new Blob([html], {type:'application/vnd.ms-excel;charset=utf-8;'});
    const a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(blob),
        download: 'payment_due_' + new Date().toISOString().slice(0,10) + '.xls'
    });
    a.click(); URL.revokeObjectURL(a.href);
}
</script>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
