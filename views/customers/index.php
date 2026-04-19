<?php
// views/customers/index.php — Customer Registration list view
// Variables: $customers (array), flash messages handled via header partial

// Get flash from session
$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>

<style>
/* ── Page toolbar ── */
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; flex-wrap:wrap; gap:10px; }
.page-header h2 { font-size:1.2rem; font-weight:700; color:#1e293b; }
.toolbar { display:flex; gap:8px; flex-wrap:wrap; }
.btn { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:7px;
       font-size:.85rem; font-weight:600; cursor:pointer; border:none; font-family:inherit;
       transition:all .15s; text-decoration:none; white-space:nowrap; }
.btn-primary   { background:linear-gradient(135deg,#3b82f6,#2563eb); color:#fff; }
.btn-success   { background:linear-gradient(135deg,#22c55e,#16a34a); color:#fff; }
.btn-warning   { background:#ffc107; color:#212529; }
.btn-danger    { background:#ef4444; color:#fff; }
.btn-secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.btn-sm        { padding:5px 12px; font-size:.8rem; }
.btn:hover { opacity:.9; transform:translateY(-1px); }
.btn:active { transform:translateY(0); }

/* ── Filters ── */
.filters-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px 20px; margin-bottom:14px; }
.filters-row { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
.filter-group { display:flex; flex-direction:column; gap:4px; }
.filter-group label { font-size:.72rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; }
.filter-group input, .filter-group select {
    padding:7px 12px; border:1.5px solid #e2e8f0; border-radius:7px;
    font-size:.85rem; font-family:inherit; color:#1e293b; background:#f8fafc; outline:none;
    transition:border-color .2s;
}
.filter-group input:focus, .filter-group select:focus { border-color:#3b82f6; }
.filter-group input[type="text"] { width:200px; }
.filter-group input[type="date"] { width:150px; }
.filter-group select { min-width:160px; }
.filter-actions { display:flex; gap:8px; align-items:flex-end; }

/* ── Export buttons ── */
.dt-bar { display:flex; gap:0; margin-bottom:10px; }
.dt-btn { background:#6c757d; color:#fff; border:1px solid #5a6268; padding:5px 14px;
          font-size:.8rem; font-weight:600; cursor:pointer; font-family:inherit; }
.dt-btn:first-child { border-radius:5px 0 0 5px; }
.dt-btn:last-child  { border-radius:0 5px 5px 0; }
.dt-btn:not(:first-child) { border-left:none; }
.dt-btn:hover { background:#5a6268; }

/* ── Table ── */
.table-wrap { background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
.table-responsive { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.table { width:100%; border-collapse:collapse; font-size:.82rem; color:#1e293b; }
.table thead th { background:#f8fafc; padding:10px 12px; font-weight:700; font-size:.75rem;
                  text-transform:uppercase; letter-spacing:.04em; color:#64748b;
                  border-bottom:2px solid #e2e8f0; white-space:nowrap; text-align:left; }
.table tbody td { padding:9px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.table tbody tr:hover { background:#fafbff; }
.table tbody tr:last-child td { border-bottom:none; }

/* ── Action column ── */
.action-stack { display:flex; flex-direction:column; gap:4px; min-width:100px; }
.flex-row { display:flex; gap:4px; }

/* ── Flash alert ── */
.flash-alert { display:flex; align-items:center; gap:9px; padding:12px 16px; border-radius:9px;
               font-size:.875rem; font-weight:500; margin-bottom:14px; }
.flash-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#16a34a; }
.flash-error   { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; }

/* ── Pagination ── */
.table-footer { display:flex; align-items:center; justify-content:space-between;
                padding:12px 16px; border-top:1px solid #e2e8f0; font-size:.82rem; color:#64748b; }

/* Status badges */
.badge { display:inline-block; padding:2px 8px; border-radius:12px; font-size:.72rem; font-weight:600; }
.badge-active    { background:#dcfce7; color:#16a34a; }
.badge-pending   { background:#fef3c7; color:#d97706; }
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
    <h2><i class="fas fa-users" style="color:#3b82f6;margin-right:8px;"></i>
        Customers <span style="font-weight:400;color:#64748b;font-size:.95rem;">(<?php echo count($customers); ?>)</span>
    </h2>
    <div class="toolbar">
        <a href="/APN-Solar/customers/create.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Create New Customer
        </a>
    </div>
</div>

<!-- Filters -->
<div class="filters-card">
    <div class="filters-row">
        <div class="filter-group">
            <label>Search</label>
            <input type="text" id="searchInput" placeholder="Name / mobile / email..." oninput="applyFilters()">
        </div>
        <div class="filter-group">
            <label>Reg From</label>
            <input type="date" id="regFrom" onchange="applyFilters()">
        </div>
        <div class="filter-group">
            <label>Reg To</label>
            <input type="date" id="regTo" onchange="applyFilters()">
        </div>
        <div class="filter-group">
            <label>JS From</label>
            <input type="date" id="jsFrom" onchange="applyFilters()">
        </div>
        <div class="filter-group">
            <label>JS To</label>
            <input type="date" id="jsTo" onchange="applyFilters()">
        </div>
        <div class="filter-group">
            <label>Group</label>
            <select id="groupFilter" onchange="applyFilters()">
                <option value="">All Groups</option>
                <?php
                $uniqueGroups = array_unique(array_filter(array_column($customers, 'group_name')));
                sort($uniqueGroups);
                foreach ($uniqueGroups as $g): ?>
                    <option value="<?php echo htmlspecialchars($g); ?>"><?php echo htmlspecialchars($g); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-actions">
            <button class="btn btn-primary btn-sm" onclick="applyFilters()">
                <i class="fas fa-filter"></i> Filter
            </button>
            <button class="btn btn-secondary btn-sm" onclick="resetFilters()">
                <i class="fas fa-times"></i> Reset
            </button>
        </div>
    </div>
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
                    <th>Operator Name</th>
                    <th>Group Name</th>
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
                    <th>Followup Remarks</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="customerBody">
            <?php if (count($customers) > 0): ?>
                <?php foreach ($customers as $i => $row): ?>
                <tr data-group="<?php echo htmlspecialchars($row['group_name'] ?? ''); ?>"
                    data-date="<?php echo substr($row['created_at'] ?? '', 0, 10); ?>">
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo htmlspecialchars($row['operator_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['group_name'] ?? ''); ?></td>
                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['mobile'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['ifsc_code'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['electricity_id'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['kw'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['account_number'] ?? ''); ?></td>
                    <td><?php echo !empty($row['total_amount']) ? '₹' . number_format($row['total_amount'], 2) : '–'; ?></td>
                    <td><?php echo !empty($row['due_amount']) ? '₹' . number_format($row['due_amount'], 2) : '–'; ?></td>
                    <td><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['followup_remarks'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(substr($row['created_at'] ?? '', 0, 10)); ?></td>
                    <td><?php echo htmlspecialchars(substr($row['updated_at'] ?? $row['created_at'] ?? '', 0, 10)); ?></td>
                    <td>
                        <div class="action-stack">
                            <div class="flex-row">
                                <a href="/APN-Solar/customers/edit.php?id=<?php echo $row['id']; ?>"
                                   class="btn btn-warning btn-sm">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </a>
                                <a href="/APN-Solar/customers/delete.php?id=<?php echo $row['id']; ?>"
                                   class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash-alt"></i> Del
                                </a>
                            </div>
                            <button class="btn btn-secondary btn-sm" style="width:100%;"
                                    onclick="markNotInterested(<?php echo $row['id']; ?>)">
                                <i class="fas fa-ban"></i> Not Interested
                            </button>
                            <button class="btn btn-sm" style="background:#f59e0b;color:#fff;width:100%;"
                                    onclick="addFollowup(<?php echo $row['id']; ?>)">
                                <i class="fas fa-phone-alt"></i> Followup
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="17" style="text-align:center;padding:40px;color:#94a3b8;">
                    <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                    No customers found.
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <span id="countLabel">Showing <?php echo count($customers); ?> customers</span>
    </div>
</div>

<script>
function applyFilters() {
    const search  = document.getElementById('searchInput').value.toLowerCase();
    const group   = document.getElementById('groupFilter').value.toLowerCase();
    const regFrom = document.getElementById('regFrom').value;
    const regTo   = document.getElementById('regTo').value;
    const rows    = document.querySelectorAll('#customerBody tr[data-date]');
    let visible   = 0;

    rows.forEach(row => {
        const text    = row.textContent.toLowerCase();
        const rowGrp  = row.getAttribute('data-group').toLowerCase();
        const rowDate = row.getAttribute('data-date');

        const matchSearch = !search || text.includes(search);
        const matchGroup  = !group  || rowGrp === group;
        const matchFrom   = !regFrom || rowDate >= regFrom;
        const matchTo     = !regTo   || rowDate <= regTo;

        const show = matchSearch && matchGroup && matchFrom && matchTo;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('countLabel').textContent = 'Showing ' + visible + ' customers';
}

function resetFilters() {
    ['searchInput','regFrom','regTo','jsFrom','jsTo'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('groupFilter').value = '';
    applyFilters();
}

function exportExcel() {
    // Build table from visible rows only
    const table = document.getElementById('customersTable');
    const rows  = table.querySelectorAll('tr');
    let html    = '<table border="1">';

    rows.forEach(row => {
        if (row.style.display === 'none') return;
        html += '<tr>';
        row.querySelectorAll('th, td').forEach((cell, idx) => {
            if (idx === 16) return; // skip Actions column
            html += '<' + (row.closest('thead') ? 'th' : 'td') + '>' +
                    cell.innerText + '</' + (row.closest('thead') ? 'th' : 'td') + '>';
        });
        html += '</tr>';
    });
    html += '</table>';

    const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'customers_' + new Date().toISOString().slice(0,10) + '.xls';
    a.click();
    URL.revokeObjectURL(url);
}

function printTable() {
    const printWin = window.open('', '', 'width=1200,height=800');
    const heads = Array.from(document.querySelectorAll('#customersTable thead th'))
                       .filter((_, i) => i !== 16).map(th => '<th>' + th.innerText + '</th>').join('');
    const bodyRows = Array.from(document.querySelectorAll('#customerBody tr'))
        .filter(r => r.style.display !== 'none')
        .map(row => {
            const cells = Array.from(row.querySelectorAll('td'))
                               .filter((_, i) => i !== 16)
                               .map(td => '<td>' + td.innerText + '</td>').join('');
            return '<tr>' + cells + '</tr>';
        }).join('');

    printWin.document.write(`<!DOCTYPE html><html><head>
        <title>Customers Report</title>
        <style>
            body{font-family:Arial,sans-serif;font-size:11px;}
            table{width:100%;border-collapse:collapse;}
            th,td{border:1px solid #ccc;padding:5px 8px;text-align:left;}
            th{background:#f1f5f9;font-weight:700;}
            h2{margin-bottom:10px;}
        </style></head><body>
        <h2>AROGYA Solar Power — Customer Registration</h2>
        <p>Printed on: ${new Date().toLocaleString()}</p>
        <table><thead><tr>${heads}</tr></thead><tbody>${bodyRows}</tbody></table>
    </body></html>`);
    printWin.document.close();
    printWin.focus();
    printWin.print();
    printWin.close();
}

function markNotInterested(id) {
    if (!confirm('Mark this customer as Not Interested?')) return;
    fetch('/APN-Solar/customers/ajax_status.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'id=' + id + '&status=not_interested'
    }).then(r => r.json()).then(d => {
        if (d.success) location.reload();
        else alert('Error: ' + d.message);
    }).catch(() => alert('Request failed'));
}

function addFollowup(id) {
    const note = prompt('Enter followup note:');
    if (note === null) return;
    fetch('/APN-Solar/customers/ajax_followup.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'id=' + id + '&note=' + encodeURIComponent(note)
    }).then(r => r.json()).then(d => {
        if (d.success) location.reload();
        else alert('Error: ' + d.message);
    }).catch(() => alert('Request failed'));
}
</script>
