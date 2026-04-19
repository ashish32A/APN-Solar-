<?php
// views/customers/index.php — Customer Registration list view
// Variables available: $customers (array)
?>
<div style="font-size: 22px; font-weight: 400; margin-bottom: 20px;">
    Customers: (<?php echo count($customers); ?>)
</div>

<div>
    <a href="/APN-Solar/customers/create.php" class="btn btn-primary" style="margin-right: 5px;">Create New Customer</a>
    <button class="btn btn-success" onclick="exportToExcel()">Export to Excel</button>
</div>

<!-- Filters -->
<div class="top-filters">
    <div class="filter-item">
        Search:
        <input type="text" id="searchInput" class="filter-input" placeholder="Search name / mobile / email" style="width: 220px;" oninput="filterTable()">
    </div>
    <div class="filter-item">
        Reg From: <input type="date" id="regFrom" class="filter-input">
    </div>
    <div class="filter-item">
        Reg To: <input type="date" id="regTo" class="filter-input">
    </div>
    <div class="filter-item">
        J S From: <input type="date" class="filter-input">
    </div>
    <div class="filter-item">
        J S To: <input type="date" class="filter-input">
    </div>
    <div class="filter-item" style="width: 100%; margin-top: 5px;">
        Group:
        <select id="groupFilter" class="filter-select" style="min-width: 180px; margin-right: 10px;">
            <option value="">Select a Group</option>
            <?php
            $groups = array_unique(array_column($customers, 'group_name'));
            foreach ($groups as $g): if ($g): ?>
                <option value="<?php echo htmlspecialchars($g); ?>"><?php echo htmlspecialchars($g); ?></option>
            <?php endif; endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" onclick="filterTable()">Filter</button>
        <button class="btn btn-secondary btn-sm" style="margin-left: 5px;" onclick="resetFilters()">Reset</button>
    </div>
</div>

<div class="dt-buttons">
    <button class="dt-btn" onclick="exportToExcel()">Excel</button>
    <button class="dt-btn" onclick="window.print()">Print</button>
</div>

<!-- Data Table -->
<div class="table-responsive">
    <table class="table" id="customersTable">
        <thead>
            <tr>
                <th>Sr No.</th>
                <th>Operator Name</th>
                <th>Group Name</th>
                <th>Name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>IFSC Code</th>
                <th>Electricity Id</th>
                <th>Kw</th>
                <th>Account Number</th>
                <th>Total Amount</th>
                <th>Due Amount</th>
                <th>Remarks</th>
                <th>Followup Remarks</th>
                <th>Created Date</th>
                <th>Updated Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="customerBody">
            <?php if (count($customers) > 0): ?>
                <?php foreach ($customers as $index => $row): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($row['operator_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['group_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['mobile'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['ifsc_code'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['electricity_id'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['kw'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['account_number'] ?? ''); ?></td>
                    <td><?php echo !empty($row['total_amount']) ? number_format($row['total_amount'], 2) : '–'; ?></td>
                    <td><?php echo !empty($row['due_amount']) ? number_format($row['due_amount'], 2) : '–'; ?></td>
                    <td><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['followup_remarks'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(substr($row['created_at'] ?? '', 0, 10)); ?><br><?php echo htmlspecialchars(substr($row['created_at'] ?? '', 11, 8)); ?></td>
                    <td><?php echo htmlspecialchars(substr($row['updated_at'] ?? $row['created_at'] ?? '', 0, 10)); ?><br><?php echo htmlspecialchars(substr($row['updated_at'] ?? $row['created_at'] ?? '', 11, 8)); ?></td>
                    <td>
                        <div class="action-stack">
                            <div class="flex-buttons">
                                <a href="/APN-Solar/customers/edit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm" style="flex:1;">Edit</a>
                                <a href="/APN-Solar/customers/delete.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" style="flex:1;"
                                   onclick="return confirm('Delete this customer?')">Delete</a>
                            </div>
                            <button class="btn btn-secondary btn-sm" style="background:#6c757d;">Not Interested</button>
                            <button class="btn btn-warning btn-sm" style="background:#ffc107;">Followup</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="17" style="text-align: center;">No customers found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const group  = document.getElementById('groupFilter').value.toLowerCase();
    const rows   = document.querySelectorAll('#customerBody tr');

    rows.forEach(row => {
        const text  = row.textContent.toLowerCase();
        const grpEl = row.cells[2] ? row.cells[2].textContent.toLowerCase() : '';
        const matchSearch = text.includes(search);
        const matchGroup  = group === '' || grpEl === group;
        row.style.display = (matchSearch && matchGroup) ? '' : 'none';
    });
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('groupFilter').value = '';
    filterTable();
}

function exportToExcel() {
    const table = document.getElementById('customersTable').outerHTML;
    const blob  = new Blob([table], { type: 'application/vnd.ms-excel' });
    const url   = URL.createObjectURL(blob);
    const a     = document.createElement('a');
    a.href      = url;
    a.download  = 'customers_' + new Date().toISOString().slice(0, 10) + '.xls';
    a.click();
}
</script>
