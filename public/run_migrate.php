<?php
$lines = file('c:/laragon/www/fastware_adsi_1/routes/web.php');
foreach ($lines as $i => $line) {
    if (stripos($line, 'TcJobController') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
