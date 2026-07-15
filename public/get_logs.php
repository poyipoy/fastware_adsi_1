<?php
$file = 'c:\\laragon\\www\\fastware_adsi_1\\storage\\logs\\laravel.log';
if (file_exists($file)) {
    $lines = file($file);
    $lastLines = array_slice($lines, -100);
    echo implode("", $lastLines);
} else {
    echo "Log file not found.";
}
