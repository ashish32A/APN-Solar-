<?php
// followup/view.php — Add followup & view history for a customer

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

// ── Ensure columns exist ──────────────────────────────────────────────────────
try {
    $pdo->exec("ALTER TABLE followups MODIFY COLUMN status
        ENUM('pending','in_progress','resolved','re_open','done') DEFAULT 'pending'");
} catch (PDOException $e) {}
try {
    $fCols = $pdo->query("SHOW COLUMNS FROM followups")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('operator_name_text', $fCols)) {
        $pdo->exec("ALTER TABLE followups ADD COLUMN operator_name_text VARCHAR(100) DEFAULT NULL AFTER operator_id");
    }
    if (!in_array('remark', $fCols)) {
        $pdo->exec("ALTER TABLE followups ADD COLUMN remark TEXT DEFAULT NULL AFTER notes");
    }
} catch (PDOException $e) {}

$customerId = (int)($_GET['id'] ?? 0);
if (!$customerId) {
    header("Location: /APN-Solar/followup/index.php");
    exit;
}

// Fetch customer
$custStmt = $pdo->prepare("SELECT id, name, mobile, group_name, operator_name FROM customers WHERE id = ?");
$custStmt->execute([$customerId]);
$customer = $custStmt->fetch();

if (!$customer) {
    header("Location: /APN-Solar/followup/index.php");
    exit;
}

