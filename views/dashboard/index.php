<?php
// views/dashboard/index.php — Dashboard view
// Variables available: $totalCustomers, $totalPendingInstallations, $paymentReceived, $totalDue
?>
<div class="row">
    <!-- Row 1 -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo number_format($totalCustomers); ?></h3>
                <p>Total Customers</p>
            </div>
            <a href="/APN-Solar/customers/" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo number_format($paymentReceived, 2); ?></h3>
                <p>Total Payment Received</p>
            </div>
            <a href="/APN-Solar/reports/payment_report.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?php echo number_format($totalDue, 0, '.', ''); ?></h3>
                <p>Total Due Amount</p>
            </div>
            <a href="/APN-Solar/customers/payment_due.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?php echo number_format($totalPendingInstallations); ?></h3>
                <p>Pending Installations</p>
            </div>
            <a href="/APN-Solar/reports/pending_installation.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <!-- Row 2 -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo number_format($pendingDispatch1st ?? 0); ?></h3>
                <p>Pending Material Dispatched First</p>
            </div>
            <a href="/APN-Solar/reports/first_materials_dispatched.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?php echo number_format($pendingDispatch2nd ?? 0); ?></h3>
                <p>Pending Material Dispatched Second</p>
            </div>
            <a href="/APN-Solar/reports/second_materials_dispatched.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?php echo number_format($pendingNetMetering ?? 47); ?></h3>
                <p>Pending Net Metering</p>
            </div>
            <a href="/APN-Solar/reports/pending_net_metering.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo number_format($pendingOnline ?? 3); ?></h3>
                <p>Pending Online Installation</p>
            </div>
            <a href="/APN-Solar/reports/pending_online_installation.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <!-- Row 3 -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?php echo number_format($pendingJanSamarth ?? 245); ?></h3>
                <p>Pending Jan Samarth</p>
            </div>
            <a href="/APN-Solar/reports/customer_jan_samarth_pending.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo number_format($pendingRegistration ?? 110); ?></h3>
                <p>Pending Customer Registration</p>
            </div>
            <a href="/APN-Solar/reports/customer_registration_pending.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo number_format($pendingComplaints ?? 163); ?></h3>
                <p>Pending Customer Complaint Issue</p>
            </div>
            <a href="/APN-Solar/complaints/index.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>
