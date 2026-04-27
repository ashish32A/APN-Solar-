<?php
$templateFile = 'c:/xampp/htdocs/APN-Solar/reports/pending_net_metering.php';
$template = file_get_contents($templateFile);

$reports = [
    [
        'file' => 'pending_online_installation.php',
        'title' => 'Pending Online Installation',
        'where' => "WHERE (i.online_installer_name IS NULL OR i.online_installer_name = '')",
        'cols' => "i.online_date, i.online_installer_name",
        'th' => '<th onclick="pnmSort(11)">Online<br>Date</th><th onclick="pnmSort(12)">Online<br>Installer</th>',
        'td' => '<td style="white-space:nowrap;"><?php echo htmlspecialchars($r[\'online_date\']??\'—\'); ?></td>
                 <td style="white-space:nowrap;color:#dc2626;font-weight:600;"><?php echo !empty($r[\'online_installer_name\']) ? htmlspecialchars($r[\'online_installer_name\']) : \'<em style="color:#dc2626;">Pending</em>\'; ?></td>'
    ],
    [
        'file' => 'pending_subsidy_first.php',
        'title' => 'Pending Subsidy First',
        'where' => "WHERE (i.subsidy_1st_status IS NULL OR i.subsidy_1st_status = 'Pending' OR i.subsidy_1st_status = '')",
        'cols' => "i.subsidy_1st_date, i.subsidy_1st_status",
        'th' => '<th onclick="pnmSort(11)">Subsidy 1st<br>Date</th><th onclick="pnmSort(12)">Subsidy 1st<br>Status</th>',
        'td' => '<td style="white-space:nowrap;"><?php echo htmlspecialchars($r[\'subsidy_1st_date\']??\'—\'); ?></td>
                 <td style="white-space:nowrap;color:#dc2626;font-weight:600;"><?php echo !empty($r[\'subsidy_1st_status\']) ? htmlspecialchars($r[\'subsidy_1st_status\']) : \'<em style="color:#dc2626;">Pending</em>\'; ?></td>'
    ],
    [
        'file' => 'pending_subsidy_second.php',
        'title' => 'Pending Subsidy Second',
        'where' => "WHERE (i.subsidy_2nd_status IS NULL OR i.subsidy_2nd_status = 'Pending' OR i.subsidy_2nd_status = '')",
        'cols' => "i.subsidy_2nd_date, i.subsidy_2nd_status",
        'th' => '<th onclick="pnmSort(11)">Subsidy 2nd<br>Date</th><th onclick="pnmSort(12)">Subsidy 2nd<br>Status</th>',
        'td' => '<td style="white-space:nowrap;"><?php echo htmlspecialchars($r[\'subsidy_2nd_date\']??\'—\'); ?></td>
                 <td style="white-space:nowrap;color:#dc2626;font-weight:600;"><?php echo !empty($r[\'subsidy_2nd_status\']) ? htmlspecialchars($r[\'subsidy_2nd_status\']) : \'<em style="color:#dc2626;">Pending</em>\'; ?></td>'
    ],
    [
        'file' => 'pending_account_number.php',
        'title' => 'Pending Account Number',
        'where' => "WHERE (c.account_number IS NULL OR c.account_number = '')",
        'cols' => "c.account_number, c.ifsc_code",
        'th' => '<th onclick="pnmSort(11)">Account<br>Number</th><th onclick="pnmSort(12)">IFSC<br>Code</th>',
        'td' => '<td style="white-space:nowrap;color:#dc2626;font-weight:600;"><?php echo !empty($r[\'account_number\']) ? htmlspecialchars($r[\'account_number\']) : \'<em style="color:#dc2626;">Pending</em>\'; ?></td>
                 <td style="white-space:nowrap;"><?php echo htmlspecialchars($r[\'ifsc_code\']??\'—\'); ?></td>'
    ],
    [
        'file' => 'first_materials_dispatched.php',
        'title' => 'Pending Dispatch 1st',
        'where' => "WHERE (i.material_dispatch_1st IS NULL OR i.material_dispatch_1st = '')",
        'cols' => "i.material_dispatch_1st, i.material_dispatch_2nd",
        'th' => '<th onclick="pnmSort(11)">Dispatch 1st</th><th onclick="pnmSort(12)">Dispatch 2nd</th>',
        'td' => '<td style="white-space:nowrap;color:#dc2626;font-weight:600;"><?php echo !empty($r[\'material_dispatch_1st\']) ? htmlspecialchars($r[\'material_dispatch_1st\']) : \'<em style="color:#dc2626;">Pending</em>\'; ?></td>
                 <td style="white-space:nowrap;"><?php echo htmlspecialchars($r[\'material_dispatch_2nd\']??\'—\'); ?></td>'
    ],
    [
        'file' => 'second_materials_dispatched.php',
        'title' => 'Pending Dispatch 2nd',
        'where' => "WHERE (i.material_dispatch_1st IS NOT NULL AND i.material_dispatch_1st != '') AND (i.material_dispatch_2nd IS NULL OR i.material_dispatch_2nd = '')",
        'cols' => "i.material_dispatch_1st, i.material_dispatch_2nd",
        'th' => '<th onclick="pnmSort(11)">Dispatch 1st</th><th onclick="pnmSort(12)">Dispatch 2nd</th>',
        'td' => '<td style="white-space:nowrap;"><?php echo htmlspecialchars($r[\'material_dispatch_1st\']??\'—\'); ?></td>
                 <td style="white-space:nowrap;color:#dc2626;font-weight:600;"><?php echo !empty($r[\'material_dispatch_2nd\']) ? htmlspecialchars($r[\'material_dispatch_2nd\']) : \'<em style="color:#dc2626;">Pending</em>\'; ?></td>'
    ],
    [
        'file' => 'customer_registration_pending.php',
        'title' => 'Customer Registration Pending',
        'where' => "WHERE c.status = 'pending'",
        'cols' => "c.status as c_status, c.remarks as c_remarks",
        'th' => '<th onclick="pnmSort(11)">Customer<br>Status</th><th onclick="pnmSort(12)">Customer<br>Remarks</th>',
        'td' => '<td style="white-space:nowrap;color:#dc2626;font-weight:600;"><?php echo htmlspecialchars(strtoupper($r[\'c_status\']??\'\')); ?></td>
                 <td style="white-space:nowrap;"><?php echo htmlspecialchars($r[\'c_remarks\']??\'—\'); ?></td>'
    ],
    [
        'file' => 'customer_jan_samarth_pending.php',
        'title' => 'Customer Jan Samarth Pending',
        'where' => "WHERE (c.jan_samarth IS NULL OR c.jan_samarth = 'Pending' OR c.jan_samarth = 'No' OR c.jan_samarth = '')",
        'cols' => "c.jan_samarth",
        'th' => '<th onclick="pnmSort(11)">Jan Samarth<br>Status</th><th onclick="pnmSort(12)">-</th>',
        'td' => '<td style="white-space:nowrap;color:#dc2626;font-weight:600;"><?php echo !empty($r[\'jan_samarth\']) ? htmlspecialchars($r[\'jan_samarth\']) : \'<em style="color:#dc2626;">Pending</em>\'; ?></td>
                 <td style="white-space:nowrap;">—</td>'
    ]
];

foreach ($reports as $rep) {
    $content = $template;

    // Replace Title
    $content = str_replace("'Pending Net Metering'", "'" . $rep['title'] . "'", $content);
    $content = str_replace(">Pending Net Metering<", ">" . $rep['title'] . "<", $content);

    // Replace the SQL Condition
    $content = preg_replace('/\$where  = "WHERE \(i\.meter_installation IS NULL OR i\.meter_installation = \'No\'\)";/', '$where  = "' . $rep['where'] . '";', $content);

    // Replace the SQL Columns
    $content = preg_replace('/i\.meter_installation,\s*i\.meter_configuration/', $rep['cols'], $content);

    // Replace HTML TH elements
    $content = preg_replace('/<th onclick="pnmSort\(11\)">Meter<br>Status<\/th>\s*<th onclick="pnmSort\(12\)">Meter<br>Configuration<\/th>/s', $rep['th'], $content);

    // Replace HTML TD elements
    $oldTdPattern = '/<td style="white-space:nowrap;color:#dc2626;font-weight:600;">\s*<\?php echo !empty\(\$r\[\'meter_installation\'\]\) && \$r\[\'meter_installation\'\] !== \'No\' \? htmlspecialchars\(\$r\[\'meter_installation\'\]\) : \'<em style="color:#dc2626;">Pending<\/em>\'; \?>\s*<\/td>\s*<td style="white-space:nowrap;"><\?php echo htmlspecialchars\(\$r\[\'meter_configuration\'\]\?\?\'—\'\); \?><\/td>/s';
    
    $content = preg_replace($oldTdPattern, $rep['td'], $content);

    // Update form action links
    $content = str_replace('pending_net_metering.php', $rep['file'], $content);

    // Update excel export function name
    $prefix = explode('.', $rep['file'])[0];
    $content = str_replace('pnmExcel', $prefix . 'Excel', $content);
    $content = str_replace('pnmSort', $prefix . 'Sort', $content);
    $content = str_replace('pnmFilter', $prefix . 'Filter', $content);
    $content = str_replace('pnmTable', $prefix . 'Table', $content);
    $content = str_replace('pnmSC', $prefix . 'SC', $content);
    $content = str_replace('pnmSA', $prefix . 'SA', $content);
    $content = str_replace('pnmUrl', $prefix . 'Url', $content);
    $content = str_replace('pnmSearch', $prefix . 'Search', $content);
    $content = str_replace('pending_net_metering_', $prefix . '_', $content);

    // Update back URL for Edit button
    $content = preg_replace('/&back=[^"]+"/', '&back=' . str_replace('.php', '', $rep['file']) . '"', $content);
    
    // Replace "No pending net metering records found"
    $content = str_replace('No pending net metering records found', 'No records found', $content);

    $outPath = 'c:/xampp/htdocs/APN-Solar/reports/' . $rep['file'];
    file_put_contents($outPath, $content);
    echo "Generated: " . $rep['file'] . "\n";
}
