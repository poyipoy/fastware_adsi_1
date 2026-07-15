<?php
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (!file_exists($logFile)) {
    die("Log file not found");
}

$lines = file($logFile);
$lastLines = array_slice($lines, -30);
echo "<pre>" . htmlspecialchars(implode("", $lastLines)) . "</pre>";
