<?php

namespace Tests\Support\KnowledgeManagement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

trait RunsKmWorkers
{
    /**
     * @param  list<list<string>>  $workerArguments
     * @return list<array<string, mixed>>
     */
    protected function runKmWorkers(array $workerArguments): array
    {
        if (! function_exists('proc_open')) {
            $this->markTestSkipped('proc_open is required for the KM parallel race test.');
        }

        $token = Str::uuid()->toString();
        $barrier = storage_path("framework/testing/km-parallel-{$token}.go");
        $worker = base_path('tests/Support/KnowledgeManagement/km_parallel_worker.php');
        $processes = [];
        $readyFiles = [];

        try {
            foreach ($workerArguments as $index => $arguments) {
                $ready = storage_path("framework/testing/km-parallel-{$token}-{$index}.ready");
                $readyFiles[] = $ready;
                $pipes = [];
                $process = proc_open(
                    [PHP_BINARY, $worker, $barrier, $ready, ...$arguments],
                    [
                        0 => ['pipe', 'r'],
                        1 => ['pipe', 'w'],
                        2 => ['pipe', 'w'],
                    ],
                    $pipes,
                    base_path(),
                    $this->parallelWorkerEnvironment(),
                );

                if (! is_resource($process)) {
                    throw new RuntimeException("Worker KM paralel {$index} gagal dimulai.");
                }

                fclose($pipes[0]);
                $processes[] = ['process' => $process, 'pipes' => $pipes];
            }

            $deadline = microtime(true) + 20;
            while (collect($readyFiles)->contains(fn (string $path): bool => ! File::exists($path))) {
                if (microtime(true) >= $deadline) {
                    throw new RuntimeException('Worker KM paralel tidak siap sebelum timeout.');
                }

                usleep(10_000);
            }

            File::put($barrier, 'go');

            $results = [];
            foreach ($processes as $index => $running) {
                $stdout = stream_get_contents($running['pipes'][1]);
                $stderr = stream_get_contents($running['pipes'][2]);
                fclose($running['pipes'][1]);
                fclose($running['pipes'][2]);
                $exitCode = proc_close($running['process']);
                unset($processes[$index]);

                $this->assertSame(
                    0,
                    $exitCode,
                    "Worker KM paralel {$index} gagal: ".trim((string) $stderr),
                );
                $results[] = json_decode(trim((string) $stdout), true, flags: JSON_THROW_ON_ERROR);
            }

            return $results;
        } finally {
            foreach ($processes as $running) {
                foreach ($running['pipes'] as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }
                if (is_resource($running['process'])) {
                    proc_terminate($running['process']);
                    proc_close($running['process']);
                }
            }

            File::delete([$barrier, ...$readyFiles]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function parallelWorkerEnvironment(): array
    {
        $base = getenv();
        $environment = is_array($base) ? $base : [];
        $connection = DB::connection()->getConfig();

        foreach ([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => (string) config('database.default'),
            'DB_HOST' => (string) ($connection['host'] ?? '127.0.0.1'),
            'DB_PORT' => (string) ($connection['port'] ?? '3306'),
            'DB_DATABASE' => (string) DB::connection()->getDatabaseName(),
            'DB_USERNAME' => (string) ($connection['username'] ?? ''),
            'DB_PASSWORD' => (string) ($connection['password'] ?? ''),
            'CACHE_DRIVER' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
        ] as $key => $value) {
            $environment[$key] = $value;
        }

        return array_filter(
            $environment,
            static fn (mixed $value): bool => is_string($value),
        );
    }
}
