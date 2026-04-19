<?php
// customers/delete.php — Delete Customer (POST only, with CSRF-like token)

require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$id) {
    header("Location: /APN-Solar/customers/");
    exit;
}

// Verify customer exists
$stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Customer not found.');
    header("Location: /APN-Solar/customers/");
    exit;
}

// On POST confirmation — delete cascade (payments & installations deleted via FK ON DELETE CASCADE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
        setFlash('success', 'Customer "' . htmlspecialchars($customer['name']) . '" deleted successfully.');
    } catch (PDOException $e) {
        setFlash('error', 'Delete failed: ' . $e->getMessage());
    }
    header("Location: /APN-Solar/customers/");
    exit;
}

// Show confirmation page (GET)
$pageTitle = 'Delete Customer';
include __DIR__ . '/../views/partials/header.php';
?>

<style>
.confirm-card { background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,.12); padding:40px 44px; max-width:520px; text-align:center; }
.confirm-icon { font-size:3rem; color:#dc2626; margin-bottom:20px; }
.confirm-card h2 { font-size:1.3rem; font-weight:700; color:#1e293b; margin-bottom:10px; }
.confirm-card p { color:#64748b; font-size:.95rem; margin-bottom:6px; }
.confirm-name { font-weight:700; color:#1e293b; font-size:1rem; background:#fef2f2; border:1px solid #fecaca; padding:10px 18px; border-radius:8px; margin:16px 0; display:inline-block; }
.confirm-actions { display:flex; gap:12px; justify-content:center; margin-top:28px; }
.btn { display:inline-flex; align-items:center; gap:7px; padding:11px 24px; border-radius:8px;
       font-size:.9rem; font-weight:600; cursor:pointer; border:none; font-family:inherit;
       transition:all .15s; text-decoration:none; }
.btn-danger { background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; box-shadow:0 4px 12px rgba(239,68,68,.3); }
.btn-danger:hover { transform:translateY(-1px); box-shadow:0 6px 16px rgba(239,68,68,.4); }
.btn-secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.btn-secondary:hover { background:#e2e8f0; }
</style>

<div class="confirm-card">
    <div class="confirm-icon">
        <i class="fas fa-trash-alt"></i>
    </div>
    <h2>Delete Customer</h2>
    <p>Are you sure you want to permanently delete:</p>
    <div class="confirm-name">
        <i class="fas fa-user" style="margin-right:6px;color:#dc2626;"></i>
        <?php echo htmlspecialchars($customer['name']); ?> (ID #<?php echo $id; ?>)
    </div>
    <p style="color:#dc2626; font-size:.85rem; margin-top:8px;">
        <i class="fas fa-exclamation-triangle"></i>
        This will also delete all related payments and installations.
    </p>

    <div class="confirm-actions">
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash-alt"></i> Yes, Delete
            </button>
        </form>
        <a href="/APN-Solar/customers/" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel
        </a>
    </div>
</div>

<?php include __DIR__ . '/../views/partials/footer.php'; ?>
