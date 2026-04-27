<?php
// villages/index.php — Villages List

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';
requireLogin();

// Auto-create table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS villages (
        id                 INT AUTO_INCREMENT PRIMARY KEY,
        state              VARCHAR(100) DEFAULT 'Uttar Pradesh',
        district           VARCHAR(100) NOT NULL,
        block              VARCHAR(100) NOT NULL,
        gram_panchayat_id  INT DEFAULT NULL,
        gram_panchayat     VARCHAR(200) DEFAULT NULL,
        name               VARCHAR(200) NOT NULL,
        status             ENUM('active','inactive') DEFAULT 'active',
        created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {}

$pageTitle    = 'Villages';
$validPerPage = [10, 25, 50, 100];
$requestedPer = (int)($_GET['per_page'] ?? 10);
$perPage      = in_array($requestedPer, $validPerPage) ? $requestedPer : 10;
$page         = max(1, (int)($_GET['page'] ?? 1));
$search       = trim($_GET['search'] ?? '');
$filterDist   = trim($_GET['district'] ?? '');

$where  = "WHERE 1=1";
$params = [];
if ($search !== '') {
    $where .= " AND (v.name LIKE ? OR v.district LIKE ? OR v.block LIKE ? OR v.gram_panchayat LIKE ?)";
    $like   = "%$search%";
    $params = [$like, $like, $like, $like];
}
if ($filterDist !== '') {
    $where .= " AND v.district = ?";
    $params[] = $filterDist;
}

try {
    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM villages v $where");
    $cStmt->execute($params);
    $totalRecords = (int)$cStmt->fetchColumn();
} catch (PDOException $e) { $totalRecords = 0; }

$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

