<?php
// customers/edit.php — Edit Customer (handles GET + POST)

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header("Location: /APN-Solar/customers/");
    exit;
}

// Fetch existing customer
$stmt = $pdo->prepare("SELECT c.*, p.total_amount, p.due_amount, p.payment_received FROM customers c LEFT JOIN payments p ON c.id = p.customer_id WHERE c.id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Customer not found.');
    header("Location: /APN-Solar/customers/");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['operator_name','group_name','name','email','mobile',
               'ifsc_code','electricity_id','kw','account_number','district_name','remarks','followup_remarks'];
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
    }
    $data['id'] = $id;

    if (empty($data['name'])) {
        $error = "Customer name is required.";
    } else {
        try {
            $sql = "UPDATE customers SET
                    operator_name=:operator_name, group_name=:group_name, name=:name,
                    email=:email, mobile=:mobile, ifsc_code=:ifsc_code,
                    electricity_id=:electricity_id, kw=:kw, account_number=:account_number,
                    district_name=:district_name, remarks=:remarks, followup_remarks=:followup_remarks
                    WHERE id=:id";
            $pdo->prepare($sql)->execute($data);

            // Update payment if total_amount provided
            if (isset($_POST['total_amount']) && $_POST['total_amount'] !== '') {
                $totalAmt = (float)$_POST['total_amount'];
                $existingPayment = $pdo->prepare("SELECT id FROM payments WHERE customer_id = ?");
                $existingPayment->execute([$id]);
                if ($existingPayment->fetch()) {
                    $pdo->prepare("UPDATE payments SET total_amount=?, due_amount=? WHERE customer_id=?")
                        ->execute([$totalAmt, $totalAmt, $id]);
                } else {
                    $pdo->prepare("INSERT INTO payments (customer_id, total_amount, due_amount, payment_received) VALUES (?,?,?,0)")
                        ->execute([$id, $totalAmt, $totalAmt]);
                }
            }

            setFlash('success', 'Customer updated successfully.');
            header("Location: /APN-Solar/customers/");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
    // Re-populate customer on error
    $customer = array_merge($customer, $data);
}

// Fetch groups for dropdown
$groups = $pdo->query("SELECT DISTINCT group_name FROM customers WHERE group_name != '' ORDER BY group_name")->fetchAll();

$pageTitle = 'Edit Customer';
include __DIR__ . '/../views/partials/header.php';
?>

<style>
.page-card { background:#fff; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,.12); padding:28px 32px; max-width:900px; }
.page-title { font-size:1.25rem; font-weight:700; color:#1e293b; margin-bottom:24px; display:flex; align-items:center; gap:10px; }
.page-title i { color:#f59e0b; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.form-grid .full { grid-column:1/-1; }
.form-group label { display:block; font-size:.78rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px; }
.form-group input, .form-group select, .form-group textarea {
    width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:8px;
    font-size:.92rem; font-family:inherit; color:#1e293b; background:#f8fafc;
    transition:border-color .2s, box-shadow .2s; outline:none;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color:#f59e0b; background:#fff; box-shadow:0 0 0 3px rgba(245,158,11,.12);
}
.form-group textarea { resize:vertical; min-height:80px; }
.form-actions { display:flex; gap:12px; margin-top:24px; padding-top:20px; border-top:1px solid #e2e8f0; }
.btn { display:inline-flex; align-items:center; gap:7px; padding:10px 22px; border-radius:8px;
       font-size:.9rem; font-weight:600; cursor:pointer; border:none; font-family:inherit;
       transition:all .15s; text-decoration:none; }
.btn-primary { background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; box-shadow:0 4px 12px rgba(245,158,11,.3); }
.btn-primary:hover { transform:translateY(-1px); }
.btn-secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.btn-secondary:hover { background:#e2e8f0; }
.alert-error { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:.875rem; }
.badge-id { background:#f1f5f9; color:#64748b; font-size:.75rem; padding:4px 10px; border-radius:20px; font-weight:600; }
.section-label { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; margin:20px 0 12px; padding-bottom:6px; border-bottom:1px solid #e2e8f0; grid-column:1/-1; }
</style>

<div class="page-card">
    <div class="page-title">
        <i class="fas fa-user-edit"></i>
        Edit Customer
        <span class="badge-id">ID #<?php echo $id; ?></span>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-grid">

            <div class="section-label">Basic Information</div>

            <div class="form-group">
                <label>Customer Name <span style="color:#dc2626">*</span></label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($customer['name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Mobile Number</label>
                <input type="text" name="mobile" maxlength="15" value="<?php echo htmlspecialchars($customer['mobile'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Operator Name</label>
                <input type="text" name="operator_name" value="<?php echo htmlspecialchars($customer['operator_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Group Name</label>
                <select name="group_name">
                    <option value="">-- Select Group --</option>
                    <?php foreach ($groups as $g): ?>
                        <option value="<?php echo htmlspecialchars($g['group_name']); ?>"
                            <?php echo ($customer['group_name'] ?? '') === $g['group_name'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($g['group_name']); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="<?php echo htmlspecialchars($customer['group_name'] ?? ''); ?>"
                        <?php echo !empty($customer['group_name']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($customer['group_name'] ?? ''); ?>
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label>District</label>
                <input type="text" name="district_name" value="<?php echo htmlspecialchars($customer['district_name'] ?? ''); ?>">
            </div>

            <div class="section-label">Solar / Financial Details</div>

            <div class="form-group">
                <label>KW (Solar Capacity)</label>
                <input type="number" name="kw" step="0.01" value="<?php echo htmlspecialchars($customer['kw'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Total Amount (₹)</label>
                <input type="number" name="total_amount" step="0.01" value="<?php echo htmlspecialchars($customer['total_amount'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Electricity Consumer ID</label>
                <input type="text" name="electricity_id" value="<?php echo htmlspecialchars($customer['electricity_id'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Bank Account Number</label>
                <input type="text" name="account_number" value="<?php echo htmlspecialchars($customer['account_number'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>IFSC Code</label>
                <input type="text" name="ifsc_code" value="<?php echo htmlspecialchars($customer['ifsc_code'] ?? ''); ?>">
            </div>

            <div class="form-group full">
                <label>Remarks</label>
                <textarea name="remarks"><?php echo htmlspecialchars($customer['remarks'] ?? ''); ?></textarea>
            </div>

            <div class="form-group full">
                <label>Followup Remarks</label>
                <textarea name="followup_remarks"><?php echo htmlspecialchars($customer['followup_remarks'] ?? ''); ?></textarea>
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Customer
            </button>
            <a href="/APN-Solar/customers/" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Cancel
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
