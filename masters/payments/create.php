<?php
// masters/payments/create.php — Add a Payment
require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid      = (int)($_POST['customer_id'] ?? 0);
    $total    = (float)($_POST['total_amount'] ?? 0);
    $received = (float)($_POST['payment_received'] ?? 0);
    $due      = round($total - $received, 2);
    $mode     = in_array($_POST['payment_mode']??'', ['cash','online','cheque','neft']) ? $_POST['payment_mode'] : 'online';
    $txn      = trim($_POST['transaction_no'] ?? '');
    $date     = trim($_POST['payment_date'] ?? '') ?: null;
    $notes    = trim($_POST['notes'] ?? '');

    if (!$cid) { $error = "Please select a customer."; }
    else {
        try {
            $pdo->prepare("INSERT INTO payments (customer_id, total_amount, payment_received, due_amount, payment_mode, transaction_no, payment_date, notes)
                VALUES (?,?,?,?,?,?,?,?)")->execute([$cid, $total, $received, $due, $mode, $txn, $date, $notes]);
            setFlash('success', 'Payment recorded successfully.');
            header("Location: /APN-Solar/masters/payments/index.php"); exit;
        } catch (PDOException $e) { $error = "Error: " . $e->getMessage(); }
    }
}
try { $customers = $pdo->query("SELECT id, name, mobile FROM customers ORDER BY name ASC")->fetchAll(); }
catch (PDOException $e) { $customers = []; }

$pageTitle = 'Add Payment'; include __DIR__ . '/../../views/partials/header.php';
?>
<style>
.page-card{background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.12);padding:28px 32px;max-width:620px;}
.page-title{font-size:1.1rem;font-weight:700;color:#1e293b;margin-bottom:18px;display:flex;align-items:center;gap:9px;}.page-title i{color:#3b82f6;}
.fg-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}.fg-grid .full{grid-column:1/-1;}
.fg label{display:block;font-size:.71rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;}
.fg input,.fg select,.fg textarea{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.88rem;font-family:inherit;color:#1e293b;background:#f8fafc;outline:none;box-sizing:border-box;}
.fg input:focus,.fg select:focus{border-color:#3b82f6;background:#fff;}
.due-display{padding:9px 12px;background:#fee2e2;color:#dc2626;border-radius:7px;font-weight:700;font-size:.9rem;}
.form-actions{display:flex;gap:11px;margin-top:18px;padding-top:16px;border-top:1px solid #e2e8f0;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 22px;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;}
.btn-primary{background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;}.btn-secondary{background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0;}
.btn:hover{opacity:.87;transform:translateY(-1px);}
.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.875rem;}
</style>
<div class="page-card">
    <div class="page-title"><i class="fas fa-rupee-sign"></i> Add Payment</div>
    <?php if($error):?><div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error);?></div><?php endif;?>
    <form method="POST" action="">
        <div class="fg-grid">
            <div class="fg full"><label>Customer <span style="color:#dc2626">*</span></label>
                <select name="customer_id" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach($customers as $c):?>
                        <option value="<?php echo (int)$c['id'];?>" <?php echo ((int)($_POST['customer_id']??0)===(int)$c['id'])?'selected':'';?>>
                            <?php echo htmlspecialchars($c['name']);?> (<?php echo htmlspecialchars($c['mobile']??'');?>)
                        </option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="fg"><label>Total Amount (₹)</label>
                <input type="number" step="0.01" name="total_amount" id="totalAmt" value="<?php echo htmlspecialchars($_POST['total_amount']??'0');?>" oninput="calcDue()"></div>
            <div class="fg"><label>Amount Received (₹)</label>
                <input type="number" step="0.01" name="payment_received" id="receivedAmt" value="<?php echo htmlspecialchars($_POST['payment_received']??'0');?>" oninput="calcDue()"></div>
            <div class="fg"><label>Due Amount (₹)</label>
                <div class="due-display" id="dueDisplay">₹0.00</div></div>
            <div class="fg"><label>Payment Mode</label>
                <select name="payment_mode">
                    <?php foreach(['cash'=>'Cash','online'=>'Online','cheque'=>'Cheque','neft'=>'NEFT'] as $v=>$l):?>
                        <option value="<?php echo $v;?>" <?php echo ($_POST['payment_mode']??'online')===$v?'selected':'';?>><?php echo $l;?></option>
                    <?php endforeach;?>
                </select></div>
            <div class="fg"><label>Transaction No.</label>
                <input type="text" name="transaction_no" value="<?php echo htmlspecialchars($_POST['transaction_no']??'');?>" placeholder="Optional"></div>
            <div class="fg"><label>Payment Date</label>
                <input type="date" name="payment_date" value="<?php echo htmlspecialchars($_POST['payment_date']??'');?>"></div>
            <div class="fg"><label>Notes</label>
                <input type="text" name="notes" value="<?php echo htmlspecialchars($_POST['notes']??'');?>" placeholder="Optional notes"></div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Record Payment</button>
            <a href="/APN-Solar/masters/payments/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
        </div>
    </form>
</div>
<script>
function calcDue(){
    const t=parseFloat(document.getElementById('totalAmt').value)||0;
    const r=parseFloat(document.getElementById('receivedAmt').value)||0;
    const due=Math.max(0,t-r);
    document.getElementById('dueDisplay').textContent='₹'+due.toFixed(2);
    document.getElementById('dueDisplay').style.background=due>0?'#fee2e2':'#dcfce7';
    document.getElementById('dueDisplay').style.color=due>0?'#dc2626':'#15803d';
}
calcDue();
</script>
<?php include __DIR__ . '/../../views/partials/footer.php'; ?>
