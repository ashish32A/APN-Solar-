<?php
// routes/web.php — Route map

return [
    // Auth
    'GET  /APN-Solar/solaradmin/public/all/login/'  => ['AuthController', 'showLogin'],
    'POST /APN-Solar/solaradmin/public/all/login/'  => ['AuthController', 'login'],
    'GET  /APN-Solar/public/logout.php'             => ['AuthController', 'logout'],

    // Dashboard
    'GET  /APN-Solar/'                              => ['DashboardController', 'index'],
    'GET  /APN-Solar/index.php'                     => ['DashboardController', 'index'],

    // Customer Management
    'GET  /APN-Solar/customers.php'                 => ['CustomerController', 'index'],
    'GET  /APN-Solar/customers/create'              => ['CustomerController', 'create'],
    'POST /APN-Solar/customers/store'               => ['CustomerController', 'store'],
    'GET  /APN-Solar/customers/edit/{id}'           => ['CustomerController', 'edit'],
    'POST /APN-Solar/customers/update/{id}'         => ['CustomerController', 'update'],
    'POST /APN-Solar/customers/delete/{id}'         => ['CustomerController', 'destroy'],
    'GET  /APN-Solar/customers/not-interested'      => ['CustomerController', 'notInterested'],
    'GET  /APN-Solar/customers/pending'             => ['CustomerController', 'pendingList'],
    'GET  /APN-Solar/customers/payment-due'         => ['CustomerController', 'paymentDueList'],
    'GET  /APN-Solar/customers/payment-received'    => ['CustomerController', 'paymentReceivedList'],

    // Reports
    'GET  /APN-Solar/reports/pending-installation'  => ['ReportController', 'pendingInstallation'],
    'GET  /APN-Solar/reports/pending-net-metering'  => ['ReportController', 'pendingNetMetering'],
    'GET  /APN-Solar/reports/pending-online'        => ['ReportController', 'pendingOnlineInstallation'],
    'GET  /APN-Solar/reports/pending-subsidy-first' => ['ReportController', 'pendingSubsidyFirst'],
    'GET  /APN-Solar/reports/pending-subsidy-second'=> ['ReportController', 'pendingSubsidySecond'],
    'GET  /APN-Solar/reports/pending-account'       => ['ReportController', 'pendingAccountNumber'],
    'GET  /APN-Solar/reports/first-dispatch'        => ['ReportController', 'firstMaterialsDispatched'],
    'GET  /APN-Solar/reports/second-dispatch'       => ['ReportController', 'secondMaterialsDispatched'],
    'GET  /APN-Solar/reports/registration-pending'  => ['ReportController', 'customerRegistrationPending'],
    'GET  /APN-Solar/reports/jan-samarth-pending'   => ['ReportController', 'customerJanSamarthPending'],
    'GET  /APN-Solar/reports/centralized'           => ['ReportController', 'centralizedReport'],
    'GET  /APN-Solar/reports/payment'               => ['ReportController', 'paymentReport'],
    'GET  /APN-Solar/reports/group-wise'            => ['ReportController', 'groupWiseReport'],

    // Masters
    'GET  /APN-Solar/masters/users/'                => ['UserController', 'index'],
    'GET  /APN-Solar/masters/users/create'          => ['UserController', 'create'],
    'POST /APN-Solar/masters/users/store'           => ['UserController', 'store'],

    // Complaints
    'GET  /APN-Solar/complaints/'                   => ['ComplaintController', 'index'],
    'GET  /APN-Solar/complaints/create'             => ['ComplaintController', 'create'],
    'POST /APN-Solar/complaints/store'              => ['ComplaintController', 'store'],

    // Leads
    'GET  /APN-Solar/leads/'                        => ['LeadController', 'index'],
    'GET  /APN-Solar/leads/create'                  => ['LeadController', 'create'],
    'POST /APN-Solar/leads/store'                   => ['LeadController', 'store'],
];
