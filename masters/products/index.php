<?php
// masters/products/index.php — Product Master list

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

// ── Auto-add missing columns to products ─────────────────────────────────────
try {
    $pCols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
    $addPCols = [
        'description' => "TEXT DEFAULT NULL",
        'quantity'    => "INT DEFAULT 0",
        'stock'       => "INT DEFAULT 0",
    ];
    foreach ($addPCols as $col => $def) {
        if (!in_array($col, $pCols)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN `$col` $def");
        }
    }
} catch (PDOException $e) {}

$pageTitle    = 'Product Master';
$validPerPage = [10, 25, 50, 100];
$requestedPer = (int)($_GET['per_page'] ?? 10);
$perPage      = in_array($requestedPer, $validPerPage) ? $requestedPer : 10;
$page         = max(1, (int)($_GET['page'] ?? 1));
$search       = trim($_GET['search'] ?? '');

$where  = "WHERE 1=1";
$params = [];
if ($search !== '') {
    $where   .= " AND (name LIKE ? OR description LIKE ?)";
    $like     = "%$search%";
    $params   = [$like, $like];
}

try {
    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM products $where");
    $cStmt->execute($params);
    $totalRecords = (int)$cStmt->fetchColumn();
} catch (PDOException $e) { $totalRecords = 0; }

$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

