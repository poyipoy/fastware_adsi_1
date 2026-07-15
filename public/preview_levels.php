<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MstJobPosition;

$positions = MstJobPosition::all();

$mapped = [];

foreach ($positions as $pos) {
    $name = $pos->position_name;
    
    $level = 'staff';
    if (preg_match('/(Dept\. Head|Dept Head)/i', $name)) {
        $level = 'dept_head';
    } elseif (preg_match('/(Div Head|Div\. Head)/i', $name)) {
        $level = 'div_head';
    } elseif (preg_match('/(Sec Head|Sect\. Head|Office Head)/i', $name)) {
        $level = 'sec_head';
    }
    
    $mapped[] = [
        'id' => $pos->id,
        'name' => $name,
        'detected_level' => $level
    ];
}

header('Content-Type: application/json');
echo json_encode($mapped, JSON_PRETTY_PRINT);
