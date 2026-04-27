<?php
// views/dashboard/index.php — Dashboard view
// Variables available: $totalCustomers, $totalPendingInstallations, $paymentReceived, $totalDue
?><style>
    .dash-section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .chart-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .chart-card .card-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        border-radius: 8px 8px 0 0;
    }
</style>

<!-- 1. MAIN OVERVIEW KPIs -->
<div class="dash-section-title">
    <i class="fas fa-chart-line text-primary"></i> Main Overview
</div>
<div class="row" style="margin-left:-12px; margin-right:-12px;">
    <div class="col-lg-4 col-md-6 mb-4" style="padding-left:12px; padding-right:12px;">
        <div class="small-box bg-info shadow-sm" style="border-radius:8px;">
            <div class="inner">
                <h3><?php echo number_format($totalCustomers); ?></h3>
                <p>Total Customers</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
            <a href="/APN-Solar/customers/" class="small-box-footer" style="border-radius:0 0 8px 8px;">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-4" style="padding-left:12px; padding-right:12px;">
        <div class="small-box bg-success shadow-sm" style="border-radius:8px;">
            <div class="inner">
                <h3>₹<?php echo number_format($paymentReceived, 2); ?></h3>
                <p>Total Payment Received</p>
            </div>
            <div class="icon"><i class="fas fa-wallet"></i></div>
            <a href="/APN-Solar/reports/payment_report.php" class="small-box-footer" style="border-radius:0 0 8px 8px;">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-4 col-md-12 mb-4" style="padding-left:12px; padding-right:12px;">
        <div class="small-box bg-warning shadow-sm" style="border-radius:8px;">
            <div class="inner">
                <h3>₹<?php echo number_format($totalDue, 0, '.', ''); ?></h3>
                <p>Total Due Amount</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
            <a href="/APN-Solar/customers/payment_due.php" class="small-box-footer" style="border-radius:0 0 8px 8px;">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<!-- 2. ANALYTICS & CHARTS -->
<div class="dash-section-title">
    <i class="fas fa-chart-pie text-warning"></i> Analytics & Insights
</div>

<div class="row">
    <!-- Geography Row -->
    <div class="col-md-4 mb-4">
        <div class="card chart-card h-100">
            <div class="card-header"><h3 class="card-title text-sm font-weight-bold"><i class="fas fa-map-marker-alt text-danger"></i> District Distribution</h3></div>
            <div class="card-body"><canvas id="districtChart" style="height:220px;width:100%;"></canvas></div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card chart-card h-100">
            <div class="card-header"><h3 class="card-title text-sm font-weight-bold"><i class="fas fa-home text-success"></i> Village (Gaon) Reach</h3></div>
            <div class="card-body"><canvas id="villageChart" style="height:220px;width:100%;"></canvas></div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card chart-card h-100">
            <div class="card-header"><h3 class="card-title text-sm font-weight-bold"><i class="fas fa-university text-secondary"></i> Gram Panchayat</h3></div>
            <div class="card-body"><canvas id="gpChart" style="height:220px;width:100%;"></canvas></div>
        </div>
    </div>

    <!-- Demographics & Finance Row -->
    <div class="col-md-3 mb-4">
        <div class="card chart-card h-100">
            <div class="card-header"><h3 class="card-title text-sm font-weight-bold"><i class="fas fa-layer-group text-warning"></i> Customer Groups</h3></div>
            <div class="card-body"><canvas id="groupChart" style="height:220px;width:100%;"></canvas></div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card chart-card h-100">
            <div class="card-header"><h3 class="card-title text-sm font-weight-bold"><i class="fas fa-user-tie text-info"></i> Operators</h3></div>
            <div class="card-body"><canvas id="operatorChart" style="height:220px;width:100%;"></canvas></div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card chart-card h-100">
            <div class="card-header"><h3 class="card-title text-sm font-weight-bold"><i class="fas fa-money-check-alt text-success"></i> Payments</h3></div>
            <div class="card-body"><canvas id="paymentChart" style="height:220px;width:100%;"></canvas></div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card chart-card h-100">
            <div class="card-header"><h3 class="card-title text-sm font-weight-bold"><i class="fas fa-users-cog text-dark"></i> System Users</h3></div>
            <div class="card-body"><canvas id="userChart" style="height:220px;width:100%;"></canvas></div>
        </div>
    </div>
</div>

<!-- 3. PENDING OPERATIONS -->
<div class="dash-section-title">
    <i class="fas fa-tasks text-danger"></i> Pending Operations
