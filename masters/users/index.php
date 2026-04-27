<?php
// masters/users/index.php — User Master

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle    = 'User Master';
$validPerPage = [10, 25, 50, 100];
$requestedPer = (int)($_GET['per_page'] ?? 10);
$perPage      = in_array($requestedPer, $validPerPage) ? $requestedPer : 10;
$page         = max(1, (int)($_GET['page'] ?? 1));
$search       = trim($_GET['search'] ?? '');

$where  = "WHERE 1=1";
$params = [];
if ($search !== '') {
    $where   .= " AND (name LIKE ? OR email LIKE ? OR role LIKE ?)";
    $like     = "%$search%";
    $params   = [$like, $like, $like];
}

try {
    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
    $cStmt->execute($params);
    $totalRecords = (int)$cStmt->fetchColumn();
} catch (PDOException $e) { $totalRecords = 0; }

$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

try {
    $stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY id ASC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) { $users = []; }

$flash = null;
if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

include __DIR__ . '/../../views/partials/header.php';

function usrUrl(array $ov = []): string {
    $p = array_merge(['search' => $_GET['search'] ?? '', 'page' => $_GET['page'] ?? 1, 'per_page' => $_GET['per_page'] ?? 10], $ov);
    $q = http_build_query(array_filter($p, fn($v) => $v !== ''));
    return '/APN-Solar/masters/users/index.php' . ($q ? '?' . $q : '');
}
?>

