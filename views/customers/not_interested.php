<?php
// views/customers/not_interested.php
// Variables: $customers, $totalRecords, $totalPages, $page, $perPage,
//            $group, $regFrom, $regTo, $jsFrom, $jsTo, $allGroups

// Flash
$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

function niUrl(array $overrides = []): string {
    $params = array_merge([
        'group'    => $_GET['group']    ?? '',
        'reg_from' => $_GET['reg_from'] ?? '',
        'reg_to'   => $_GET['reg_to']   ?? '',
        'js_from'  => $_GET['js_from']  ?? '',
        'js_to'    => $_GET['js_to']    ?? '',
        'page'     => $_GET['page']     ?? 1,
        'per_page' => $_GET['per_page'] ?? 25,
    ], $overrides);
    $query = http_build_query(array_filter($params, fn($v) => $v !== ''));
    return '/APN-Solar/customers/not_interested.php' . ($query ? '?' . $query : '');
}

$startRecord = $totalRecords > 0 ? ($page - 1) * $perPage + 1 : 0;
$endRecord   = min($page * $perPage, $totalRecords);
?>

<style>
/* ── Page Header ── */
.ni-page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px; }
.ni-page-header h2 { font-size:1.15rem; font-weight:700; color:#1e293b; }

/* ── Buttons ── */
.btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:7px;
       font-size:.82rem; font-weight:600; cursor:pointer; border:none; font-family:inherit;
       transition:all .15s; text-decoration:none; white-space:nowrap; }
