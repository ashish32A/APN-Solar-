<?php
// gram_panchayats/create.php — Add Gram Panchayat (cascading dropdowns matching screenshot)
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();

$upData    = require __DIR__ . '/../data/up_districts_blocks.php';
$districts = array_keys($upData); sort($districts);

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $state    = trim($_POST['state']    ?? 'Uttar Pradesh');
    $district = trim($_POST['district'] ?? '');
    $block    = trim($_POST['block']    ?? '');
    $name     = trim($_POST['name']     ?? '');
    $status   = in_array($_POST['status']??'', ['active','inactive']) ? $_POST['status'] : 'active';
    if (!$district || !$block || !$name) {
        $error = "District, Block and Gram Panchayat name are required.";
    } else {
        try {
            $pdo->prepare("INSERT INTO gram_panchayats (state,district,block,name,status) VALUES (?,?,?,?,?)")
                ->execute([$state,$district,$block,$name,$status]);
            setFlash('success','Gram Panchayat added.');
            header("Location: /APN-Solar/gram_panchayats/index.php"); exit;
        } catch (PDOException $e) { $error = "Error: ".$e->getMessage(); }
    }
}
$pageTitle = 'Add Gram Panchayat';
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

<div class="page-title-bar">Add Gram Panchayat</div>
<?php if($error):?><div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error);?></div><?php endif;?>

<div class="page-card">
    <form method="POST" action="">
        <div class="form-grid">
            <div class="fg"><label>State</label>
                <select name="state" id="stateSelect" onchange="loadDistricts()">
                    <option value="">Select</option>
                    <option value="Uttar Pradesh" selected>Uttar Pradesh</option>
                </select>
            </div>
            <div class="fg"><label>District</label>
                <select name="district" id="districtSelect" onchange="loadBlocks()">
                    <option value="">Select District</option>
                    <?php foreach($districts as $d):?>
                        <option value="<?php echo htmlspecialchars($d);?>" <?php echo ($_POST['district']??'')===$d?'selected':'';?>><?php echo htmlspecialchars($d);?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="fg"><label>Block</label>
                <select name="block" id="blockSelect">
                    <option value="">Select Block</option>
                    <?php
                    $selDist = $_POST['district'] ?? '';
                    if ($selDist && isset($upData[$selDist])) {
                        foreach ($upData[$selDist] as $blk) {
                            $sel = ($_POST['block']??'')===$blk?'selected':'';
                            echo "<option value=\"".htmlspecialchars($blk)."\" $sel>".htmlspecialchars($blk)."</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="fg"><label>Gram Panchayat Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name']??'');?>" placeholder="Enter GP name" required>
            </div>
        </div>
        <div class="form-grid-2">
            <div class="fg"><label>Status</label>
                <select name="status">
                    <option value="active" <?php echo ($_POST['status']??'active')==='active'?'selected':'';?>>Active</option>
                    <option value="inactive" <?php echo ($_POST['status']??'')==='inactive'?'selected':'';?>>Inactive</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-green"><i class="fas fa-save"></i> Save</button>
            <a href="/APN-Solar/gram_panchayats/index.php" class="btn btn-gray"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </form>
</div>

<!-- Embedded block data for JS -->
<script>
const upBlocks = <?php echo json_encode($upData, JSON_UNESCAPED_UNICODE); ?>;

function loadBlocks() {
    const district = document.getElementById('districtSelect').value;
    const blockSel = document.getElementById('blockSelect');
    blockSel.innerHTML = '<option value="">Select Block</option>';
    if (district && upBlocks[district]) {
        upBlocks[district].forEach(b => {
            const opt = document.createElement('option');
            opt.value = b; opt.textContent = b;
            blockSel.appendChild(opt);
        });
    }
}
// Re-populate on page load if district was already selected (POST error case)
window.addEventListener('DOMContentLoaded', () => {
    const district = document.getElementById('districtSelect').value;
    const currentBlock = "<?php echo addslashes($_POST['block'] ?? ''); ?>";
    if (district && upBlocks[district]) {
        const blockSel = document.getElementById('blockSelect');
        // Only repopulate if only default option
        if (blockSel.options.length <= 1) {
            upBlocks[district].forEach(b => {
                const opt = document.createElement('option');
                opt.value = b; opt.textContent = b;
                if (b === currentBlock) opt.selected = true;
                blockSel.appendChild(opt);
            });
        }
    }
});
</script>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
