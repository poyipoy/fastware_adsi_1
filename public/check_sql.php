<?php
$files = [
    __DIR__.'/../dms_adasi.sql',
    __DIR__.'/../dms_adasi_rev1 (2).sql',
    __DIR__.'/../dms_adasi_rev1 local.sql'
];
foreach ($files as $f) {
    if (file_exists($f)) {
        echo basename($f) . ": " . date("F d Y H:i:s.", filemtime($f)) . "\n";
    }
}
