<?php
// leads/edit.php — Edit Customer (Lead)

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$modelOptions = [
    'CPD On Grid Model',
    'CPD Off Grid Model',
    'CPD Hybrid Model',
    'CPD Resko Village Model',
    'CPD Village Model',
    'CPD City Model',
    'CPD On Grid With TATA',
    'CPD Commercial On Grid',
    'CPD Solar Ata Chakki',
    'CPD Solar Pump',
    'CPD On Grid Normal',
    'CPD Resko Hybrid',
];

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /APN-Solar/leads/');
    exit;
}

// Fetch customer
$stmt = $pdo->prepare("
    SELECT c.*, p.total_amount
    FROM customers c
    LEFT JOIN payments p ON c.id = p.customer_id
    WHERE c.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Customer not found.');
    header('Location: /APN-Solar/leads/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['operator_name','group_name','district_name','name','mobile','email',
               'address','pincode','electricity_id','kw','jan_samarth','model_number','remarks'];
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
    }
    $data['id'] = $id;

    if (empty($data['name'])) {
        $error = 'Customer name is required.';
    } else {
        try {
            $pdo->prepare("
                UPDATE customers SET
                    operator_name=:operator_name, group_name=:group_name, district_name=:district_name,
                    name=:name, mobile=:mobile, email=:email, address=:address, pincode=:pincode,
                    electricity_id=:electricity_id, kw=:kw, jan_samarth=:jan_samarth,
                    model_number=:model_number, remarks=:remarks
                WHERE id=:id
            ")->execute($data);

            // Update payment
            if (isset($_POST['total_amount']) && $_POST['total_amount'] !== '') {
                $totalAmt = (float)$_POST['total_amount'];
                $ep = $pdo->prepare("SELECT id FROM payments WHERE customer_id = ?");
                $ep->execute([$id]);
                if ($ep->fetch()) {
                    $pdo->prepare("UPDATE payments SET total_amount=?, due_amount=? WHERE customer_id=?")
                        ->execute([$totalAmt, $totalAmt, $id]);
                } else {
                    $pdo->prepare("INSERT INTO payments (customer_id, total_amount, due_amount, payment_received) VALUES (?,?,?,0)")
                        ->execute([$id, $totalAmt, $totalAmt]);
                }
            }

            setFlash('success', 'Customer "' . htmlspecialchars($data['name']) . '" updated successfully.');
            header('Location: /APN-Solar/leads/');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
    $customer = array_merge($customer, $data);
}

// Groups dropdown
$groups = $pdo->query("SELECT DISTINCT group_name FROM customers WHERE group_name != '' ORDER BY group_name")->fetchAll();

$pageTitle = 'Edit Customer';
include __DIR__ . '/../views/partials/header.php';
?>

<style>
.lf-card { background:#fff; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,.1); padding:24px 28px; max-width:700px; }
.lf-title { font-size:1.1rem; font-weight:700; color:#1e293b; margin-bottom:6px; }
.badge-id { background:#f1f5f9; color:#64748b; font-size:.73rem; padding:3px 9px; border-radius:20px; font-weight:600; }
.lf-group { margin-bottom:14px; }
.lf-group label { display:block; font-size:.82rem; font-weight:600; color:#212529; margin-bottom:5px; }
.lf-group input, .lf-group select, .lf-group textarea {
    width:100%; padding:8px 12px; border:1px solid #ced4da; border-radius:4px;
    font-size:.88rem; font-family:inherit; color:#212529; background:#f8f9fa; outline:none;
    transition:border-color .15s;
}
.lf-group input:focus, .lf-group select:focus, .lf-group textarea:focus {
    border-color:#80bdff; background:#fff; box-shadow:0 0 0 2px rgba(0,123,255,.15);
}
.lf-group textarea { resize:vertical; min-height:80px; }
.lf-actions { display:flex; gap:10px; margin-top:18px; padding-top:16px; border-top:1px solid #e2e8f0; }
.btn { display:inline-flex; align-items:center; gap:6px; padding:9px 20px; border-radius:5px;
       font-size:.88rem; font-weight:600; cursor:pointer; border:none; font-family:inherit;
       transition:all .15s; text-decoration:none; }
.btn-primary   { background:#007bff; color:#fff; }
.btn-secondary { background:#6c757d; color:#fff; }
.btn:hover { opacity:.87; }
.alert-error { background:#f8d7da; border:1px solid #f5c6cb; color:#721c24; padding:10px 14px; border-radius:5px; margin-bottom:14px; font-size:.875rem; }
</style>

<div class="lf-card">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <div class="lf-title">Edit Customer</div>
        <span class="badge-id">ID #<?php echo $id; ?></span>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">

        <div class="lf-group">
            <label>Group</label>
            <select name="group_name">
                <option value="">-- Select Group --</option>
                <?php foreach ($groups as $g): ?>
                    <option value="<?php echo htmlspecialchars($g['group_name']); ?>"
                        <?php echo ($customer['group_name'] ?? '') === $g['group_name'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($g['group_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="lf-group">
            <label>District</label>
            <input type="text" name="district_name" value="<?php echo htmlspecialchars($customer['district_name'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>Customer Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($customer['name'] ?? ''); ?>" required>
        </div>

        <div class="lf-group">
            <label>Mobile Number</label>
            <input type="text" name="mobile" maxlength="15" value="<?php echo htmlspecialchars($customer['mobile'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>Address</label>
            <textarea name="address"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
        </div>

        <div class="lf-group">
            <label>Pincode</label>
            <input type="text" name="pincode" maxlength="10" value="<?php echo htmlspecialchars($customer['pincode'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>Electricity Account ID</label>
            <input type="text" name="electricity_id" value="<?php echo htmlspecialchars($customer['electricity_id'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>KW</label>
            <input type="number" name="kw" step="0.01" value="<?php echo htmlspecialchars($customer['kw'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>Jan Samarth /Cash/Ecofy</label>
            <input type="text" name="jan_samarth" value="<?php echo htmlspecialchars($customer['jan_samarth'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>Modal Number</label>
            <select name="model_number">
                <option value="">Select Modal Number</option>
                <?php foreach ($modelOptions as $m): ?>
                    <option value="<?php echo htmlspecialchars($m); ?>"
                        <?php echo ($customer['model_number'] ?? '') === $m ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($m); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="lf-group">
            <label>Total Amount</label>
            <input type="number" name="total_amount" step="0.01" value="<?php echo htmlspecialchars($customer['total_amount'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>Remarks</label>
            <textarea name="remarks"><?php echo htmlspecialchars($customer['remarks'] ?? ''); ?></textarea>
        </div>

        <div class="lf-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Customer
            </button>
            <a href="/APN-Solar/leads/" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Cancel
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
