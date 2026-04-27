<?php
// leads/create.php — Create New Customer (Lead)

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['operator_name','group_name','district_name','name','mobile','email',
               'address','pincode','electricity_id','kw','jan_samarth','model_number',
               'total_amount','remarks'];
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
    }

    if (empty($data['name'])) {
        $error = 'Customer name is required.';
    } else {
        try {
            $pdo->prepare("
                INSERT INTO customers
                    (operator_name, group_name, district_name, name, mobile, email,
                     address, pincode, electricity_id, kw, jan_samarth, model_number, remarks)
                VALUES
                    (:operator_name, :group_name, :district_name, :name, :mobile, :email,
                     :address, :pincode, :electricity_id, :kw, :jan_samarth, :model_number, :remarks)
            ")->execute($data);

            $newId = $pdo->lastInsertId();

            // Payment record
            $totalAmt = !empty($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0;
            if ($totalAmt > 0) {
                $pdo->prepare("INSERT INTO payments (customer_id, total_amount, due_amount, payment_received) VALUES (?,?,?,0)")
                    ->execute([$newId, $totalAmt, $totalAmt]);
            }

            setFlash('success', 'Customer "' . htmlspecialchars($data['name']) . '" created successfully.');
            header('Location: /APN-Solar/leads/');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Groups dropdown
$groups = $pdo->query("SELECT DISTINCT group_name FROM customers WHERE group_name != '' ORDER BY group_name")->fetchAll();

$pageTitle = 'Create New Lead';
include __DIR__ . '/../views/partials/header.php';
?>

<style>
.lf-card { background:#fff; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,.1); padding:24px 28px; max-width:700px; }
.lf-title { font-size:1.1rem; font-weight:700; color:#1e293b; margin-bottom:20px; }
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
.btn-success   { background:#28a745; color:#fff; }
.btn-secondary { background:#6c757d; color:#fff; }
.btn:hover { opacity:.87; }
.alert-error { background:#f8d7da; border:1px solid #f5c6cb; color:#721c24; padding:10px 14px; border-radius:5px; margin-bottom:14px; font-size:.875rem; }
</style>

<div class="lf-card">
    <div class="lf-title">Create New Lead</div>

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
                        <?php echo ($_POST['group_name'] ?? '') === $g['group_name'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($g['group_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="lf-group">
            <label>District</label>
            <input type="text" name="district_name" value="<?php echo htmlspecialchars($_POST['district_name'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>Customer Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>Mobile Number</label>
            <input type="text" name="mobile" maxlength="15" value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>Address</label>
            <textarea name="address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
        </div>

        <div class="lf-group">
            <label>Pincode</label>
            <input type="text" name="pincode" maxlength="10" value="<?php echo htmlspecialchars($_POST['pincode'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>Electricity Account ID</label>
            <input type="text" name="electricity_id" value="<?php echo htmlspecialchars($_POST['electricity_id'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>KW</label>
            <input type="number" name="kw" step="0.01" value="<?php echo htmlspecialchars($_POST['kw'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>Jan Samarth /Cash/Ecofy</label>
            <input type="text" name="jan_samarth" value="<?php echo htmlspecialchars($_POST['jan_samarth'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>Modal Number</label>
            <select name="model_number">
                <option value="">Select Modal Number</option>
                <?php foreach ($modelOptions as $m): ?>
                    <option value="<?php echo htmlspecialchars($m); ?>"
                        <?php echo ($_POST['model_number'] ?? '') === $m ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($m); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="lf-group">
            <label>Total Amount</label>
            <input type="number" name="total_amount" step="0.01" value="<?php echo htmlspecialchars($_POST['total_amount'] ?? ''); ?>">
        </div>

        <div class="lf-group">
            <label>Remarks</label>
            <textarea name="remarks"><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
        </div>

        <div class="lf-actions">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Create Customer
            </button>
            <a href="/APN-Solar/leads/" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Cancel
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
