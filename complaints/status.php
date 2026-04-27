<?php
// complaints/status.php — Followup Status page for a complaint (matches screenshot exactly)

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: /APN-Solar/complaints/index.php"); exit; }

$stmt = $pdo->prepare("SELECT c.*, cu.name AS customer_name FROM complaints c LEFT JOIN customers cu ON c.customer_id = cu.id WHERE c.id=?");
$stmt->execute([$id]);
$complaint = $stmt->fetch();
if (!$complaint) { setFlash('error','Complaint not found.'); header("Location: /APN-Solar/complaints/index.php"); exit; }

// Handle Add Followup POST
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $remarks = trim($_POST['remarks'] ?? '');
    $status  = trim($_POST['status'] ?? '');
    $allowed = ['Pending','In Progress','Resolved','Re Open','Closed'];

    if (!in_array($status, $allowed)) {
        $error = "Please select a valid status.";
    } elseif ($remarks === '') {
        $error = "Remarks cannot be empty.";
    } else {
        try {
            $pdo->prepare("INSERT INTO complaint_followups (complaint_id, remarks, status, created_by) VALUES (?,?,?,?)")
                ->execute([$id, $remarks, $status, $_SESSION['admin_id'] ?? null]);

            // Update complaint status
            $cStatus = match($status) {
                'Closed'      => 'closed',
                'Resolved'    => 'resolved',
                'In Progress' => 'in_progress',
                'Re Open'     => 'open',
                default       => 'pending',
            };
            $pdo->prepare("UPDATE complaints SET status=? WHERE id=?")->execute([$cStatus, $id]);

            setFlash('success', 'Followup added.');
            header("Location: /APN-Solar/complaints/status.php?id=$id"); exit;
        } catch (PDOException $e) { $error = $e->getMessage(); }
    }
}

// Load followup history
try {
    $history = $pdo->prepare("SELECT * FROM complaint_followups WHERE complaint_id=? ORDER BY created_at DESC");
    $history->execute([$id]);
    $history = $history->fetchAll();
} catch (PDOException $e) { $history=[]; }

$pageTitle = 'Complaint Status — '.$complaint['complaint_no'];
include __DIR__ . '/../views/partials/header.php';
?>

<style>
.fu-back { display:inline-flex;align-items:center;gap:7px;padding:8px 18px;border-radius:6px;background:#4a5568;color:#fff;font-size:.85rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;text-decoration:none;margin-bottom:18px;transition:opacity .15s; }
.fu-back:hover { opacity:.85; }
.fu-page-title { font-size:1.05rem;font-weight:700;color:#1e293b;margin-bottom:18px; }

.card { background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:20px 22px;margin-bottom:18px; }
.card-head { font-size:.88rem;font-weight:700;color:#374151;margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid #f1f5f9; }
.fg label { display:block;font-size:.75rem;font-weight:700;color:#374151;margin-bottom:5px; }
.fg textarea { width:100%;padding:9px 11px;border:1px solid #e2e8f0;border-radius:5px;font-size:.87rem;font-family:inherit;resize:vertical;min-height:90px;outline:none;box-sizing:border-box; }
.fg textarea:focus { border-color:#3b82f6; }
.fg select { width:100%;padding:9px 11px;border:1px solid #e2e8f0;border-radius:5px;font-size:.87rem;font-family:inherit;outline:none;background:#fff; }
.fg select:focus { border-color:#3b82f6; }
.fg { margin-bottom:12px; }

.btn-add { display:inline-flex;align-items:center;gap:6px;padding:9px 20px;border-radius:6px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;font-size:.87rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:opacity .15s; }
.btn-add:hover { opacity:.87; }

/* History table */
.hist-table { width:100%;border-collapse:collapse;font-size:.8rem; }
.hist-table thead th { padding:9px 12px;background:#f8fafc;font-weight:700;font-size:.72rem;text-align:left;border-bottom:1px solid #e2e8f0;color:#374151; }
.hist-table tbody td { padding:9px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle; }
.hist-table tbody tr:last-child td { border-bottom:none; }
.hist-table tbody tr:hover { background:#f8fbff; }

.badge { display:inline-block;border-radius:5px;padding:2px 9px;font-size:.7rem;font-weight:700;white-space:nowrap; }
.badge-Closed      { background:#1e293b;color:#fff; }
.badge-Resolved    { background:#f59e0b;color:#fff; }
.badge-Pending     { background:#fbbf24;color:#fff; }
.badge-In-Progress { background:#3b82f6;color:#fff; }
.badge-Re-Open     { background:#8b5cf6;color:#fff; }

.alert-error { background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 14px;border-radius:7px;margin-bottom:12px;font-size:.87rem; }
.flash-alert { display:flex;align-items:center;gap:8px;padding:10px 14px;border-radius:7px;font-size:.87rem;font-weight:500;margin-bottom:12px; }
.flash-success { background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a; }
</style>

<?php
$flash = null;
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }
if ($flash): ?>
<div class="flash-alert flash-<?php echo $flash['type']==='success'?'success':'error'; ?>">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($flash['message']); ?>
</div>
<?php endif; ?>

<a href="/APN-Solar/complaints/index.php" class="fu-back">
    <i class="fas fa-arrow-left"></i> Back to Complaints
</a>

<div class="fu-page-title">
    Followups for Complaint #<?php echo ltrim(str_replace('CPD-','',$complaint['complaint_no']??''), '0') ?: ($complaint['id']); ?>
    (<?php echo htmlspecialchars($complaint['customer_name'] ?? ''); ?>)
</div>

<!-- Add New Followup -->
<div class="card">
    <div class="card-head">Add New Followup</div>
    <?php if($error):?><div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error);?></div><?php endif;?>
    <form method="POST" action="">
        <div class="fg">
            <label>Remarks</label>
            <textarea name="remarks" placeholder="Enter followup remark..."></textarea>
        </div>
        <div class="fg">
            <label>Status</label>
            <select name="status">
                <option value="">Select status</option>
                <option value="Pending">Pending</option>
                <option value="In Progress">In Progress</option>
                <option value="Resolved">Resolved</option>
                <option value="Re Open">Re Open</option>
                <option value="Closed">Closed</option>
            </select>
        </div>
        <button type="submit" class="btn-add">
            <i class="fas fa-plus"></i> Add Followup
        </button>
    </form>
</div>

<!-- Followup History -->
<div class="card">
    <div class="card-head">Followup History</div>
    <?php if ($history): ?>
    <table class="hist-table">
        <thead>
            <tr>
                <th>Remarks</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($history as $h):
            $s = $h['status'] ?? 'Pending';
            $cls = str_replace(' ','-',$s);
        ?>
            <tr>
                <td><?php echo nl2br(htmlspecialchars($h['remarks'] ?? '')); ?></td>
                <td><span class="badge badge-<?php echo htmlspecialchars($cls); ?>"><?php echo htmlspecialchars($s); ?></span></td>
                <td style="white-space:nowrap;color:#64748b;">
                    <?php echo date('d-M-Y H:i', strtotime($h['created_at'])); ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="color:#94a3b8;text-align:center;padding:20px;">No followup history yet.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
