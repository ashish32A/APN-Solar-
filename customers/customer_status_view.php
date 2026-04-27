<?php
// customers/current_status_view.php — Read-only detail view of a customer's status

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header("Location: /APN-Solar/customers/customer_status.php");
    exit;
}

$stmt = $pdo->prepare("
     SELECT c.*, p.total_amount, p.due_amount, p.payment_received,
            i.id AS install_id, i.invoice_no, i.invoice_date, i.material_dispatch_1st, i.material_dispatch_2nd, 
            i.installer_name, i.installation_date, i.dcr_certificate, i.installation_indent, i.meter_installation, 
            i.meter_configuration, i.online_installer_name, i.subsidy_1st_status, i.subsidy_2nd_status, 
            i.warranty_download, i.warranty_delivery_operator, i.warranty_delivery_date,
            i.address AS install_address, i.remarks AS install_remarks,
            i.status AS install_status, i.updated_at AS last_updated
    FROM customers c
    LEFT JOIN installations i ON c.id = i.customer_id
    LEFT JOIN payments p ON c.id = p.customer_id
    WHERE c.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    header("Location: /APN-Solar/customers/customer_status.php");
    exit;
}

$pageTitle = 'Customer Status — ' . htmlspecialchars($row['name']);
include __DIR__ . '/../views/partials/header.php';

function statusDate($date, $label)
{
    if ($date && $date !== '0000-00-00') {
        return '<span style="display:inline-flex;align-items:center;gap:5px;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;border-radius:6px;padding:3px 10px;font-size:.78rem;font-weight:600;"><i class="fas fa-check-circle"></i> ' . htmlspecialchars($date) . '</span>';
    }
    return '<span style="color:#cbd5e1;font-size:.78rem;">— Not set —</span>';
}
?>

<style>
    .view-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .1);
        padding: 28px 32px;
        max-width: 960px;
    }

    .view-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .view-title i {
        color: #3b82f6;
    }

    .badge-active {
        background: #dcfce7;
        color: #15803d;
        border-radius: 20px;
        padding: 3px 12px;
        font-size: .75rem;
        font-weight: 700;
    }

    .badge-pending {
        background: #fef3c7;
        color: #92400e;
        border-radius: 20px;
        padding: 3px 12px;
        font-size: .75rem;
        font-weight: 700;
    }

    .badge-completed {
        background: #dbeafe;
        color: #1d4ed8;
        border-radius: 20px;
        padding: 3px 12px;
        font-size: .75rem;
        font-weight: 700;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 14px;
        margin-bottom: 20px;
    }

    .info-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 15px;
    }

    .info-card .label {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #94a3b8;
        margin-bottom: 4px;
    }

    .info-card .value {
        font-size: .9rem;
        font-weight: 600;
        color: #1e293b;
    }

    .section-head {
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #94a3b8;
        margin: 22px 0 12px;
        padding-bottom: 6px;
        border-bottom: 1px solid #e2e8f0;
    }

    .milestones {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
    }

    .milestone {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 14px;
    }

    .milestone .m-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #94a3b8;
        margin-bottom: 6px;
    }

    .action-bar {
        display: flex;
        gap: 10px;
        margin-top: 24px;
        padding-top: 18px;
        border-top: 1px solid #e2e8f0;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 20px;
        border-radius: 8px;
        font-size: .88rem;
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

    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
        border: 1.5px solid #e2e8f0;
    }

    .btn:hover {
        opacity: .87;
        transform: translateY(-1px);
    }
</style>

<div class="view-card">
    <div class="view-title">
        <i class="fas fa-clipboard-check"></i>
        <?php echo htmlspecialchars($row['name']); ?>
        <?php
        $cs = $row['customer_status'] ?? $row['status'] ?? '';
        if ($cs === 'active')
            echo '<span class="badge-active">Active</span>';
        elseif ($cs === 'pending')
            echo '<span class="badge-pending">Pending</span>';
        elseif ($cs === 'completed')
            echo '<span class="badge-completed">Completed</span>';
        ?>
    </div>

    <!-- Customer Status Information (Matching Screenshot) -->
    <div class="section-head" style="background-color: #f8fafc; padding: 10px; margin-top: 0; border: 1px solid #e2e8f0; border-bottom: none; font-size: 0.85rem; color: #1e293b; font-weight: normal; text-transform: none; letter-spacing: normal;">Customer Status Information</div>
    <div style="border: 1px solid #e2e8f0;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
            <tbody>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; width: 40%; color: #1e293b; background-color: #f8fafc;">Group Name</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['group_name'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Customer Name</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Customer Mobile Number</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['mobile'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Electric Account id</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['electricity_id'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Division Name</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['district_name'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">KW</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo number_format((float)($row['kw'] ?? 0), 2); ?></td>
                </tr>
                <?php if ($row['install_id']): ?>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Invoice No.</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['invoice_no'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Invoice Date</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['invoice_date'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Material Dispatch Date (1st Lot)</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['material_dispatch_1st'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Material Dispatch Date (2nd Lot)</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['material_dispatch_2nd'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Installer Name</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['installer_name'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Installation Date</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['installation_date'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">DCR Certificate</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['dcr_certificate'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Installation Indent</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['installation_indent'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Meter Installation</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['meter_installation'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Meter Configuration</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['meter_configuration'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Online Installer Name</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['online_installer_name'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Subsidy Receive Status (1st)</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['subsidy_1st_status'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Subsidy Receive Status (2nd)</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['subsidy_2nd_status'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Warranty Certificate Download</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['warranty_download'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Warranty Certificate Delivery Operator Name</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['warranty_delivery_operator'] ?? ''); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 15px; font-weight: 700; color: #1e293b; background-color: #f8fafc;">Warranty Certificate Delivery Date</td>
                    <td style="padding: 12px 15px; color: #475569;"><?php echo htmlspecialchars($row['warranty_delivery_date'] ?? ''); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($row['install_id']): ?>



        <?php if (!empty($row['install_remarks'])): ?>
            <div class="section-head">Remarks</div>
            <div
                style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;font-size:.88rem;color:#374151;line-height:1.6;">
                <?php echo nl2br(htmlspecialchars($row['install_remarks'])); ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div
            style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:14px 16px;font-size:.88rem;color:#92400e;">
            <i class="fas fa-exclamation-triangle"></i> No installation record found for this customer.
            <a href="/APN-Solar/customers/customer_status_update.php?id=<?php echo $id; ?>"
                style="color:#1d4ed8;font-weight:600;margin-left:8px;">Add one now →</a>
        </div>
    <?php endif; ?>

    <div class="action-bar" style="margin-top: 15px; border-top: none; padding-top: 0; display: flex; flex-direction: row-reverse;">
        <?php
        $backUrl = ($_GET['back'] ?? '') === 'pending'
            ? '/APN-Solar/customers/pending_list.php'
            : '/APN-Solar/customers/customer_status.php';
        ?>
        <a href="<?php echo $backUrl; ?>" class="btn btn-secondary" style="background:#64748b; color: white; border:none; padding: 10px 15px; border-radius: 4px; font-size: 0.8rem;">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>


<?php include __DIR__ . '/../views/partials/footer.php'; ?>