try {
    $stmt = $pdo->prepare("SELECT * FROM products $where ORDER BY name ASC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) { $products = []; }

$flash = null;
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

include __DIR__ . '/../../views/partials/header.php';

function prodUrl(array $ov = []): string {
    $p = array_merge(['search' => $_GET['search'] ?? '', 'page' => $_GET['page'] ?? 1, 'per_page' => $_GET['per_page'] ?? 10], $ov);
    $q = http_build_query(array_filter($p, fn($v) => $v !== ''));
    return '/APN-Solar/masters/products/index.php' . ($q ? '?' . $q : '');
}
?>

<style>
.pm-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px; }
.pm-header h2 { font-size:1.15rem;font-weight:700;color:#1e293b; }
.btn { display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:7px;font-size:.83rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;white-space:nowrap; }
.btn-primary { background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff; }
.btn-warning { background:#f59e0b;color:#fff; }
.btn-danger  { background:#ef4444;color:#fff; }
.btn-secondary{background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0; }
.btn-sm { padding:4px 11px;font-size:.75rem; }
.btn:hover { opacity:.87;transform:translateY(-1px); }
.action-btns { display:flex;gap:4px; }

.ctrl-bar { display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px; }
.show-wrap { display:flex;align-items:center;gap:6px;font-size:.82rem;color:#64748b; }
.show-wrap select { padding:4px 8px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.82rem; }
.search-wrap { display:flex;align-items:center;gap:6px;font-size:.82rem;color:#64748b; }
.search-wrap input { padding:6px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.82rem;font-family:inherit;outline:none;width:200px; }
.search-wrap input:focus { border-color:#3b82f6; }

.table-wrap { background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden; }
.table-responsive { overflow-x:auto; }
.table { width:100%;border-collapse:collapse;font-size:.82rem;color:#1e293b; }
.table thead th { background:#f0f4f8;padding:9px 12px;font-weight:700;font-size:.71rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b;border-bottom:2px solid #e2e8f0;white-space:nowrap;text-align:left; }
.table thead th.sortable { cursor:pointer;user-select:none; }
.table thead th.sortable::after { content:' ⇅';opacity:.3;font-size:.6rem; }
.table thead th.sort-asc::after { content:' ↑';opacity:1; }
.table thead th.sort-desc::after{ content:' ↓';opacity:1; }
.table tbody td { padding:9px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle; }
.table tbody tr:hover { background:#f8fbff; }
.table tbody tr:last-child td { border-bottom:none; }
.table tbody tr.hidden-row { display:none; }

.flash-alert   { display:flex;align-items:center;gap:9px;padding:11px 15px;border-radius:8px;font-size:.875rem;font-weight:500;margin-bottom:12px; }
.flash-success { background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a; }
.flash-error   { background:#fef2f2;border:1px solid #fecaca;color:#dc2626; }

.pagination-bar { display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:1px solid #e2e8f0;flex-wrap:wrap;gap:8px;font-size:.8rem;color:#64748b; }
.pagination { display:flex;gap:4px;flex-wrap:wrap; }
.pg-btn { display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border:1.5px solid #e2e8f0;border-radius:6px;background:#fff;color:#475569;font-size:.78rem;font-weight:600;cursor:pointer;text-decoration:none;transition:all .15s; }
.pg-btn:hover    { background:#f1f5f9; }
.pg-btn.active   { background:#3b82f6;border-color:#3b82f6;color:#fff; }
.pg-btn.disabled { opacity:.4;pointer-events:none; }

/* Modal */
.modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9998;align-items:center;justify-content:center; }
.modal-overlay.open { display:flex; }
.modal-box { background:#fff;border-radius:12px;padding:28px 32px;max-width:520px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2); }
.modal-box h3 { font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:16px; }
.modal-grid { display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px; }
.modal-grid .full { grid-column:1/-1; }
.modal-group label { display:block;font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px; }
.modal-group input,.modal-group textarea {
    width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:7px;
    font-size:.87rem;font-family:inherit;color:#1e293b;background:#f8fafc;outline:none;box-sizing:border-box;
}
.modal-group input:focus,.modal-group textarea:focus { border-color:#3b82f6;background:#fff; }
.modal-group textarea { resize:vertical;min-height:64px; }
.modal-actions { display:flex;gap:8px;justify-content:flex-end; }

/* Delete confirm */
.del-modal-box { background:#fff;border-radius:12px;padding:32px 36px;max-width:420px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25); }
.del-modal-icon { font-size:2.8rem;color:#ef4444;margin-bottom:14px; }
.del-modal-box h3 { font-size:1.1rem;font-weight:700;color:#1e293b;margin-bottom:8px; }
.del-modal-box p  { color:#64748b;font-size:.88rem;margin-bottom:18px; }
</style>

<?php if ($flash): ?>
<div class="flash-alert flash-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($flash['message']); ?>
</div>
<?php endif; ?>

<div class="pm-header">
    <h2><i class="fas fa-solar-panel" style="color:#3b82f6;margin-right:7px;"></i>Product List</h2>
    <button class="btn btn-primary" onclick="openCreateModal()">
        <i class="fas fa-plus"></i> Create Product
    </button>
</div>

<div class="ctrl-bar">
    <form method="GET" action="/APN-Solar/masters/products/index.php" class="show-wrap">
        <?php if ($search): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
        <label>Show</label>
        <select name="per_page" onchange="this.form.submit()">
            <?php foreach ($validPerPage as $n): ?>
                <option value="<?php echo $n; ?>" <?php echo $perPage==$n?'selected':''; ?>><?php echo $n; ?></option>
            <?php endforeach; ?>
        </select>
        <label>entries</label>
    </form>
    <div class="search-wrap">
        <label>Search:</label>
        <input type="text" id="prodSearch" placeholder="Product name..." oninput="prodFilter(this.value)"
               value="<?php echo htmlspecialchars($search); ?>">
    </div>
</div>

<div class="table-wrap">
    <div class="table-responsive">
        <table class="table" id="prodTable">
            <thead>
                <tr>
                    <th class="sortable" onclick="prodSort(0)" style="width:65px;">Sr No.</th>
                    <th class="sortable" onclick="prodSort(1)">Name</th>
                    <th class="sortable" onclick="prodSort(2)">Description</th>
                    <th class="sortable" onclick="prodSort(3)">Quantity</th>
                    <th class="sortable" onclick="prodSort(4)">Stock</th>
                    <th style="width:120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($products):
                $rowNum = ($page-1)*$perPage+1;
                foreach ($products as $p): ?>
                <tr>
                    <td><?php echo $rowNum++; ?></td>
                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($p['description'] ?? ''); ?></td>
                    <td><?php echo (int)($p['quantity'] ?? 0); ?></td>
                    <td><?php echo (int)($p['stock'] ?? 0); ?></td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-warning btn-sm"
                                onclick="openEditModal(<?php echo (int)$p['id']; ?>,'<?php echo addslashes(htmlspecialchars($p['name'])); ?>','<?php echo addslashes(htmlspecialchars($p['description']??'')); ?>',<?php echo (int)($p['quantity']??0); ?>,<?php echo (int)($p['stock']??0); ?>)">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </button>
                            <button class="btn btn-danger btn-sm"
                                onclick="prodConfirmDelete(<?php echo (int)$p['id']; ?>,'<?php echo addslashes(htmlspecialchars($p['name'])); ?>')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8;">
                    <i class="fas fa-solar-panel" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                    No products found<?php echo $search ? ' matching your search.' : '.'; ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="pagination-bar">
        <span>Showing <strong><?php echo $totalRecords>0?number_format(($page-1)*$perPage+1):0; ?>–<?php echo number_format(min($page*$perPage,$totalRecords)); ?></strong> of <strong><?php echo number_format($totalRecords); ?></strong> entries</span>
        <div class="pagination">
            <?php if($page>1):?><a class="pg-btn" href="<?php echo prodUrl(['page'=>$page-1]);?>">Previous</a><?php else:?><span class="pg-btn disabled">Previous</span><?php endif;?>
            <?php $ws=max(1,$page-2);$we=min($totalPages,$page+2);
            if($ws>1){echo '<a class="pg-btn" href="'.prodUrl(['page'=>1]).'">1</a>';if($ws>2)echo '<span class="pg-btn disabled">…</span>';}
            for($p=$ws;$p<=$we;$p++) echo '<a class="pg-btn '.($p===$page?'active':'').'" href="'.prodUrl(['page'=>$p]).'">'.$p.'</a>';
            if($we<$totalPages){if($we<$totalPages-1)echo '<span class="pg-btn disabled">…</span>';echo '<a class="pg-btn" href="'.prodUrl(['page'=>$totalPages]).'">'.$totalPages.'</a>';}
            ?>
            <?php if($page<$totalPages):?><a class="pg-btn" href="<?php echo prodUrl(['page'=>$page+1]);?>">Next</a><?php else:?><span class="pg-btn disabled">Next</span><?php endif;?>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal-overlay" id="createModal">
    <div class="modal-box">
        <h3><i class="fas fa-plus-circle" style="color:#3b82f6;margin-right:6px;"></i>Create Product</h3>
        <form method="POST" action="/APN-Solar/masters/products/save.php">
            <input type="hidden" name="action" value="create">
            <div class="modal-grid">
                <div class="modal-group full">
                    <label>Product Name <span style="color:#dc2626">*</span></label>
                    <input type="text" name="name" id="createName" placeholder="Product name..." required>
                </div>
                <div class="modal-group full">
                    <label>Description</label>
                    <textarea name="description" id="createDesc" placeholder="Short description..."></textarea>
                </div>
                <div class="modal-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" id="createQty" value="0" min="0">
                </div>
                <div class="modal-group">
                    <label>Stock</label>
                    <input type="number" name="stock" id="createStock" value="0" min="0">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('createModal')">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <h3><i class="fas fa-pencil-alt" style="color:#f59e0b;margin-right:6px;"></i>Edit Product</h3>
        <form method="POST" action="/APN-Solar/masters/products/save.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="modal-grid">
                <div class="modal-group full">
                    <label>Product Name <span style="color:#dc2626">*</span></label>
                    <input type="text" name="name" id="editName" required>
                </div>
                <div class="modal-group full">
                    <label>Description</label>
                    <textarea name="description" id="editDesc"></textarea>
                </div>
                <div class="modal-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" id="editQty" min="0">
                </div>
                <div class="modal-group">
                    <label>Stock</label>
                    <input type="number" name="stock" id="editStock" min="0">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-save"></i> Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="del-modal-box">
        <div class="del-modal-icon"><i class="fas fa-trash-alt"></i></div>
        <h3>Delete Product?</h3>
        <p id="deleteMsg">This will permanently delete this product.</p>
        <div class="modal-actions" style="justify-content:center;margin-top:0;">
            <form id="deleteForm" method="POST" action="/APN-Solar/masters/products/save.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> Yes, Delete</button>
            </form>
            <button class="btn btn-secondary btn-sm" onclick="closeModal('deleteModal')">Cancel</button>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createName').value='';
    document.getElementById('createDesc').value='';
    document.getElementById('createQty').value=0;
    document.getElementById('createStock').value=0;
    document.getElementById('createModal').classList.add('open');
    setTimeout(()=>document.getElementById('createName').focus(),100);
}
function openEditModal(id,name,desc,qty,stock) {
    document.getElementById('editId').value=id;
    document.getElementById('editName').value=name;
    document.getElementById('editDesc').value=desc;
    document.getElementById('editQty').value=qty;
    document.getElementById('editStock').value=stock;
    document.getElementById('editModal').classList.add('open');
}
function prodConfirmDelete(id,name) {
    document.getElementById('deleteId').value=id;
    document.getElementById('deleteMsg').textContent='Delete product "'+name+'"? This cannot be undone.';
    document.getElementById('deleteModal').classList.add('open');
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open');}));

function prodFilter(q) {
    q=q.toLowerCase();
    document.querySelectorAll('#prodTable tbody tr').forEach(r=>{r.classList.toggle('hidden-row',q!==''&&!r.innerText.toLowerCase().includes(q));});
}
let pSortCol=-1,pSortAsc=true;
function prodSort(col) {
    const ths=document.querySelectorAll('#prodTable thead th');
    ths.forEach(t=>t.classList.remove('sort-asc','sort-desc'));
    if(pSortCol===col){pSortAsc=!pSortAsc;}else{pSortCol=col;pSortAsc=true;}
    ths[col].classList.add(pSortAsc?'sort-asc':'sort-desc');
    const tbody=document.querySelector('#prodTable tbody');
    const rows=[...tbody.querySelectorAll('tr')].filter(r=>!r.querySelector('[colspan]'));
    rows.sort((a,b)=>{const va=a.cells[col]?.innerText.trim()||'',vb=b.cells[col]?.innerText.trim()||'';const na=parseFloat(va),nb=parseFloat(vb);const c=(!isNaN(na)&&!isNaN(nb))?na-nb:va.localeCompare(vb);return pSortAsc?c:-c;});
    rows.forEach(r=>tbody.appendChild(r));
}
</script>

<?php include __DIR__ . '/../../views/partials/footer.php'; ?>
