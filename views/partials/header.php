<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'AROGYA Solar Power'); ?></title>
    <meta name="description" content="AROGYA Solar Power Admin Dashboard">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/APN-Solar/assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Main Header -->
        <header class="main-header">
            <!-- Logo -->
            <a href="/APN-Solar/index.php" class="logo-area">
                <img src="https://ui-avatars.com/api/?name=AS&background=2c6e49&color=fff&rounded=true" alt="Arogya Solar">
                <span><b>AROGYA Solar</b> Power</span>
            </a>

            <!-- Navbar -->
            <nav class="navbar">
                <ul class="nav-links">
                    <li><a href="#" onclick="toggleSidebar()"><i class="fas fa-bars"></i></a></li>
                </ul>
                <ul class="nav-links nav-right">
                    <li><a href="#"><i class="fas fa-search"></i></a></li>
                    <li><a href="#"><i class="fas fa-expand-arrows-alt"></i></a></li>
                    <li>
                        <a href="#" style="display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-user-circle" style="font-size:18px;"></i>
                            <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
                        </a>
                    </li>
                    <li><a href="/APN-Solar/public/logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i></a></li>
                </ul>
            </nav>
        </header>

        <div class="page-container">
            <?php include __DIR__ . '/sidebar.php'; ?>
            <!-- Main Content -->
            <main class="main-content">
