<?php

// Deliberately do NOT require vendor/autoload.php: its autoload.files already
// includes Warehouse locally and would hide a broken upload-only loader.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
$root = dirname(__DIR__, 2);
$runtime = $root.'/deploy-warehouse/WAREHOUSE-20261022';
require $root.'/vendor/composer/ClassLoader.php';
$loader = new Composer\Autoload\ClassLoader();
foreach (require $root.'/vendor/composer/autoload_psr4.php' as $prefix => $paths) {
    $loader->addPsr4($prefix, $paths);
}
$loader->addPsr4('App\\', $runtime.'/app', true);
foreach (require $root.'/vendor/composer/autoload_namespaces.php' as $prefix => $paths) {
    $loader->add($prefix, $paths);
}
$map = require $root.'/vendor/composer/autoload_classmap.php';
$loader->addClassMap(array_filter($map, fn ($key) => ! str_starts_with($key, 'App\\'), ARRAY_FILTER_USE_KEY));
$loader->register();
$consolidated = json_decode(file_get_contents($root.'/composer.json'), true)['autoload']['files'];
$excluded = array_map(fn ($p) => strtolower(str_replace('\\', '/', realpath($root.'/'.$p))), $consolidated);
foreach (require $root.'/vendor/composer/autoload_files.php' as $id => $file) {
    if (! in_array(strtolower(str_replace('\\', '/', realpath($file))), $excluded, true)) {
        require_once $file;
    }
}
if (class_exists(App\Services\Warehouse\WarehouseTransactionNumberGenerator::class, false)) {
    throw new RuntimeException('Invalid test: consolidated class was preloaded.');
}
require $runtime.'/bootstrap/warehouse-autoload.php';
require $runtime.'/bootstrap/warehouse-autoload.php';
$classes = [
    App\Services\Warehouse\WarehouseTransactionNumberGenerator::class,
    App\Enums\Warehouse\WarehouseTransactionType::class,
    App\Data\Warehouse\WarehouseStockCommand::class,
    App\Http\Requests\Warehouse\StoreWarehouseTransactionRequest::class,
    App\Http\Requests\Warehouse\ScanWarehouseItemRequest::class,
    App\Http\Requests\Warehouse\StoreWarehouseConsumableRequest::class,
    App\Http\Controllers\Warehouse\WarehouseStockInController::class,
    App\Services\Warehouse\WarehouseStockInNumberGenerator::class,
    App\Models\Warehouse\WarehouseStockTransactionMachineUsage::class,
];
foreach ($classes as $class) {
    if (! class_exists($class) && ! enum_exists($class)) {
        throw new RuntimeException('Loader missing '.$class);
    }
}
$all = [];
// Check the dedicated Warehouse declarations; shared application classes have
// other module dependencies deliberately supplied by the production baseline.
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($runtime.'/app', FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $source = file_get_contents($file->getPathname());
    if (! str_contains(str_replace('\\', '/', $file->getPathname()), '/Warehouse/')
        && ! in_array($file->getFilename(), ['WarehouseDomainException.php', 'WarehouseTransactionsExport.php', 'EnsureWarehousePermission.php'], true)) {
        continue;
    }
    if (! preg_match('/namespace ([^;]+);/', $source, $namespace)) {
        continue;
    }
    preg_match_all('/^(?:abstract |final )?(?:class|enum|interface) (\w+)/m', $source, $declarations);
    foreach ($declarations[1] as $declaration) {
        $class = $namespace[1].'\\'.$declaration;
        if (! class_exists($class) && ! enum_exists($class) && ! interface_exists($class)) {
            throw new RuntimeException('Payload class cannot be loaded: '.$class);
        }
        $actual = (new ReflectionClass($class))->getFileName();
        if (realpath($actual) !== realpath($file->getPathname())) {
            throw new RuntimeException('Class loaded outside the upload payload: '.$class);
        }
        $all[] = $class;
    }
}
echo 'Upload-only loader passed without Composer Warehouse autoload.files; '.count($all)." payload classes/enums verified, double include safe.\n";
$shared = [App\Http\Kernel::class, App\Providers\AuthServiceProvider::class, App\Providers\RouteServiceProvider::class, App\Models\MstDepartment::class, App\Models\MstJobPosition::class, App\Models\UserJobPosition::class];
foreach ($shared as $class) {
    $path = $runtime.'/'.str_replace('\\', '/', lcfirst($class)).'.php';
    if (! class_exists($class) || realpath((new ReflectionClass($class))->getFileName()) !== realpath($path)) {
        throw new RuntimeException('Shared integration class did not load from package: '.$class);
    }
}
// Verify the actual packaged bootstrap includes the loader, using installed
// application dependencies while keeping the package free of vendor/.env.
$_ENV['APP_BASE_PATH'] = $root;
$application = require $runtime.'/bootstrap/app.php';
if (! $application instanceof Illuminate\Foundation\Application) {
    throw new RuntimeException('Packaged bootstrap did not return the Laravel application.');
}
echo "Root-layout bootstrap and six integrated shared classes verified.\n";
