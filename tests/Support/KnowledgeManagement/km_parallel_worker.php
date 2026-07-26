<?php

use App\Enums\KnowledgeManagement\KmApprovalAction;
use App\Exceptions\KnowledgeManagement\InvalidKmTransitionException;
use App\Exceptions\KnowledgeManagement\KmBulkApprovalConflictException;
use App\Models\KmKategori;
use App\Models\KmPengajuan;
use App\Models\User;
use App\Services\KnowledgeManagement\KmApprovalService;
use App\Services\KnowledgeManagement\KmReadingService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$barrier = (string) ($argv[1] ?? '');
$ready = (string) ($argv[2] ?? '');
$mode = (string) ($argv[3] ?? '');
$testingRoot = str_replace('\\', '/', storage_path('framework/testing/'));

try {
    if (! app()->environment('testing')
        || DB::getDriverName() !== 'mysql'
        || ! str_ends_with(DB::getDatabaseName(), '_testing')) {
        throw new RuntimeException('Worker KM menolak database non-testing.');
    }

    foreach ([$barrier, $ready] as $path) {
        $normalized = str_replace('\\', '/', $path);
        if ($normalized === '' || ! str_starts_with(strtolower($normalized), strtolower($testingRoot))) {
            throw new RuntimeException('Path sinkronisasi worker KM tidak aman.');
        }
    }

    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
    DB::statement('SET SESSION innodb_lock_wait_timeout = 15');

    $operation = match ($mode) {
        'complete' => (function () use ($argv): callable {
            $user = User::query()->findOrFail((int) ($argv[4] ?? 0));
            $document = KmPengajuan::query()->findOrFail((int) ($argv[5] ?? 0));

            return static fn (): array => app(KmReadingService::class)->complete($user, $document);
        })(),
        'approve' => (function () use ($argv): callable {
            $document = KmPengajuan::query()->findOrFail((int) ($argv[4] ?? 0));
            $actor = User::query()->findOrFail((int) ($argv[5] ?? 0));
            $category = KmKategori::query()->findOrFail((int) ($argv[6] ?? 0));
            $requestId = (string) ($argv[7] ?? 'parallel-approval');
            $attributes = [
                'posisi' => $document->posisi ?: 'All Employee',
                'id_km_kategori' => $category->getKey(),
                'judul' => 'Approved by '.$requestId,
                'keterangan' => $document->keterangan,
            ];

            return static function () use ($document, $actor, $attributes, $requestId): array {
                try {
                    app(KmApprovalService::class)->approve(
                        $document,
                        $actor,
                        $attributes,
                        ['request_id' => $requestId],
                    );

                    return ['outcome' => 'approved', 'request_id' => $requestId];
                } catch (InvalidKmTransitionException $exception) {
                    return [
                        'outcome' => 'invalid_transition',
                        'request_id' => $requestId,
                        'from_status' => $exception->from?->value,
                    ];
                }
            };
        })(),
        'bulk_approve' => (function () use ($argv): callable {
            $documentIds = array_values(array_filter(array_map(
                'intval',
                explode(',', (string) ($argv[4] ?? '')),
            )));
            $actor = User::query()->findOrFail((int) ($argv[5] ?? 0));
            $category = KmKategori::query()->findOrFail((int) ($argv[6] ?? 0));
            $requestId = (string) ($argv[7] ?? 'parallel-bulk-approval');
            $items = array_map(
                static fn (int $documentId): array => [
                    'document_id' => $documentId,
                    'id_km_kategori' => (int) $category->getKey(),
                ],
                $documentIds,
            );

            return static function () use ($actor, $items, $requestId): array {
                try {
                    $documents = app(KmApprovalService::class)->bulkAct(
                        $actor,
                        $items,
                        KmApprovalAction::APPROVED,
                        null,
                        ['request_id' => $requestId],
                    );

                    return [
                        'outcome' => 'approved',
                        'request_id' => $requestId,
                        'document_ids' => $documents->pluck('id')->all(),
                    ];
                } catch (InvalidKmTransitionException|KmBulkApprovalConflictException $exception) {
                    return [
                        'outcome' => 'rejected',
                        'request_id' => $requestId,
                        'exception' => $exception::class,
                    ];
                }
            };
        })(),
        default => throw new RuntimeException('Mode worker KM tidak dikenal.'),
    };

    if (file_put_contents($ready, 'ready') === false) {
        throw new RuntimeException('Worker KM gagal menulis sinyal siap.');
    }

    $deadline = microtime(true) + 20;
    while (! is_file($barrier)) {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException('Worker KM menunggu barrier melewati timeout.');
        }

        usleep(10_000);
    }

    echo json_encode($operation(), JSON_THROW_ON_ERROR);
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage());
    exit(1);
}
