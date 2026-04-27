<?php
// customers/followup.php — Dedicated Follow Up page for a customer

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

// ── Ensure followups.status has all needed values ─────────────────────────────
try {
    $pdo->exec("ALTER TABLE followups MODIFY COLUMN status
        ENUM('pending','in_progress','resolved','re_open','done') DEFAULT 'pending'");
} catch (PDOException $e) {}

// ── Ensure extra columns exist on followups ──────────────────────────────────
try {
    $fCols = $pdo->query("SHOW COLUMNS FROM followups")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('operator_id', $fCols)) {
        $pdo->exec("ALTER TABLE followups ADD COLUMN operator_id INT DEFAULT NULL AFTER followup_by");
    }
    if (!in_array('operator_name_text', $fCols)) {
        $pdo->exec("ALTER TABLE followups ADD COLUMN operator_name_text VARCHAR(100) DEFAULT NULL AFTER operator_id");
    }
    if (!in_array('remark', $fCols)) {
        $pdo->exec("ALTER TABLE followups ADD COLUMN remark TEXT DEFAULT NULL AFTER notes");
    }
} catch (PDOException $e) {}

$customerId = (int)($_GET['id'] ?? 0);
if (!$customerId) {
    header("Location: /APN-Solar/customers/");
    exit;
}

// Fetch customer
$customer = $pdo->prepare("SELECT id, name, mobile, group_name, operator_name FROM customers WHERE id = ?");
$customer->execute([$customerId]);
$customer = $customer->fetch();

if (!$customer) {
    header("Location: /APN-Solar/customers/");
    exit;
}

// ── Handle form submission ────────────────────────────────────────────────────
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operatorNameText = trim($_POST['operator_name_text'] ?? '');
    $remark           = trim($_POST['remark'] ?? '');
    $entries          = $_POST['followup_note']   ?? [];
    $statuses         = $_POST['followup_status'] ?? [];

    try {
        $pdo->beginTransaction();

        // Insert each followup entry
        $hasEntry = false;
        foreach ($entries as $i => $note) {
            $note   = trim($note);
            $status = $statuses[$i] ?? 'pending';
            if ($note === '') continue;
            $hasEntry = true;
            $pdo->prepare("INSERT INTO followups (customer_id, followup_by, operator_name_text, notes, remark, status)
                           VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$customerId, $_SESSION['admin_id'] ?? null, $operatorNameText, $note, $remark, $status]);
        }

        // If remark/operator provided but no note, still store a record
        if (!$hasEntry && ($remark !== '' || $operatorNameText !== '')) {
            $pdo->prepare("INSERT INTO followups (customer_id, followup_by, operator_name_text, notes, remark, status)
                           VALUES (?, ?, ?, '', ?, 'pending')")
                ->execute([$customerId, $_SESSION['admin_id'] ?? null, $operatorNameText, $remark]);
        }

        // Update customer followup_remarks with latest remark
        if ($remark !== '') {
            $pdo->prepare("UPDATE customers SET followup_remarks = ? WHERE id = ?")
                ->execute([$remark, $customerId]);
        }

        $pdo->commit();
        setFlash('success', 'Follow up saved successfully.');
        header("Location: /APN-Solar/customers/followup.php?id=$customerId");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Database error: " . $e->getMessage();
    }
}

