<?php
// customers/current_status_update.php — Edit installation record for a customer

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header("Location: /APN-Solar/customers/customer_status.php");
    exit;
}

// Where to go back after save/cancel
$backParam = trim($_GET['back'] ?? '');
$backUrl   = match($backParam) {
    'pending_installation' => '/APN-Solar/reports/pending_installation.php',
    'pending_net_metering' => '/APN-Solar/reports/pending_net_metering.php',
    'pending_online_installation' => '/APN-Solar/reports/pending_online_installation.php',
    'pending_subsidy_first' => '/APN-Solar/reports/pending_subsidy_first.php',
    'pending_subsidy_second' => '/APN-Solar/reports/pending_subsidy_second.php',
    'pending_account_number' => '/APN-Solar/reports/pending_account_number.php',
    'first_materials_dispatched' => '/APN-Solar/reports/first_materials_dispatched.php',
    'second_materials_dispatched' => '/APN-Solar/reports/second_materials_dispatched.php',
    'customer_registration_pending' => '/APN-Solar/reports/customer_registration_pending.php',
    'customer_jan_samarth_pending' => '/APN-Solar/reports/customer_jan_samarth_pending.php',
    'payment_due'          => '/APN-Solar/customers/payment_due.php',
    'payment_received'     => '/APN-Solar/customers/payment_received.php',
    'subsidy_first'        => '/APN-Solar/customers/subsidy_first.php',
    'subsidy_second'       => '/APN-Solar/customers/subsidy_second.php',
    default                => '/APN-Solar/customers/customer_status.php',
};

// Fetch customer + installation
$stmt = $pdo->prepare("
    SELECT c.*, i.id AS install_id, i.invoice_no, i.invoice_date, i.material_dispatch_1st, i.material_dispatch_2nd, 
           i.installer_name, i.installation_date, i.dcr_certificate, i.installation_indent, i.meter_installation, 
           i.meter_configuration, i.online_installer_name, i.subsidy_1st_status, i.subsidy_2nd_status, 
           i.warranty_download, i.warranty_delivery_operator, i.warranty_delivery_date,
           i.address, i.district_name AS install_district, i.remarks AS install_remarks, i.status AS install_status
    FROM customers c
    LEFT JOIN installations i ON c.id = i.customer_id
    WHERE c.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    setFlash('error', 'Customer not found.');
    header("Location: /APN-Solar/customers/customer_status.php");
    exit;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'invoice_no', 'invoice_date', 'material_dispatch_1st', 'material_dispatch_2nd',
        'installer_name', 'installation_date', 'dcr_certificate', 'installation_indent',
        'meter_installation', 'meter_configuration', 'online_installer_name',
        'subsidy_1st_status', 'subsidy_2nd_status', 'warranty_download',
        'warranty_delivery_operator', 'warranty_delivery_date', 'remarks', 'install_status'
    ];
    $data = [];
    foreach ($fields as $f) {
        $v = trim($_POST[$f] ?? '');
        $data[$f] = $v === '' ? null : $v;
    }

    try {
        if ($row['install_id']) {
            // Update existing installation
            $pdo->prepare("
                UPDATE installations SET
                    invoice_no=?, invoice_date=?, material_dispatch_1st=?, material_dispatch_2nd=?,
                    installer_name=?, installation_date=?, dcr_certificate=?, installation_indent=?,
                    meter_installation=?, meter_configuration=?, online_installer_name=?,
                    subsidy_1st_status=?, subsidy_2nd_status=?, warranty_download=?,
                    warranty_delivery_operator=?, warranty_delivery_date=?, remarks=?, status=?, updated_at=NOW()
                WHERE id=?
            ")->execute([
                        $data['invoice_no'], $data['invoice_date'], $data['material_dispatch_1st'], $data['material_dispatch_2nd'],
                        $data['installer_name'], $data['installation_date'], $data['dcr_certificate'], $data['installation_indent'],
                        $data['meter_installation'], $data['meter_configuration'], $data['online_installer_name'],
                        $data['subsidy_1st_status'], $data['subsidy_2nd_status'], $data['warranty_download'],
                        $data['warranty_delivery_operator'], $data['warranty_delivery_date'], $data['remarks'], 
                        $data['install_status'] ?? 'pending', $row['install_id']
                    ]);
        } else {
            // Create new installation record
            $pdo->prepare("
                INSERT INTO installations
                    (customer_id, invoice_no, invoice_date, material_dispatch_1st, material_dispatch_2nd,
                     installer_name, installation_date, dcr_certificate, installation_indent,
                     meter_installation, meter_configuration, online_installer_name,
                     subsidy_1st_status, subsidy_2nd_status, warranty_download,
                     warranty_delivery_operator, warranty_delivery_date, remarks, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                        $id,
                        $data['invoice_no'], $data['invoice_date'], $data['material_dispatch_1st'], $data['material_dispatch_2nd'],
                        $data['installer_name'], $data['installation_date'], $data['dcr_certificate'], $data['installation_indent'],
                        $data['meter_installation'], $data['meter_configuration'], $data['online_installer_name'],
                        $data['subsidy_1st_status'], $data['subsidy_2nd_status'], $data['warranty_download'],
                        $data['warranty_delivery_operator'], $data['warranty_delivery_date'], $data['remarks'],
                        $data['install_status'] ?? 'pending'
                    ]);
        }
        setFlash('success', 'Installation status updated for "' . htmlspecialchars($row['name']) . '".');
        header("Location: $backUrl");
        exit;
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

