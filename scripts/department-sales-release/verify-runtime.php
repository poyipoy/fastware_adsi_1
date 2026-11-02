<?php

// CLI-only checks of the release overlay; no requests, SQL imports, or application data writes.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__, 2);
$package = $root.'/deploy-department-sales/SALES-DEPARTMENT-20261026';
$loader = require $root.'/vendor/autoload.php';
$loader->addPsr4('App\\', $package.'/app', true);
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$app['router']->setRoutes(new Illuminate\Routing\RouteCollection);
require $package.'/routes/web.php';
$matches = [];
foreach ($app['router']->getRoutes() as $route) {
    if (str_starts_with((string) $route->getName(), 'mst-department.')) {
        if (isset($matches[$route->getName()]) || ! in_array('auth', $route->gatherMiddleware(), true)) {
            throw new RuntimeException('Duplicate or unauthenticated department route.');
        }
        [$controller, $method] = explode('@', $route->getActionName());
        $reflection = new ReflectionClass($controller);
        if (! $reflection->hasMethod($method) || realpath($reflection->getFileName()) !== realpath($package.'/app/Http/Controllers/MstDepartmentController.php')) {
            throw new RuntimeException('Department route does not resolve through the packaged controller.');
        }
        $matches[$route->getName()] = $route->uri();
    }
}
if (count($matches) !== 6) {
    throw new RuntimeException('Expected six department routes.');
}
$compiledDirectory = sys_get_temp_dir().'/sales-department-release-check-'.bin2hex(random_bytes(10));
mkdir($compiledDirectory, 0700);
$compiler = new Illuminate\View\Compilers\BladeCompiler($app['files'], $compiledDirectory);
$count = 0;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($package.'/resources/views', FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }
    $compiled = $compiledDirectory.'/'.hash('sha256', $file->getPathname()).'.php';
    file_put_contents($compiled, $compiler->compileString(file_get_contents($file->getPathname())));
    $process = new Symfony\Component\Process\Process([PHP_BINARY, '-l', $compiled]);
    $process->mustRun();
    unlink($compiled);
    $count++;
}
rmdir($compiledDirectory);
echo json_encode(['packaged_routes_verified' => count($matches), 'blade_templates_compiled_and_linted' => $count], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
