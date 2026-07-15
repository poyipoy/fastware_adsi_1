<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function replaceInDir($dir, &$updated) {
    $it = new RecursiveDirectoryIterator($dir);
    foreach (new RecursiveIteratorIterator($it) as $file) {
        if ($file->isDir()) continue;
        $path = $file->getPathname();
        
        // Only process php and blade files
        if (!preg_match('/\.php$/', $path)) continue;
        
        $content = file_get_contents($path);
        
        // Match 'RICHARDUS' or "RICHARDUS" but not followed by " CHRISTIAN"
        $newContent = preg_replace("/'RICHARDUS'(?! CHRISTIAN)/", "'RICHARDUS CHRISTIAN'", $content);
        $newContent = preg_replace("/\"RICHARDUS\"(?! CHRISTIAN)/", "\"RICHARDUS CHRISTIAN\"", $newContent);
        
        if ($newContent !== $content) {
            file_put_contents($path, $newContent);
            $updated[] = $path;
        }
    }
}

$updated = [];
replaceInDir(__DIR__ . '/../resources/views', $updated);
replaceInDir(__DIR__ . '/../app', $updated);

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'count' => count($updated),
    'updated' => $updated
], JSON_PRETTY_PRINT);