$pageTitle = 'Edit Customer Status — ' . htmlspecialchars($row['name']);
include __DIR__ . '/../views/partials/header.php';
?>

<style>
    .page-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .12);
        padding: 28px 32px;
        max-width: 960px;
    }

    .page-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .page-title i {
        color: #3b82f6;
    }

    .badge-id {
        background: #f1f5f9;
        color: #64748b;
        font-size: .75rem;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 16px;
    }

    .form-grid .span2 {
        grid-column: span 2;
    }

    .form-grid .full {
        grid-column: 1/-1;
    }

    .form-group label {
        display: block;
        font-size: .72rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 5px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 9px 12px;
        border: 1.5px solid #e2e8f0;
        border-radius: 7px;
        font-size: .88rem;
        font-family: inherit;
        color: #1e293b;
        background: #f8fafc;
        outline: none;
        transition: border-color .2s;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #3b82f6;
        background: #fff;
    }

    .section-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #94a3b8;
        padding-bottom: 6px;
        border-bottom: 1px solid #e2e8f0;
        grid-column: 1/-1;
        margin-top: 10px;
    }

    .customer-info {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px 16px;
        margin-bottom: 20px;
    }

    .ci-item {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .ci-label {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #94a3b8;
        letter-spacing: .05em;
    }

    .ci-value {
        font-size: .88rem;
        font-weight: 600;
        color: #1e293b;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 22px;
        padding-top: 18px;
        border-top: 1px solid #e2e8f0;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 22px;
        border-radius: 8px;
        font-size: .9rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        font-family: inherit;
        transition: all .15s;
        text-decoration: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
    }

    .btn-primary:hover {
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
        border: 1.5px solid #e2e8f0;
    }

    .alert-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 18px;
        font-size: .875rem;
    }
</style>