// ── Handle POST ───────────────────────────────────────────────────────────────
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $remark = trim($_POST['remark'] ?? '');
    $status = trim($_POST['status'] ?? 'pending');

    $allowed = ['pending','in_progress','resolved','re_open','done'];
    if (!in_array($status, $allowed)) $status = 'pending';

    if ($remark === '') {
        $error = 'Remark is required.';
    } else {
        try {
            $pdo->prepare("INSERT INTO followups (customer_id, followup_by, notes, remark, status) VALUES (?,?,?,?,?)")
                ->execute([$customerId, $_SESSION['admin_id'] ?? null, $remark, $remark, $status]);

            // Update customer followup_remarks
            $pdo->prepare("UPDATE customers SET followup_remarks = ? WHERE id = ?")->execute([$remark, $customerId]);

            setFlash('success', 'Followup added successfully.');
            header("Location: /APN-Solar/followup/view.php?id=$customerId");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// ── Load followup history ─────────────────────────────────────────────────────
try {
    $fuStmt = $pdo->prepare("SELECT * FROM followups WHERE customer_id = ? ORDER BY created_at DESC");
    $fuStmt->execute([$customerId]);
    $followups = $fuStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $followups = [];
}

// Flash
$flash = null;
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

$pageTitle = 'Followups for Customer #' . $customerId . ' (' . htmlspecialchars($customer['name']) . ')';
include __DIR__ . '/../views/partials/header.php';
?>

<style>
.fv-wrap  { max-width:1000px; }

/* Back button */
.fv-back { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:4px;
           background:#495057; color:#fff; font-size:.83rem; font-weight:600; cursor:pointer;
           border:none; text-decoration:none; margin-bottom:16px; transition:opacity .15s; }
.fv-back:hover { opacity:.85; }

/* Card */
.fv-card { background:#fff; border:1px solid #dee2e6; border-radius:4px; padding:18px 20px; margin-bottom:16px; }
.fv-card-title { font-size:.9rem; font-weight:700; color:#212529; margin-bottom:14px; padding-bottom:8px; border-bottom:1px solid #f1f5f9; }

/* Form */
.fg { margin-bottom:12px; }
.fg label { display:block; font-size:.8rem; font-weight:600; color:#495057; margin-bottom:4px; }
.fg textarea, .fg select {
    width:100%; padding:8px 10px; border:1px solid #ced4da; border-radius:4px;
    font-size:.87rem; font-family:inherit; color:#212529; outline:none;
    transition:border-color .15s;
}
.fg textarea:focus, .fg select:focus { border-color:#80bdff; }
.fg textarea { resize:vertical; min-height:90px; }

/* Submit button */
.btn-add-fu { background:#007bff; color:#fff; border:none; border-radius:4px;
              padding:8px 18px; font-size:.85rem; font-weight:600; cursor:pointer;
              display:inline-flex; align-items:center; gap:6px; font-family:inherit;
              transition:background .15s; }
.btn-add-fu:hover { background:#0069d9; }

/* Flash */
.flash { display:flex; align-items:center; gap:8px; padding:10px 14px; border-radius:4px;
         font-size:.875rem; font-weight:500; margin-bottom:12px; }
.flash-success { background:#d4edda; border:1px solid #c3e6cb; color:#155724; }
.flash-error   { background:#f8d7da; border:1px solid #f5c6cb; color:#721c24; }

/* Error inline */
.err-alert { background:#f8d7da; border:1px solid #f5c6cb; color:#721c24;
             padding:8px 12px; border-radius:4px; font-size:.83rem; margin-bottom:10px; }

/* History table */
.hist-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.hist-table thead th { background:#f8f9fa; padding:8px 10px; font-weight:700; font-size:.72rem;
                       text-transform:uppercase; color:#6c757d; border-bottom:2px solid #dee2e6; text-align:left; }
.hist-table tbody td { padding:8px 10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.hist-table tbody tr:last-child td { border-bottom:none; }
.hist-table tbody tr:hover { background:#f8f9fa; }

/* Status badges */
.badge { display:inline-block; border-radius:20px; padding:2px 10px; font-size:.68rem; font-weight:700; }
.badge-pending     { background:#fef3c7; color:#92400e; }
.badge-in_progress { background:#dbeafe; color:#1e40af; }
.badge-resolved    { background:#dcfce7; color:#15803d; }
.badge-re_open     { background:#fce7f3; color:#9d174d; }
.badge-done        { background:#e0e7ff; color:#3730a3; }

/* Customer strip */
.cust-strip { background:#f8f9fa; border:1px solid #dee2e6; border-radius:4px;
              padding:10px 14px; margin-bottom:16px; display:flex; flex-wrap:wrap; gap:20px; }
.cs-item .cs-lbl { font-size:.62rem; font-weight:700; text-transform:uppercase; color:#94a3b8; }
.cs-item .cs-val { font-size:.87rem; font-weight:600; color:#212529; }
</style>

<?php if ($flash): ?>
<div class="flash flash-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($flash['message']); ?>
</div>
<?php endif; ?>

<div class="fv-wrap">

    <h2 style="font-size:1.05rem;font-weight:700;color:#212529;margin-bottom:12px;">
        Followups for Customer #<?php echo $customerId; ?> (<?php echo htmlspecialchars($customer['name']); ?>)
    </h2>

    <a href="/APN-Solar/followup/index.php" class="fv-back">
        <i class="fas fa-arrow-left"></i> Back to Complaints
    </a>

    <!-- Customer strip -->
    <div class="cust-strip">
        <div class="cs-item">
            <div class="cs-lbl">Customer</div>
            <div class="cs-val"><?php echo htmlspecialchars($customer['name']); ?></div>
        </div>
        <div class="cs-item">
            <div class="cs-lbl">Mobile</div>
            <div class="cs-val"><?php echo htmlspecialchars($customer['mobile'] ?? '—'); ?></div>
        </div>
        <div class="cs-item">
            <div class="cs-lbl">Group</div>
            <div class="cs-val"><?php echo htmlspecialchars($customer['group_name'] ?? '—'); ?></div>
        </div>
        <div class="cs-item">
            <div class="cs-lbl">Operator</div>
            <div class="cs-val"><?php echo htmlspecialchars($customer['operator_name'] ?? '—'); ?></div>
        </div>
    </div>

    <!-- Add New Followup -->
    <div class="fv-card">
        <div class="fv-card-title">Add New Followup</div>

        <?php if ($error): ?>
            <div class="err-alert"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="fg">
                <label>Remarks</label>
                <textarea name="remark" placeholder="Enter followup remarks..." required></textarea>
            </div>
            <div class="fg">
                <label>Status</label>
                <select name="status">
                    <option value="">Select status</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="re_open">Re Open</option>
                </select>
            </div>
            <button type="submit" class="btn-add-fu">
                <i class="fas fa-plus"></i> Add Followup
            </button>
        </form>
    </div>

    <!-- Followup History -->
    <div class="fv-card">
        <div class="fv-card-title">
            Followup History
            <span style="font-weight:400;color:#94a3b8;font-size:.8rem;margin-left:6px;">(<?php echo count($followups); ?> records)</span>
        </div>
        <?php if (count($followups) > 0): ?>
        <div style="overflow-x:auto;">
            <table class="hist-table">
                <thead>
                    <tr>
                        <th>Remarks</th>
                        <th style="width:120px;">Status</th>
                        <th style="width:140px;">Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($followups as $fu):
                    $s = $fu['status'] ?? 'pending';
                    $label = match($s) {
                        'in_progress' => 'In Progress',
                        'resolved'    => 'Resolved',
                        're_open'     => 'Re Open',
                        'done'        => 'Done',
                        default       => 'Pending',
                    };
                    $dt = $fu['created_at'] ?? '';
                    $dtFmt = $dt ? date('d-M-Y H:i', strtotime($dt)) : '—';
                ?>
                    <tr>
                        <td><?php echo nl2br(htmlspecialchars($fu['remark'] ?: ($fu['notes'] ?? ''))); ?></td>
                        <td><span class="badge badge-<?php echo $s; ?>"><?php echo $label; ?></span></td>
                        <td style="white-space:nowrap;font-size:.76rem;"><?php echo $dtFmt; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p style="color:#94a3b8;font-size:.85rem;padding:10px 0;">No followup history found for this customer.</p>
        <?php endif; ?>
    </div>

</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
