<?php
$content = file('c:\laragon\www\fastware_adsi_1\app\Http\Controllers\PenilaianTCController.php');
foreach ($content as $i => $line) {
    if (strpos($line, 'function dsCompetency') !== false ||
        strpos($line, 'function dsDetailCompetency') !== false ||
        strpos($line, 'function getCompetencyData') !== false ||
        strpos($line, 'function getDetailCompetency') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
