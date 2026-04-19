<!-- views/partials/sidebar.php -->
<?php
$currentUri = strtok($_SERVER['REQUEST_URI'], '?');
?>
<aside class="sidebar">
    <div class="search-container">
        <div class="input-group">
            <input type="text" id="sidebarSearch" placeholder="Search menu..." oninput="filterSidebar(this.value)">
            <button><i class="fas fa-search"></i></button>
        </div>
    </div>

    <ul class="sidebar-menu" id="sidebarMenu">

        <!-- Dashboard -->
        <li class="<?php echo $currentUri === '/APN-Solar/index.php' || $currentUri === '/APN-Solar/' ? 'active' : ''; ?>">
            <a href="/APN-Solar/index.php">
                <i class="nav-icon fas fa-tachometer-alt"></i> Dashboard Master
            </a>
        </li>

        <!-- Customer Management -->
        <li class="has-treeview <?php echo strpos($currentUri, '/APN-Solar/customers') === 0 ? 'menu-open' : ''; ?>">
            <a href="#" onclick="toggleMenu(this)">
                <i class="nav-icon fas fa-users"></i>
                Customer Management
                <i class="fas fa-angle-left right-icon"></i>
            </a>
            <ul class="nav-treeview">
                <li><a href="/APN-Solar/customers/"><i class="far fa-file-alt"></i> Customer Registration</a></li>
                <li><a href="/APN-Solar/customers/not_interested.php"><i class="far fa-file-alt"></i> Not Interested Customer</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Current Status</a></li>
                <li><a href="/APN-Solar/customers/pending_list.php"><i class="far fa-file-alt"></i> Pending Customer List</a></li>
                <li><a href="/APN-Solar/customers/payment_due.php"><i class="far fa-file-alt"></i> Payment Due list</a></li>
                <li><a href="/APN-Solar/customers/payment_received.php"><i class="far fa-file-alt"></i> Payment Received list</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Received Subsidy First</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Received Subsidy Second</a></li>
            </ul>
        </li>

        <!-- Reports -->
        <li class="has-treeview">
            <a href="#" onclick="toggleMenu(this)">
                <i class="nav-icon fas fa-chart-bar"></i>
                Reports
                <i class="fas fa-angle-left right-icon"></i>
            </a>
            <ul class="nav-treeview">
                <li><a href="/APN-Solar/reports/pending_installation.php"><i class="far fa-file-alt"></i> Pending Installation</a></li>
                <li><a href="/APN-Solar/reports/pending_net_metering.php"><i class="far fa-file-alt"></i> Pending Netmetering</a></li>
                <li><a href="/APN-Solar/reports/pending_online_installation.php"><i class="far fa-file-alt"></i> Pending Online Installation</a></li>
                <li><a href="/APN-Solar/reports/pending_subsidy_first.php"><i class="far fa-file-alt"></i> Pending Subsidy First</a></li>
                <li><a href="/APN-Solar/reports/pending_subsidy_second.php"><i class="far fa-file-alt"></i> Pending Subsidy Second</a></li>
                <li><a href="/APN-Solar/reports/pending_account_number.php"><i class="far fa-file-alt"></i> Pending Account Number</a></li>
                <li><a href="/APN-Solar/reports/first_materials_dispatched.php"><i class="far fa-file-alt"></i> First Materials Dispatched</a></li>
                <li><a href="/APN-Solar/reports/second_materials_dispatched.php"><i class="far fa-file-alt"></i> Second Materials Dispatched</a></li>
                <li><a href="/APN-Solar/reports/customer_registration_pending.php"><i class="far fa-file-alt"></i> Customer Registration Pending</a></li>
                <li><a href="/APN-Solar/reports/customer_jan_samarth_pending.php"><i class="far fa-file-alt"></i> Customer Jan Samarth Pending</a></li>
            </ul>
        </li>

        <!-- Masters -->
        <li><a href="/APN-Solar/masters/group/index.php"><i class="nav-icon fas fa-layer-group"></i> Group Master</a></li>
        <li><a href="/APN-Solar/masters/payments/index.php"><i class="nav-icon fas fa-money-bill-wave"></i> Payments Master</a></li>
        <li><a href="/APN-Solar/masters/dispatch/index.php"><i class="nav-icon fas fa-truck"></i> Dispatch Master</a></li>
        <li><a href="/APN-Solar/masters/products/index.php"><i class="nav-icon fas fa-solar-panel"></i> Products Master</a></li>
        <li><a href="/APN-Solar/masters/users/index.php"><i class="nav-icon fas fa-user-cog"></i> User Master</a></li>
        <li><a href="/APN-Solar/masters/access/index.php"><i class="nav-icon fas fa-shield-alt"></i> Access Master</a></li>

        <!-- Other Modules -->
        <li><a href="/APN-Solar/complaints/index.php"><i class="nav-icon fas fa-exclamation-circle"></i> Complaints Management</a></li>
        <li><a href="/APN-Solar/leads/index.php"><i class="nav-icon fas fa-funnel-dollar"></i> Lead Management</a></li>
        <li><a href="/APN-Solar/followup/index.php"><i class="nav-icon fas fa-phone-alt"></i> Customer Follow Up</a></li>
        <li><a href="/APN-Solar/villages/index.php"><i class="nav-icon fas fa-map-marker-alt"></i> Manage Villages</a></li>
        <li><a href="/APN-Solar/gram_panchayats/index.php"><i class="nav-icon fas fa-landmark"></i> Manage Gram Panchayats</a></li>

        <!-- Reports (Centralized) -->
        <li><a href="/APN-Solar/reports/centralized_report.php"><i class="nav-icon fas fa-file-excel"></i> Get Centralized Report</a></li>
        <li><a href="/APN-Solar/reports/payment_report.php"><i class="nav-icon fas fa-file-invoice-dollar"></i> Get Payment Report</a></li>
        <li><a href="/APN-Solar/reports/group_wise_report.php"><i class="nav-icon fas fa-file-alt"></i> Get Group Wise Report</a></li>

    </ul>
</aside>
