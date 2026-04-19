<?php
// views/layouts/main.php — Main layout wrapper

$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?> | AROGYA Solar Power</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/APN-Solar/assets/css/style.css">
</head>
<body>
<div class="wrapper">
  <?php include __DIR__ . '/../partials/header.php'; ?>
  <div class="page-container">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
    <main class="main-content">
      <?php include __DIR__ . '/../partials/flash.php'; ?>
      <?php echo $content ?? ''; ?>
    </main>
  </div>
</div>
<?php include __DIR__ . '/../partials/footer_scripts.php'; ?>
</body>
</html>