<div class="page-card">
    <div class="page-title">
        <i class="fas fa-clipboard-check"></i>
        Edit Customer Status
        <span class="badge-id">ID #<?php echo $id; ?></span>
    </div>

    <!-- Customer Info Bar -->
    <div class="customer-info">
        <div class="ci-item">
            <span class="ci-label">Customer Name</span>
            <span class="ci-value"><?php echo htmlspecialchars($row['name']); ?></span>
        </div>
        <div class="ci-item">
            <span class="ci-label">Mobile</span>
            <span class="ci-value"><?php echo htmlspecialchars($row['mobile'] ?? '—'); ?></span>
        </div>
        <div class="ci-item">
            <span class="ci-label">Group</span>
            <span class="ci-value"><?php echo htmlspecialchars($row['group_name'] ?? '—'); ?></span>
        </div>
        <div class="ci-item">
            <span class="ci-label">KW</span>
            <span class="ci-value"><?php echo htmlspecialchars($row['kw'] ?? '—'); ?></span>
        </div>
        <div class="ci-item">
            <span class="ci-label">Electricity ID</span>
            <span class="ci-value"><?php echo htmlspecialchars($row['electricity_id'] ?? '—'); ?></span>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-grid" style="display: flex; flex-direction: column;">
            <div class="form-group full">
                <label>Group Name</label>
                <select name="group_id" disabled style="background-color: #e2e8f0;">
                    <option><?php echo htmlspecialchars($row['group_name'] ?? ''); ?></option>
                </select>
            </div>

            <div class="form-group full">
                <label>Customer Name</label>
                <select name="customer_id" disabled style="background-color: #e2e8f0;">
                    <option><?php echo htmlspecialchars($row['name'] ?? ''); ?></option>
                </select>
            </div>

            <div class="form-group full">
                <label>Invoice No.</label>
                <input type="text" name="invoice_no" placeholder="e.g. CPDV2400000391"
                    value="<?php echo htmlspecialchars($row['invoice_no'] ?? ''); ?>">
            </div>

            <div class="form-group full">
                <label>Invoice date</label>
                <input type="date" name="invoice_date"
                    value="<?php echo htmlspecialchars($row['invoice_date'] ?? ''); ?>">
            </div>

            <div class="form-group full">
                <label>Material Dispatch Date (1st Lot)</label>
                <input type="date" name="material_dispatch_1st"
                    value="<?php echo htmlspecialchars($row['material_dispatch_1st'] ?? ''); ?>">
            </div>

            <div class="form-group full">
                <label>Material Dispatch Date (2nd Lot)</label>
                <input type="date" name="material_dispatch_2nd"
                    value="<?php echo htmlspecialchars($row['material_dispatch_2nd'] ?? ''); ?>">
            </div>

            <div class="form-group full">
                <label>Installer Name</label>
                <input type="text" name="installer_name"
                    value="<?php echo htmlspecialchars($row['installer_name'] ?? ''); ?>">
            </div>

            <div class="form-group full">
                <label>Installation Date</label>
                <input type="date" name="installation_date"
                    value="<?php echo htmlspecialchars($row['installation_date'] ?? ''); ?>">
            </div>

            <div class="form-group full">
                <label>DCR Certificate</label>
                <select name="dcr_certificate">
                    <option value="">-- Select --</option>
                    <option value="Yes" <?php echo ($row['dcr_certificate'] ?? '') === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                    <option value="No" <?php echo ($row['dcr_certificate'] ?? '') === 'No' ? 'selected' : ''; ?>>No</option>
                </select>
            </div>

            <div class="form-group full">
                <label>Installation Indent</label>
                <select name="installation_indent">
                    <option value="">-- Select --</option>
                    <option value="Yes" <?php echo ($row['installation_indent'] ?? '') === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                    <option value="No" <?php echo ($row['installation_indent'] ?? '') === 'No' ? 'selected' : ''; ?>>No</option>
                </select>
            </div>

            <div class="form-group full">
                <label>Meter Installation</label>
                <select name="meter_installation">
                    <option value="">-- Select --</option>
                    <option value="Yes" <?php echo ($row['meter_installation'] ?? '') === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                    <option value="No" <?php echo ($row['meter_installation'] ?? '') === 'No' ? 'selected' : ''; ?>>No</option>
                </select>
            </div>
            
            <div class="form-group full">
                <label>Meter Configuration</label>
                <select name="meter_configuration">
                    <option value="">-- Select --</option>
                    <option value="Selling Certificate" <?php echo ($row['meter_configuration'] ?? '') === 'Selling Certificate' ? 'selected' : ''; ?>>Selling Certificate</option>
                    <option value="Not Completed" <?php echo ($row['meter_configuration'] ?? '') === 'Not Completed' ? 'selected' : ''; ?>>Not Completed</option>
                </select>
            </div>

            <div class="form-group full">
                <label>Installation Submission Operator</label>
                <input type="text" name="online_installer_name"
                    value="<?php echo htmlspecialchars($row['online_installer_name'] ?? ''); ?>">
            </div>

            <div class="form-group full">
                <label>Subsidy Receive Status (1st)</label>
                <select name="subsidy_1st_status">
                    <option value="">-- Select --</option>
                    <option value="Yes" <?php echo ($row['subsidy_1st_status'] ?? '') === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                    <option value="No" <?php echo ($row['subsidy_1st_status'] ?? '') === 'No' ? 'selected' : ''; ?>>No</option>
                </select>
            </div>

            <div class="form-group full">
                <label>Subsidy Receive Status (2nd)</label>
                <select name="subsidy_2nd_status">
                    <option value="">-- Select --</option>
                    <option value="Yes" <?php echo ($row['subsidy_2nd_status'] ?? '') === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                    <option value="No" <?php echo ($row['subsidy_2nd_status'] ?? '') === 'No' ? 'selected' : ''; ?>>No</option>
                </select>
            </div>

            <div class="form-group full">
                <label>Warranty Certificate Download</label>
                <select name="warranty_download">
                    <option value="">-- Select --</option>
                    <option value="Yes" <?php echo ($row['warranty_download'] ?? '') === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                    <option value="No" <?php echo ($row['warranty_download'] ?? '') === 'No' ? 'selected' : ''; ?>>No</option>
                </select>
            </div>

            <div class="form-group full">
                <label>Warranty Certificate Delivery Operator</label>
                <input type="text" name="warranty_delivery_operator"
                    value="<?php echo htmlspecialchars($row['warranty_delivery_operator'] ?? ''); ?>">
            </div>

            <div class="form-group full">
                <label>Warranty Certificate Delivery Date</label>
                <input type="date" name="warranty_delivery_date"
                    value="<?php echo htmlspecialchars($row['warranty_delivery_date'] ?? ''); ?>">
            </div>

            <div class="form-group full" style="display:none;">
                <label>Installation Status</label>
                <select name="install_status">
                    <option value="pending" <?php echo ($row['install_status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo ($row['install_status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>

            <div class="form-group full">
                <label>Remarks</label>
                <textarea name="remarks" rows="3"
                    placeholder="Status remarks..."><?php echo htmlspecialchars($row['install_remarks'] ?? ''); ?></textarea>
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Status
            </button>
            <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Cancel
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>