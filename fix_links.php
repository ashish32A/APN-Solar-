<?php
$files = [
    'reports/pending_account_number.php', 
    'reports/customer_registration_pending.php', 
    'reports/customer_jan_samarth_pending.php'
]; 
foreach($files as $f) { 
    $c = file_get_contents($f); 
    $c = str_replace('customer_status_update.php', 'edit.php', $c); 
    file_put_contents($f, $c); 
}
echo "Done replacing links.";
