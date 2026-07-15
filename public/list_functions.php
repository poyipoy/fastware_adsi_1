<?php
$content = file_get_contents('c:\laragon\www\fastware_adsi_1\app\Http\Controllers\PenilaianTCController.php');
preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/', $content, $matches);
echo implode("\n", $matches[1]);
