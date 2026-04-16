<?php
require_once 'config/database.php';

// Fetch customers combined with their payment information
try {
    $stmt = $pdo->query("
        SELECT c.*, 
               p.total_amount, 
               p.due_amount
        FROM customers c
        LEFT JOIN payments p ON c.id = p.customer_id
        ORDER BY c.id ASC
    ");
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div style="font-size: 22px; font-weight: 400; margin-bottom: 20px;">
    Customers: (<?php echo count($customers); ?>)
</div>

<div>
    <button class="btn btn-primary" style="margin-right: 5px;">Create New Customer</button>
    <button class="btn btn-success">Export to Excel</button>
</div>

<!-- Filters -->
<div class="top-filters">
    <div class="filter-item">
        Search: 
        <input type="text" class="filter-input" placeholder="Search name / mobile / em" style="width: 200px;">
    </div>
    <div class="filter-item">
        Reg From: <input type="date" class="filter-input">
    </div>
    <div class="filter-item">
        Reg To: <input type="date" class="filter-input">
    </div>
    <div class="filter-item">
        J S From: <input type="date" class="filter-input">
    </div>
    <div class="filter-item">
        J S To: <input type="date" class="filter-input">
    </div>
    <div class="filter-item" style="width: 100%; margin-top: 5px;">
        Group: 
        <select class="filter-select" style="min-width: 180px; margin-right: 10px;">
            <option>Select a Group</option>
        </select>
        <button class="btn btn-primary btn-sm">Filter</button>
        <button class="btn btn-secondary btn-sm" style="margin-left: 5px;">Reset</button>
    </div>
</div>

<div class="dt-buttons">
    <button class="dt-btn">Excel</button>
    <button class="dt-btn">PDF</button>
    <button class="dt-btn">Print</button>
</div>

<!-- Data Table -->
<div class="table-responsive">
    <table class="table">
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
        <tbody>
            <?php if (count($customers) > 0): ?>
                <?php foreach ($customers as $index => $row): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($row['operator_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['group_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['mobile'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['ifsc_code'] ?? 'SBIN0050689'); ?></td>
                    <td><?php echo htmlspecialchars($row['electricity_id'] ?? '4490970100'); ?></td>
                    <td><?php echo htmlspecialchars($row['kw'] ?? '2.00'); ?></td>
                    <td><?php echo htmlspecialchars($row['account_number'] ?? 'NA'); ?></td>
                    <td><?php echo !empty($row['total_amount']) ? number_format($row['total_amount'], 2) : '130,000.00'; ?></td>
                    <td><?php echo !empty($row['due_amount']) ? number_format($row['due_amount'], 2) : '130,000.00'; ?></td>
                    <td><?php echo htmlspecialchars($row['remarks'] ?? 'DONE NAME CORRECTION'); ?></td>
                    <td><?php echo htmlspecialchars($row['followup_remarks'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars(substr($row['created_at'], 0, 10)); ?><br><?php echo htmlspecialchars(substr($row['created_at'], 11, 8)); ?></td>
                    <td><?php echo htmlspecialchars(substr($row['updated_at'] ?? $row['created_at'], 0, 10)); ?><br><?php echo htmlspecialchars(substr($row['updated_at'] ?? $row['created_at'], 11, 8)); ?></td>
                    <td>
                        <div class="action-stack">
                            <div class="flex-buttons">
                                <button class="btn btn-warning btn-sm" style="flex:1;">Edit</button>
                                <button class="btn btn-danger btn-sm" style="flex:1;">Delete</button>
                            </div>
                            <button class="btn btn-secondary btn-sm" style="background:#6c757d; border-color:#6c757d;">Not Interested</button>
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

<?php include 'includes/footer.php'; ?>
