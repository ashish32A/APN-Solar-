<?php
// masters/dispatch/edit.php — Edit a dispatch record

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header("Location: /APN-Solar/masters/dispatch/index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM dispatches WHERE id = ?");
$stmt->execute([$id]);
$dispatch = $stmt->fetch();

if (!$dispatch) {
    setFlash('error', 'Dispatch record not found.');
    header("Location: /APN-Solar/masters/dispatch/index.php");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'customer_id'  => (int)($_POST['customer_id'] ?? 0),
        'dispatch_no'  => trim($_POST['dispatch_no'] ?? ''),
        'dispatch_date'=> trim($_POST['dispatch_date'] ?? '') ?: null,
        'type'         => in_array($_POST['type'] ?? '', ['first','second']) ? $_POST['type'] : 'first',
        'driver'       => trim($_POST['driver'] ?? ''),
        'van'          => trim($_POST['van'] ?? ''),
        'mobile'       => trim($_POST['mobile'] ?? ''),
        'status'       => in_array($_POST['status'] ?? '', ['Pending','Dispatched','Delivered']) ? $_POST['status'] : 'Pending',
        'notes'        => trim($_POST['notes'] ?? ''),
        'id'           => $id,
    ];

    if (!$data['customer_id']) {
        $error = "Please select a customer.";
    } else {
        try {
            $pdo->prepare("UPDATE dispatches SET
                customer_id=:customer_id, dispatch_no=:dispatch_no, dispatch_date=:dispatch_date,
                type=:type, driver=:driver, van=:van, mobile=:mobile, status=:status, notes=:notes
                WHERE id=:id")->execute($data);
            setFlash('success', 'Dispatch updated successfully.');
            header("Location: /APN-Solar/masters/dispatch/index.php");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
    $dispatch = array_merge($dispatch, $data);
}

// Format dispatch_date for datetime-local input
$dispDateVal = '';
if (!empty($dispatch['dispatch_date'])) {
    $dispDateVal = date('Y-m-d\TH:i', strtotime($dispatch['dispatch_date']));
}

try {
    $customers = $pdo->query("SELECT id, name, mobile FROM customers ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) { $customers = []; }

$pageTitle = 'Edit Dispatch';
include __DIR__ . '/../../views/partials/header.php';
?>

<style>
.page-card { background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.12);padding:28px 32px;max-width:760px; }
.page-title { font-size:1.15rem;font-weight:700;color:#1e293b;margin-bottom:20px;display:flex;align-items:center;gap:10px; }
.page-title i { color:#f59e0b; }
.badge-id { background:#f1f5f9;color:#64748b;font-size:.75rem;padding:4px 10px;border-radius:20px;font-weight:600; }
.form-grid { display:grid;grid-template-columns:1fr 1fr;gap:16px; }
.form-grid .full { grid-column:1/-1; }
.form-group label { display:block;font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px; }
.form-group input,.form-group select,.form-group textarea {
    width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:7px;
    font-size:.88rem;font-family:inherit;color:#1e293b;background:#f8fafc;outline:none;transition:border-color .2s;
}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus { border-color:#f59e0b;background:#fff; }
.form-group textarea { resize:vertical;min-height:80px; }
.section-label { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;padding-bottom:5px;border-bottom:1px solid #e2e8f0;grid-column:1/-1;margin-top:8px; }
.form-actions { display:flex;gap:12px;margin-top:20px;padding-top:18px;border-top:1px solid #e2e8f0; }
.btn { display:inline-flex;align-items:center;gap:7px;padding:10px 22px;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none; }
.btn-warning { background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff; }
.btn-secondary { background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0; }
.btn:hover { opacity:.87;transform:translateY(-1px); }
.alert-error { background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:.875rem; }
</style>

<div class="page-card">
    <div class="page-title">
        <i class="fas fa-pencil-alt"></i> Edit Dispatch
        <span class="badge-id">ID #<?php echo $id; ?></span>
    </div>

    <?php if ($error): ?>
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-grid">

            <div class="section-label">Dispatch Info</div>

            <div class="form-group full">
                <label>Customer <span style="color:#dc2626">*</span></label>
                <select name="customer_id" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>"
                            <?php echo (int)($dispatch['customer_id'] ?? 0) === (int)$c['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['mobile'] ?? ''); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Dispatch No.</label>
                <input type="text" name="dispatch_no" value="<?php echo htmlspecialchars($dispatch['dispatch_no'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Dispatch Date</label>
                <input type="datetime-local" name="dispatch_date" value="<?php echo htmlspecialchars($dispDateVal); ?>">
            </div>

            <div class="form-group">
                <label>Dispatch Type</label>
                <select name="type">
                    <option value="first"  <?php echo ($dispatch['type'] ?? '') === 'first'  ? 'selected' : ''; ?>>First</option>
                    <option value="second" <?php echo ($dispatch['type'] ?? '') === 'second' ? 'selected' : ''; ?>>Second</option>
                </select>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="Pending"    <?php echo ($dispatch['status'] ?? 'Pending') === 'Pending'    ? 'selected' : ''; ?>>Pending</option>
                    <option value="Dispatched" <?php echo ($dispatch['status'] ?? '') === 'Dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                    <option value="Delivered"  <?php echo ($dispatch['status'] ?? '') === 'Delivered'  ? 'selected' : ''; ?>>Delivered</option>
                </select>
            </div>

            <div class="section-label">Vehicle & Driver</div>

            <div class="form-group">
                <label>Driver Name</label>
                <input type="text" name="driver" value="<?php echo htmlspecialchars($dispatch['driver'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Van / Vehicle</label>
                <input type="text" name="van" value="<?php echo htmlspecialchars($dispatch['van'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Driver Mobile</label>
                <input type="text" name="mobile" value="<?php echo htmlspecialchars($dispatch['mobile'] ?? ''); ?>">
            </div>

            <div class="form-group full">
                <label>Remarks</label>
                <textarea name="notes"><?php echo htmlspecialchars($dispatch['notes'] ?? ''); ?></textarea>
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-save"></i> Update Dispatch
            </button>
            <a href="/APN-Solar/masters/dispatch/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Cancel
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../views/partials/footer.php'; ?>