<style>
.um-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px; }
.um-header h2 { font-size:1.15rem;font-weight:700;color:#1e293b; }
.btn { display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:7px;font-size:.83rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;white-space:nowrap; }
.btn-primary  { background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff; }
.btn-warning  { background:#f59e0b;color:#fff; }
.btn-danger   { background:#ef4444;color:#fff; }
.btn-success  { background:#22c55e;color:#fff; }
.btn-secondary{ background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0; }
.btn-sm       { padding:4px 11px;font-size:.75rem; }
.btn:hover    { opacity:.87;transform:translateY(-1px); }
.action-btns  { display:flex;gap:4px;flex-wrap:wrap; }

.ctrl-bar { display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px; }
.show-wrap { display:flex;align-items:center;gap:6px;font-size:.82rem;color:#64748b; }
.show-wrap select { padding:4px 8px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.82rem; }
.search-wrap { display:flex;align-items:center;gap:6px;font-size:.82rem;color:#64748b; }
.search-wrap input { padding:6px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.82rem;font-family:inherit;outline:none;width:210px; }
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

/* Avatar */
.user-avatar { width:34px;height:34px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:.82rem;color:#fff;flex-shrink:0; }

/* Role badges */
.badge-role { display:inline-block;border-radius:20px;padding:3px 10px;font-size:.7rem;font-weight:700; }
.role-superadmin { background:#fde68a;color:#78350f; }
.role-admin      { background:#dbeafe;color:#1e40af; }
.role-operator   { background:#dcfce7;color:#15803d; }

/* Status toggle */
.status-badge { display:inline-flex;align-items:center;gap:5px;border-radius:20px;padding:3px 10px;font-size:.7rem;font-weight:700; }
.status-active   { background:#dcfce7;color:#15803d; }
.status-inactive { background:#fee2e2;color:#dc2626; }

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
.modal-box { background:#fff;border-radius:12px;padding:32px 36px;max-width:420px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25); }
.modal-icon { font-size:2.8rem;color:#ef4444;margin-bottom:14px; }
.modal-box h3 { font-size:1.1rem;font-weight:700;color:#1e293b;margin-bottom:8px; }
.modal-box p  { color:#64748b;font-size:.88rem;margin-bottom:18px; }
.modal-actions { display:flex;gap:10px;justify-content:center; }
</style>

<?php if ($flash): ?>
<div class="flash-alert flash-<?php echo $flash['type']==='success'?'success':'error'; ?>">
    <i class="fas fa-<?php echo $flash['type']==='success'?'check-circle':'exclamation-circle'; ?>"></i>
    <?php echo htmlspecialchars($flash['message']); ?>
</div>
<?php endif; ?>

<div class="um-header">
    <h2><i class="fas fa-users" style="color:#3b82f6;margin-right:7px;"></i>User Master
        <span style="font-weight:400;color:#94a3b8;font-size:.85rem;margin-left:5px;">(<?php echo number_format($totalRecords); ?> users)</span>
    </h2>
    <a href="/APN-Solar/masters/users/create.php" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Add New User
    </a>
</div>

<div class="ctrl-bar">
    <form method="GET" action="/APN-Solar/masters/users/index.php" class="show-wrap">
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
        <input type="text" id="usrSearch" placeholder="Name, email, role..."
               oninput="usrFilter(this.value)" value="<?php echo htmlspecialchars($search); ?>">
    </div>
</div>

<div class="table-wrap">
    <div class="table-responsive">
        <table class="table" id="usrTable">
            <thead>
                <tr>
                    <th class="sortable" onclick="usrSort(0)" style="width:55px;">Sr No.</th>
                    <th class="sortable" onclick="usrSort(1)">User</th>
                    <th class="sortable" onclick="usrSort(2)">Email</th>
                    <th class="sortable" onclick="usrSort(3)">Role</th>
                    <th class="sortable" onclick="usrSort(4)">Status</th>
                    <th class="sortable" onclick="usrSort(5)">Created At</th>
                    <th style="width:160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($users):
                $avColors = ['#3b82f6','#8b5cf6','#ec4899','#f59e0b','#10b981','#ef4444','#06b6d4'];
                $rowNum = ($page-1)*$perPage+1;
                foreach ($users as $u):
                    $initials = strtoupper(substr(trim($u['name']),0,1));
                    $bgColor  = $avColors[($u['id']-1) % count($avColors)];
                    $role     = $u['role'] ?? 'admin';
                    $isActive = (int)($u['is_active'] ?? 1);
                ?>
                <tr>
                    <td><?php echo $rowNum++; ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="user-avatar" style="background:<?php echo $bgColor; ?>;"><?php echo $initials; ?></div>
                            <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                        </div>
                    </td>
                    <td style="color:#64748b;"><?php echo htmlspecialchars($u['email']); ?></td>
                    <td>
                        <span class="badge-role role-<?php echo htmlspecialchars($role); ?>">
                            <?php echo ucfirst(str_replace('_',' ',$role)); ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $isActive ? 'status-active' : 'status-inactive'; ?>">
                            <i class="fas fa-circle" style="font-size:.45rem;"></i>
                            <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td style="font-size:.76rem;color:#64748b;"><?php echo htmlspecialchars(substr($u['created_at'] ?? '', 0, 16)); ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="/APN-Solar/masters/users/edit.php?id=<?php echo (int)$u['id']; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </a>
                            <a href="/APN-Solar/masters/users/toggle.php?id=<?php echo (int)$u['id']; ?>"
                               class="btn btn-sm <?php echo $isActive ? 'btn-secondary' : 'btn-success'; ?>"
                               title="<?php echo $isActive ? 'Deactivate' : 'Activate'; ?>">
                                <i class="fas fa-<?php echo $isActive ? 'ban' : 'check'; ?>"></i>
                            </a>
                            <button class="btn btn-danger btn-sm"
                                    onclick="usrConfirmDelete(<?php echo (int)$u['id']; ?>,'<?php echo addslashes(htmlspecialchars($u['name'])); ?>')">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">
                    <i class="fas fa-users" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                    No users found<?php echo $search ? ' matching your search.' : '.'; ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="pagination-bar">
        <span>Showing <strong><?php echo $totalRecords>0?number_format(($page-1)*$perPage+1):0; ?>–<?php echo number_format(min($page*$perPage,$totalRecords)); ?></strong> of <strong><?php echo number_format($totalRecords); ?></strong> entries</span>
        <div class="pagination">
            <?php if($page>1):?><a class="pg-btn" href="<?php echo usrUrl(['page'=>$page-1]);?>">Previous</a><?php else:?><span class="pg-btn disabled">Previous</span><?php endif;?>
            <?php $ws=max(1,$page-2);$we=min($totalPages,$page+2);
            if($ws>1){echo '<a class="pg-btn" href="'.usrUrl(['page'=>1]).'">1</a>';if($ws>2)echo '<span class="pg-btn disabled">…</span>';}
            for($pp=$ws;$pp<=$we;$pp++) echo '<a class="pg-btn '.($pp===$page?'active':'').'" href="'.usrUrl(['page'=>$pp]).'">'.$pp.'</a>';
            if($we<$totalPages){if($we<$totalPages-1)echo '<span class="pg-btn disabled">…</span>';echo '<a class="pg-btn" href="'.usrUrl(['page'=>$totalPages]).'">'.$totalPages.'</a>';}
            ?>
            <?php if($page<$totalPages):?><a class="pg-btn" href="<?php echo usrUrl(['page'=>$page+1]);?>">Next</a><?php else:?><span class="pg-btn disabled">Next</span><?php endif;?>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="modal-icon"><i class="fas fa-user-times"></i></div>
        <h3>Delete User?</h3>
        <p id="deleteMsg">This will permanently delete this user account.</p>
        <div class="modal-actions">
            <form id="deleteForm" method="POST" action="/APN-Solar/masters/users/delete.php">
                <input type="hidden" name="id" id="deleteId">
                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> Yes, Delete</button>
            </form>
            <button class="btn btn-secondary btn-sm" onclick="document.getElementById('deleteModal').classList.remove('open')">Cancel</button>
        </div>
    </div>
</div>

<script>
function usrFilter(q) {
    q=q.toLowerCase();
    document.querySelectorAll('#usrTable tbody tr').forEach(r=>{r.classList.toggle('hidden-row',q!==''&&!r.innerText.toLowerCase().includes(q));});
}
let uSortCol=-1,uSortAsc=true;
function usrSort(col) {
    const ths=document.querySelectorAll('#usrTable thead th');
    ths.forEach(t=>t.classList.remove('sort-asc','sort-desc'));
    if(uSortCol===col){uSortAsc=!uSortAsc;}else{uSortCol=col;uSortAsc=true;}
    ths[col].classList.add(uSortAsc?'sort-asc':'sort-desc');
    const tbody=document.querySelector('#usrTable tbody');
    const rows=[...tbody.querySelectorAll('tr')].filter(r=>!r.querySelector('[colspan]'));
    rows.sort((a,b)=>{const va=a.cells[col]?.innerText.trim()||'',vb=b.cells[col]?.innerText.trim()||'';return uSortAsc?va.localeCompare(vb):vb.localeCompare(va);});
    rows.forEach(r=>tbody.appendChild(r));
}
function usrConfirmDelete(id,name) {
    document.getElementById('deleteId').value=id;
    document.getElementById('deleteMsg').textContent='Delete user "'+name+'"? This cannot be undone.';
    document.getElementById('deleteModal').classList.add('open');
}
document.getElementById('deleteModal').addEventListener('click',e=>{if(e.target===document.getElementById('deleteModal'))document.getElementById('deleteModal').classList.remove('open');});
</script>

<?php include __DIR__ . '/../../views/partials/footer.php'; ?>