// ── Load operators from customers table (lead management) ────────────────────
try {
    $operators = $pdo->query("
        SELECT DISTINCT operator_name
        FROM customers
        WHERE operator_name IS NOT NULL AND operator_name != ''
        ORDER BY operator_name ASC
    ")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $operators = [];
}

// ── Load existing followups for this customer ─────────────────────────────────
try {
    $followups = $pdo->prepare("
        SELECT f.*
        FROM followups f
        WHERE f.customer_id = ?
        ORDER BY f.created_at DESC
    ");
    $followups->execute([$customerId]);
    $followups = $followups->fetchAll();
} catch (PDOException $e) {
    $followups = [];
}

$pageTitle = 'Follow Up — ' . htmlspecialchars($customer['name']);
include __DIR__ . '/../views/partials/header.php';
?>

<style>
.fu-wrap { max-width:860px; }
.fu-back  { display:inline-flex; align-items:center; gap:7px; padding:8px 18px; border-radius:6px;
            background:#4a5568; color:#fff; font-size:.85rem; font-weight:600; cursor:pointer;
            border:none; font-family:inherit; text-decoration:none; margin-bottom:18px; transition:opacity .15s; }
.fu-back:hover { opacity:.85; }

/* Card */
.fu-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:22px 24px; margin-bottom:18px; }
.fu-card h3 { font-size:.95rem; font-weight:700; color:#1e293b; margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid #f1f5f9; }

/* Form controls */
.fg { margin-bottom:14px; }
.fg label { display:block; font-size:.76rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:5px; }
.fg input, .fg select, .fg textarea {
    width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:7px;
    font-size:.88rem; font-family:inherit; color:#1e293b; background:#f8fafc; outline:none;
    transition:border-color .2s;
}
.fg input:focus, .fg select:focus, .fg textarea:focus { border-color:#3b82f6; background:#fff; }
.fg textarea { resize:vertical; min-height:80px; }

/* Followup entry rows */
.fu-entries { display:flex; flex-direction:column; gap:8px; margin-bottom:14px; }
.fu-entry   { display:flex; gap:8px; align-items:center; }
.fu-entry input  { flex:1; padding:8px 12px; border:1.5px solid #e2e8f0; border-radius:7px; font-size:.87rem; font-family:inherit; outline:none; }
.fu-entry input:focus { border-color:#3b82f6; }
.fu-entry select { width:160px; padding:8px 12px; border:1.5px solid #e2e8f0; border-radius:7px; font-size:.87rem; font-family:inherit; outline:none; }
.fu-entry select:focus { border-color:#3b82f6; }
.fu-entry .rm-btn { background:#ef4444; color:#fff; border:none; border-radius:7px; width:32px; height:36px;
                    display:flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0; }
.fu-entry .rm-btn:hover { background:#dc2626; }

/* Buttons */
.btn { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; border-radius:7px;
       font-size:.85rem; font-weight:600; cursor:pointer; border:none; font-family:inherit;
       transition:all .15s; text-decoration:none; }
.btn-add    { background:#4a5568; color:#fff; }
.btn-add:hover { background:#374151; }
.btn-submit { background:#22c55e; color:#fff; }
.btn-submit:hover { background:#16a34a; }
.btn-sm { padding:4px 10px; font-size:.76rem; }

/* Flash */
.flash-alert   { display:flex;align-items:center;gap:9px;padding:11px 15px;border-radius:8px;font-size:.875rem;font-weight:500;margin-bottom:14px; }
.flash-success { background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a; }
.flash-error   { background:#fef2f2;border:1px solid #fecaca;color:#dc2626; }

/* History table */
.hist-table { width:100%; border-collapse:collapse; font-size:.8rem; }
.hist-table thead th { background:#f8fafc; padding:8px 10px; font-size:.7rem; font-weight:700;
                       text-transform:uppercase; letter-spacing:.04em; color:#64748b;
                       border-bottom:2px solid #e2e8f0; text-align:left; }
.hist-table tbody td { padding:8px 10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.hist-table tbody tr:last-child td { border-bottom:none; }
.hist-table tbody tr:hover { background:#f8fbff; }
.badge { display:inline-block; border-radius:20px; padding:2px 9px; font-size:.68rem; font-weight:700; }
.badge-pending     { background:#fef3c7; color:#92400e; }
.badge-in_progress { background:#dbeafe; color:#1e40af; }
.badge-resolved    { background:#dcfce7; color:#15803d; }
.badge-re_open     { background:#fce7f3; color:#9d174d; }
.badge-done        { background:#e0e7ff; color:#3730a3; }

/* Customer info pill */
.cust-pill { display:flex; flex-wrap:wrap; gap:14px; background:#f8fafc; border:1px solid #e2e8f0;
             border-radius:8px; padding:12px 16px; margin-bottom:18px; }
.cp-item .cp-lbl { font-size:.64rem; font-weight:700; text-transform:uppercase; color:#94a3b8; }
.cp-item .cp-val { font-size:.88rem; font-weight:600; color:#1e293b; }
</style>

<?php
$flash = null;
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }
?>

<?php if ($flash): ?>
<div class="flash-alert flash-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($flash['message']); ?>
</div>
<?php endif; ?>

<div class="fu-wrap">

    <?php
    $backUrl = ($_GET['back'] ?? '') === 'pending'
        ? '/APN-Solar/customers/pending_list.php'
        : '/APN-Solar/customers/';
    ?>
    <a href="<?php echo $backUrl; ?>" class="fu-back">
        <i class="fas fa-arrow-left"></i> Back
    </a>

    <!-- Customer info strip -->
    <div class="cust-pill">
        <div class="cp-item">
            <div class="cp-lbl">Customer</div>
            <div class="cp-val"><?php echo htmlspecialchars($customer['name']); ?></div>
        </div>
        <div class="cp-item">
            <div class="cp-lbl">Mobile</div>
            <div class="cp-val"><?php echo htmlspecialchars($customer['mobile'] ?? '—'); ?></div>
        </div>
        <div class="cp-item">
            <div class="cp-lbl">Group</div>
            <div class="cp-val"><?php echo htmlspecialchars($customer['group_name'] ?? '—'); ?></div>
        </div>
        <div class="cp-item">
            <div class="cp-lbl">Operator</div>
            <div class="cp-val"><?php echo htmlspecialchars($customer['operator_name'] ?? '—'); ?></div>
        </div>
    </div>

    <!-- Add New Followup card -->
    <div class="fu-card">
        <h3><i class="fas fa-plus-circle" style="color:#3b82f6;margin-right:6px;"></i>Add New Followup</h3>

        <?php if ($error): ?>
            <div class="flash-alert flash-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="fuForm">

            <div class="fg">
                <label>Select Operator To Assign</label>
                <select name="operator_name_text" id="operatorSelect">
                    <option value="">-- Select Operator --</option>
                    <?php foreach ($operators as $op): ?>
                        <option value="<?php echo htmlspecialchars($op); ?>">
                            <?php echo htmlspecialchars($op); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="fg">
                <label>Remark</label>
                <textarea name="remark" placeholder="General remark for this follow-up..."></textarea>
            </div>

            <!-- Followups section -->
            <div class="fg">
                <label>Followups</label>
                <div class="fu-entries" id="fuEntries">
                    <div class="fu-entry">
                        <input type="text" name="followup_note[]" placeholder="Follow-up Remark">
                        <select name="followup_status[]">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="re_open">Re Open</option>
                        </select>
                        <button type="button" class="rm-btn" onclick="removeFuEntry(this)" title="Remove" style="display:none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="button" class="btn btn-add" onclick="addFuEntry()">
                    <i class="fas fa-plus"></i> Add Followup
                </button>
                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit
                </button>
            </div>

        </form>
    </div>

    <!-- Followup History -->
    <?php if (count($followups) > 0): ?>
    <div class="fu-card">
        <h3><i class="fas fa-history" style="color:#64748b;margin-right:6px;"></i>Follow Up History
            <span style="font-weight:400;color:#94a3b8;font-size:.82rem;margin-left:6px;">(<?php echo count($followups); ?> records)</span>
        </h3>
        <div style="overflow-x:auto;">
            <table class="hist-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Assigned To</th>
                        <th>Followup Note</th>
                        <th>Remark</th>
                        <th>Status</th>
                        <th>Added On</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ($followups as $fu): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($fu['operator_name_text'] ?? '—'); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($fu['notes'] ?? '')); ?></td>
                        <td style="max-width:160px;"><?php echo nl2br(htmlspecialchars($fu['remark'] ?? '')); ?></td>
                        <td>
                            <?php
                            $s = $fu['status'] ?? 'pending';
                            $label = match($s) {
                                'in_progress' => 'In Progress',
                                'resolved'    => 'Resolved',
                                're_open'     => 'Re Open',
                                'done'        => 'Done',
                                default       => 'Pending',
                            };
                            echo "<span class=\"badge badge-$s\">$label</span>";
                            ?>
                        </td>
                        <td style="white-space:nowrap;font-size:.76rem;">
                            <?php echo htmlspecialchars(substr($fu['created_at'] ?? '', 0, 16)); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function addFuEntry() {
    const entries = document.getElementById('fuEntries');
    const first   = entries.querySelector('.fu-entry');
    const clone   = first.cloneNode(true);
    // Clear cloned values
    clone.querySelector('input').value = '';
    clone.querySelector('select').value = 'pending';
    clone.querySelector('.rm-btn').style.display = 'flex';
    entries.appendChild(clone);
    // Show remove button on first row if more than one entry
    if (entries.children.length > 1) {
        first.querySelector('.rm-btn').style.display = 'flex';
    }
}

function removeFuEntry(btn) {
    const entries = document.getElementById('fuEntries');
    const row     = btn.closest('.fu-entry');
    if (entries.children.length > 1) {
        row.remove();
        // Hide remove button if only one left
        if (entries.children.length === 1) {
            entries.querySelector('.rm-btn').style.display = 'none';
        }
    }
}
</script>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
