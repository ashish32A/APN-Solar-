<?php
// masters/payments/edit.php — Edit a payment
require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: /APN-Solar/masters/payments/index.php"); exit; }
$stmt = $pdo->prepare("SELECT p.*, cu.name AS customer_name FROM payments p LEFT JOIN customers cu ON p.customer_id=cu.id WHERE p.id=?");
$stmt->execute([$id]); $pay = $stmt->fetch();
if (!$pay) { setFlash('error','Payment not found.'); header("Location: /APN-Solar/masters/payments/index.php"); exit; }

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $total    = (float)($_POST['total_amount'] ?? 0);
    $received = (float)($_POST['payment_received'] ?? 0);
    $due      = round($total - $received, 2);
    $mode     = in_array($_POST['payment_mode']??'', ['cash','online','cheque','neft']) ? $_POST['payment_mode'] : 'online';
    $txn      = trim($_POST['transaction_no'] ?? '');
    $date     = trim($_POST['payment_date'] ?? '') ?: null;
    $notes    = trim($_POST['notes'] ?? '');
    try {
        $pdo->prepare("UPDATE payments SET total_amount=?,payment_received=?,due_amount=?,payment_mode=?,transaction_no=?,payment_date=?,notes=? WHERE id=?")
            ->execute([$total,$received,$due,$mode,$txn,$date,$notes,$id]);
        setFlash('success','Payment updated.'); header("Location: /APN-Solar/masters/payments/index.php"); exit;
    } catch (PDOException $e) { $error="Error: ".$e->getMessage(); }
    $pay = array_merge($pay,['total_amount'=>$total,'payment_received'=>$received,'due_amount'=>$due,'payment_mode'=>$mode,'transaction_no'=>$txn,'payment_date'=>$date,'notes'=>$notes]);
}
$pageTitle = 'Edit Payment'; include __DIR__ . '/../../views/partials/header.php';
?>
<style>
.page-card{background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.12);padding:28px 32px;max-width:620px;}
.page-title{font-size:1.1rem;font-weight:700;color:#1e293b;margin-bottom:4px;display:flex;align-items:center;gap:9px;}.page-title i{color:#f59e0b;}
.cust-info{font-size:.82rem;color:#64748b;margin-bottom:16px;padding:9px 12px;background:#f8fafc;border-radius:7px;}
.badge-id{background:#f1f5f9;color:#64748b;font-size:.72rem;padding:3px 10px;border-radius:20px;font-weight:600;}
.fg-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}.fg-grid .full{grid-column:1/-1;}
.fg label{display:block;font-size:.71rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;}
.fg input,.fg select{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.88rem;font-family:inherit;color:#1e293b;background:#f8fafc;outline:none;box-sizing:border-box;}
.fg input:focus,.fg select:focus{border-color:#f59e0b;background:#fff;}
.due-display{padding:9px 12px;border-radius:7px;font-weight:700;font-size:.9rem;}
.form-actions{display:flex;gap:11px;margin-top:18px;padding-top:16px;border-top:1px solid #e2e8f0;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 22px;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;}
.btn-warning{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;}.btn-secondary{background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0;}
.btn:hover{opacity:.87;transform:translateY(-1px);}
.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.875rem;}
</style>
<div class="page-card">
    <div class="page-title"><i class="fas fa-pencil-alt"></i> Edit Payment <span class="badge-id">ID #<?php echo $id;?></span></div>
    <div class="cust-info"><i class="fas fa-user" style="margin-right:5px;color:#94a3b8;"></i><strong><?php echo htmlspecialchars($pay['customer_name']??'');?></strong></div>
    <?php if($error):?><div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error);?></div><?php endif;?>
    <form method="POST" action="">
        <div class="fg-grid">
            <div class="fg"><label>Total Amount (₹)</label>
                <input type="number" step="0.01" name="total_amount" id="totalAmt" value="<?php echo htmlspecialchars($pay['total_amount']??0);?>" oninput="calcDue()"></div>
            <div class="fg"><label>Amount Received (₹)</label>
                <input type="number" step="0.01" name="payment_received" id="receivedAmt" value="<?php echo htmlspecialchars($pay['payment_received']??0);?>" oninput="calcDue()"></div>
            <div class="fg"><label>Due Amount (₹)</label>
                <div class="due-display" id="dueDisplay">₹<?php echo number_format((float)($pay['due_amount']??0),2);?></div></div>
            <div class="fg"><label>Payment Mode</label>
                <select name="payment_mode">
                    <?php foreach(['cash'=>'Cash','online'=>'Online','cheque'=>'Cheque','neft'=>'NEFT'] as $v=>$l):?>
                        <option value="<?php echo $v;?>" <?php echo ($pay['payment_mode']??'online')===$v?'selected':'';?>><?php echo $l;?></option>
                    <?php endforeach;?>
                </select></div>
            <div class="fg"><label>Transaction No.</label>
                <input type="text" name="transaction_no" value="<?php echo htmlspecialchars($pay['transaction_no']??'');?>"></div>
            <div class="fg"><label>Payment Date</label>
                <input type="date" name="payment_date" value="<?php echo htmlspecialchars($pay['payment_date']??'');?>"></div>
            <div class="fg full"><label>Notes</label>
                <input type="text" name="notes" value="<?php echo htmlspecialchars($pay['notes']??'');?>"></div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Update Payment</button>
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
