<?php
$content = file('c:\laragon\www\fastware_adsi_1\routes\web.php');
foreach ($content as $i => $line) {
    if (stripos($line, 'competency') !== false || stripos($line, 'Competency') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
