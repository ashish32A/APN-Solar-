<!-- includes/sidebar.php -->
<aside class="sidebar">
    <div class="search-container">
        <div class="input-group">
            <input type="text" placeholder="search">
            <button><i class="fas fa-search"></i></button>
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li class="active">
            <a href="index.php">
                <i class="nav-icon far fa-file-alt"></i>
                Dashboard Master
            </a>
        </li>
        
        <li class="has-treeview menu-open">
            <a href="#" onclick="toggleMenu(this)">
                <i class="nav-icon fas fa-users"></i>
                Customer Management
                <i class="fas fa-angle-down right-icon"></i>
            </a>
            <ul class="nav-treeview">
                <li><a href="customers.php"><i class="far fa-file-alt"></i> Customer Registration</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Not Interested Customer</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Current Status</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Pending Customer List</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Payment Due list</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Payment Received list</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Received Subsidy First</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Received Subsidy Second</a></li>
            </ul>
        </li>
        
        <li class="has-treeview">
            <a href="#" onclick="toggleMenu(this)">
                <i class="nav-icon fas fa-users"></i>
                Reports
                <i class="fas fa-angle-left right-icon"></i>
            </a>
            <ul class="nav-treeview">
                <li><a href="#"><i class="far fa-file-alt"></i> Pending Installation</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Pending Netmetring</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Pending Online Installation</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Pending Subsidy First</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Pending Subsidy Second</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Pending Account Number</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> First Materials Dispatched</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Second Materials Dispatched</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Customer Registration Pending</a></li>
                <li><a href="#"><i class="far fa-file-alt"></i> Customer Jan samarth Pending</a></li>
            </ul>
        </li>
        
        <li><a href="#"><i class="nav-icon fas fa-user"></i> Group Master</a></li>
        <li><a href="#"><i class="nav-icon far fa-file-alt"></i> Payments Master</a></li>
        <li><a href="#"><i class="nav-icon far fa-file-alt"></i> Dispatch Master</a></li>
        <li><a href="#"><i class="nav-icon far fa-file-alt"></i> Products Master</a></li>
        <li><a href="#"><i class="nav-icon far fa-file-alt"></i> User Master</a></li>
        <li><a href="#"><i class="nav-icon far fa-file-alt"></i> Access Master</a></li>
        <li><a href="#"><i class="nav-icon far fa-file-alt"></i> Complaints Management</a></li>
        <li><a href="#"><i class="nav-icon far fa-file-alt"></i> Lead Management</a></li>
        <li><a href="#"><i class="nav-icon far fa-file-alt"></i> Customer Follow Up</a></li>
        <li><a href="#"><i class="nav-icon far fa-file-alt"></i> Manage villages</a></li>
        <li><a href="#"><i class="nav-icon far fa-file-alt"></i> Manage gram panchayats</a></li>
        <li><a href="#"><i class="nav-icon far fa-file-alt"></i> Get Centerlized Report</a></li>
        <li><a href="#"><i class="nav-icon far fa-file-alt"></i> Get Payment Report</a></li>
        <li><a href="#"><i class="nav-icon far fa-file-alt"></i> Get Group Wise Report</a></li>
    </ul>
</aside>
