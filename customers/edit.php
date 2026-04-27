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
    $fields = [
        'group_name', 'district_name', 'name', 'mobile', 'email', 'state', 'block', 
        'gram_panchayat', 'village', 'address', 'pincode', 'electricity_id', 
        'division_name', 'kw', 'app_ref_no', 'registration_date', 'js_cash_ecofy', 
        'account_number', 'js_bank_name', 'js_bank_branch', 'js_ifsc_code', 
        'js_date', 'doc_submission_date', 'model_number', 'remarks'
    ];
    $data = [];
    foreach ($fields as $f) {
        $val = trim($_POST[$f] ?? '');
        $data[$f] = $val === '' ? null : $val;
    }
    $data['id'] = $id;

    $backParam = trim($_POST['back'] ?? '');
    $backUrl   = match($backParam) {
        'pending'                       => '/APN-Solar/customers/pending_list.php',
        'pending_account_number'        => '/APN-Solar/reports/pending_account_number.php',
        'customer_registration_pending' => '/APN-Solar/reports/customer_registration_pending.php',
        'customer_jan_samarth_pending'  => '/APN-Solar/reports/customer_jan_samarth_pending.php',
        'subsidy_first'                 => '/APN-Solar/reports/pending_subsidy_first.php',
        'subsidy_second'                => '/APN-Solar/reports/pending_subsidy_second.php',
        default                         => '/APN-Solar/customers/',
    };

    if (empty($data['name'])) {
        $error = "Customer name is required.";
    } else {
        try {
            $sql = "UPDATE customers SET
                    group_name=:group_name, district_name=:district_name, name=:name,
                    mobile=:mobile, email=:email, state=:state, block=:block,
                    gram_panchayat=:gram_panchayat, village=:village, address=:address,
                    pincode=:pincode, electricity_id=:electricity_id, division_name=:division_name,
                    kw=:kw, app_ref_no=:app_ref_no, registration_date=:registration_date,
                    js_cash_ecofy=:js_cash_ecofy, account_number=:account_number,
                    js_bank_name=:js_bank_name, js_bank_branch=:js_bank_branch,
                    js_ifsc_code=:js_ifsc_code, js_date=:js_date, doc_submission_date=:doc_submission_date,
                    model_number=:model_number, remarks=:remarks
                    WHERE id=:id";
            $pdo->prepare($sql)->execute($data);

            // Update payment if total_amount provided
            if (isset($_POST['total_amount']) && $_POST['total_amount'] !== '') {
                $totalAmt = (float)$_POST['total_amount'];
                $existingPayment = $pdo->prepare("SELECT id FROM payments WHERE customer_id = ?");
                $existingPayment->execute([$id]);
                if ($existingPayment->fetch()) {
                    $pdo->prepare("UPDATE payments SET total_amount=?, due_amount=total_amount-payment_received WHERE customer_id=?")
                        ->execute([$totalAmt, $id]);
                } else {
                    $pdo->prepare("INSERT INTO payments (customer_id, total_amount, due_amount, payment_received) VALUES (?,?,?,0)")
                        ->execute([$id, $totalAmt, $totalAmt]);
                }
            }

            setFlash('success', 'Customer updated successfully.');
            header("Location: $backUrl");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
    // Re-populate customer on error
    $customer = array_merge($customer, $data);
}

// Fetch groups for dropdown
$groups = $pdo->query("SELECT DISTINCT name FROM `groups` ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Edit Customer';
include __DIR__ . '/../views/partials/header.php';
?>

<style>
    body { background-color: #f4f6f9; }
    .content-wrapper { padding: 20px; }
    .card { background: #fff; border: 1px solid #dee2e6; border-radius: .25rem; margin-bottom: 20px; }
    .card-header { padding: .75rem 1.25rem; border-bottom: 1px solid rgba(0,0,0,.125); background-color: rgba(0,0,0,.03); }
    .card-title { font-size: 1.1rem; font-weight: 400; margin: 0; }
    .card-body { padding: 1.25rem; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; margin-bottom: .5rem; font-weight: 700; color: #333; font-size: 0.9rem; }
    .form-control { display: block; width: 100%; height: calc(2.25rem + 2px); padding: .375rem .75rem; font-size: 1rem; font-weight: 400; line-height: 1.5; color: #495057; background-color: #fff; background-clip: padding-box; border: 1px solid #ced4da; border-radius: .25rem; transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out; }
    .form-control:focus { border-color: #80bdff; outline: 0; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
    textarea.form-control { height: auto; }
    .btn-update { background-color: #007bff; border-color: #007bff; color: #fff; padding: .375rem .75rem; border-radius: .25rem; font-size: 1rem; cursor: pointer; }
    .btn-update:hover { background-color: #0069d9; border-color: #0062cc; }
    .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: .75rem 1.25rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: .25rem; }
</style>

<div class="content-wrapper">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Customer</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="back" value="<?= htmlspecialchars($_GET['back'] ?? '') ?>">

                <div class="form-group">
                    <label>Group</label>
                    <select name="group_name" class="form-control">
                        <option value="">-- Select Group --</option>
                        <?php foreach ($groups as $g): ?>
                            <option value="<?= htmlspecialchars($g) ?>" <?= ($customer['group_name'] ?? '') === $g ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>District</label>
                    <input type="text" name="district_name" class="form-control" value="<?= htmlspecialchars($customer['district_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($customer['name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Mobile Number</label>
                    <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($customer['mobile'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>State</label>
                    <select name="state" class="form-control">
                        <option value="Uttar Pradesh" <?= ($customer['state'] ?? '') === 'Uttar Pradesh' ? 'selected' : '' ?>>Uttar Pradesh</option>
                        <!-- Add other states if needed -->
                    </select>
                </div>

                <div class="form-group">
                    <label>District</label>
                    <select name="district_name_select" class="form-control">
                        <option value="<?= htmlspecialchars($customer['district_name'] ?? '') ?>"><?= htmlspecialchars($customer['district_name'] ?? 'Select District') ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Block</label>
                    <select name="block" class="form-control">
                        <option value="<?= htmlspecialchars($customer['block'] ?? '') ?>"><?= htmlspecialchars($customer['block'] ?? 'Select Block') ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Gram Panchayat</label>
                    <select name="gram_panchayat" class="form-control">
                        <option value="<?= htmlspecialchars($customer['gram_panchayat'] ?? '') ?>"><?= htmlspecialchars($customer['gram_panchayat'] ?? 'Select Gram Panchayat') ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Village</label>
                    <select name="village" class="form-control">
                        <option value="<?= htmlspecialchars($customer['village'] ?? '') ?>"><?= htmlspecialchars($customer['village'] ?? 'Select Village') ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Pincode</label>
                    <input type="text" name="pincode" class="form-control" value="<?= htmlspecialchars($customer['pincode'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Electricity Account ID</label>
                    <input type="text" name="electricity_id" class="form-control" value="<?= htmlspecialchars($customer['electricity_id'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Division Name</label>
                    <input type="text" name="division_name" class="form-control" value="<?= htmlspecialchars($customer['division_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>KW</label>
                    <input type="text" name="kw" class="form-control" value="<?= htmlspecialchars($customer['kw'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Application Reference No</label>
                    <input type="text" name="app_ref_no" class="form-control" value="<?= htmlspecialchars($customer['app_ref_no'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Registration Date</label>
                    <input type="date" name="registration_date" class="form-control" value="<?= htmlspecialchars($customer['registration_date'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Jan Samarth / Cash / Ecofy</label>
                    <input type="text" name="js_cash_ecofy" class="form-control" value="<?= htmlspecialchars($customer['js_cash_ecofy'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Account Number</label>
                    <input type="text" name="account_number" class="form-control" value="<?= htmlspecialchars($customer['account_number'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Jan Samarth Bank Name</label>
                    <input type="text" name="js_bank_name" class="form-control" value="<?= htmlspecialchars($customer['js_bank_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Jan Samarth Bank Branch</label>
                    <input type="text" name="js_bank_branch" class="form-control" value="<?= htmlspecialchars($customer['js_bank_branch'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Jan Samarth IFSC Code</label>
                    <input type="text" name="js_ifsc_code" class="form-control" value="<?= htmlspecialchars($customer['js_ifsc_code'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Jan Samarth Date</label>
                    <input type="date" name="js_date" class="form-control" value="<?= htmlspecialchars($customer['js_date'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Document Submission Date</label>
                    <input type="date" name="doc_submission_date" class="form-control" value="<?= htmlspecialchars($customer['doc_submission_date'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Model Number</label>
                    <select name="model_number" class="form-control">
                        <option value="">-- Select Model Number --</option>
                        <option value="Arogya On Grid Model" <?= ($customer['model_number'] ?? '') === 'Arogya On Grid Model' ? 'selected' : '' ?>>Arogya On Grid Model</option>
                        <option value="Arogya Off Grid Model" <?= ($customer['model_number'] ?? '') === 'Arogya Off Grid Model' ? 'selected' : '' ?>>Arogya Off Grid Model</option>
                        <option value="Arogya Hybrid Model" <?= ($customer['model_number'] ?? '') === 'Arogya Hybrid Model' ? 'selected' : '' ?>>Arogya Hybrid Model</option>
                        <option value="Arogya Resko Village Model" <?= ($customer['model_number'] ?? '') === 'Arogya Resko Village Model' ? 'selected' : '' ?>>Arogya Resko Village Model</option>
                        <option value="Arogya Village Model" <?= ($customer['model_number'] ?? '') === 'Arogya Village Model' ? 'selected' : '' ?>>Arogya Village Model</option>
                        <option value="Arogya City Model" <?= ($customer['model_number'] ?? '') === 'Arogya City Model' ? 'selected' : '' ?>>Arogya City Model</option>
                        <option value="Arogya On Grid With TATA" <?= ($customer['model_number'] ?? '') === 'Arogya On Grid With TATA' ? 'selected' : '' ?>>Arogya On Grid With TATA</option>
                        <option value="Arogya Commercial On Grid" <?= ($customer['model_number'] ?? '') === 'Arogya Commercial On Grid' ? 'selected' : '' ?>>Arogya Commercial On Grid</option>
                        <option value="Arogya Solar Ata Chakki" <?= ($customer['model_number'] ?? '') === 'Arogya Solar Ata Chakki' ? 'selected' : '' ?>>Arogya Solar Ata Chakki</option>
                        <option value="Arogya Solar Pump" <?= ($customer['model_number'] ?? '') === 'Arogya Solar Pump' ? 'selected' : '' ?>>Arogya Solar Pump</option>
                        <option value="Arogya On Grid Normal" <?= ($customer['model_number'] ?? '') === 'Arogya On Grid Normal' ? 'selected' : '' ?>>Arogya On Grid Normal</option>
                        <option value="Arogya Resko Hybrid" <?= ($customer['model_number'] ?? '') === 'Arogya Resko Hybrid' ? 'selected' : '' ?>>Arogya Resko Hybrid</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Total Amount</label>
                    <input type="number" step="0.01" name="total_amount" class="form-control" value="<?= htmlspecialchars($customer['total_amount'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3"><?= htmlspecialchars($customer['remarks'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn-update">Update Customer</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
