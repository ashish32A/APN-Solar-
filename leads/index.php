<?php
// leads/index.php — Customer/Lead list with pagination, search, Edit & Delete

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$pageTitle    = 'Customers';
$validPerPage = [10, 25, 50, 100];
$requestedPer = (int)($_GET['per_page'] ?? 10);
$perPage      = in_array($requestedPer, $validPerPage) ? $requestedPer : 10;
$page         = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');
$group  = trim($_GET['group']  ?? '');

$where  = "WHERE 1=1";
$params = [];

if ($search !== '') {
    $where   .= " AND (c.name LIKE ? OR c.mobile LIKE ? OR c.email LIKE ? OR c.operator_name LIKE ? OR c.electricity_id LIKE ?)";
    $like     = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($group !== '') {
    $where   .= " AND c.group_name = ?";
    $params[] = $group;
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
        SELECT c.id, c.operator_name, c.group_name, c.name, c.email, c.mobile,
               c.electricity_id, c.kw, c.remarks, c.created_at, c.updated_at
        FROM customers c
        $where
        ORDER BY c.id DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $customers = [];
}

// Groups for filter dropdown
try {
    $allGroups = $pdo->query("SELECT DISTINCT group_name FROM customers WHERE group_name != '' ORDER BY group_name")
                     ->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $allGroups = [];
}

// Flash message
$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$startRecord = $totalRecords > 0 ? ($page - 1) * $perPage + 1 : 0;
$endRecord   = min($page * $perPage, $totalRecords);

function leadsUrl(array $overrides = []): string {
    $params = array_merge([
        'search'   => $_GET['search']   ?? '',
        'group'    => $_GET['group']    ?? '',
        'page'     => $_GET['page']     ?? 1,
        'per_page' => $_GET['per_page'] ?? 10,
    ], $overrides);
    $query = http_build_query(array_filter($params, fn($v) => $v !== ''));
    return '/APN-Solar/leads/' . ($query ? '?' . $query : '');
}

include __DIR__ . '/../views/partials/header.php';
?>

<style>
/* ── Page ── */
.lh2 { font-size:1.1rem; font-weight:700; color:#1e293b; margin:0; }
.lhead { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:10px; }

/* ── Buttons ── */
.btn { display:inline-flex; align-items:center; gap:5px; padding:6px 14px; border-radius:5px;
       font-size:.8rem; font-weight:600; cursor:pointer; border:none; font-family:inherit;
       transition:all .15s; text-decoration:none; white-space:nowrap; }
.btn-primary  { background:#3b82f6; color:#fff; }
.btn-edit     { background:#f59e0b; color:#fff; }
.btn-danger   { background:#ef4444; color:#fff; }
.btn-secondary{ background:#6c757d; color:#fff; border:none; }
.btn-sm       { padding:3px 9px; font-size:.74rem; }
.btn:hover    { opacity:.85; transform:translateY(-1px); }

/* ── Export bar ── */
.dt-bar  { display:flex; gap:0; margin-bottom:8px; }
.dt-btn  { background:#6c757d; color:#fff; border:1px solid #5a6268; padding:5px 14px;
           font-size:.78rem; font-weight:600; cursor:pointer; font-family:inherit;
           display:inline-flex; align-items:center; gap:5px; }
.dt-btn:first-child { border-radius:5px 0 0 5px; }
.dt-btn:last-child  { border-radius:0 5px 5px 0; }
.dt-btn:not(:first-child) { border-left:none; }
.dt-btn:hover { background:#545b62; }

/* ── Filters ── */
.filter-row { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:10px; }
.filter-row input, .filter-row select { padding:5px 10px; border:1px solid #dee2e6; border-radius:4px; font-size:.82rem; font-family:inherit; }
.filter-row input[type="text"] { width:220px; }
.search-lbl { font-size:.82rem; color:#495057; }

/* ── Table ── */
.table-wrap { background:#fff; border:1px solid #dee2e6; border-radius:4px; overflow:hidden; }
.table-responsive { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.table { width:100%; border-collapse:collapse; font-size:.82rem; color:#212529; }
.table thead th { background:#f8f9fa; padding:8px 10px; font-weight:700; font-size:.76rem;
                  text-transform:uppercase; letter-spacing:.03em; color:#6c757d;
                  border-bottom:2px solid #dee2e6; white-space:nowrap; text-align:left; cursor:pointer; user-select:none; }
.table thead th::after { content:' ⇅'; opacity:.25; font-size:.6rem; }
.table thead th.sort-asc::after  { content:' ↑'; opacity:1; }
.table thead th.sort-desc::after { content:' ↓'; opacity:1; }
.table tbody td { padding:7px 10px; border-bottom:1px solid #f1f3f5; vertical-align:middle; }
.table tbody tr:hover { background:#fffef0; }
.table tbody tr:last-child td { border-bottom:none; }
.table tbody tr.hidden-row { display:none; }

/* ── Pagination ── */
.pag-bar { display:flex; align-items:center; justify-content:space-between; padding:8px 12px; border-top:1px solid #dee2e6; flex-wrap:wrap; gap:6px; font-size:.8rem; color:#6c757d; }
.pagination { display:flex; gap:3px; }
.pg-btn { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:32px; padding:0 8px;
          border:1px solid #dee2e6; border-radius:4px; background:#fff; color:#007bff;
          font-size:.8rem; font-weight:600; cursor:pointer; text-decoration:none; transition:all .12s; }
.pg-btn:hover    { background:#e9ecef; }
.pg-btn.active   { background:#007bff; border-color:#007bff; color:#fff; }
.pg-btn.disabled { opacity:.5; pointer-events:none; color:#6c757d; }

/* ── Flash ── */
.flash { display:flex; align-items:center; gap:8px; padding:10px 14px; border-radius:6px; font-size:.875rem; font-weight:500; margin-bottom:12px; }
.flash-success { background:#d4edda; border:1px solid #c3e6cb; color:#155724; }
.flash-error   { background:#f8d7da; border:1px solid #f5c6cb; color:#721c24; }

/* ── Delete Modal ── */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.open { display:flex; }
.modal-box { background:#fff; border-radius:8px; padding:30px 34px; max-width:420px; width:90%; text-align:center; box-shadow:0 10px 40px rgba(0,0,0,.25); }
.modal-icon { font-size:2.5rem; color:#dc3545; margin-bottom:12px; }
.modal-box h3 { font-size:1.05rem; font-weight:700; color:#212529; margin-bottom:6px; }
.modal-box p  { color:#6c757d; font-size:.875rem; margin-bottom:16px; }
.modal-actions { display:flex; gap:10px; justify-content:center; }
</style>

<?php if ($flash): ?>
<div class="flash flash-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($flash['message']); ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="lhead">
    <h2 class="lh2">
        Customers:-
        <span style="font-weight:400;color:#6c757d;">(<?php echo $totalRecords; ?>)</span>
    </h2>
    <a href="/APN-Solar/leads/create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Create New Customer
    </a>
</div>

<!-- Export bar -->
<div class="dt-bar">
    <button class="dt-btn" onclick="leadsExportExcel()"><i class="fas fa-file-excel"></i> Excel</button>
    <button class="dt-btn" onclick="leadsExportPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
    <button class="dt-btn" onclick="leadsPrint()"><i class="fas fa-print"></i> Print</button>
</div>

<!-- Filters -->
<div class="filter-row">
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <form method="GET" action="/APN-Solar/leads/" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            <select name="group" onchange="this.form.submit()" style="min-width:160px;">
                <option value="">-- All Groups --</option>
                <?php foreach ($allGroups as $g): ?>
                    <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $group === $g ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($g); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($group): ?>
                <a href="/APN-Solar/leads/" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
            <?php endif; ?>

            <label class="search-lbl">Show
                <select name="per_page" onchange="this.form.submit()" style="margin:0 4px;">
                    <?php foreach ([10, 25, 50, 100] as $n): ?>
                        <option value="<?php echo $n; ?>" <?php echo $perPage == $n ? 'selected' : ''; ?>><?php echo $n; ?></option>
                    <?php endforeach; ?>
                </select>
                entries
            </label>
        </form>
    </div>

    <div style="display:flex;gap:6px;align-items:center;">
        <span class="search-lbl">Search:</span>
        <input type="text" id="leadsSearch" placeholder="Name, mobile, email..."
               oninput="leadsFilter(this.value)"
               value="<?php echo htmlspecialchars($search); ?>">
    </div>
</div>

<!-- Table -->
<div class="table-wrap">
    <div class="table-responsive">
        <table class="table" id="leadsTable">
            <thead>
                <tr>
                    <th onclick="leadsSort(0)">Sr No.</th>
                    <th onclick="leadsSort(1)">Operator Name</th>
                    <th onclick="leadsSort(2)">Group Name</th>
                    <th onclick="leadsSort(3)">Name</th>
                    <th onclick="leadsSort(4)">Email</th>
                    <th onclick="leadsSort(5)">Mobile</th>
                    <th onclick="leadsSort(6)">Electricity Id</th>
                    <th onclick="leadsSort(7)">Kw</th>
                    <th onclick="leadsSort(8)">Remarks</th>
                    <th onclick="leadsSort(9)">Created Date</th>
                    <th onclick="leadsSort(10)">Updated Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($customers) > 0):
                $rowNum = ($page - 1) * $perPage + 1;
                foreach ($customers as $row): ?>
                <tr>
                    <td><?php echo $rowNum++; ?></td>
                    <td><?php echo htmlspecialchars($row['operator_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['group_name'] ?? ''); ?></td>
                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['mobile'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['electricity_id'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['kw'] ?? ''); ?></td>
                    <td style="max-width:130px;word-wrap:break-word;"><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></td>
                    <td style="white-space:nowrap;font-size:.76rem;"><?php echo htmlspecialchars(substr($row['created_at'] ?? '', 0, 16)); ?></td>
                    <td style="white-space:nowrap;font-size:.76rem;"><?php echo htmlspecialchars(substr($row['updated_at'] ?? '', 0, 16)); ?></td>
                    <td>
                        <div style="display:flex;gap:4px;min-width:94px;">
                            <a href="/APN-Solar/leads/edit.php?id=<?php echo (int)$row['id']; ?>"
                               class="btn btn-edit btn-sm"><i class="fas fa-pencil-alt"></i> Edit</a>
                            <button class="btn btn-danger btn-sm"
                                    onclick="leadsConfirmDelete(<?php echo (int)$row['id']; ?>, '<?php echo addslashes(htmlspecialchars($row['name'])); ?>')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="12" style="text-align:center;padding:40px;color:#94a3b8;">
                    <i class="fas fa-users" style="font-size:2rem;display:block;margin-bottom:10px;color:#bfdbfe;"></i>
                    No customers found<?php echo $group || $search ? ' matching your filters.' : '.'; ?>
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
                <a class="pg-btn" href="<?php echo leadsUrl(['page' => $page - 1]); ?>">Previous</a>
            <?php else: ?>
                <span class="pg-btn disabled">Previous</span>
            <?php endif; ?>

            <?php
            $window = 3;
            $pStart = max(1, $page - $window);
            $pEnd   = min($totalPages, $page + $window);
            if ($pStart > 1): ?>
                <a class="pg-btn" href="<?php echo leadsUrl(['page' => 1]); ?>">1</a>
                <?php if ($pStart > 2): ?><span class="pg-btn disabled">…</span><?php endif; ?>
            <?php endif;
            for ($p = $pStart; $p <= $pEnd; $p++): ?>
                <a class="pg-btn <?php echo $p === $page ? 'active' : ''; ?>"
                   href="<?php echo leadsUrl(['page' => $p]); ?>"><?php echo $p; ?></a>
            <?php endfor;
            if ($pEnd < $totalPages): ?>
                <?php if ($pEnd < $totalPages - 1): ?><span class="pg-btn disabled">…</span><?php endif; ?>
                <a class="pg-btn" href="<?php echo leadsUrl(['page' => $totalPages]); ?>"><?php echo $totalPages; ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a class="pg-btn" href="<?php echo leadsUrl(['page' => $page + 1]); ?>">Next</a>
            <?php else: ?>
                <span class="pg-btn disabled">Next</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon"><i class="fas fa-trash-alt"></i></div>
        <h3>Delete Customer?</h3>
        <p id="deleteModalMsg">This will permanently delete the customer and all related records.</p>
        <div class="modal-actions">
            <form id="deleteForm" method="POST" action="/APN-Solar/leads/delete.php">
                <input type="hidden" name="id" id="deleteId">
                <input type="hidden" name="confirm_delete" value="1">
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Yes, Delete</button>
            </form>
            <button class="btn btn-secondary" onclick="document.getElementById('deleteModal').classList.remove('open')">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </div>
</div>

<script>
/* ── Client-side search ── */
function leadsFilter(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#leadsTable tbody tr').forEach(row => {
        const text = row.innerText.toLowerCase();
        row.classList.toggle('hidden-row', q !== '' && !text.includes(q));
    });
}

/* ── Column sort ── */
let lSortCol = -1, lSortAsc = true;
function leadsSort(col) {
    const ths = document.querySelectorAll('#leadsTable thead th');
    ths.forEach(th => th.classList.remove('sort-asc', 'sort-desc'));
    if (lSortCol === col) { lSortAsc = !lSortAsc; } else { lSortCol = col; lSortAsc = true; }
    ths[col].classList.add(lSortAsc ? 'sort-asc' : 'sort-desc');
    const tbody = document.querySelector('#leadsTable tbody');
    const rows  = [...tbody.querySelectorAll('tr')].filter(r => !r.querySelector('[colspan]'));
    rows.sort((a, b) => {
        const va = a.cells[col]?.innerText.trim() ?? '';
        const vb = b.cells[col]?.innerText.trim() ?? '';
        const na = parseFloat(va.replace(/[^0-9.\-]/g, ''));
        const nb = parseFloat(vb.replace(/[^0-9.\-]/g, ''));
        const cmp = (!isNaN(na) && !isNaN(nb)) ? na - nb : va.localeCompare(vb);
        return lSortAsc ? cmp : -cmp;
    });
    rows.forEach(r => tbody.appendChild(r));
}

/* ── Delete confirm ── */
function leadsConfirmDelete(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModalMsg').textContent = 'Permanently delete "' + name + '" and all related records?';
    document.getElementById('deleteModal').classList.add('open');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
});

/* ── Export Excel ── */
function leadsExportExcel() {
    const rows = document.querySelectorAll('#leadsTable tr');
    let html = '<table border="1"><thead>';
    let inBody = false;
    rows.forEach(row => {
        const isHead = row.closest('thead');
        if (isHead && !inBody) html += '<tr>';
        else if (!isHead && !inBody) { html += '</thead><tbody><tr>'; inBody = true; }
        else html += '<tr>';
        row.querySelectorAll('th,td').forEach((cell, i) => {
            if (i === 11) return; // skip Actions
            const tag = isHead ? 'th' : 'td';
            html += `<${tag}>${cell.innerText.trim()}</${tag}>`;
        });
        html += '</tr>';
    });
    html += '</tbody></table>';
    const blob = new Blob([html], {type: 'application/vnd.ms-excel;charset=utf-8;'});
    const a = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(blob),
        download: 'customers_' + new Date().toISOString().slice(0,10) + '.xls'
    });
    a.click(); URL.revokeObjectURL(a.href);
}

/* ── Export PDF ── */
function leadsExportPDF() {
    const pw = window.open('', '', 'width=1100,height=800');
    const ths = [...document.querySelectorAll('#leadsTable thead th')]
                  .filter((_,i) => i !== 11).map(t => `<th>${t.innerText}</th>`).join('');
    const trs = [...document.querySelectorAll('#leadsTable tbody tr')].map(r => {
        const tds = [...r.querySelectorAll('td')].filter((_,i) => i !== 11).map(c => `<td>${c.innerText}</td>`).join('');
        return `<tr>${tds}</tr>`;
    }).join('');
    pw.document.write(`<!DOCTYPE html><html><head><title>Customers</title>
        <style>body{font-family:Arial,sans-serif;font-size:9px;}table{width:100%;border-collapse:collapse;}
        th,td{border:1px solid #ccc;padding:3px 5px;}th{background:#f1f5f9;font-weight:700;}</style>
        </head><body><h3>Customers (<?php echo $totalRecords; ?> records)</h3>
        <p style="font-size:8px;">Printed: ${new Date().toLocaleString()}</p>
        <table><thead><tr>${ths}</tr></thead><tbody>${trs}</tbody></table></body></html>`);
    pw.document.close(); pw.focus(); setTimeout(() => { pw.print(); pw.close(); }, 400);
}

/* ── Print ── */
function leadsPrint() {
    leadsExportPDF();
}
</script>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
