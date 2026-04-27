<?php
// customers/create.php — Create New Customer (handles GET + POST)

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

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

    if (empty($data['name'])) {
        $error = "Customer name is required.";
    } else {
        try {
            $sql = "INSERT INTO customers (
                        group_name, district_name, name, mobile, email, state, block,
                        gram_panchayat, village, address, pincode, electricity_id,
                        division_name, kw, app_ref_no, registration_date, js_cash_ecofy,
                        account_number, js_bank_name, js_bank_branch, js_ifsc_code,
                        js_date, doc_submission_date, model_number, remarks, status
                    ) VALUES (
                        :group_name, :district_name, :name, :mobile, :email, :state, :block,
                        :gram_panchayat, :village, :address, :pincode, :electricity_id,
                        :division_name, :kw, :app_ref_no, :registration_date, :js_cash_ecofy,
                        :account_number, :js_bank_name, :js_bank_branch, :js_ifsc_code,
                        :js_date, :doc_submission_date, :model_number, :remarks, 'pending'
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);

            $newId = $pdo->lastInsertId();
            $totalAmt = !empty($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0;
            $pdo->prepare("INSERT INTO payments (customer_id, total_amount, due_amount, payment_received)
                           VALUES (?,?,?,0)")->execute([$newId, $totalAmt, $totalAmt]);

            setFlash('success', 'Customer created successfully.');
            header("Location: /APN-Solar/customers/");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch groups for dropdown
$groups = $pdo->query("SELECT DISTINCT name FROM `groups` ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Create New Customer';
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
    .btn-save { background-color: #007bff; border-color: #007bff; color: #fff; padding: .375rem .75rem; border-radius: .25rem; font-size: 1rem; cursor: pointer; }
    .btn-save:hover { background-color: #0069d9; border-color: #0062cc; }
</style>

<div class="content-wrapper">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create New Customer</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Group</label>
                    <select name="group_name" class="form-control">
                        <option value="">-- Select Group --</option>
                        <?php foreach ($groups as $g): ?>
                            <option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>District</label>
                    <input type="text" name="district_name" class="form-control">
                </div>

                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Mobile Number</label>
                    <input type="text" name="mobile" class="form-control">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control">
                </div>

                <div class="form-group">
                    <label>State</label>
                    <select name="state" class="form-control">
                        <option value="Uttar Pradesh">Uttar Pradesh</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>Pincode</label>
                    <input type="text" name="pincode" class="form-control">
                </div>

                <div class="form-group">
                    <label>Electricity Account ID</label>
                    <input type="text" name="electricity_id" class="form-control">
                </div>

                <div class="form-group">
                    <label>Division Name</label>
                    <input type="text" name="division_name" class="form-control">
                </div>

                <div class="form-group">
                    <label>KW</label>
                    <input type="text" name="kw" class="form-control">
                </div>

                <div class="form-group">
                    <label>Model Number</label>
                    <select name="model_number" class="form-control">
                        <option value="">-- Select Model Number --</option>
                        <option value="Arogya On Grid Model">Arogya On Grid Model</option>
                        <option value="Arogya Off Grid Model">Arogya Off Grid Model</option>
                        <option value="Arogya Hybrid Model">Arogya Hybrid Model</option>
                        <option value="Arogya Resko Village Model">Arogya Resko Village Model</option>
                        <option value="Arogya Village Model">Arogya Village Model</option>
                        <option value="Arogya City Model">Arogya City Model</option>
                        <option value="Arogya On Grid With TATA">Arogya On Grid With TATA</option>
                        <option value="Arogya Commercial On Grid">Arogya Commercial On Grid</option>
                        <option value="Arogya Solar Ata Chakki">Arogya Solar Ata Chakki</option>
                        <option value="Arogya Solar Pump">Arogya Solar Pump</option>
                        <option value="Arogya On Grid Normal">Arogya On Grid Normal</option>
                        <option value="Arogya Resko Hybrid">Arogya Resko Hybrid</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Total Amount</label>
                    <input type="number" step="0.01" name="total_amount" class="form-control">
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" class="form-control" rows="3"></textarea>
                </div>

                <button type="submit" class="btn-save">Save Customer</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>