<?php
// gram_panchayats/edit.php
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: /APN-Solar/gram_panchayats/index.php"); exit; }
$stmt = $pdo->prepare("SELECT * FROM gram_panchayats WHERE id=?"); $stmt->execute([$id]); $row = $stmt->fetch();
if (!$row) { setFlash('error','Not found.'); header("Location: /APN-Solar/gram_panchayats/index.php"); exit; }

$upData = require __DIR__ . '/../data/up_districts_blocks.php';
$districts = array_keys($upData); sort($districts);

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $state    = trim($_POST['state']    ?? 'Uttar Pradesh');
    $district = trim($_POST['district'] ?? '');
    $block    = trim($_POST['block']    ?? '');
    $name     = trim($_POST['name']     ?? '');
    $status   = in_array($_POST['status']??'', ['active','inactive']) ? $_POST['status'] : 'active';
    if (!$district || !$block || !$name) { $error = "All fields are required."; }
    else {
        try {
            $pdo->prepare("UPDATE gram_panchayats SET state=?,district=?,block=?,name=?,status=? WHERE id=?")
                ->execute([$state,$district,$block,$name,$status,$id]);
            setFlash('success','Gram Panchayat updated.'); header("Location: /APN-Solar/gram_panchayats/index.php"); exit;
        } catch (PDOException $e) { $error = "Error: ".$e->getMessage(); }
    }
    $row = array_merge($row,['state'=>$state,'district'=>$district,'block'=>$block,'name'=>$name,'status'=>$status]);
}
$pageTitle = 'Edit Gram Panchayat';
include __DIR__ . '/../views/partials/header.php';
?>
<style>
.page-card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:20px 24px;margin-bottom:16px;}
.form-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:14px;}
.form-grid-2{display:grid;grid-template-columns:1fr 3fr;gap:14px;margin-bottom:14px;}
.fg label{display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:5px;}
.fg select,.fg input{width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:5px;font-size:.87rem;font-family:inherit;outline:none;background:#fff;box-sizing:border-box;}
.fg select:focus,.fg input:focus{border-color:#3b82f6;}
.form-actions{display:flex;gap:9px;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 20px;border-radius:6px;font-size:.85rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;}
.btn-green{background:#16a34a;color:#fff;}.btn-gray{background:#6b7280;color:#fff;}
.btn:hover{opacity:.87;}
.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 14px;border-radius:7px;margin-bottom:14px;font-size:.87rem;}
.page-title-bar{font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:14px;}
</style>
<div class="page-title-bar">Edit Gram Panchayat</div>
<?php if($error):?><div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error);?></div><?php endif;?>
<div class="page-card">
    <form method="POST" action="">
        <div class="form-grid">
            <div class="fg"><label>State</label>
                <select name="state"><option value="Uttar Pradesh" selected>Uttar Pradesh</option></select>
            </div>
            <div class="fg"><label>District</label>
                <select name="district" id="districtSelect" onchange="loadBlocks()">
                    <option value="">Select District</option>
                    <?php foreach($districts as $d):?>
                        <option value="<?php echo htmlspecialchars($d);?>" <?php echo $row['district']===$d?'selected':'';?>><?php echo htmlspecialchars($d);?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="fg"><label>Block</label>
                <select name="block" id="blockSelect">
                    <option value="">Select Block</option>
                    <?php $curDist=$row['district'];
                    if($curDist&&isset($upData[$curDist])){foreach($upData[$curDist] as $blk){$sel=$row['block']===$blk?'selected':'';echo "<option value=\"".htmlspecialchars($blk)."\" $sel>".htmlspecialchars($blk)."</option>";}}?>
                </select>
            </div>
            <div class="fg"><label>Gram Panchayat Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($row['name']);?>" required>
            </div>
        </div>
        <div class="form-grid-2">
            <div class="fg"><label>Status</label>
                <select name="status">
                    <option value="active" <?php echo $row['status']==='active'?'selected':'';?>>Active</option>
                    <option value="inactive" <?php echo $row['status']==='inactive'?'selected':'';?>>Inactive</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-green"><i class="fas fa-save"></i> Save</button>
            <a href="/APN-Solar/gram_panchayats/index.php" class="btn btn-gray"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </form>
</div>
<script>
const upBlocks = <?php echo json_encode($upData, JSON_UNESCAPED_UNICODE); ?>;
function loadBlocks() {
    const d = document.getElementById('districtSelect').value;
    const bs = document.getElementById('blockSelect');
    const cur = bs.value;
    bs.innerHTML = '<option value="">Select Block</option>';
    if (d && upBlocks[d]) {
        upBlocks[d].forEach(b => { const o=document.createElement('option');o.value=b;o.textContent=b;if(b===cur)o.selected=true;bs.appendChild(o); });
    }
}
</script>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
