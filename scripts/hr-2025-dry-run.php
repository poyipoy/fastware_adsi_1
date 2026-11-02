<?php

// Offline entry point: Composer autoload only, no Laravel bootstrap or DB config.
require dirname(__DIR__).'/vendor/autoload.php';

use App\Services\HR\Reconciliation\Hr2025DryRun;

try {
    $options = [];
    foreach (array_slice($argv, 1) as $argument) {
        if (! preg_match('/\A--(legacy|target|year|crosswalk)=(.+)\z/s', $argument, $match) || isset($options[$match[1]])) {
            throw new RuntimeException('UNSUPPORTED_OR_DUPLICATE_OPTION');
        }
        $options[$match[1]] = $match[2];
    }
    if (! isset($options['legacy'], $options['target']) || ($options['year'] ?? '2025') !== '2025') {
        throw new RuntimeException('EXPLICIT_SNAPSHOTS_AND_YEAR_2025_REQUIRED');
    }
    $service = new Hr2025DryRun;
    echo $service->json($service->run($options['legacy'], $options['target'], 2025, $options['crosswalk'] ?? null));
} catch (Throwable $exception) {
    $reason = $exception instanceof RuntimeException && preg_match('/\A[A-Z0-9_]+\z/', $exception->getMessage())
        ? $exception->getMessage() : 'INPUT_CONTRACT_INVALID';
    fwrite(STDERR, 'HR2025_DRY_RUN_REJECTED: '.$reason.PHP_EOL);
    exit(1);
}
