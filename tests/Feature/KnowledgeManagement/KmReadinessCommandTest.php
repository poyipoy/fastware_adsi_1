<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Models\KmPengajuan;
use App\Models\User;
use App\Services\KnowledgeManagement\KmFileService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class KmReadinessCommandTest extends KmTestCase
{
    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = storage_path('framework/testing/km-readiness-'.Str::uuid());
        File::ensureDirectoryExists($this->storageRoot);
        config()->set('filesystems.disks.'.KmFileService::DISK, [
            'driver' => 'local',
            'root' => $this->storageRoot,
            'visibility' => 'private',
            'throw' => true,
        ]);
        Storage::forgetDisk(KmFileService::DISK);
    }

    protected function tearDown(): void
    {
        Storage::forgetDisk(KmFileService::DISK);
        if (isset($this->storageRoot)
            && str_starts_with(
                strtolower(str_replace('\\', '/', $this->storageRoot)),
                strtolower(str_replace('\\', '/', storage_path('framework/testing/km-readiness-'))),
            )) {
            File::deleteDirectory($this->storageRoot);
        }

        parent::tearDown();
    }

    public function test_default_readiness_reports_required_passes_and_non_blocking_infrastructure_warnings(): void
    {
        $exitCode = Artisan::call('km:readiness');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('[PASS] schema.columns', $output);
        $this->assertStringContainsString('[PASS] schema.column_shapes', $output);
        $this->assertStringContainsString('[PASS] schema.unique', $output);
        $this->assertStringContainsString('[PASS] schema.indexes', $output);
        $this->assertStringContainsString('[PASS] schema.foreign_keys', $output);
        $this->assertStringContainsString('[PASS] storage.private', $output);
        $this->assertStringContainsString('[PASS] files.metadata', $output);
        $this->assertStringContainsString('[PASS] files.public_exposure', $output);
        $this->assertStringContainsString('[PASS] files.checksum', $output);
        $this->assertStringContainsString('[WARN] queue.connection', $output);
        $this->assertStringContainsString('[WARN] scheduler.deployment', $output);
        $this->assertStringNotContainsString('[FAIL]', $output);
    }

    public function test_strict_mode_turns_non_blocking_warning_into_failure_exit_code(): void
    {
        $exitCode = Artisan::call('km:readiness', ['--strict' => true]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('[WARN] queue.connection', $output);
        $this->assertStringContainsString('[WARN] scheduler.deployment', $output);
        $this->assertStringNotContainsString('[FAIL]', $output);
    }

    public function test_json_mode_returns_machine_readable_checks_and_summary(): void
    {
        $exitCode = Artisan::call('km:readiness', ['--json' => true]);
        $payload = json_decode(trim(Artisan::output()), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertArrayHasKey('checks', $payload);
        $this->assertArrayHasKey('summary', $payload);
        $this->assertGreaterThan(0, $payload['summary']['pass']);
        $this->assertGreaterThanOrEqual(2, $payload['summary']['warn']);
        $this->assertSame(0, $payload['summary']['fail']);

        $checks = collect($payload['checks'])->keyBy('name');
        $this->assertSame('PASS', $checks->get('storage.private')['status']);
        $this->assertTrue($checks->get('storage.private')['required']);
        $this->assertSame('WARN', $checks->get('queue.connection')['status']);
        $this->assertFalse($checks->get('queue.connection')['required']);
    }

    public function test_storage_inside_public_root_is_required_failure(): void
    {
        config()->set('filesystems.disks.'.KmFileService::DISK.'.root', public_path('km-private-test'));
        Storage::forgetDisk(KmFileService::DISK);

        $exitCode = Artisan::call('km:readiness');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('[FAIL] storage.location', $output);
        $this->assertStringContainsString('public web root', $output);
    }

    public function test_storage_equal_to_public_root_is_required_failure(): void
    {
        config()->set('filesystems.disks.'.KmFileService::DISK.'.root', public_path());
        Storage::forgetDisk(KmFileService::DISK);

        $exitCode = Artisan::call('km:readiness');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('[FAIL] storage.location', $output);
        $this->assertStringContainsString('public web root', $output);
    }

    public function test_unique_readiness_rejects_expected_name_on_the_wrong_table(): void
    {
        DB::statement(
            'ALTER TABLE `km_transaksis` ADD INDEX `km_transaksis_user_test_index` (`id_user`)'
        );
        DB::statement(
            'ALTER TABLE `km_transaksis` DROP INDEX `km_transaksis_user_document_unique`'
        );
        DB::statement(
            'ALTER TABLE `km_sukas` ADD UNIQUE INDEX `km_transaksis_user_document_unique` '
            .'(`id`)'
        );

        $exitCode = Artisan::call('km:readiness');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('[FAIL] schema.unique', $output);
        $this->assertStringContainsString(
            'km_transaksis.km_transaksis_user_document_unique',
            $output,
        );
    }

    public function test_readiness_fails_when_an_approval_event_column_is_missing(): void
    {
        Schema::table('km_approval_events', function (Blueprint $table): void {
            $table->dropColumn('actor_name');
        });

        $exitCode = Artisan::call('km:readiness');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('[FAIL] schema.columns', $output);
        $this->assertStringContainsString('km_approval_events.actor_name', $output);
    }

    public function test_readiness_fails_when_an_approval_event_index_is_missing(): void
    {
        DB::statement(
            'ALTER TABLE km_approval_events '
            .'ADD INDEX km_approval_events_document_test_index (km_pengajuan_id)'
        );
        DB::statement(
            'ALTER TABLE km_approval_events '
            .'DROP INDEX km_approval_events_document_acted_at_index'
        );

        $exitCode = Artisan::call('km:readiness');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('[FAIL] schema.indexes', $output);
        $this->assertStringContainsString(
            'km_approval_events.km_approval_events_document_acted_at_index',
            $output,
        );
    }

    public function test_unique_readiness_rejects_wrong_column_order(): void
    {
        DB::statement(
            'ALTER TABLE `km_transaksis` ADD INDEX `km_transaksis_user_test_index` (`id_user`)'
        );
        DB::statement(
            'ALTER TABLE `km_transaksis` DROP INDEX `km_transaksis_user_document_unique`'
        );
        DB::statement(
            'ALTER TABLE `km_transaksis` ADD UNIQUE INDEX `km_transaksis_user_document_unique` '
            .'(`id_km_pengajuan`, `id_user`)'
        );

        $exitCode = Artisan::call('km:readiness');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('[FAIL] schema.unique', $output);
    }

    public function test_unique_readiness_rejects_a_non_unique_index(): void
    {
        DB::statement(
            'ALTER TABLE `km_transaksis` ADD INDEX `km_transaksis_user_test_index` (`id_user`)'
        );
        DB::statement(
            'ALTER TABLE `km_transaksis` DROP INDEX `km_transaksis_user_document_unique`'
        );
        DB::statement(
            'ALTER TABLE `km_transaksis` ADD INDEX `km_transaksis_user_document_unique` '
            .'(`id_user`, `id_km_pengajuan`)'
        );

        $exitCode = Artisan::call('km:readiness');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('[FAIL] schema.unique', $output);
    }

    public function test_foreign_key_readiness_rejects_the_wrong_target_table(): void
    {
        DB::statement(
            'ALTER TABLE `km_approval_events` '
            .'DROP FOREIGN KEY `km_approval_events_actor_foreign`'
        );
        DB::statement(
            'ALTER TABLE `km_approval_events` '
            .'ADD CONSTRAINT `km_approval_events_actor_foreign` FOREIGN KEY (`actor_id`) '
            .'REFERENCES `roles` (`id`) ON DELETE SET NULL'
        );

        $exitCode = Artisan::call('km:readiness');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('[FAIL] schema.foreign_keys', $output);
        $this->assertStringContainsString(
            'km_approval_events.km_approval_events_actor_foreign',
            $output,
        );
    }

    public function test_foreign_key_readiness_rejects_the_wrong_delete_rule(): void
    {
        DB::statement(
            'ALTER TABLE `km_pengajuans` DROP FOREIGN KEY `km_pengajuans_user_foreign`'
        );
        DB::statement(
            'ALTER TABLE `km_pengajuans` '
            .'ADD CONSTRAINT `km_pengajuans_user_foreign` FOREIGN KEY (`id_user`) '
            .'REFERENCES `users` (`id`) ON DELETE CASCADE'
        );

        $exitCode = Artisan::call('km:readiness');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('[FAIL] schema.foreign_keys', $output);
        $this->assertStringContainsString(
            'km_pengajuans.km_pengajuans_user_foreign',
            $output,
        );
    }

    public function test_missing_or_checksum_mismatched_private_file_is_required_failure(): void
    {
        $owner = $this->user(3101, 'Readiness Checksum Owner');
        $document = KmPengajuan::factory()->published()->for($owner, 'user')->create();
        $path = sprintf('documents/%d/%s.pdf', $document->id, Str::uuid());
        Storage::disk(KmFileService::DISK)->put($path, 'private file contents');
        $document->forceFill([
            'file' => basename($path),
            'file_disk' => KmFileService::DISK,
            'file_path' => $path,
            'file_original_name' => 'Readiness.pdf',
            'file_mime_type' => 'application/pdf',
            'file_size_bytes' => strlen('private file contents'),
            'file_checksum_sha256' => str_repeat('0', 64),
            'file_migrated_at' => now(),
        ])->save();

        $exitCode = Artisan::call('km:readiness');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('[FAIL] files.checksum', $output);
        $this->assertStringContainsString('1 private file hilang atau checksum mismatch', $output);
    }

    public function test_partial_or_wrong_disk_file_metadata_is_required_failure(): void
    {
        $owner = $this->user(3104, 'Partial Metadata Owner');
        KmPengajuan::factory()->published()->for($owner, 'user')->create([
            'file' => 'wrong-disk.pdf',
            'file_disk' => 'public',
            'file_path' => 'documents/1/'.Str::uuid().'.pdf',
            'file_original_name' => 'wrong-disk.pdf',
            'file_mime_type' => 'application/pdf',
            'file_size_bytes' => 10,
            'file_checksum_sha256' => str_repeat('a', 64),
            'file_migrated_at' => now(),
        ]);
        $partial = KmPengajuan::factory()->published()->for($owner, 'user')->create([
            'file' => 'partial.pdf',
        ]);
        $partial->forceFill([
            'file_disk' => KmFileService::DISK,
            'file_path' => 'documents/'.$partial->id.'/'.Str::uuid().'.pdf',
            'file_original_name' => 'partial.pdf',
            'file_mime_type' => null,
            'file_size_bytes' => 10,
            'file_checksum_sha256' => str_repeat('b', 64),
            'file_migrated_at' => now(),
        ])->save();

        $exitCode = Artisan::call('km:readiness');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('[FAIL] files.metadata', $output);
        $this->assertStringContainsString('2 dokumen', $output);
    }

    public function test_private_document_with_public_source_leftover_is_required_failure(): void
    {
        $owner = $this->user(3105, 'Public Leftover Owner');
        $content = "%PDF-1.4\npublic leftover";
        $legacyName = 'public-leftover-'.Str::uuid().'.pdf';
        $source = public_path('assets/image/'.$legacyName);
        File::ensureDirectoryExists(dirname($source));
        File::put($source, $content);
        $document = KmPengajuan::factory()->published()->for($owner, 'user')->create([
            'file' => $legacyName,
        ]);
        $path = 'documents/'.$document->id.'/'.Str::uuid().'.pdf';
        Storage::disk(KmFileService::DISK)->put($path, $content);
        $document->forceFill([
            'file_disk' => KmFileService::DISK,
            'file_path' => $path,
            'file_original_name' => $legacyName,
            'file_mime_type' => 'application/pdf',
            'file_size_bytes' => strlen($content),
            'file_checksum_sha256' => hash('sha256', $content),
            'file_migrated_at' => now(),
        ])->save();

        try {
            $exitCode = Artisan::call('km:readiness');
            $output = Artisan::output();

            $this->assertSame(1, $exitCode, $output);
            $this->assertStringContainsString('[FAIL] files.public_exposure', $output);
            $this->assertStringContainsString('1 dokumen private', $output);
        } finally {
            File::delete($source);
        }
    }

    public function test_users_point_columns_are_required_and_must_be_integer_compatible(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('km_total_poin');
        });
        $missingExitCode = Artisan::call('km:readiness');
        $missingOutput = Artisan::output();

        $this->assertSame(1, $missingExitCode, $missingOutput);
        $this->assertStringContainsString('users.km_total_poin', $missingOutput);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('km_total_poin')->nullable();
        });
        $shapeExitCode = Artisan::call('km:readiness');
        $shapeOutput = Artisan::output();

        $this->assertSame(1, $shapeExitCode, $shapeOutput);
        $this->assertStringContainsString('[FAIL] schema.column_shapes', $shapeOutput);
        $this->assertStringContainsString('users.km_total_poin', $shapeOutput);
    }

    public function test_legacy_file_warning_is_non_blocking_by_default_and_blocking_in_strict_mode(): void
    {
        $owner = $this->user(3102, 'Legacy Readiness Owner');
        KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'file' => 'legacy-readiness.pdf',
            'file_name' => 'legacy-readiness.pdf',
            'file_disk' => null,
            'file_path' => null,
        ]);

        $exitCode = Artisan::call('km:readiness');
        $output = Artisan::output();
        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('[WARN] files.legacy', $output);
        $this->assertStringContainsString('1 dokumen legacy belum dimigrasikan', $output);

        $strictExitCode = Artisan::call('km:readiness', ['--strict' => true]);
        $strictOutput = Artisan::output();
        $this->assertSame(1, $strictExitCode, $strictOutput);
    }

    public function test_database_queue_performs_read_only_select_on_failed_jobs(): void
    {
        $this->createQueueTables();
        DB::table('failed_jobs')->insert(['id' => 901]);
        $before = DB::table('failed_jobs')->orderBy('id')->get()->map(
            static fn (object $row): array => (array) $row,
        )->all();
        $this->useDatabaseQueue();

        $exitCode = Artisan::call('km:readiness');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('[PASS] queue.tables', $output);
        $this->assertStringContainsString('[PASS] queue.jobs', $output);
        $this->assertStringContainsString('[PASS] queue.failed_jobs', $output);
        $this->assertStringContainsString(
            'SELECT read-only pada failed_jobs berhasil (1 row sampel)',
            $output,
        );
        $this->assertSame(
            $before,
            DB::table('failed_jobs')->orderBy('id')->get()->map(
                static fn (object $row): array => (array) $row,
            )->all(),
        );
    }

    public function test_database_queue_reports_warning_when_failed_jobs_cannot_be_read(): void
    {
        Schema::create('jobs', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('failed_jobs', function (Blueprint $table): void {
            $table->text('payload');
        });
        $this->useDatabaseQueue();

        $exitCode = Artisan::call('km:readiness');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('[PASS] queue.tables', $output);
        $this->assertStringContainsString('[WARN] queue.failed_jobs', $output);
        $this->assertStringContainsString('Tidak dapat membaca failed_jobs', $output);
    }

    public function test_readiness_is_read_only_for_business_rows_and_private_files(): void
    {
        $owner = $this->user(3103, 'Read Only Readiness Owner');
        $content = "%PDF-1.4\nread-only readiness";
        $document = KmPengajuan::factory()->published()->for($owner, 'user')->create([
            'file' => 'read-only.pdf',
            'file_name' => 'read-only.pdf',
        ]);
        $path = sprintf('documents/%d/%s.pdf', $document->id, Str::uuid());
        Storage::disk(KmFileService::DISK)->put($path, $content);
        $document->forceFill([
            'file_disk' => KmFileService::DISK,
            'file_path' => $path,
            'file_original_name' => 'read-only.pdf',
            'file_mime_type' => 'application/pdf',
            'file_size_bytes' => strlen($content),
            'file_checksum_sha256' => hash('sha256', $content),
            'file_migrated_at' => now(),
        ])->save();

        $tables = [
            'km_kategoris',
            'km_pengajuans',
            'km_transaksis',
            'km_lihat_bukus',
            'km_sukas',
            'km_insights',
            'km_approval_events',
        ];
        $beforeRows = $this->tableSnapshots($tables);
        $beforeFiles = Storage::disk(KmFileService::DISK)->allFiles();
        $beforeChecksum = hash_file(
            'sha256',
            Storage::disk(KmFileService::DISK)->path($path),
        );

        $this->assertSame(0, Artisan::call('km:readiness'), Artisan::output());

        $this->assertSame($beforeRows, $this->tableSnapshots($tables));
        $this->assertSame($beforeFiles, Storage::disk(KmFileService::DISK)->allFiles());
        $this->assertSame(
            $beforeChecksum,
            hash_file('sha256', Storage::disk(KmFileService::DISK)->path($path)),
        );
    }

    private function user(int $id, string $name): User
    {
        return User::factory()->create([
            'id' => $id,
            'name' => $name,
            'role_id' => 4,
        ]);
    }

    private function createQueueTables(): void
    {
        Schema::create('jobs', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('failed_jobs', function (Blueprint $table): void {
            $table->id();
        });
    }

    private function useDatabaseQueue(): void
    {
        $connection = DB::getDefaultConnection();
        config()->set('queue.default', 'database');
        config()->set('queue.connections.database.connection', $connection);
        config()->set('queue.failed.database', $connection);
        config()->set('queue.failed.table', 'failed_jobs');
    }

    /**
     * @param  list<string>  $tables
     * @return array<string, list<array<string, mixed>>>
     */
    private function tableSnapshots(array $tables): array
    {
        $snapshots = [];
        foreach ($tables as $table) {
            $snapshots[$table] = DB::table($table)
                ->orderBy('id')
                ->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all();
        }

        return $snapshots;
    }
}
