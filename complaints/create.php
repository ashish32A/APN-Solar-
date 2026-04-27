<?php
// complaints/create.php — Create New Complaint

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $opName     = trim($_POST['operator_name'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $district   = trim($_POST['district'] ?? '');
    $elecAccNo  = trim($_POST['electricity_account_no'] ?? '');
    $kw         = trim($_POST['kw'] ?? '') ?: null;
    $division   = trim($_POST['division'] ?? '');
    $issue      = trim($_POST['issue'] ?? '');
    $remarks    = trim($_POST['remarks'] ?? '');
    $status     = in_array($_POST['status'] ?? '', ['open','pending','in_progress','resolved','closed']) ? $_POST['status'] : 'open';

    if (!$customerId) {
        $error = "Please select a customer.";
    } else {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO complaints
                (customer_id, operator_name, address, district, electricity_account_no, kw,
                 division, issue, remarks, status, complaint_type)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$customerId, $opName, $address, $district, $elecAccNo, $kw,
                           $division, $issue, $remarks, $status, $issue]);
            $newId = (int)$pdo->lastInsertId();
            // Set complaint_no
            $pdo->prepare("UPDATE complaints SET complaint_no = CONCAT('CPD-', LPAD(id, 4, '0')) WHERE id = ?")->execute([$newId]);
            $pdo->commit();
            setFlash('success', 'Complaint created successfully.');
            header("Location: /APN-Solar/complaints/index.php");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}

try { $customers = $pdo->query("SELECT id, name, mobile FROM customers ORDER BY name ASC")->fetchAll(); }
catch (PDOException $e) { $customers = []; }

$pageTitle = 'Create Complaint';
include __DIR__ . '/../views/partials/header.php';
?>
<style>
.page-card { background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.12);padding:28px 32px;max-width:820px; }
.page-title { font-size:1.1rem;font-weight:700;color:#1e293b;margin-bottom:18px;display:flex;align-items:center;gap:9px; }
.page-title i { color:#3b82f6; }
.fg-grid { display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px; }
.fg-grid .full { grid-column:1/-1; }
.fg-grid .span2 { grid-column:span 2; }
.sec-lbl { font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;padding-bottom:5px;border-bottom:1px solid #e2e8f0;grid-column:1/-1;margin-top:6px; }
.fg label { display:block;font-size:.71rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px; }
.fg input,.fg select,.fg textarea { width:100%;padding:9px 11px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.87rem;font-family:inherit;color:#1e293b;background:#f8fafc;outline:none;transition:border-color .2s; }
.fg input:focus,.fg select:focus,.fg textarea:focus { border-color:#3b82f6;background:#fff; }
.fg textarea { resize:vertical;min-height:72px; }
.form-actions { display:flex;gap:11px;margin-top:18px;padding-top:16px;border-top:1px solid #e2e8f0; }
.btn { display:inline-flex;align-items:center;gap:6px;padding:10px 22px;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none; }
.btn-primary { background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff; }
.btn-secondary { background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0; }
.btn:hover { opacity:.87;transform:translateY(-1px); }
.alert-error { background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.875rem; }
</style>

<div class="page-card">
    <div class="page-title"><i class="fas fa-plus-circle"></i> Create New Complaint</div>

    <?php if ($error): ?>
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="fg-grid">
            <div class="sec-lbl">Complaint Details</div>

            <div class="fg full">
                <label>Customer <span style="color:#dc2626">*</span></label>
                <select name="customer_id" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)($_POST['customer_id']??0)===(int)$c['id'])?'selected':''; ?>>
                            <?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['mobile']??''); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="fg"><label>Operator Name</label>
                <input type="text" name="operator_name" value="<?php echo htmlspecialchars($_POST['operator_name']??''); ?>" placeholder="Operator name">
            </div>
            <div class="fg"><label>District</label>
                <input type="text" name="district" value="<?php echo htmlspecialchars($_POST['district']??''); ?>" placeholder="District">
            </div>
            <div class="fg"><label>Division</label>
                <input type="text" name="division" value="<?php echo htmlspecialchars($_POST['division']??''); ?>" placeholder="Division">
            </div>
            <div class="fg"><label>Electricity Account No</label>
                <input type="text" name="electricity_account_no" value="<?php echo htmlspecialchars($_POST['electricity_account_no']??''); ?>">
            </div>
            <div class="fg"><label>KW</label>
                <input type="number" step="0.01" name="kw" value="<?php echo htmlspecialchars($_POST['kw']??''); ?>" placeholder="e.g. 2.00">
            </div>
            <div class="fg"><label>Issue / Type</label>
                <select name="issue">
                    <option value="">-- Select Issue --</option>
                    <?php foreach (['Bill issue','Other Issue','Cleaning Service','Configure Pending','Technical Issue','Net Metering'] as $iss): ?>
                        <option value="<?php echo $iss; ?>" <?php echo ($_POST['issue']??'')===$iss?'selected':''; ?>><?php echo $iss; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fg"><label>Status</label>
                <select name="status">
                    <option value="open"        <?php echo ($_POST['status']??'open')==='open'?'selected':''; ?>>Open</option>
                    <option value="pending"     <?php echo ($_POST['status']??'')==='pending'?'selected':''; ?>>Pending</option>
                    <option value="in_progress" <?php echo ($_POST['status']??'')==='in_progress'?'selected':''; ?>>In Progress</option>
                    <option value="resolved"    <?php echo ($_POST['status']??'')==='resolved'?'selected':''; ?>>Resolved</option>
                    <option value="closed"      <?php echo ($_POST['status']??'')==='closed'?'selected':''; ?>>Closed</option>
                </select>
            </div>
            <div class="fg full"><label>Address</label>
                <textarea name="address" placeholder="Full address..."><?php echo htmlspecialchars($_POST['address']??''); ?></textarea>
            </div>
            <div class="fg full"><label>Remarks</label>
                <textarea name="remarks" placeholder="Remarks..."><?php echo htmlspecialchars($_POST['remarks']??''); ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Complaint</button>
            <a href="/APN-Solar/complaints/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