.btn-primary   { background:linear-gradient(135deg,#3b82f6,#2563eb); color:#fff; }
.btn-warning   { background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; }
.btn-restore   { background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; }
.btn-secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.btn-sm        { padding:5px 12px; font-size:.77rem; }
.btn:hover     { opacity:.88; transform:translateY(-1px); }
.btn:active    { transform:translateY(0); }

/* ── Filters ── */
.filters-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:14px 18px; margin-bottom:12px; }
.filters-row  { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; }
.fg { display:flex; flex-direction:column; gap:3px; }
.fg label { font-size:.7rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; }
.fg input, .fg select {
    padding:7px 11px; border:1.5px solid #e2e8f0; border-radius:7px;
    font-size:.84rem; font-family:inherit; color:#1e293b; background:#f8fafc; outline:none;
}
.fg input:focus, .fg select:focus { border-color:#3b82f6; background:#fff; }
.fg input[type="date"]  { width:148px; }
.fg select              { min-width:155px; }

/* ── Search & Export bar ── */
.bar-row { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:8px; }
.dt-bar  { display:flex; gap:0; }
.dt-btn  { background:#6c757d; color:#fff; border:1px solid #5a6268; padding:5px 13px;
           font-size:.78rem; font-weight:600; cursor:pointer; font-family:inherit; display:inline-flex; align-items:center; gap:5px; }
.dt-btn:first-child { border-radius:5px 0 0 5px; }
.dt-btn:last-child  { border-radius:0 5px 5px 0; }
.dt-btn:not(:first-child) { border-left:none; }
.dt-btn:hover { background:#545b62; }

.search-wrap { display:flex; align-items:center; gap:6px; font-size:.83rem; color:#64748b; }
.search-wrap input { padding:6px 10px; border:1.5px solid #e2e8f0; border-radius:7px; font-size:.83rem; font-family:inherit; outline:none; width:210px; }
.search-wrap input:focus { border-color:#3b82f6; }

/* ── Table ── */
.table-wrap { background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
.table-responsive { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.table { width:100%; border-collapse:collapse; font-size:.8rem; color:#1e293b; }
.table thead th { background:#f0f4f8; padding:9px 11px; font-weight:700; font-size:.72rem;
                  text-transform:uppercase; letter-spacing:.04em; color:#64748b;
                  border-bottom:2px solid #e2e8f0; white-space:nowrap; text-align:left; }
.table tbody td { padding:8px 11px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.table tbody tr:hover { background:#fffbf0; }
.table tbody tr:last-child td { border-bottom:none; }
.table tbody tr.hidden-row { display:none; }

/* ── Sort icons ── */
th.sortable { cursor:pointer; user-select:none; }
th.sortable::after { content:' ⇅'; opacity:.35; font-size:.65rem; }
th.sort-asc::after  { content:' ↑'; opacity:1; }
th.sort-desc::after { content:' ↓'; opacity:1; }

/* ── Status badge ── */
.badge-ni { display:inline-flex; align-items:center; gap:4px; background:#fef3c7; color:#92400e;
            border:1px solid #fde68a; border-radius:20px; padding:2px 9px; font-size:.7rem; font-weight:700; }

/* ── Flash ── */
.flash-alert   { display:flex; align-items:center; gap:9px; padding:11px 15px; border-radius:8px; font-size:.875rem; font-weight:500; margin-bottom:12px; }
.flash-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#16a34a; }
.flash-error   { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }

/* ── Pagination ── */
.pagination-bar { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border-top:1px solid #e2e8f0; flex-wrap:wrap; gap:8px; font-size:.8rem; color:#64748b; }
.pagination     { display:flex; gap:4px; flex-wrap:wrap; }
.pg-btn { display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 6px;
          border:1.5px solid #e2e8f0; border-radius:6px; background:#fff; color:#475569;
          font-size:.78rem; font-weight:600; cursor:pointer; text-decoration:none; transition:all .15s; }
.pg-btn:hover    { background:#f1f5f9; border-color:#cbd5e1; }
.pg-btn.active   { background:#f59e0b; border-color:#f59e0b; color:#fff; }
.pg-btn.disabled { opacity:.4; pointer-events:none; }
</style>

<?php if ($flash): ?>
<div class="flash-alert flash-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($flash['message']); ?>
</div>
<?php endif; ?>

<!-- Page header -->
<div class="ni-page-header">
    <h2>
        <i class="fas fa-ban" style="color:#f59e0b;margin-right:7px;"></i>
        Not Interested Customers
        <span style="font-weight:400;color:#94a3b8;margin-left:4px;">(<?php echo number_format($totalRecords); ?> total)</span>
    </h2>
    <a href="/APN-Solar/customers/" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Back to Customers
    </a>
</div>

<!-- Filters -->
<div class="filters-card">
    <form method="GET" action="/APN-Solar/customers/not_interested.php">
        <div class="filters-row">
            <div class="fg">
                <label>Reg From</label>
                <input type="date" name="reg_from" value="<?php echo htmlspecialchars($regFrom); ?>">
            </div>
            <div class="fg">
                <label>Reg To</label>
                <input type="date" name="reg_to" value="<?php echo htmlspecialchars($regTo); ?>">
            </div>
            <div class="fg">
                <label>JS From</label>
                <input type="date" name="js_from" value="<?php echo htmlspecialchars($jsFrom); ?>">
            </div>
            <div class="fg">
                <label>JS To</label>
                <input type="date" name="js_to" value="<?php echo htmlspecialchars($jsTo); ?>">
            </div>
            <div class="fg">
                <label>Group</label>
                <select name="group">
                    <option value="">Select a Group</option>
                    <?php foreach ($allGroups as $g): ?>
                        <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $group === $g ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($g); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:6px;align-items:flex-end;">
                <button type="submit" class="btn btn-primary btn-sm" style="background:linear-gradient(135deg,#3b82f6,#2563eb);"><i class="fas fa-filter"></i> Filter</button>
                <a href="/APN-Solar/customers/not_interested.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Export bar + search -->
<div class="bar-row">
    <div class="dt-bar">
        <button class="dt-btn" onclick="niExportExcel()"><i class="fas fa-file-excel"></i> Excel</button>
        <button class="dt-btn" onclick="niExportPdf()"><i class="fas fa-file-pdf"></i> PDF</button>
        <button class="dt-btn" onclick="niPrint()"><i class="fas fa-print"></i> Print</button>
    </div>
    <div class="search-wrap">
        <label for="niSearch">Search:</label>
        <input type="text" id="niSearch" placeholder="Name, mobile, group..." oninput="niFilterTable(this.value)">
    </div>
</div>

<!-- Table -->
<div class="table-wrap">
    <div class="table-responsive">
        <table class="table" id="niTable">
            <thead>
                <tr>
                    <th class="sortable" onclick="niSort(0)">Sr<br>No.</th>
                    <th class="sortable" onclick="niSort(1)">Operator<br>Name</th>
                    <th class="sortable" onclick="niSort(2)">Group<br>Name</th>
                    <th class="sortable" onclick="niSort(3)">Name</th>
                    <th class="sortable" onclick="niSort(4)">Email</th>
                    <th class="sortable" onclick="niSort(5)">Mobile</th>
                    <th class="sortable" onclick="niSort(6)">IFSC Code</th>
                    <th class="sortable" onclick="niSort(7)">Electricity<br>Id</th>
                    <th class="sortable" onclick="niSort(8)">Kw</th>
                    <th class="sortable" onclick="niSort(9)">Account Number</th>
                    <th class="sortable" onclick="niSort(10)">Total<br>Amount</th>
                    <th class="sortable" onclick="niSort(11)">Due<br>Amount</th>
                    <th class="sortable" onclick="niSort(12)">Remarks</th>
                    <th class="sortable" onclick="niSort(13)">Created<br>Date</th>
                    <th class="sortable" onclick="niSort(14)">Updated<br>Date</th>
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
                    <td><?php echo htmlspecialchars($row['ifsc_code'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['electricity_id'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['kw'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['account_number'] ?? '-'); ?></td>
                    <td><?php echo !empty($row['total_amount']) ? number_format((float)$row['total_amount'], 2) : '–'; ?></td>
                    <td><?php echo !empty($row['due_amount'])   ? number_format((float)$row['due_amount'],   2) : '–'; ?></td>
                    <td style="max-width:130px;word-wrap:break-word;"><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></td>
                    <td style="white-space:nowrap;"><?php echo htmlspecialchars(substr($row['created_at'] ?? '', 0, 16)); ?></td>
                    <td style="white-space:nowrap;"><?php echo htmlspecialchars(substr($row['updated_at'] ?? '', 0, 16)); ?></td>
                    <td>
                        <button class="btn btn-restore btn-sm" onclick="niRestore(<?php echo (int)$row['id']; ?>)">
                            <i class="fas fa-undo-alt"></i> Restore
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="16" style="text-align:center;padding:40px;color:#94a3b8;">
                    <i class="fas fa-ban" style="font-size:2rem;display:block;margin-bottom:10px;color:#fcd34d;"></i>
                    No not-interested customers found<?php echo ($regFrom || $regTo || $jsFrom || $jsTo || $group) ? ' matching your filters.' : '.'; ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-bar">
        <span>
            Showing <strong><?php echo number_format($startRecord); ?>–<?php echo number_format($endRecord); ?></strong>
            of <strong><?php echo number_format($totalRecords); ?></strong> records
        </span>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a class="pg-btn" href="<?php echo niUrl(['page' => $page - 1]); ?>"><i class="fas fa-chevron-left"></i></a>
            <?php else: ?>
                <span class="pg-btn disabled"><i class="fas fa-chevron-left"></i></span>
            <?php endif; ?>

            <?php
            $window = 3;
            $start  = max(1, $page - $window);
            $end    = min($totalPages, $page + $window);
            if ($start > 1): ?>
                <a class="pg-btn" href="<?php echo niUrl(['page' => 1]); ?>">1</a>
                <?php if ($start > 2): ?><span class="pg-btn disabled">…</span><?php endif; ?>
            <?php endif;
            for ($p = $start; $p <= $end; $p++): ?>
                <a class="pg-btn <?php echo $p === $page ? 'active' : ''; ?>"
                   href="<?php echo niUrl(['page' => $p]); ?>"><?php echo $p; ?></a>
            <?php endfor;
            if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><span class="pg-btn disabled">…</span><?php endif; ?>
                <a class="pg-btn" href="<?php echo niUrl(['page' => $totalPages]); ?>"><?php echo $totalPages; ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a class="pg-btn" href="<?php echo niUrl(['page' => $page + 1]); ?>"><i class="fas fa-chevron-right"></i></a>
            <?php else: ?>
                <span class="pg-btn disabled"><i class="fas fa-chevron-right"></i></span>
            <?php endif; ?>
        </div>

        <!-- Per-page selector -->
        <form method="GET" action="/APN-Solar/customers/not_interested.php" style="display:flex;align-items:center;gap:6px;">
            <?php foreach (['group'=>$group,'reg_from'=>$regFrom,'reg_to'=>$regTo,'js_from'=>$jsFrom,'js_to'=>$jsTo] as $k=>$v): if($v): ?>
                <input type="hidden" name="<?php echo $k; ?>" value="<?php echo htmlspecialchars($v); ?>">
            <?php endif; endforeach; ?>
            <label style="font-size:.78rem;color:#64748b;">Rows/page:</label>
            <select name="per_page" onchange="this.form.submit()" style="padding:4px 8px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.78rem;">
                <?php foreach ([10,25,50,100] as $n): ?>
                    <option value="<?php echo $n; ?>" <?php echo $perPage == $n ? 'selected' : ''; ?>><?php echo $n; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<script>
/* ── Client-side search filter ── */
function niFilterTable(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#niTable tbody tr').forEach(row => {
        const text = row.innerText.toLowerCase();
        row.classList.toggle('hidden-row', q !== '' && !text.includes(q));
    });
}

/* ── Client-side column sort ── */
let niSortCol = -1, niSortAsc = true;
function niSort(col) {
    const table = document.getElementById('niTable');
    const ths   = table.querySelectorAll('thead th');
    ths.forEach((th, i) => { th.classList.remove('sort-asc','sort-desc'); });
    if (niSortCol === col) { niSortAsc = !niSortAsc; } else { niSortCol = col; niSortAsc = true; }
    ths[col].classList.add(niSortAsc ? 'sort-asc' : 'sort-desc');

    const tbody = table.querySelector('tbody');
    const rows  = [...tbody.querySelectorAll('tr')].filter(r => !r.querySelector('[colspan]'));
    rows.sort((a, b) => {
        const va = a.cells[col]?.innerText.trim() ?? '';
        const vb = b.cells[col]?.innerText.trim() ?? '';
        const na = parseFloat(va.replace(/[^0-9.\-]/g,''));
        const nb = parseFloat(vb.replace(/[^0-9.\-]/g,''));
        const cmp = (!isNaN(na) && !isNaN(nb)) ? na - nb : va.localeCompare(vb);
        return niSortAsc ? cmp : -cmp;
    });
    rows.forEach(r => tbody.appendChild(r));
}

/* ── Restore customer ── */
function niRestore(id) {
    if (!confirm('Restore this customer back to Active status?')) return;
    fetch('/APN-Solar/customers/ajax_restore.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'id='+id
    }).then(r => r.json()).then(d => {
        if (d.success) {
            // Remove row from table visually
            const btn = document.querySelector(`button[onclick="niRestore(${id})"]`);
            if (btn) {
                const row = btn.closest('tr');
                row.style.transition = 'opacity .3s';
                row.style.opacity    = '0';
                setTimeout(() => { row.remove(); }, 310);
            }
        } else {
            alert('Error: ' + (d.message || 'Could not restore customer.'));
        }
    }).catch(() => alert('Request failed. Please try again.'));
}

/* ── Export to Excel ── */
function niExportExcel() {
    const rows = document.querySelectorAll('#niTable tr');
    let html   = '<table border="1"><thead>';
    let inBody = false;
    rows.forEach(row => {
        const isHead = row.closest('thead');
        if (isHead && !inBody) html += '<tr>';
        else if (!isHead && !inBody) { html += '</thead><tbody><tr>'; inBody = true; }
        else html += '<tr>';
        row.querySelectorAll('th,td').forEach((cell, i) => {
            if (i === 15) return; // skip Actions
            const tag = isHead ? 'th' : 'td';
            html += `<${tag}>${cell.innerText}</${tag}>`;
        });
        html += '</tr>';
    });
    html += '</tbody></table>';
    const blob = new Blob([html], {type:'application/vnd.ms-excel;charset=utf-8;'});
    const a    = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(blob),
        download: 'not_interested_' + new Date().toISOString().slice(0,10) + '.xls'
    });
    a.click(); URL.revokeObjectURL(a.href);
}

/* ── Export to PDF (print-to-PDF friendly) ── */
function niExportPdf() { niPrint(true); }

/* ── Print ── */
function niPrint(pdf = false) {
    const ths = [...document.querySelectorAll('#niTable thead th')]
                  .filter((_,i) => i !== 15).map(t => `<th>${t.innerText}</th>`).join('');
    const trs = [...document.querySelectorAll('#niTable tbody tr')]
                  .filter(r => !r.classList.contains('hidden-row') && !r.querySelector('[colspan]'))
                  .map(r => {
                      const tds = [...r.querySelectorAll('td')].filter((_,i) => i !== 15)
                                    .map(c => `<td>${c.innerText}</td>`).join('');
                      return `<tr>${tds}</tr>`;
                  }).join('');
    const pw = window.open('','','width=1200,height=800');
    pw.document.write(`<!DOCTYPE html><html><head><title>Not Interested Customers</title>
        <style>
            body{font-family:Arial,sans-serif;font-size:10px;}
            table{width:100%;border-collapse:collapse;}
            th,td{border:1px solid #ccc;padding:4px 6px;text-align:left;}
            th{background:#fef3c7;font-weight:700;}
            h3{margin-bottom:6px;font-size:13px;}
            p{margin:0 0 8px;font-size:9px;color:#666;}
        </style>
        </head><body>
        <h3>AROGYA Solar Power — Not Interested Customers (<?php echo $totalRecords; ?> records)</h3>
        <p>Printed: ${new Date().toLocaleString()}</p>
        <table><thead><tr>${ths}</tr></thead><tbody>${trs}</tbody></table>
        </body></html>`);
    pw.document.close();
    pw.focus();
    setTimeout(() => { pw.print(); if(!pdf) pw.close(); }, 400);
}
</script>
