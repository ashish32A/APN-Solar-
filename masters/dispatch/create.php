<?php
// masters/dispatch/create.php — Create New Dispatch (with dynamic product rows)

require_once __DIR__ . '/../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../app/Helpers/FlashHelper.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

// ── Ensure dispatch_items table exists ────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS dispatch_items (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        dispatch_id INT NOT NULL,
        product_id  INT DEFAULT NULL,
        product_name VARCHAR(200) DEFAULT NULL,
        quantity    INT DEFAULT 1,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (dispatch_id) REFERENCES dispatches(id) ON DELETE CASCADE
    )");
} catch (PDOException $e) {}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId  = (int)($_POST['customer_id'] ?? 0);
    $dispatchNo  = trim($_POST['dispatch_no'] ?? '');
    $dispatchDate= trim($_POST['dispatch_date'] ?? '') ?: null;
    $type        = in_array($_POST['type'] ?? '', ['first','second']) ? $_POST['type'] : 'first';
    $driver      = trim($_POST['driver'] ?? '');
    $van         = trim($_POST['van'] ?? '');
    $mobile      = trim($_POST['mobile'] ?? '');
    $status      = in_array($_POST['status'] ?? '', ['Pending','Dispatched','Delivered']) ? $_POST['status'] : 'Pending';
    $notes       = trim($_POST['notes'] ?? '');

    // Product rows
    $productIds   = $_POST['product_id']   ?? [];
    $quantities   = $_POST['quantity']     ?? [];

    if (!$customerId) {
        $error = "Please select a customer.";
    } else {
        try {
            $pdo->beginTransaction();

            $pdo->prepare("INSERT INTO dispatches
                (customer_id, dispatch_no, dispatch_date, type, driver, van, mobile, status, notes)
                VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$customerId, $dispatchNo, $dispatchDate, $type, $driver, $van, $mobile, $status, $notes]);

            $dispatchId = (int)$pdo->lastInsertId();

            // Auto-number if blank
            if ($dispatchNo === '') {
                $pdo->prepare("UPDATE dispatches SET dispatch_no = CONCAT('CPD-', LPAD(id, 5, '0')) WHERE id = ?")->execute([$dispatchId]);
            }

            // Insert product rows
            $itemStmt = $pdo->prepare("INSERT INTO dispatch_items (dispatch_id, product_id, product_name, quantity) VALUES (?,?,?,?)");
            foreach ($productIds as $i => $pid) {
                $pid = (int)$pid;
                $qty = max(1, (int)($quantities[$i] ?? 1));
                if ($pid) {
                    // Get product name
                    $pName = $pdo->prepare("SELECT name FROM products WHERE id=?");
                    $pName->execute([$pid]);
                    $pName = $pName->fetchColumn() ?: '';
                    $itemStmt->execute([$dispatchId, $pid, $pName, $qty]);
                }
            }

            $pdo->commit();
            setFlash('success', 'Dispatch created successfully.');
            header("Location: /APN-Solar/masters/dispatch/index.php");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Customers for dropdown
try {
    $customers = $pdo->query("SELECT id, name, mobile FROM customers ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) { $customers = []; }

// Products for dropdown
try {
    $products = $pdo->query("SELECT id, name FROM products ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) { $products = []; }

$pageTitle = 'Create New Dispatch';
include __DIR__ . '/../../views/partials/header.php';
?>

<style>
.page-card { background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.12);padding:28px 32px;max-width:860px; }
.page-title { font-size:1.15rem;font-weight:700;color:#1e293b;margin-bottom:20px;display:flex;align-items:center;gap:10px; }
.page-title i { color:#3b82f6; }

.form-grid { display:grid;grid-template-columns:1fr 1fr;gap:16px; }
.form-grid .full { grid-column:1/-1; }
.form-group label { display:block;font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px; }
.form-group input, .form-group select, .form-group textarea {
    width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:7px;
    font-size:.88rem;font-family:inherit;color:#1e293b;background:#f8fafc;outline:none;
    transition:border-color .2s;box-sizing:border-box;
}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus { border-color:#3b82f6;background:#fff; }
.form-group textarea { resize:vertical;min-height:80px; }
.section-label { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;
                  padding-bottom:5px;border-bottom:1px solid #e2e8f0;grid-column:1/-1;margin-top:8px; }

/* Products table */
.products-section { grid-column:1/-1;margin-top:4px; }
.products-section .sec-head { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;
                               color:#94a3b8;padding-bottom:5px;border-bottom:1px solid #e2e8f0;margin-bottom:10px; }
.product-row { display:grid;grid-template-columns:1fr 120px 40px;gap:8px;align-items:center;margin-bottom:8px; }
.product-row select, .product-row input {
    padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:7px;
    font-size:.87rem;font-family:inherit;color:#1e293b;background:#f8fafc;outline:none;
    transition:border-color .2s;width:100%;box-sizing:border-box;
}
.product-row select:focus, .product-row input:focus { border-color:#3b82f6;background:#fff; }
.product-row-labels { display:grid;grid-template-columns:1fr 120px 40px;gap:8px;margin-bottom:4px; }
.product-row-labels span { font-size:.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em; }
.rm-btn { display:flex;align-items:center;justify-content:center;width:36px;height:36px;background:#ef4444;
          color:#fff;border:none;border-radius:7px;cursor:pointer;font-size:.9rem;flex-shrink:0;transition:background .15s; }
.rm-btn:hover { background:#dc2626; }

.add-row-btn { display:inline-flex;align-items:center;gap:6px;padding:7px 15px;border-radius:7px;
               background:#f0fdf4;border:1.5px dashed #22c55e;color:#16a34a;font-size:.83rem;
               font-weight:600;cursor:pointer;margin-top:4px;transition:all .15s; }
.add-row-btn:hover { background:#dcfce7; }

.form-actions { display:flex;gap:12px;margin-top:20px;padding-top:18px;border-top:1px solid #e2e8f0; }
.btn { display:inline-flex;align-items:center;gap:7px;padding:10px 22px;border-radius:8px;font-size:.9rem;
       font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none; }
.btn-primary   { background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff; }
.btn-secondary { background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0; }
.btn:hover { opacity:.87;transform:translateY(-1px); }
.alert-error { background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:.875rem; }

.empty-products { display:flex;align-items:center;gap:8px;color:#94a3b8;font-size:.82rem;padding:10px;
                  background:#f8fafc;border:1.5px dashed #e2e8f0;border-radius:7px;margin-bottom:8px; }
</style>

<div class="page-card">
    <div class="page-title">
        <i class="fas fa-truck"></i> Create New Dispatch
    </div>

    <?php if ($error): ?>
        <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-grid">

            <div class="section-label">Dispatch Info</div>

            <div class="form-group full">
                <label>Customer <span style="color:#dc2626">*</span></label>
                <select name="customer_id" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>"
                            <?php echo ((int)($_POST['customer_id'] ?? 0) === (int)$c['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['mobile'] ?? ''); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Dispatch No.</label>
                <input type="text" name="dispatch_no" placeholder="e.g. CPD-00001 (auto if blank)"
                       value="<?php echo htmlspecialchars($_POST['dispatch_no'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Dispatch Date</label>
                <input type="datetime-local" name="dispatch_date"
                       value="<?php echo htmlspecialchars($_POST['dispatch_date'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Dispatch Type</label>
                <select name="type">
                    <option value="first"  <?php echo ($_POST['type'] ?? 'first') === 'first'  ? 'selected' : ''; ?>>First</option>
                    <option value="second" <?php echo ($_POST['type'] ?? '') === 'second' ? 'selected' : ''; ?>>Second</option>
                </select>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="Pending"    <?php echo ($_POST['status'] ?? 'Pending') === 'Pending'    ? 'selected' : ''; ?>>Pending</option>
                    <option value="Dispatched" <?php echo ($_POST['status'] ?? '') === 'Dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                    <option value="Delivered"  <?php echo ($_POST['status'] ?? '') === 'Delivered'  ? 'selected' : ''; ?>>Delivered</option>
                </select>
            </div>

            <div class="section-label">Vehicle &amp; Driver</div>

            <div class="form-group">
                <label>Driver Name</label>
                <input type="text" name="driver" placeholder="Driver's name"
                       value="<?php echo htmlspecialchars($_POST['driver'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Van / Vehicle</label>
                <input type="text" name="van" placeholder="Van number or name"
                       value="<?php echo htmlspecialchars($_POST['van'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Driver Mobile</label>
                <input type="text" name="mobile" placeholder="Mobile number"
                       value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>">
            </div>

            <div class="form-group full">
                <label>Remarks</label>
                <textarea name="notes" placeholder="Additional remarks..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
            </div>

            <!-- ── Products Section ── -->
            <div class="products-section">
                <div class="sec-head"><i class="fas fa-boxes" style="margin-right:5px;"></i>Products</div>

                <?php if (empty($products)): ?>
                    <div class="empty-products">
                        <i class="fas fa-info-circle"></i>
                        No products found. <a href="/APN-Solar/masters/products/index.php" style="color:#3b82f6;margin-left:4px;">Create products first →</a>
                    </div>
                <?php else: ?>
                    <div class="product-row-labels">
                        <span>Product</span>
                        <span>Quantity</span>
                        <span></span>
                    </div>
                    <div id="productRows">
                        <!-- First row (always shown) -->
                        <div class="product-row" id="prodRow0">
                            <select name="product_id[]">
                                <option value="">-- Select Product --</option>
                                <?php foreach ($products as $pr): ?>
                                    <option value="<?php echo (int)$pr['id']; ?>"><?php echo htmlspecialchars($pr['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="quantity[]" value="1" min="1" placeholder="Qty">
                            <button type="button" class="rm-btn" onclick="removeProductRow(this)" title="Remove" style="visibility:hidden;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="add-row-btn" onclick="addProductRow()">
                        <i class="fas fa-plus-circle"></i> Add More Product
                    </button>
                <?php endif; ?>
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Dispatch
            </button>
            <a href="/APN-Solar/masters/dispatch/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Cancel
            </a>
        </div>
    </form>
</div>

<!-- Hidden product options template for JS cloning -->
<template id="productOptionsTemplate">
    <?php foreach ($products as $pr): ?>
        <option value="<?php echo (int)$pr['id']; ?>"><?php echo htmlspecialchars($pr['name']); ?></option>
    <?php endforeach; ?>
</template>

<script>
let rowCount = 1;

function addProductRow() {
    const container = document.getElementById('productRows');
    const tmpl      = document.getElementById('productOptionsTemplate');

    const row = document.createElement('div');
    row.className = 'product-row';
    row.id = 'prodRow' + rowCount;

    // Build select
    const sel = document.createElement('select');
    sel.name = 'product_id[]';
    const defOpt = document.createElement('option');
    defOpt.value = ''; defOpt.textContent = '-- Select Product --';
    sel.appendChild(defOpt);
    // Clone options from template
    if (tmpl) {
        tmpl.content.querySelectorAll('option').forEach(o => sel.appendChild(o.cloneNode(true)));
    }

    // Qty input
    const qty = document.createElement('input');
    qty.type = 'number'; qty.name = 'quantity[]'; qty.value = 1; qty.min = 1; qty.placeholder = 'Qty';

    // Remove button
    const rm = document.createElement('button');
    rm.type = 'button'; rm.className = 'rm-btn'; rm.title = 'Remove';
    rm.innerHTML = '<i class="fas fa-times"></i>';
    rm.onclick = function() { removeProductRow(this); };

    row.appendChild(sel);
    row.appendChild(qty);
    row.appendChild(rm);
    container.appendChild(row);
    rowCount++;

    // Show remove button on first row now that we have more than 1
    updateRemoveButtons();
}

function removeProductRow(btn) {
    const row = btn.closest('.product-row');
    const container = document.getElementById('productRows');
    if (container.children.length > 1) {
        row.remove();
        updateRemoveButtons();
    }
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('#productRows .product-row');
    rows.forEach((r, i) => {
        const btn = r.querySelector('.rm-btn');
        if (btn) btn.style.visibility = rows.length > 1 ? 'visible' : 'hidden';
    });
}
</script>

<?php include __DIR__ . '/../../views/partials/footer.php'; ?>
