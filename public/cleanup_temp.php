<?php
$files = [
    'find_functions.php',
    'find_sales_logic.php',
    'find_status_3.php',
    'check_job_position_depts.php',
    'verify_fix.php',
];
foreach ($files as $f) {
    $path = __DIR__ . '/' . $f;
    if (file_exists($path)) {
        unlink($path);
        echo "Deleted: $f\n";
    } else {
        echo "Not found: $f\n";
    }
}
echo "Cleanup done.\n";
