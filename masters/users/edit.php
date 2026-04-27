<?php
// masters/users/edit.php — Edit a user
require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: /APN-Solar/masters/users/index.php"); exit; }
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([$id]); $user = $stmt->fetch();
if (!$user) { setFlash('error','User not found.'); header("Location: /APN-Solar/masters/users/index.php"); exit; }

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = in_array($_POST['role']??'', ['superadmin','admin','operator']) ? $_POST['role'] : 'admin';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    if (!$name || !$email) { $error = "Name and email are required."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = "Invalid email."; }
    else {
        try {
            if ($password !== '') {
                if (strlen($password) < 6) { $error = "Password must be at least 6 characters."; goto done; }
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET name=?,email=?,password=?,role=?,is_active=? WHERE id=?")->execute([$name,$email,$hashed,$role,$isActive,$id]);
            } else {
                $pdo->prepare("UPDATE users SET name=?,email=?,role=?,is_active=? WHERE id=?")->execute([$name,$email,$role,$isActive,$id]);
            }
            setFlash('success','User updated successfully.'); header("Location: /APN-Solar/masters/users/index.php"); exit;
        } catch (PDOException $e) { $error = ($e->getCode()==23000)?'Email already exists.':'Error: '.$e->getMessage(); }
    }
    done:
    $user = array_merge($user, ['name'=>$name,'email'=>$email,'role'=>$role,'is_active'=>$isActive]);
}
$pageTitle = 'Edit User'; include __DIR__ . '/../../views/partials/header.php';
?>
<style>
.page-card{background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.12);padding:28px 32px;max-width:560px;}
.page-title{font-size:1.1rem;font-weight:700;color:#1e293b;margin-bottom:18px;display:flex;align-items:center;gap:9px;}.page-title i{color:#f59e0b;}
.badge-id{background:#f1f5f9;color:#64748b;font-size:.72rem;padding:3px 10px;border-radius:20px;font-weight:600;}
.fg{margin-bottom:14px;}.fg label{display:block;font-size:.71rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;}
.fg input,.fg select{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.88rem;font-family:inherit;color:#1e293b;background:#f8fafc;outline:none;box-sizing:border-box;}
.fg input:focus,.fg select:focus{border-color:#f59e0b;background:#fff;}
.check-row{display:flex;align-items:center;gap:8px;font-size:.87rem;color:#374151;}.check-row input[type=checkbox]{width:16px;height:16px;}
.hint{font-size:.72rem;color:#94a3b8;margin-top:3px;}
.form-actions{display:flex;gap:11px;margin-top:18px;padding-top:16px;border-top:1px solid #e2e8f0;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 22px;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;}
.btn-warning{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;}.btn-secondary{background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0;}
.btn:hover{opacity:.87;transform:translateY(-1px);}
.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.875rem;}
.pw-wrap{position:relative;}.pw-wrap input{padding-right:40px;}.pw-toggle{position:absolute;right:11px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;font-size:.9rem;}
</style>
<div class="page-card">
    <div class="page-title"><i class="fas fa-pencil-alt"></i> Edit User <span class="badge-id">ID #<?php echo $id;?></span></div>
    <?php if($error):?><div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error);?></div><?php endif;?>
    <form method="POST" action="">
        <div class="fg"><label>Full Name <span style="color:#dc2626">*</span></label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']);?>" required></div>
        <div class="fg"><label>Email Address <span style="color:#dc2626">*</span></label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']);?>" required></div>
        <div class="fg"><label>New Password</label>
            <div class="pw-wrap">
                <input type="password" name="password" id="pwInput" placeholder="Leave blank to keep current">
                <button type="button" class="pw-toggle" onclick="togglePw()"><i class="fas fa-eye" id="pwIcon"></i></button>
            </div>
            <div class="hint">Leave blank to keep the current password.</div>
        </div>
        <div class="fg"><label>Role</label>
            <select name="role">
                <option value="admin"      <?php echo ($user['role']??'admin')==='admin'?'selected':'';?>>Admin</option>
                <option value="operator"   <?php echo ($user['role']??'')==='operator'?'selected':'';?>>Operator</option>
                <option value="superadmin" <?php echo ($user['role']??'')==='superadmin'?'selected':'';?>>Super Admin</option>
            </select>
        </div>
        <div class="fg"><label class="check-row"><input type="checkbox" name="is_active" value="1" <?php echo (int)($user['is_active']??1)?'checked':'';?>> Active User</label></div>
        <div class="form-actions">
            <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Update User</button>
            <a href="/APN-Solar/masters/users/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
        </div>
    </form>
</div>
<script>function togglePw(){const i=document.getElementById('pwInput'),ic=document.getElementById('pwIcon');i.type=i.type==='password'?'text':'password';ic.className=i.type==='password'?'fas fa-eye':'fas fa-eye-slash';}</script>
<?php include __DIR__ . '/../../views/partials/footer.php'; ?>
