<?php
// views/customers/index.php — Customer list (server-side filters + pagination)
// Variables: $customers, $totalRecords, $totalPages, $page, $perPage,
//            $search, $group, $regFrom, $regTo, $allGroups

// Retrieve flash from session
$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Build URL with preserved query params (for pagination links)
function buildUrl(array $overrides = []): string {
    $params = array_merge([
        'search'   => $_GET['search']   ?? '',
        'group'    => $_GET['group']    ?? '',
        'reg_from' => $_GET['reg_from'] ?? '',
        'reg_to'   => $_GET['reg_to']   ?? '',
        'page'     => $_GET['page']     ?? 1,
    ], $overrides);
    $query = http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== '1' || ($params['page'] ?? 1) > 1));
    return '/APN-Solar/customers/' . ($query ? '?' . $query : '');
}

$startRecord = ($page - 1) * $perPage + 1;
$endRecord   = min($page * $perPage, $totalRecords);
?>

<style>
/* ── Toolbar ── */
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px; }
.page-header h2 { font-size:1.15rem; font-weight:700; color:#1e293b; }
.btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:7px;
       font-size:.82rem; font-weight:600; cursor:pointer; border:none; font-family:inherit;
       transition:all .15s; text-decoration:none; white-space:nowrap; }
.btn-primary   { background:linear-gradient(135deg,#3b82f6,#2563eb); color:#fff; }
.btn-success   { background:linear-gradient(135deg,#22c55e,#16a34a); color:#fff; }
.btn-warning   { background:#f59e0b; color:#fff; }
.btn-danger    { background:#ef4444; color:#fff; }
.btn-secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.btn-sm        { padding:4px 10px; font-size:.77rem; }
.btn:hover { opacity:.88; transform:translateY(-1px); }
.btn:active { transform:translateY(0); }

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
.fg input[type="text"]   { width:198px; }
.fg input[type="date"]   { width:148px; }
.fg select               { min-width:155px; }

/* ── Export bar ── */
.dt-bar   { display:flex; gap:0; margin-bottom:8px; }
.dt-btn   { background:#6c757d; color:#fff; border:1px solid #5a6268; padding:5px 13px;
            font-size:.78rem; font-weight:600; cursor:pointer; font-family:inherit; display:inline-flex; align-items:center; gap:5px; }
.dt-btn:first-child { border-radius:5px 0 0 5px; }
.dt-btn:last-child  { border-radius:0 5px 5px 0; }
.dt-btn:not(:first-child) { border-left:none; }
.dt-btn:hover { background:#545b62; }

/* ── Table ── */
.table-wrap { background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
.table-responsive { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.table { width:100%; border-collapse:collapse; font-size:.8rem; color:#1e293b; }
.table thead th { background:#f0f4f8; padding:9px 11px; font-weight:700; font-size:.72rem;
                  text-transform:uppercase; letter-spacing:.04em; color:#64748b;
                  border-bottom:2px solid #e2e8f0; white-space:nowrap; text-align:left; position:sticky; top:0; z-index:1; }
.table tbody td { padding:8px 11px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.table tbody tr:hover { background:#f8fbff; }
.table tbody tr:last-child td { border-bottom:none; }

/* ── Action column ── */
.action-stack { display:flex; flex-direction:column; gap:3px; min-width:108px; }
.flex-row     { display:flex; gap:4px; }

/* ── Flash ── */
.flash-alert    { display:flex; align-items:center; gap:9px; padding:11px 15px; border-radius:8px; font-size:.875rem; font-weight:500; margin-bottom:12px; }
.flash-success  { background:#f0fdf4; border:1px solid #bbf7d0; color:#16a34a; }
.flash-error    { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }

/* ── Pagination ── */
.pagination-bar { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border-top:1px solid #e2e8f0; flex-wrap:wrap; gap:8px; font-size:.8rem; color:#64748b; }
.pagination     { display:flex; gap:4px; flex-wrap:wrap; }
.pg-btn { display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; padding:0 6px;
          border:1.5px solid #e2e8f0; border-radius:6px; background:#fff; color:#475569;
          font-size:.78rem; font-weight:600; cursor:pointer; text-decoration:none; transition:all .15s; }
.pg-btn:hover    { background:#f1f5f9; border-color:#cbd5e1; }
.pg-btn.active   { background:#3b82f6; border-color:#3b82f6; color:#fff; }
.pg-btn.disabled { opacity:.4; pointer-events:none; }
</style>

<!-- Flash -->
<?php if ($flash): ?>
<div class="flash-alert flash-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($flash['message']); ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="page-header">
    <h2>
        <i class="fas fa-users" style="color:#3b82f6;margin-right:7px;"></i>
        Customers
        <span style="font-weight:400;color:#94a3b8;margin-left:4px;">(<?php echo number_format($totalRecords); ?> total)</span>
    </h2>
    <a href="/APN-Solar/customers/create.php" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Create New Customer
    </a>
</div>

<!-- Filters form (GET-based, server-side) -->
<div class="filters-card">
    <form method="GET" action="/APN-Solar/customers/">
        <div class="filters-row">
            <div class="fg">
                <label>Search</label>
                <input type="text" name="search" placeholder="Name / mobile / email..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="fg">
                <label>Reg From</label>
                <input type="date" name="reg_from" value="<?php echo htmlspecialchars($regFrom); ?>">
            </div>
            <div class="fg">
                <label>Reg To</label>
                <input type="date" name="reg_to" value="<?php echo htmlspecialchars($regTo); ?>">
            </div>
            <div class="fg">
                <label>Group</label>
                <select name="group">
                    <option value="">All Groups</option>
                    <?php foreach ($allGroups as $g): ?>
                        <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $group === $g ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($g); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; gap:6px; align-items:flex-end;">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
                <a href="/APN-Solar/customers/" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Export bar -->
<div class="dt-bar">
    <button class="dt-btn" onclick="exportExcel()"><i class="fas fa-file-excel"></i> Excel</button>
    <button class="dt-btn" onclick="printTable()"><i class="fas fa-print"></i> Print</button>
</div>

<!-- Table -->
<div class="table-wrap">
    <div class="table-responsive">
        <table class="table" id="customersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>District</th>
                    <th>Group Name</th>
                    <th>Operator Name</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>IFSC Code</th>
                    <th>Electricity ID</th>
                    <th>Kw</th>
                    <th>Account No.</th>
                    <th>Total Amt</th>
                    <th>Due Amt</th>
                    <th>Remarks</th>
                    <th>Followup</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($customers) > 0):
                $rowNum = ($page - 1) * $perPage + 1;
                foreach ($customers as $row): ?>
                <tr>
                    <td><?php echo $rowNum++; ?></td>
                    <td><?php echo htmlspecialchars($row['district_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['group_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['operator_name'] ?? ''); ?></td>
                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['mobile'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['ifsc_code'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['electricity_id'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['kw'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['account_number'] ?? ''); ?></td>
                    <td><?php echo !empty($row['total_amount']) ? '₹' . number_format($row['total_amount'], 0) : '–'; ?></td>
                    <td><?php echo !empty($row['due_amount']) ? '₹' . number_format($row['due_amount'], 0) : '–'; ?></td>
                    <td style="max-width:130px;word-wrap:break-word;"><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></td>
                    <td style="max-width:120px;word-wrap:break-word;"><?php echo htmlspecialchars($row['followup_remarks'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(substr($row['created_at'] ?? '', 0, 10)); ?></td>
                    <td>
                        <div class="action-stack">
                            <div class="flex-row">
                                <a href="/APN-Solar/customers/edit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </a>
                                <a href="/APN-Solar/customers/delete.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                            <button class="btn btn-secondary btn-sm" style="width:100%;font-size:.72rem;"
                                    onclick="markNotInterested(<?php echo $row['id']; ?>)">
                                <i class="fas fa-ban"></i> Not Interested
                            </button>
                            <a href="/APN-Solar/customers/followup.php?id=<?php echo (int)$row['id']; ?>"
                               class="btn btn-sm" style="background:#f59e0b;color:#fff;width:100%;font-size:.72rem;justify-content:center;">
                                <i class="fas fa-phone-alt"></i> Followup
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="17" style="text-align:center;padding:40px;color:#94a3b8;">
                    <i class="fas fa-search" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                    No customers found<?php echo $search || $group || $regFrom || $regTo ? ' matching your filters.' : '.'; ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-bar">
        <span>
            Showing <strong><?php echo $totalRecords > 0 ? number_format($startRecord) : 0; ?>–<?php echo number_format($endRecord); ?></strong>
            of <strong><?php echo number_format($totalRecords); ?></strong> customers
        </span>

        <div class="pagination">
            <!-- Previous -->
            <?php if ($page > 1): ?>
                <a class="pg-btn" href="<?php echo buildUrl(['page' => $page - 1]); ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php else: ?>
                <span class="pg-btn disabled"><i class="fas fa-chevron-left"></i></span>
            <?php endif; ?>

            <!-- Page numbers (smart window) -->
            <?php
            $window  = 3;
            $start   = max(1, $page - $window);
            $end     = min($totalPages, $page + $window);
            if ($start > 1): ?>
                <a class="pg-btn" href="<?php echo buildUrl(['page' => 1]); ?>">1</a>
                <?php if ($start > 2): ?><span class="pg-btn disabled">…</span><?php endif; ?>
            <?php endif;
            for ($p = $start; $p <= $end; $p++): ?>
                <a class="pg-btn <?php echo $p === $page ? 'active' : ''; ?>"
                   href="<?php echo buildUrl(['page' => $p]); ?>"><?php echo $p; ?></a>
            <?php endfor;
            if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><span class="pg-btn disabled">…</span><?php endif; ?>
                <a class="pg-btn" href="<?php echo buildUrl(['page' => $totalPages]); ?>"><?php echo $totalPages; ?></a>
            <?php endif; ?>

            <!-- Next -->
            <?php if ($page < $totalPages): ?>
                <a class="pg-btn" href="<?php echo buildUrl(['page' => $page + 1]); ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="pg-btn disabled"><i class="fas fa-chevron-right"></i></span>
            <?php endif; ?>
        </div>

        <!-- Per-page selector -->
        <form method="GET" action="/APN-Solar/customers/" style="display:flex;align-items:center;gap:6px;">
            <?php foreach (['search'=>$search,'group'=>$group,'reg_from'=>$regFrom,'reg_to'=>$regTo] as $k=>$v): if($v): ?>
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
function exportExcel() {
    const rows    = document.querySelectorAll('#customersTable tr');
    let html      = '<table border="1"><thead>';
    let inBody    = false;
    rows.forEach(row => {
        const isHead = row.closest('thead');
        if (isHead && !inBody) { html += '<tr>'; }
        else if (!isHead && !inBody) { html += '</thead><tbody><tr>'; inBody = true; }
        else { html += '<tr>'; }
        row.querySelectorAll('th,td').forEach((cell, i) => {
            if (i === 16) return; // skip Actions
            const tag = isHead ? 'th' : 'td';
            html += `<${tag}>${cell.innerText}</${tag}>`;
        });
        html += '</tr>';
    });
    html += '</tbody></table>';
    const blob = new Blob([html], {type:'application/vnd.ms-excel;charset=utf-8;'});
    const a    = Object.assign(document.createElement('a'), {
        href: URL.createObjectURL(blob),
        download: 'customers_' + new Date().toISOString().slice(0,10) + '.xls'
    });
    a.click(); URL.revokeObjectURL(a.href);
}

function printTable() {
    const pw = window.open('', '', 'width=1200,height=800');
    const ths = [...document.querySelectorAll('#customersTable thead th')]
                  .filter((_,i)=>i!==16).map(t=>`<th>${t.innerText}</th>`).join('');
    const trs = [...document.querySelectorAll('#customersTable tbody tr')].map(r=>{
        const tds = [...r.querySelectorAll('td')].filter((_,i)=>i!==16).map(c=>`<td>${c.innerText}</td>`).join('');
        return `<tr>${tds}</tr>`;
    }).join('');
    pw.document.write(`<!DOCTYPE html><html><head><title>Customers</title>
        <style>body{font-family:Arial,sans-serif;font-size:10px;}table{width:100%;border-collapse:collapse;}
        th,td{border:1px solid #ccc;padding:4px 6px;}th{background:#f1f5f9;font-weight:700;}h3{margin-bottom:8px;}</style>
        </head><body><h3>AROGYA Solar Power — Customer Registration (<?php echo $totalRecords; ?> records)</h3>
        <p style="font-size:9px;margin-bottom:8px;">Printed: ${new Date().toLocaleString()}</p>
        <table><thead><tr>${ths}</tr></thead><tbody>${trs}</tbody></table></body></html>`);
    pw.document.close(); pw.focus(); setTimeout(()=>{ pw.print(); pw.close(); }, 400);
}

function markNotInterested(id) {
    if (!confirm('Mark this customer as Not Interested?')) return;
    fetch('/APN-Solar/customers/ajax_status.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'id='+id+'&status=not_interested'
    }).then(r=>r.json()).then(d => { d.success ? location.reload() : alert('Error: '+d.message); })
      .catch(()=>alert('Request failed'));
}



</script>
