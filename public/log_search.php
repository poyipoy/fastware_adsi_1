<?php
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (!file_exists($logFile)) {
    die("Log file not found");
}

$lines = file($logFile);
$matches = [];
foreach ($lines as $line) {
    if (strpos($line, 'Error adding Job Position') !== false || strpos($line, 'TcJobController') !== false) {
        $matches[] = $line;
    }
}

// Just output the last 30 matches
$lastMatches = array_slice($matches, -30);
echo "<pre>" . htmlspecialchars(implode("", $lastMatches)) . "</pre>";
