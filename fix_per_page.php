<?php
$dir = 'c:/xampp/htdocs/APN-Solar/reports/';
foreach(glob($dir . '*.php') as $file) {
    $content = file_get_contents($file);
    $newContent = str_replace(
        "(int)\$_GET['per_page'] : 10;", 
        "(int)(\$_GET['per_page'] ?? 10) : 10;", 
        $content
    );
    if ($content !== $newContent) {
        file_put_contents($file, $newContent);
        echo "Fixed $file\n";
    }
}
echo "Done.";
