<?php
// complaints/edit.php — Edit a Complaint

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: /APN-Solar/complaints/index.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM complaints WHERE id=?");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { setFlash('error','Complaint not found.'); header("Location: /APN-Solar/complaints/index.php"); exit; }

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'operator_name'          => trim($_POST['operator_name'] ?? ''),
        'address'                => trim($_POST['address'] ?? ''),
        'district'               => trim($_POST['district'] ?? ''),
        'electricity_account_no' => trim($_POST['electricity_account_no'] ?? ''),
        'kw'                     => trim($_POST['kw'] ?? '') ?: null,
        'division'               => trim($_POST['division'] ?? ''),
        'issue'                  => trim($_POST['issue'] ?? ''),
        'remarks'                => trim($_POST['remarks'] ?? ''),
        'status'                 => in_array($_POST['status']??'',['open','pending','in_progress','resolved','closed']) ? $_POST['status'] : 'open',
        'id'                     => $id,
    ];
    try {
        $pdo->prepare("UPDATE complaints SET operator_name=:operator_name,address=:address,district=:district,
            electricity_account_no=:electricity_account_no,kw=:kw,division=:division,issue=:issue,
            remarks=:remarks,status=:status,complaint_type=:issue WHERE id=:id")->execute($data);
        setFlash('success','Complaint updated.');
        header("Location: /APN-Solar/complaints/index.php"); exit;
    } catch (PDOException $e) { $error="Error: ".$e->getMessage(); }
    $row = array_merge($row, $data);
}

try { $customers = $pdo->query("SELECT id,name,mobile FROM customers ORDER BY name ASC")->fetchAll(); }
catch (PDOException $e) { $customers=[]; }

$pageTitle = 'Edit Complaint — '.$row['complaint_no'];
include __DIR__ . '/../views/partials/header.php';
?>
<style>
.page-card{background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.12);padding:28px 32px;max-width:820px;}
.page-title{font-size:1.1rem;font-weight:700;color:#1e293b;margin-bottom:18px;display:flex;align-items:center;gap:9px;}
.page-title i{color:#f59e0b;}.badge-id{background:#f1f5f9;color:#64748b;font-size:.72rem;padding:3px 10px;border-radius:20px;font-weight:600;}
.fg-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;}.fg-grid .full{grid-column:1/-1;}
.fg label{display:block;font-size:.71rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;}
.fg input,.fg select,.fg textarea{width:100%;padding:9px 11px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.87rem;font-family:inherit;color:#1e293b;background:#f8fafc;outline:none;}
.fg input:focus,.fg select:focus,.fg textarea:focus{border-color:#f59e0b;background:#fff;}.fg textarea{resize:vertical;min-height:72px;}
.form-actions{display:flex;gap:11px;margin-top:18px;padding-top:16px;border-top:1px solid #e2e8f0;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 22px;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;}
.btn-warning{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;}.btn-secondary{background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0;}
.btn:hover{opacity:.87;transform:translateY(-1px);}.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.875rem;}
</style>
<div class="page-card">
    <div class="page-title"><i class="fas fa-pencil-alt"></i> Edit Complaint <span class="badge-id"><?php echo htmlspecialchars($row['complaint_no']??''); ?></span></div>
    <?php if($error):?><div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error);?></div><?php endif;?>
    <form method="POST" action="">
        <div class="fg-grid">
            <div class="fg"><label>Operator Name</label><input type="text" name="operator_name" value="<?php echo htmlspecialchars($row['operator_name']??'');?>"></div>
            <div class="fg"><label>District</label><input type="text" name="district" value="<?php echo htmlspecialchars($row['district']??'');?>"></div>
            <div class="fg"><label>Division</label><input type="text" name="division" value="<?php echo htmlspecialchars($row['division']??'');?>"></div>
            <div class="fg"><label>Electricity Account No</label><input type="text" name="electricity_account_no" value="<?php echo htmlspecialchars($row['electricity_account_no']??'');?>"></div>
            <div class="fg"><label>KW</label><input type="number" step="0.01" name="kw" value="<?php echo htmlspecialchars($row['kw']??'');?>"></div>
            <div class="fg"><label>Issue / Type</label>
                <select name="issue">
                    <?php foreach(['Bill issue','Other Issue','Cleaning Service','Configure Pending','Technical Issue','Net Metering'] as $iss):?>
                        <option value="<?php echo $iss;?>" <?php echo ($row['issue']??$row['complaint_type']??'')===$iss?'selected':'';?>><?php echo $iss;?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="fg"><label>Status</label>
                <select name="status">
                    <?php foreach(['open'=>'Open','pending'=>'Pending','in_progress'=>'In Progress','resolved'=>'Resolved','closed'=>'Closed'] as $v=>$l):?>
                        <option value="<?php echo $v;?>" <?php echo ($row['status']??'open')===$v?'selected':'';?>><?php echo $l;?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="fg full"><label>Address</label><textarea name="address"><?php echo htmlspecialchars($row['address']??'');?></textarea></div>
            <div class="fg full"><label>Remarks</label><textarea name="remarks"><?php echo htmlspecialchars($row['remarks']??$row['resolved_note']??'');?></textarea></div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Update Complaint</button>
            <a href="/APN-Solar/complaints/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