try {
    $stmt = $pdo->prepare("SELECT v.* FROM villages v $where ORDER BY v.district ASC, v.name ASC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $villages = $stmt->fetchAll();
} catch (PDOException $e) { $villages = []; }

$districts = array_keys(require __DIR__ . '/../data/up_districts_blocks.php');
sort($districts);

$flash = null;
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

include __DIR__ . '/../views/partials/header.php';

function vilUrl(array $ov = []): string {
    $p = array_merge(['search'=>$_GET['search']??'','district'=>$_GET['district']??'','page'=>$_GET['page']??1,'per_page'=>$_GET['per_page']??10], $ov);
    $q = http_build_query(array_filter($p, fn($v)=>$v!==''));
    return '/APN-Solar/villages/index.php'.($q?'?'.$q:'');
}
?>
<style>
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;}
.page-header h2{font-size:1.1rem;font-weight:700;color:#1e293b;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:7px;font-size:.83rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;white-space:nowrap;}
.btn-primary{background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;}
.btn-warning{background:#f59e0b;color:#fff;}
.btn-danger{background:#ef4444;color:#fff;}
.btn-sm{padding:4px 12px;font-size:.75rem;}
.btn:hover{opacity:.87;transform:translateY(-1px);}

.filter-row{display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap;}
.filter-row select,.filter-row input{padding:7px 11px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.83rem;font-family:inherit;outline:none;background:#fff;}

.table-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;}
.table-responsive{overflow-x:auto;}
.vil-table{width:100%;border-collapse:collapse;font-size:.82rem;}
.vil-table thead th{background:#f8fafc;padding:10px 13px;font-weight:700;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;border-bottom:2px solid #e2e8f0;text-align:left;white-space:nowrap;}
.vil-table tbody td{padding:10px 13px;border-bottom:1px solid #f1f5f9;color:#1e293b;vertical-align:middle;}
.vil-table tbody tr:hover{background:#f8fbff;}
.vil-table tbody tr:last-child td{border-bottom:none;}

.badge-active{background:#22c55e;color:#fff;border-radius:20px;padding:2px 11px;font-size:.7rem;font-weight:700;display:inline-block;}
.badge-inactive{background:#f59e0b;color:#fff;border-radius:20px;padding:2px 11px;font-size:.7rem;font-weight:700;display:inline-block;}

.flash-alert{display:flex;align-items:center;gap:9px;padding:11px 15px;border-radius:8px;font-size:.875rem;font-weight:500;margin-bottom:12px;}
.flash-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;}
.flash-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;}

.pagination-bar{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:1px solid #e2e8f0;flex-wrap:wrap;gap:8px;font-size:.8rem;color:#64748b;}
.pagination{display:flex;gap:4px;flex-wrap:wrap;}
.pg-btn{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border:1.5px solid #e2e8f0;border-radius:6px;background:#fff;color:#475569;font-size:.78rem;font-weight:600;cursor:pointer;text-decoration:none;transition:all .15s;}
.pg-btn:hover{background:#f1f5f9;}
.pg-btn.active{background:#3b82f6;border-color:#3b82f6;color:#fff;}
.pg-btn.disabled{opacity:.4;pointer-events:none;}
</style>

<?php if($flash):?>
<div class="flash-alert flash-<?php echo $flash['type']==='success'?'success':'error';?>">
    <i class="fas fa-<?php echo $flash['type']==='success'?'check-circle':'exclamation-circle';?>"></i>
    <?php echo htmlspecialchars($flash['message']);?>
</div>
<?php endif;?>

<div class="page-header">
    <h2>Villages <span style="font-weight:400;color:#94a3b8;font-size:.85rem;">(<?php echo number_format($totalRecords);?>)</span></h2>
    <a href="/APN-Solar/villages/create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Village</a>
</div>

<div class="filter-row">
    <form method="GET" action="/APN-Solar/villages/index.php" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;width:100%;">
        <select name="district" onchange="this.form.submit()">
            <option value="">All Districts</option>
            <?php foreach($districts as $d):?>
                <option value="<?php echo htmlspecialchars($d);?>" <?php echo $filterDist===$d?'selected':'';?>><?php echo htmlspecialchars($d);?></option>
            <?php endforeach;?>
        </select>
        <input type="text" name="search" placeholder="Search village, block, GP..." value="<?php echo htmlspecialchars($search);?>">
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <?php if($search||$filterDist):?><a href="/APN-Solar/villages/index.php" class="btn btn-sm" style="background:#f1f5f9;color:#64748b;border:1.5px solid #e2e8f0;">Clear</a><?php endif;?>
        <div style="margin-left:auto;display:flex;align-items:center;gap:6px;font-size:.82rem;color:#64748b;">
            Show <select name="per_page" onchange="this.form.submit()">
                <?php foreach($validPerPage as $n):?><option value="<?php echo $n;?>" <?php echo $perPage==$n?'selected':'';?>><?php echo $n;?></option><?php endforeach;?>
            </select> entries
        </div>
    </form>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="vil-table">
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th>State</th>
                    <th>District</th>
                    <th>Block</th>
                    <th>Gram Panchayat</th>
                    <th>Village</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if($villages):
                $rowNum=($page-1)*$perPage+1;
                foreach($villages as $v):?>
                <tr>
                    <td><?php echo $rowNum++;?></td>
                    <td><?php echo htmlspecialchars($v['state']??'Uttar Pradesh');?></td>
                    <td><?php echo htmlspecialchars($v['district']);?></td>
                    <td><?php echo htmlspecialchars($v['block']);?></td>
                    <td><?php echo htmlspecialchars($v['gram_panchayat']??'');?></td>
                    <td><strong><?php echo htmlspecialchars($v['name']);?></strong></td>
                    <td><span class="badge-<?php echo $v['status']==='active'?'active':'inactive';?>"><?php echo ucfirst($v['status']);?></span></td>
                    <td>
                        <a href="/APN-Solar/villages/edit.php?id=<?php echo (int)$v['id'];?>" class="btn btn-warning btn-sm"><i class="fas fa-pencil-alt"></i> Edit</a>
                        <form method="POST" action="/APN-Solar/villages/delete.php" style="display:inline;" onsubmit="return confirm('Delete this village?')">
                            <input type="hidden" name="id" value="<?php echo (int)$v['id'];?>">
                            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; else:?>
                <tr><td colspan="8" style="text-align:center;padding:40px;color:#94a3b8;">
                    <i class="fas fa-home" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                    No villages found<?php echo $search?' matching your search.':'.';?>
                </td></tr>
            <?php endif;?>
            </tbody>
        </table>
    </div>
    <div class="pagination-bar">
        <span>Showing <strong><?php echo $totalRecords>0?number_format(($page-1)*$perPage+1):0;?>–<?php echo number_format(min($page*$perPage,$totalRecords));?></strong> of <strong><?php echo number_format($totalRecords);?></strong></span>
        <div class="pagination">
            <?php if($page>1):?><a class="pg-btn" href="<?php echo vilUrl(['page'=>$page-1]);?>">‹ Prev</a><?php else:?><span class="pg-btn disabled">‹ Prev</span><?php endif;?>
            <?php $ws=max(1,$page-2);$we=min($totalPages,$page+2);
            if($ws>1){echo '<a class="pg-btn" href="'.vilUrl(['page'=>1]).'">1</a>';if($ws>2)echo '<span class="pg-btn disabled">…</span>';}
            for($pp=$ws;$pp<=$we;$pp++) echo '<a class="pg-btn '.($pp===$page?'active':'').'" href="'.vilUrl(['page'=>$pp]).'">'.$pp.'</a>';
            if($we<$totalPages){if($we<$totalPages-1)echo '<span class="pg-btn disabled">…</span>';echo '<a class="pg-btn" href="'.vilUrl(['page'=>$totalPages]).'">'.$totalPages.'</a>';}?>
            <?php if($page<$totalPages):?><a class="pg-btn" href="<?php echo vilUrl(['page'=>$page+1]);?>">Next ›</a><?php else:?><span class="pg-btn disabled">Next ›</span><?php endif;?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
