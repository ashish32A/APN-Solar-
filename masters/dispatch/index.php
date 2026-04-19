<?php
require_once __DIR__ . '/../../../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../config/database.php';
requireLogin();
$pageTitle = 'Dispatch Master';
include __DIR__ . '/../../../views/partials/header.php';
?>
<div style="padding:20px;">
  <h2 style="margin-bottom:16px;">Dispatch Master</h2>
  <p style="color:#6c757d;">This module is under development. Connect your controller and view here.</p>
</div>
<?php include __DIR__ . '/../../../views/partials/footer.php'; ?>