</div>
<div class="row" style="margin-left:-10px; margin-right:-10px;">
    <div class="col-lg-3 col-6 mb-4" style="padding-left:10px; padding-right:10px;">
        <div class="small-box bg-danger shadow-sm" style="border-radius:8px;">
            <div class="inner"><h3><?php echo number_format($totalPendingInstallations); ?></h3><p>Pending Installations</p></div>
            <a href="/APN-Solar/reports/pending_installation.php" class="small-box-footer" style="border-radius:0 0 8px 8px;">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-4" style="padding-left:10px; padding-right:10px;">
        <div class="small-box bg-info shadow-sm" style="border-radius:8px;">
            <div class="inner"><h3><?php echo number_format($pendingDispatch1st); ?></h3><p>Pending Dispatch 1st</p></div>
            <a href="/APN-Solar/reports/first_materials_dispatched.php" class="small-box-footer" style="border-radius:0 0 8px 8px;">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-4" style="padding-left:10px; padding-right:10px;">
        <div class="small-box bg-danger shadow-sm" style="border-radius:8px;">
            <div class="inner"><h3><?php echo number_format($pendingDispatch2nd); ?></h3><p>Pending Dispatch 2nd</p></div>
            <a href="/APN-Solar/reports/second_materials_dispatched.php" class="small-box-footer" style="border-radius:0 0 8px 8px;">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-4" style="padding-left:10px; padding-right:10px;">
        <div class="small-box bg-danger shadow-sm" style="border-radius:8px;">
            <div class="inner"><h3><?php echo number_format($pendingNetMetering); ?></h3><p>Pending Net Metering</p></div>
            <a href="/APN-Solar/reports/pending_net_metering.php" class="small-box-footer" style="border-radius:0 0 8px 8px;">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-4" style="padding-left:10px; padding-right:10px;">
        <div class="small-box bg-info shadow-sm" style="border-radius:8px;">
            <div class="inner"><h3><?php echo number_format($pendingOnline); ?></h3><p>Pending Online Install</p></div>
            <a href="/APN-Solar/reports/pending_online_installation.php" class="small-box-footer" style="border-radius:0 0 8px 8px;">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-4" style="padding-left:10px; padding-right:10px;">
        <div class="small-box bg-danger shadow-sm" style="border-radius:8px;">
            <div class="inner"><h3><?php echo number_format($pendingJanSamarth); ?></h3><p>Pending Jan Samarth</p></div>
            <a href="/APN-Solar/reports/customer_jan_samarth_pending.php" class="small-box-footer" style="border-radius:0 0 8px 8px;">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-4" style="padding-left:10px; padding-right:10px;">
        <div class="small-box bg-info shadow-sm" style="border-radius:8px;">
            <div class="inner"><h3><?php echo number_format($pendingRegistration); ?></h3><p>Pending Registration</p></div>
            <a href="/APN-Solar/reports/customer_registration_pending.php" class="small-box-footer" style="border-radius:0 0 8px 8px;">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-4" style="padding-left:10px; padding-right:10px;">
        <div class="small-box bg-warning shadow-sm" style="border-radius:8px;">
            <div class="inner"><h3><?php echo number_format($pendingComplaints); ?></h3><p>Pending Complaints</p></div>
            <a href="/APN-Solar/complaints/index.php" class="small-box-footer" style="border-radius:0 0 8px 8px;">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const distData = <?php echo json_encode($chartDistricts); ?>;
    const groupData = <?php echo json_encode($chartGroups); ?>;
    const opData = <?php echo json_encode($chartOperators); ?>;
    const vilData = <?php echo json_encode($chartVillages); ?>;
    const gpData = <?php echo json_encode($chartGramPanchayats); ?>;
    const userData = <?php echo json_encode($chartUsers); ?>;
    const payData = <?php echo json_encode($paymentOverview); ?>;

    const pieColors = ['#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4'];
    const barColor = 'rgba(59, 130, 246, 0.7)';
    const barBorder = 'rgba(59, 130, 246, 1)';
    const payColors = ['#94a3b8', '#10b981', '#ef4444'];

    const commonBarOptions = {
        responsive: true, maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        plugins: { legend: { display: false } }
    };
    const commonPieOptions = {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } }
    };

    new Chart(document.getElementById('districtChart').getContext('2d'), {
        type: 'bar',
        data: { labels: distData.map(d=>d.label), datasets: [{ data: distData.map(d=>d.val), backgroundColor: barColor, borderColor: barBorder, borderWidth: 1, borderRadius: 4 }] },
        options: commonBarOptions
    });

    new Chart(document.getElementById('villageChart').getContext('2d'), {
        type: 'bar',
        data: { labels: vilData.map(d=>d.label), datasets: [{ data: vilData.map(d=>d.val), backgroundColor: 'rgba(16, 185, 129, 0.7)', borderColor: 'rgba(16, 185, 129, 1)', borderWidth: 1, borderRadius: 4 }] },
        options: commonBarOptions
    });

    new Chart(document.getElementById('gpChart').getContext('2d'), {
        type: 'bar',
        data: { labels: gpData.map(d=>d.label), datasets: [{ data: gpData.map(d=>d.val), backgroundColor: 'rgba(139, 92, 246, 0.7)', borderColor: 'rgba(139, 92, 246, 1)', borderWidth: 1, borderRadius: 4 }] },
        options: commonBarOptions
    });

    new Chart(document.getElementById('groupChart').getContext('2d'), {
        type: 'doughnut',
        data: { labels: groupData.map(d=>d.label), datasets: [{ data: groupData.map(d=>d.val), backgroundColor: pieColors }] },
        options: { ...commonPieOptions, cutout: '50%' }
    });

    new Chart(document.getElementById('operatorChart').getContext('2d'), {
        type: 'bar',
        data: { labels: opData.map(d=>d.label), datasets: [{ data: opData.map(d=>d.val), backgroundColor: 'rgba(245, 158, 11, 0.7)', borderColor: 'rgba(245, 158, 11, 1)', borderWidth: 1, borderRadius: 4 }] },
        options: commonBarOptions
    });

    new Chart(document.getElementById('paymentChart').getContext('2d'), {
        type: 'pie',
        data: { labels: payData.map(d=>d.label), datasets: [{ data: payData.map(d=>d.val), backgroundColor: payColors }] },
        options: commonPieOptions
    });

    new Chart(document.getElementById('userChart').getContext('2d'), {
        type: 'doughnut',
        data: { labels: userData.map(d=>d.label), datasets: [{ data: userData.map(d=>d.val), backgroundColor: ['#3b82f6','#f59e0b','#10b981'] }] },
        options: { ...commonPieOptions, cutout: '50%' }
    });
});
</script>t>
