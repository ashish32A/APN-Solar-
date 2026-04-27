<?php
// views/customers/customer_status.php
// Variables: $customers, $totalRecords, $totalPages, $page, $perPage, $group, $search, $allGroups

if (!empty($queryError)) {
    echo '<div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:12px;font-family:monospace;font-size:.82rem;">'
        . '<strong>Query Error:</strong> ' . htmlspecialchars($queryError) . '</div>';
}

$flash = null;
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

function csUrl(array $overrides = []): string
{
    $params = array_merge([
        'group' => $_GET['group'] ?? '',
        'search' => $_GET['search'] ?? '',
        'page' => $_GET['page'] ?? 1,
        'per_page' => $_GET['per_page'] ?? 10,
    ], $overrides);
    $query = http_build_query(array_filter($params, fn($v) => $v !== ''));
    return '/APN-Solar/customers/customer_status.php' . ($query ? '?' . $query : '');
}

$startRecord = $totalRecords > 0 ? ($page - 1) * $perPage + 1 : 0;
$endRecord = min($page * $perPage, $totalRecords);
?>

<style>
    /* ── Page ── */
    .cs-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .cs-header h2 {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1e293b;
    }

    /* ── Buttons ── */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 15px;
        border-radius: 6px;
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        font-family: inherit;
        transition: all .15s;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn-excel {
        background: #1d6f42;
        color: #fff;
    }

    .btn-edit {
        background: #f59e0b;
        color: #fff;
    }

    .btn-view {
        background: #3b82f6;
        color: #fff;
    }

    .btn-danger {
        background: #ef4444;
        color: #fff;
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
        border: 1.5px solid #e2e8f0;
    }

    .btn-sm {
        padding: 4px 10px;
        font-size: .74rem;
    }

    .btn:hover {
        opacity: .87;
        transform: translateY(-1px);
    }

    /* ── Controls bar ── */
    .ctrl-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 10px;
    }

    .show-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: .82rem;
        color: #64748b;
    }

    .show-wrap select {
        padding: 4px 8px;
        border: 1.5px solid #e2e8f0;
        border-radius: 6px;
        font-size: .82rem;
    }

    .search-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: .82rem;
        color: #64748b;
    }

    .search-wrap input {
        padding: 6px 10px;
        border: 1.5px solid #e2e8f0;
        border-radius: 6px;
        font-size: .82rem;
        font-family: inherit;
        outline: none;
        width: 210px;
    }

    .search-wrap input:focus {
        border-color: #3b82f6;
    }

    /* ── Group filter ── */
    .gf-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }

    .gf-bar label {
        font-size: .78rem;
        color: #64748b;
        font-weight: 600;
    }

    .gf-bar select {
        padding: 6px 10px;
        border: 1.5px solid #e2e8f0;
        border-radius: 6px;
        font-size: .82rem;
    }

    /* ── Table ── */
    .table-wrap {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: .8rem;
        color: #1e293b;
    }

    .table thead th {
        background: #f0f4f8;
        padding: 9px 10px;
        font-weight: 700;
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
        text-align: left;
    }

    .table thead th.sortable {
        cursor: pointer;
        user-select: none;
    }

    .table thead th.sortable::after {
        content: ' ⇅';
        opacity: .3;
        font-size: .6rem;
    }

    .table thead th.sort-asc::after {
        content: ' ↑';
        opacity: 1;
    }

    .table thead th.sort-desc::after {
        content: ' ↓';
        opacity: 1;
    }

    .table tbody td {
        padding: 8px 10px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background: #fffbf0;
    }

    .table tbody tr:last-child td {
        border-bottom: none;
    }

    .table tbody tr.hidden-row {
        display: none;
    }

    /* ── Badges ── */
    .badge-yes {
        display: inline-block;
        background: #dcfce7;
        color: #15803d;
        border-radius: 20px;
        padding: 2px 9px;
        font-size: .68rem;
        font-weight: 700;
    }

    .badge-no {
        display: inline-block;
        background: #fee2e2;
        color: #b91c1c;
        border-radius: 20px;
        padding: 2px 9px;
        font-size: .68rem;
        font-weight: 700;
    }

    .badge-done {
        display: inline-block;
        background: #dbeafe;
        color: #1d4ed8;
        border-radius: 20px;
        padding: 2px 9px;
        font-size: .68rem;
        font-weight: 700;
    }

    .badge-pend {
        display: inline-block;
        background: #fef3c7;
        color: #92400e;
        border-radius: 20px;
        padding: 2px 9px;
        font-size: .68rem;
        font-weight: 700;
    }

    /* ── Flash ── */
    .flash-alert {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 11px 15px;
        border-radius: 8px;
        font-size: .875rem;
        font-weight: 500;
        margin-bottom: 12px;
    }

    .flash-success {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #16a34a;
    }

    .flash-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
    }

    /* ── Pagination ── */
    .pagination-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-top: 1px solid #e2e8f0;
        flex-wrap: wrap;
        gap: 8px;
        font-size: .8rem;
        color: #64748b;
    }

    .pagination {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }

    .pg-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 30px;
        height: 30px;
        padding: 0 6px;
        border: 1.5px solid #e2e8f0;
        border-radius: 6px;
        background: #fff;
        color: #475569;
        font-size: .78rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: all .15s;
    }

    .pg-btn:hover {
        background: #f1f5f9;
    }

    .pg-btn.active {
        background: #3b82f6;
        border-color: #3b82f6;
        color: #fff;
    }

    .pg-btn.disabled {
        opacity: .4;
        pointer-events: none;
    }

    /* ── Delete confirm modal ── */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .45);
        z-index: 9998;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.open {
        display: flex;
    }

    .modal-box {
        background: #fff;
        border-radius: 12px;
        padding: 32px 36px;
        max-width: 420px;
        width: 90%;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
    }

    .modal-icon {
        font-size: 2.8rem;
        color: #ef4444;
        margin-bottom: 14px;
    }

    .modal-box h3 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .modal-box p {
        color: #64748b;
        font-size: .88rem;
        margin-bottom: 18px;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
</style>

<?php if ($flash): ?>
    <div class="flash-alert flash-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
<?php endif; ?>

<!-- Header -->
<div class="cs-header">
    <h2>
        <i class="fas fa-clipboard-list" style="color:#3b82f6;margin-right:7px;"></i>
        Customer Status
        <span
            style="font-weight:400;color:#94a3b8;margin-left:4px;">-(<?php echo number_format($totalRecords); ?>)</span>
    </h2>
    <button class="btn btn-excel" onclick="csExportExcel()">
        <i class="fas fa-file-excel"></i> Export to Excel
    </button>
</div>

<!-- Group filter -->
<form method="GET" action="/APN-Solar/customers/customer_status.php" class="gf-bar">
    <label>Group:</label>
    <select name="group" onchange="this.form.submit()">
        <option value="">All Groups</option>
        <?php foreach ($allGroups as $g): ?>
            <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $group === $g ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($g); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php if ($group): ?>
        <a href="/APN-Solar/customers/customer_status.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-times"></i> Clear
        </a>
    <?php endif; ?>
</form>

<!-- Show / Search controls -->
<div class="ctrl-bar">
    <form method="GET" action="/APN-Solar/customers/customer_status.php" class="show-wrap">
        <?php if ($group): ?><input type="hidden" name="group"
                value="<?php echo htmlspecialchars($group); ?>"><?php endif; ?>
        <label>Show</label>
        <select name="per_page" onchange="this.form.submit()">
            <?php foreach ([10, 25, 50, 100] as $n): ?>
                <option value="<?php echo $n; ?>" <?php echo $perPage == $n ? 'selected' : ''; ?>><?php echo $n; ?></option>
            <?php endforeach; ?>
        </select>
        <label>entries</label>
    </form>
    <div class="search-wrap">
        <label>Search:</label>
        <input type="text" id="csSearch" placeholder="Name, mobile, invoice..." oninput="csFilter(this.value)"
            value="<?php echo htmlspecialchars($search); ?>">
    </div>
</div>

<!-- Table -->
<div class="table-wrap">
    <div class="table-responsive">
        <table class="table" id="csTable">
            <thead>
                <tr>
                    <th class="sortable" onclick="csSort(0)">Sr<br>No.</th>
                    <th class="sortable" onclick="csSort(1)">Group Name</th>
                    <th class="sortable" onclick="csSort(2)">Customer<br>Name</th>
                    <th class="sortable" onclick="csSort(3)">Mobile No.</th>
                    <th class="sortable" onclick="csSort(4)">Electricity<br>Id</th>
                    <th class="sortable" onclick="csSort(5)">KW</th>
                    <th class="sortable" onclick="csSort(6)">Invoice No.</th>
                    <th class="sortable" onclick="csSort(7)">Installer<br>Name</th>
                    <th class="sortable" onclick="csSort(8)">Meter<br>Installation</th>
                    <th class="sortable" onclick="csSort(9)">Online Installer<br>Name</th>
                    <th class="sortable" onclick="csSort(10)">Remarks</th>
                    <th class="sortable" onclick="csSort(11)">Last Updated on</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($customers) > 0):
                    $rowNum = ($page - 1) * $perPage + 1;
                    foreach ($customers as $row): ?>
                        <tr>
                            <td><?php echo $rowNum++; ?></td>
                            <td><?php echo htmlspecialchars($row['group_name'] ?? ''); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['mobile'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['electricity_id'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['kw'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['invoice_no'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['installer_name'] ?? ''); ?></td>
                            <td>
                                <?php $mi = $row['meter_installation'] ?? ''; ?>
                                <?php if ($mi === 'Yes'): ?>
                                    <span class="badge-yes">Yes</span>
                                <?php elseif ($mi === 'No'): ?>
                                    <span class="badge-no">No</span>
                                <?php else: ?>
                                    <span style="color:#cbd5e1;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['online_installer_name'] ?? ''); ?></td>
                            <td style="max-width:140px;word-wrap:break-word;">
                                <?php echo htmlspecialchars($row['install_remarks'] ?? ''); ?>
                            </td>
                            <td style="white-space:nowrap;font-size:.76rem;">
                                <?php echo htmlspecialchars(substr($row['last_updated'] ?? '', 0, 16)); ?>
                            </td>
                            <td>
                                <div style="display:flex;flex-direction:column;gap:3px;min-width:76px;">
                                    <a href="/APN-Solar/customers/customer_status_update.php?id=<?php echo (int) $row['id']; ?>"
                                        class="btn btn-edit btn-sm"><i class="fas fa-pencil-alt"></i> Edit</a>
                                    <a href="/APN-Solar/customers/customer_status_view.php?id=<?php echo (int) $row['id']; ?>"
                                        class="btn btn-view btn-sm"><i class="fas fa-eye"></i> View</a>
                                    <button class="btn btn-danger btn-sm"
                                        onclick="csConfirmDelete(<?php echo (int) $row['id']; ?>, '<?php echo addslashes(htmlspecialchars($row['name'])); ?>')">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="13" style="text-align:center;padding:40px;color:#94a3b8;">
                            <i class="fas fa-clipboard-list"
                                style="font-size:2rem;display:block;margin-bottom:10px;color:#bfdbfe;"></i>
                            No customer status records
                            found<?php echo $group || $search ? ' matching your filters.' : '.'; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-bar">
        <span>
            Showing <strong><?php echo number_format($startRecord); ?>–<?php echo number_format($endRecord); ?></strong>
            of <strong><?php echo number_format($totalRecords); ?></strong> entries
        </span>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a class="pg-btn" href="<?php echo csUrl(['page' => $page - 1]); ?>"><i class="fas fa-chevron-left"></i></a>
            <?php else: ?>
                <span class="pg-btn disabled"><i class="fas fa-chevron-left"></i></span>
            <?php endif; ?>

            <?php
            $window = 3;
            $pStart = max(1, $page - $window);
            $pEnd = min($totalPages, $page + $window);
            if ($pStart > 1): ?>
                <a class="pg-btn" href="<?php echo csUrl(['page' => 1]); ?>">1</a>
                <?php if ($pStart > 2): ?><span class="pg-btn disabled">…</span><?php endif; ?>
            <?php endif;
            for ($p = $pStart; $p <= $pEnd; $p++): ?>
                <a class="pg-btn <?php echo $p === $page ? 'active' : ''; ?>"
                    href="<?php echo csUrl(['page' => $p]); ?>"><?php echo $p; ?></a>
            <?php endfor;
            if ($pEnd < $totalPages): ?>
                <?php if ($pEnd < $totalPages - 1): ?><span class="pg-btn disabled">…</span><?php endif; ?>
                <a class="pg-btn" href="<?php echo csUrl(['page' => $totalPages]); ?>"><?php echo $totalPages; ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a class="pg-btn" href="<?php echo csUrl(['page' => $page + 1]); ?>"><i
                        class="fas fa-chevron-right"></i></a>
            <?php else: ?>
                <span class="pg-btn disabled"><i class="fas fa-chevron-right"></i></span>
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
            <form id="deleteForm" method="POST" action="/APN-Solar/customers/delete.php">
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
    function csFilter(q) {
        q = q.toLowerCase();
        document.querySelectorAll('#csTable tbody tr').forEach(row => {
            const text = row.innerText.toLowerCase();
            row.classList.toggle('hidden-row', q !== '' && !text.includes(q));
        });
    }

    /* ── Column sort ── */
    let csSortCol = -1, csSortAsc = true;
    function csSort(col) {
        const ths = document.querySelectorAll('#csTable thead th');
        ths.forEach(th => th.classList.remove('sort-asc', 'sort-desc'));
        if (csSortCol === col) { csSortAsc = !csSortAsc; } else { csSortCol = col; csSortAsc = true; }
        ths[col].classList.add(csSortAsc ? 'sort-asc' : 'sort-desc');
        const tbody = document.querySelector('#csTable tbody');
        const rows = [...tbody.querySelectorAll('tr')].filter(r => !r.querySelector('[colspan]'));
        rows.sort((a, b) => {
            const va = a.cells[col]?.innerText.trim() ?? '';
            const vb = b.cells[col]?.innerText.trim() ?? '';
            const na = parseFloat(va.replace(/[^0-9.\-]/g, ''));
            const nb = parseFloat(vb.replace(/[^0-9.\-]/g, ''));
            const cmp = (!isNaN(na) && !isNaN(nb)) ? na - nb : va.localeCompare(vb);
            return csSortAsc ? cmp : -cmp;
        });
        rows.forEach(r => tbody.appendChild(r));
    }

    /* ── Delete confirm ── */
    function csConfirmDelete(id, name) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteModalMsg').textContent =
            'Permanently delete "' + name + '" and all related records?';
        document.getElementById('deleteModal').classList.add('open');
    }
    document.getElementById('deleteModal').addEventListener('click', function (e) {
        if (e.target === this) this.classList.remove('open');
    });

    /* ── Export to Excel ── */
    function csExportExcel() {
        const rows = document.querySelectorAll('#csTable tr');
        let html = '<table border="1"><thead>';
        let inBody = false;
        rows.forEach(row => {
            const isHead = row.closest('thead');
            if (isHead && !inBody) html += '<tr>';
            else if (!isHead && !inBody) { html += '</thead><tbody><tr>'; inBody = true; }
            else html += '<tr>';
            row.querySelectorAll('th,td').forEach((cell, i) => {
                if (i === 12) return; // skip Actions
                const tag = isHead ? 'th' : 'td';
                html += `<${tag}>${cell.innerText.trim()}</${tag}>`;
            });
            html += '</tr>';
        });
        html += '</tbody></table>';
        const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
        const a = Object.assign(document.createElement('a'), {
            href: URL.createObjectURL(blob),
            download: 'customer_status_' + new Date().toISOString().slice(0, 10) + '.xls'
        });
        a.click(); URL.revokeObjectURL(a.href);
    }
</script>