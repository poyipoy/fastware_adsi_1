<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Services\KnowledgeManagement\KmSchemaAuditService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

final class KmSchemaMigrationTest extends TestCase
{
    private bool $safeTestingDatabase = false;

    private bool $createdUsersTable = false;

    /** @var list<int> */
    private array $createdUserIds = [];

    /** @var list<string> */
    private array $temporaryManifests = [];

    protected function setUp(): void
    {
        parent::setUp();

        $database = (string) DB::connection()->getDatabaseName();
        if (! app()->environment('testing')
            || DB::connection()->getDriverName() !== 'mysql'
            || ! str_ends_with($database, '_testing')) {
            $this->markTestSkipped(
                'KM migration tests require MySQL, APP_ENV=testing, and DB_DATABASE ending with _testing.'
            );
        }

        $this->safeTestingDatabase = true;
        $this->dropKmTables();

        if (! Schema::hasTable('users')) {
            Schema::create('users', function ($table): void {
                $table->id();
            });
            $this->createdUsersTable = true;
        }
    }

    protected function tearDown(): void
    {
        if ($this->safeTestingDatabase) {
            $this->dropKmTables();

            if (! $this->createdUsersTable && $this->createdUserIds !== []) {
                DB::table('users')->whereIn('id', $this->createdUserIds)->delete();
            }

            if ($this->createdUsersTable) {
                Schema::dropIfExists('users');
            }
        }

        foreach ($this->temporaryManifests as $path) {
            File::delete([$path, $path.'.repair.json', $path.'.repair.json.bak']);
        }

        parent::tearDown();
    }

    public function test_fresh_migrate_creates_and_hardens_all_legacy_tables(): void
    {
        $this->baselineMigration()->up();
        $this->seedDocumentAndHistoricalReading();

        $this->hardeningMigration()->up();
        $this->approvalEventsMigration()->up();
        $this->privateFileMetadataMigration()->up();

        foreach ($this->auditService()->requiredColumns() as $table => $columns) {
            $this->assertTrue(Schema::hasTable($table));
            foreach ($columns as $column) {
                $this->assertTrue(Schema::hasColumn($table, $column), "Missing {$table}.{$column}");
            }
        }

        $this->assertTrue(Schema::hasColumn('km_transaksis', 'completed_at'));
        $this->assertTrue(Schema::hasColumn('km_transaksis', 'points_awarded_at'));
        $this->assertTrue(
            $this->auditService()->hasIndex('km_transaksis', 'km_transaksis_user_document_unique')
        );
        $this->assertTrue($this->auditService()->hasIndex('km_sukas', 'km_sukas_user_document_unique'));
        $this->assertTrue(
            $this->auditService()->hasIndex('km_lihat_bukus', 'km_lihat_bukus_document_unique')
        );
        $this->assertTrue(
            $this->auditService()->hasForeignKey('km_pengajuans', 'km_pengajuans_category_foreign')
        );
        $this->assertTrue(Schema::hasTable('km_approval_events'));
        $this->assertTrue(Schema::hasColumn('km_pengajuans', 'file_checksum_sha256'));
        $this->assertTrue(
            $this->auditService()->hasIndex('km_pengajuans', 'km_pengajuans_file_checksum_index')
        );

        $historical = DB::table('km_transaksis')->first();
        $this->assertNotNull($historical->completed_at);
        $this->assertNotNull($historical->points_awarded_at);

        $this->expectException(QueryException::class);
        DB::table('km_lihat_bukus')->insert([
            'id_km_pengajuan' => 1,
            'jumlah_lihat' => 1,
        ]);
    }

    public function test_legacy_shape_is_baselined_without_recreating_existing_tables(): void
    {
        $this->createLegacyKmSchema();
        DB::table('km_kategoris')->insert([
            'id' => 10,
            'nama_kategori' => 'Legacy',
            'poin_kategori' => 5,
        ]);

        $this->baselineMigration()->up();
        $this->hardeningMigration()->up();
        $this->approvalEventsMigration()->up();
        $this->privateFileMetadataMigration()->up();

        $this->assertSame('Legacy', DB::table('km_kategoris')->where('id', 10)->value('nama_kategori'));
        $primary = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', 'km_kategoris')
            ->where('INDEX_NAME', 'PRIMARY')
            ->pluck('COLUMN_NAME')
            ->all();
        $this->assertSame(['id'], $primary);
        $this->assertTrue(
            $this->auditService()->hasIndex('km_lihat_bukus', 'km_lihat_bukus_document_unique')
        );
    }

    public function test_hardening_normalizes_set_null_foreign_key_columns_before_constraints(): void
    {
        $this->createLegacyKmSchema();
        DB::statement(
            'ALTER TABLE `km_pengajuans` MODIFY `id_km_kategori` BIGINT NOT NULL'
        );
        DB::statement(
            'ALTER TABLE `km_lihat_bukus` MODIFY `id_km_transaksi` INT NOT NULL'
        );

        $this->baselineMigration()->up();
        $this->hardeningMigration()->up();

        $this->assertTrue(
            $this->auditService()->columnDefinition('km_pengajuans', 'id_km_kategori')['nullable']
        );
        $this->assertTrue(
            $this->auditService()->columnDefinition('km_lihat_bukus', 'id_km_transaksi')['nullable']
        );
        $this->assertSame(
            'SET NULL',
            $this->auditService()
                ->foreignKeyDefinition('km_pengajuans', 'km_pengajuans_category_foreign')['delete_rule'],
        );
        $this->assertSame(
            'SET NULL',
            $this->auditService()
                ->foreignKeyDefinition('km_lihat_bukus', 'km_lihat_bukus_transaction_foreign')['delete_rule'],
        );
    }

    public function test_dirty_preflight_stops_hardening_without_data_loss(): void
    {
        $this->baselineMigration()->up();
        $this->seedDocument();
        DB::table('km_lihat_bukus')->insert([
            ['id_km_pengajuan' => 1, 'jumlah_lihat' => 2],
            ['id_km_pengajuan' => 1, 'jumlah_lihat' => 3],
        ]);

        $report = $this->auditService()->audit();
        $this->assertFalse($report['summary']['safe_for_hardening']);
        $this->assertSame(1, $report['summary']['blocking_counts']['duplicate_view_counters']);

        try {
            $this->hardeningMigration()->up();
            $this->fail('Dirty hardening should throw a RuntimeException.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('km:audit-schema', $exception->getMessage());
        }

        $this->assertSame(2, DB::table('km_lihat_bukus')->count());
        $this->assertSame(5, (int) DB::table('km_lihat_bukus')->sum('jumlah_lihat'));
        $this->assertFalse(Schema::hasColumn('km_transaksis', 'completed_at'));
    }

    public function test_audit_repair_and_restore_are_checksum_guarded(): void
    {
        $this->baselineMigration()->up();
        $this->seedDocument();
        DB::table('km_lihat_bukus')->insert([
            ['id_km_pengajuan' => 1, 'jumlah_lihat' => 2],
            ['id_km_pengajuan' => 1, 'jumlah_lihat' => 3],
        ]);

        $report = $this->auditService()->audit();
        $path = storage_path('framework/testing/km-schema-'.uniqid('', true).'.json');
        $this->temporaryManifests[] = $path;
        $this->auditService()->writeManifest($report, $path);

        $this->assertSame(0, Artisan::call('km:repair-schema', [
            'manifest' => $path,
            '--apply' => true,
        ]), Artisan::output());
        $this->assertSame(1, DB::table('km_lihat_bukus')->count());
        $this->assertSame(5, (int) DB::table('km_lihat_bukus')->value('jumlah_lihat'));

        $appliedJournal = $this->repairJournal($path);
        $this->assertSame('applied', $appliedJournal['repair']['status']);

        $this->assertSame(0, Artisan::call('km:repair-schema', [
            'manifest' => $path,
            '--restore' => true,
        ]), Artisan::output());
        $this->assertSame(2, DB::table('km_lihat_bukus')->count());
        $this->assertSame(5, (int) DB::table('km_lihat_bukus')->sum('jumlah_lihat'));
    }

    public function test_repair_aborts_when_an_affected_row_differs_from_the_audit_snapshot_after_preflight(): void
    {
        $this->baselineMigration()->up();
        $this->seedDocument();
        DB::table('km_lihat_bukus')->insert([
            ['id' => 1, 'id_km_pengajuan' => 1, 'jumlah_lihat' => 2],
            ['id' => 2, 'id_km_pengajuan' => 1, 'jumlah_lihat' => 3],
        ]);

        $report = $this->auditService()->audit();
        $path = storage_path('framework/testing/km-schema-'.uniqid('', true).'.json');
        $this->temporaryManifests[] = $path;
        $this->auditService()->writeManifest($report, $path);

        DB::table('km_lihat_bukus')->where('id', 1)->update(['jumlah_lihat' => 99]);

        // Simulate a change in the checksum-to-lock window: global preflight
        // matches, while the immutable audit row snapshot remains stale.
        $manifest = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        $manifest['database_checksum'] = $this->auditService()->fingerprint();
        File::put($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        $this->assertSame(1, Artisan::call('km:repair-schema', [
            'manifest' => $path,
            '--apply' => true,
        ]));
        $this->assertStringContainsString('changed after the audit snapshot', Artisan::output());
        $this->assertSame(2, DB::table('km_lihat_bukus')->count());
        $this->assertSame(99, (int) DB::table('km_lihat_bukus')->where('id', 1)->value('jumlah_lihat'));
        $this->assertSame(3, (int) DB::table('km_lihat_bukus')->where('id', 2)->value('jumlah_lihat'));

        $failedJournal = $this->repairJournal($path);
        $this->assertSame('applying', $failedJournal['repair']['status']);
    }

    public function test_restore_aborts_when_an_affected_row_differs_from_the_post_repair_snapshot(): void
    {
        $this->baselineMigration()->up();
        $this->seedDocument();
        DB::table('km_lihat_bukus')->insert([
            ['id' => 1, 'id_km_pengajuan' => 1, 'jumlah_lihat' => 2],
            ['id' => 2, 'id_km_pengajuan' => 1, 'jumlah_lihat' => 3],
        ]);

        $report = $this->auditService()->audit();
        $path = storage_path('framework/testing/km-schema-'.uniqid('', true).'.json');
        $this->temporaryManifests[] = $path;
        $this->auditService()->writeManifest($report, $path);

        $this->assertSame(0, Artisan::call('km:repair-schema', [
            'manifest' => $path,
            '--apply' => true,
        ]), Artisan::output());

        $journal = $this->repairJournal($path);
        $this->assertNotEmpty($journal['repair']['affected_rows_after'] ?? []);
        DB::table('km_lihat_bukus')->where('id', 1)->update(['jumlah_lihat' => 42]);

        // Keep the global preflight current to exercise the row-level guard
        // that runs after the affected rows have been locked.
        $journal['repair']['after_checksum'] = $this->auditService()->fingerprint()['value'];
        $this->auditService()->writeRepairJournal($journal, $path.'.repair.json');

        $this->assertSame(1, Artisan::call('km:repair-schema', [
            'manifest' => $path,
            '--restore' => true,
        ]));
        $this->assertStringContainsString('changed after repair was applied', Artisan::output());
        $this->assertSame(1, DB::table('km_lihat_bukus')->count());
        $this->assertSame(42, (int) DB::table('km_lihat_bukus')->where('id', 1)->value('jumlah_lihat'));

        $unchangedJournal = $this->repairJournal($path);
        $this->assertSame('applied', $unchangedJournal['repair']['status']);
    }

    public function test_repair_journal_keeps_audit_manifest_immutable_and_hashes_business_rows(): void
    {
        $this->baselineMigration()->up();
        DB::table('km_pengajuans')->insert([
            'id' => 1,
            'id_km_kategori' => 999,
            'judul' => 'JUDUL-SENSITIF-JANGAN-JURNAL',
            'keterangan' => 'KETERANGAN-SENSITIF-JANGAN-JURNAL',
            'status' => 3,
        ]);
        DB::table('km_insights')->insert([
            'id' => 1,
            'id_km_pengajuan' => 1,
            'content' => 'CONTENT-SENSITIF-JANGAN-JURNAL',
        ]);

        $report = $this->auditService()->audit();
        $path = storage_path('framework/testing/km-schema-'.uniqid('', true).'.json');
        $this->temporaryManifests[] = $path;
        $this->auditService()->writeManifest($report, $path);
        $immutableAudit = File::get($path);

        $this->assertSame(0, Artisan::call('km:repair-schema', [
            'manifest' => $path,
            '--apply' => true,
        ]), Artisan::output());

        $this->assertSame($immutableAudit, File::get($path));
        $journalJson = File::get($path.'.repair.json');
        $this->assertStringNotContainsString('JUDUL-SENSITIF-JANGAN-JURNAL', $journalJson);
        $this->assertStringNotContainsString('KETERANGAN-SENSITIF-JANGAN-JURNAL', $journalJson);
        $this->assertStringNotContainsString('CONTENT-SENSITIF-JANGAN-JURNAL', $journalJson);

        $journal = json_decode($journalJson, true, flags: JSON_THROW_ON_ERROR);
        foreach ($journal['repair']['affected_rows_after'] as $entry) {
            $this->assertSame(
                ['table', 'id', 'exists', 'row_hash_sha256'],
                array_keys($entry),
            );
            if ($entry['exists']) {
                $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $entry['row_hash_sha256']);
            } else {
                $this->assertNull($entry['row_hash_sha256']);
            }
        }
    }

    public function test_committing_journal_and_corrupt_primary_are_reconciled_after_database_commit(): void
    {
        $this->baselineMigration()->up();
        $this->seedDocument();
        DB::table('km_lihat_bukus')->insert([
            ['id' => 1, 'id_km_pengajuan' => 1, 'jumlah_lihat' => 2],
            ['id' => 2, 'id_km_pengajuan' => 1, 'jumlah_lihat' => 3],
        ]);

        $report = $this->auditService()->audit();
        $path = storage_path('framework/testing/km-schema-'.uniqid('', true).'.json');
        $this->temporaryManifests[] = $path;
        $this->auditService()->writeManifest($report, $path);
        $this->assertSame(0, Artisan::call('km:repair-schema', [
            'manifest' => $path,
            '--apply' => true,
        ]), Artisan::output());

        // The backup made immediately before the final applied write is the
        // durable committing record. Simulate a torn/failing final write.
        $backup = json_decode(
            File::get($path.'.repair.json.bak'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $this->assertSame('committing', $backup['repair']['status']);
        File::put($path.'.repair.json', '{incomplete-json');

        $reconcileExitCode = Artisan::call('km:repair-schema', [
            'manifest' => $path,
            '--apply' => true,
        ]);
        $reconcileOutput = Artisan::output();

        $this->assertSame(0, $reconcileExitCode, $reconcileOutput);
        $this->assertStringContainsString('committed before its journal was finalized', $reconcileOutput);
        $this->assertSame('applied', $this->repairJournal($path)['repair']['status']);
        $this->assertSame(1, DB::table('km_lihat_bukus')->count());
        $this->assertSame(5, (int) DB::table('km_lihat_bukus')->value('jumlah_lihat'));
    }

    public function test_committing_journal_at_before_checksum_is_reconciled_and_apply_resumes(): void
    {
        $this->baselineMigration()->up();
        $this->seedDocument();
        DB::table('km_lihat_bukus')->insert([
            ['id' => 1, 'id_km_pengajuan' => 1, 'jumlah_lihat' => 2],
            ['id' => 2, 'id_km_pengajuan' => 1, 'jumlah_lihat' => 3],
        ]);

        $report = $this->auditService()->audit();
        $path = storage_path('framework/testing/km-schema-'.uniqid('', true).'.json');
        $this->temporaryManifests[] = $path;
        $this->auditService()->writeManifest($report, $path);
        $this->assertSame(0, Artisan::call('km:repair-schema', [
            'manifest' => $path,
            '--apply' => true,
        ]), Artisan::output());
        $committing = json_decode(
            File::get($path.'.repair.json.bak'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $this->assertSame(0, Artisan::call('km:repair-schema', [
            'manifest' => $path,
            '--restore' => true,
        ]), Artisan::output());
        $this->assertSame(2, DB::table('km_lihat_bukus')->count());

        // Simulate a process that durably wrote committing but whose DB
        // transaction rolled back before commit.
        $this->auditService()->writeRepairJournal($committing, $path.'.repair.json');
        $this->assertSame(0, Artisan::call('km:repair-schema', [
            'manifest' => $path,
            '--apply' => true,
        ]), Artisan::output());

        $this->assertSame('applied', $this->repairJournal($path)['repair']['status']);
        $this->assertSame(1, DB::table('km_lihat_bukus')->count());
        $this->assertSame(5, (int) DB::table('km_lihat_bukus')->value('jumlah_lihat'));
    }

    public function test_all_four_migrations_can_roll_back_and_migrate_again_on_testing_database(): void
    {
        $this->baselineMigration()->up();
        $this->hardeningMigration()->up();
        $this->approvalEventsMigration()->up();
        $this->privateFileMetadataMigration()->up();

        $this->privateFileMetadataMigration()->down();
        $this->approvalEventsMigration()->down();
        $this->hardeningMigration()->down();
        $this->baselineMigration()->down();
        $this->assertFalse(Schema::hasTable('km_kategoris'));
        $this->assertFalse(Schema::hasTable('km_approval_events'));

        $this->baselineMigration()->up();
        $this->hardeningMigration()->up();
        $this->approvalEventsMigration()->up();
        $this->privateFileMetadataMigration()->up();

        $this->assertTrue(Schema::hasTable('km_approval_events'));
        $this->assertTrue(Schema::hasColumn('km_pengajuans', 'file_checksum_sha256'));
        $this->assertTrue(Schema::hasColumn('km_transaksis', 'completed_at'));
        $this->assertTrue(
            $this->auditService()->hasIndex('km_transaksis', 'km_transaksis_user_document_unique')
        );
    }

    public function test_private_metadata_rollback_requires_files_to_be_restored_first(): void
    {
        $this->baselineMigration()->up();
        $this->hardeningMigration()->up();
        $this->approvalEventsMigration()->up();
        $this->privateFileMetadataMigration()->up();
        $this->seedDocument();
        DB::table('km_pengajuans')->where('id', 1)->update([
            'file_disk' => 'km_private',
            'file_path' => 'documents/1/11111111-1111-1111-1111-111111111111.pdf',
            'file_original_name' => 'legacy.pdf',
            'file_mime_type' => 'application/pdf',
            'file_size_bytes' => 12,
            'file_checksum_sha256' => str_repeat('a', 64),
            'file_migrated_at' => now(),
        ]);

        try {
            $this->privateFileMetadataMigration()->down();
            $this->fail('Metadata rollback must stop until private files are restored from a manifest.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('km:migrate-private-files --restore-manifest', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('km_pengajuans', 'file_path'));
        $this->assertSame('km_private', DB::table('km_pengajuans')->where('id', 1)->value('file_disk'));

        DB::table('km_pengajuans')->where('id', 1)->update([
            'file_disk' => null,
            'file_path' => null,
            'file_original_name' => null,
            'file_mime_type' => null,
            'file_size_bytes' => null,
            'file_checksum_sha256' => null,
            'file_migrated_at' => null,
        ]);
        $this->privateFileMetadataMigration()->down();

        $this->assertFalse(Schema::hasColumn('km_pengajuans', 'file_path'));
    }

    public function test_repair_merges_reading_and_like_duplicates_without_leaving_view_orphans(): void
    {
        $this->baselineMigration()->up();
        $userId = $this->testUserId();
        $this->seedDocument();
        DB::table('km_pengajuans')->where('id', 1)->update(['id_user' => $userId]);
        DB::table('km_transaksis')->insert([
            [
                'id' => 1,
                'id_km_pengajuan' => 1,
                'id_user' => $userId,
                'status' => 2,
                'created_at' => '2026-07-01 08:00:00',
                'updated_at' => '2026-07-01 09:00:00',
                'modified_by' => $userId,
            ],
            [
                'id' => 2,
                'id_km_pengajuan' => 1,
                'id_user' => $userId,
                'status' => 3,
                'created_at' => '2026-07-01 10:00:00',
                'updated_at' => '2026-07-01 11:00:00',
                'modified_by' => $userId,
            ],
        ]);
        DB::table('km_sukas')->insert([
            ['id' => 1, 'id_user' => $userId, 'id_km_pengajuan' => 1],
            ['id' => 2, 'id_user' => $userId, 'id_km_pengajuan' => 1],
        ]);
        DB::table('km_lihat_bukus')->insert([
            'id' => 1,
            'id_km_transaksi' => 2,
            'id_km_pengajuan' => 1,
            'jumlah_lihat' => 4,
        ]);

        $report = $this->auditService()->audit();
        $this->assertSame(1, $report['summary']['blocking_counts']['duplicate_transactions']);
        $this->assertSame(1, $report['summary']['blocking_counts']['duplicate_likes']);
        $this->assertCount(1, $report['findings']['transaction_view_dependencies']);
        $path = storage_path('framework/testing/km-schema-'.uniqid('', true).'.json');
        $this->temporaryManifests[] = $path;
        $this->auditService()->writeManifest($report, $path);

        $this->assertSame(0, Artisan::call('km:repair-schema', [
            'manifest' => $path,
            '--apply' => true,
        ]), Artisan::output());
        $this->assertSame(1, DB::table('km_transaksis')->count());
        $this->assertSame(3, (int) DB::table('km_transaksis')->where('id', 1)->value('status'));
        $this->assertSame('2026-07-01 08:00:00', DB::table('km_transaksis')->where('id', 1)->value('created_at'));
        $this->assertSame('2026-07-01 11:00:00', DB::table('km_transaksis')->where('id', 1)->value('updated_at'));
        $this->assertSame(1, DB::table('km_sukas')->count());
        $this->assertNull(DB::table('km_lihat_bukus')->where('id', 1)->value('id_km_transaksi'));

        $this->assertSame(0, Artisan::call('km:repair-schema', [
            'manifest' => $path,
            '--restore' => true,
        ]), Artisan::output());
        $this->assertSame(2, DB::table('km_transaksis')->count());
        $this->assertSame(2, DB::table('km_sukas')->count());
        $this->assertSame(2, (int) DB::table('km_lihat_bukus')->where('id', 1)->value('id_km_transaksi'));
    }

    public function test_repair_archives_orphans_and_restores_them_exactly(): void
    {
        $this->baselineMigration()->up();
        $userId = $this->testUserId();
        $this->seedDocument();
        DB::table('km_pengajuans')->where('id', 1)->update([
            'id_user' => 999999999,
            'id_km_kategori' => 999999999,
        ]);
        DB::table('km_transaksis')->insert([
            'id' => 1,
            'id_km_pengajuan' => 999999999,
            'id_user' => $userId,
            'status' => 2,
            'modified_by' => $userId,
        ]);
        DB::table('km_lihat_bukus')->insert([
            'id' => 1,
            'id_km_transaksi' => 1,
            'id_km_pengajuan' => 1,
            'jumlah_lihat' => 1,
        ]);

        $report = $this->auditService()->audit();
        $this->assertGreaterThanOrEqual(3, $report['summary']['blocking_counts']['orphan_references']);
        $this->assertCount(1, $report['findings']['transaction_view_dependencies']);
        $path = storage_path('framework/testing/km-schema-'.uniqid('', true).'.json');
        $this->temporaryManifests[] = $path;
        $this->auditService()->writeManifest($report, $path);

        $this->assertSame(0, Artisan::call('km:repair-schema', [
            'manifest' => $path,
            '--apply' => true,
        ]), Artisan::output());
        $this->assertNull(DB::table('km_pengajuans')->where('id', 1)->value('id_user'));
        $this->assertNull(DB::table('km_pengajuans')->where('id', 1)->value('id_km_kategori'));
        $this->assertSame(0, DB::table('km_transaksis')->count());
        $this->assertNull(DB::table('km_lihat_bukus')->where('id', 1)->value('id_km_transaksi'));

        $this->assertSame(0, Artisan::call('km:repair-schema', [
            'manifest' => $path,
            '--restore' => true,
        ]), Artisan::output());
        $this->assertSame(999999999, (int) DB::table('km_pengajuans')->where('id', 1)->value('id_user'));
        $this->assertSame(999999999, (int) DB::table('km_pengajuans')->where('id', 1)->value('id_km_kategori'));
        $this->assertSame(1, DB::table('km_transaksis')->count());
        $this->assertSame(1, (int) DB::table('km_lihat_bukus')->where('id', 1)->value('id_km_transaksi'));
    }

    public function test_hardening_enforces_all_three_unique_business_keys(): void
    {
        $this->baselineMigration()->up();
        $userId = $this->testUserId();
        $this->seedDocument();
        DB::table('km_pengajuans')->where('id', 1)->update(['id_user' => $userId]);
        $this->hardeningMigration()->up();

        DB::table('km_transaksis')->insert([
            'id_user' => $userId,
            'id_km_pengajuan' => 1,
            'status' => 2,
            'modified_by' => $userId,
        ]);
        $this->assertDuplicateRejected(fn () => DB::table('km_transaksis')->insert([
            'id_user' => $userId,
            'id_km_pengajuan' => 1,
            'status' => 2,
            'modified_by' => $userId,
        ]));

        DB::table('km_sukas')->insert(['id_user' => $userId, 'id_km_pengajuan' => 1]);
        $this->assertDuplicateRejected(fn () => DB::table('km_sukas')->insert([
            'id_user' => $userId,
            'id_km_pengajuan' => 1,
        ]));

        DB::table('km_lihat_bukus')->insert(['id_km_pengajuan' => 1, 'jumlah_lihat' => 1]);
        $this->assertDuplicateRejected(fn () => DB::table('km_lihat_bukus')->insert([
            'id_km_pengajuan' => 1,
            'jumlah_lihat' => 1,
        ]));
    }

    public function test_hardening_only_rollback_restores_legacy_counter_shape(): void
    {
        $this->baselineMigration()->up();
        $this->hardeningMigration()->up();

        $this->hardeningMigration()->down();
        $this->assertFalse(Schema::hasColumn('km_transaksis', 'completed_at'));
        $this->assertFalse(
            $this->auditService()->hasIndex('km_transaksis', 'km_transaksis_user_document_unique')
        );
        $counterType = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', 'km_lihat_bukus')
            ->where('COLUMN_NAME', 'jumlah_lihat')
            ->value('COLUMN_TYPE');
        $this->assertSame('varchar(255)', strtolower((string) $counterType));

        $this->hardeningMigration()->up();
        $this->assertTrue(Schema::hasColumn('km_transaksis', 'completed_at'));
        $this->assertTrue(
            $this->auditService()->hasIndex('km_transaksis', 'km_transaksis_user_document_unique')
        );
    }

    public function test_audit_and_hardening_reject_existing_named_constraints_with_wrong_shape(): void
    {
        $this->baselineMigration()->up();
        Schema::table('km_transaksis', function ($table): void {
            $table->index(
                ['id_km_pengajuan', 'id_user'],
                'km_transaksis_user_document_unique'
            );
        });
        Schema::table('km_pengajuans', function ($table): void {
            $table->foreign('id_user', 'km_pengajuans_user_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        $report = $this->auditService()->audit();
        $this->assertFalse($report['summary']['safe_for_hardening']);
        $this->assertSame(2, $report['summary']['blocking_counts']['constraint_shape_mismatches']);
        $this->assertArrayHasKey(
            'index:km_transaksis.km_transaksis_user_document_unique',
            $report['findings']['constraint_shape_mismatches']
        );
        $this->assertArrayHasKey(
            'foreign_key:km_pengajuans.km_pengajuans_user_foreign',
            $report['findings']['constraint_shape_mismatches']
        );

        try {
            $this->hardeningMigration()->up();
            $this->fail('Hardening must reject reused constraint names with incompatible definitions.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'km_transaksis_user_document_unique',
                $exception->getMessage()
            );
            $this->assertStringContainsString('km_pengajuans_user_foreign', $exception->getMessage());
            $this->assertStringContainsString('expected ordered columns', $exception->getMessage());
        }

        $this->assertFalse(Schema::hasColumn('km_transaksis', 'completed_at'));
    }

    public function test_existing_approval_event_table_rejects_column_index_and_foreign_key_drift(): void
    {
        $this->baselineMigration()->up();
        $this->approvalEventsMigration()->up();
        $this->approvalEventsMigration()->up();

        Schema::table('km_approval_events', function ($table): void {
            $table->dropForeign('km_approval_events_actor_foreign');
        });
        Schema::table('km_approval_events', function ($table): void {
            $table->dropIndex('km_approval_events_actor_acted_at_index');
        });
        Schema::table('km_approval_events', function ($table): void {
            $table->index(
                ['acted_at', 'actor_id'],
                'km_approval_events_actor_acted_at_index'
            );
            $table->foreign('actor_id', 'km_approval_events_actor_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->timestamp('updated_at')->nullable();
        });
        DB::statement(
            'ALTER TABLE `km_approval_events` MODIFY `action` VARCHAR(64) NOT NULL'
        );

        try {
            $this->approvalEventsMigration()->up();
            $this->fail('Existing approval-event schema drift must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('action expected varchar(32)', $exception->getMessage());
            $this->assertStringContainsString('updated_at must not exist', $exception->getMessage());
            $this->assertStringContainsString(
                'km_approval_events_actor_acted_at_index',
                $exception->getMessage()
            );
            $this->assertStringContainsString(
                'km_approval_events_actor_foreign',
                $exception->getMessage()
            );
        }
    }

    public function test_private_file_metadata_migration_and_audit_reject_existing_wrong_definitions(): void
    {
        $this->baselineMigration()->up();
        $this->privateFileMetadataMigration()->up();
        $this->privateFileMetadataMigration()->up();

        Schema::table('km_pengajuans', function ($table): void {
            $table->dropIndex('km_pengajuans_file_checksum_index');
        });
        Schema::table('km_pengajuans', function ($table): void {
            $table->unique('file_migrated_at', 'km_pengajuans_file_checksum_index');
        });
        DB::statement(
            'ALTER TABLE `km_pengajuans` MODIFY `file_disk` VARCHAR(64) NOT NULL'
        );

        $report = $this->auditService()->audit();
        $this->assertFalse($report['summary']['safe_for_hardening']);
        $this->assertArrayHasKey(
            'index:km_pengajuans.km_pengajuans_file_checksum_index',
            $report['findings']['constraint_shape_mismatches']
        );

        try {
            $this->privateFileMetadataMigration()->up();
            $this->fail('Existing private-file metadata drift must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('file_disk expected varchar(32), NULL', $exception->getMessage());
            $this->assertStringContainsString(
                'km_pengajuans_file_checksum_index',
                $exception->getMessage()
            );
            $this->assertStringContainsString('will not overwrite them automatically', $exception->getMessage());
        }
    }

    private function seedDocument(): void
    {
        DB::table('km_kategoris')->insert([
            'id' => 1,
            'nama_kategori' => 'Test',
            'poin_kategori' => 5,
        ]);
        DB::table('km_pengajuans')->insert([
            'id' => 1,
            'id_km_kategori' => 1,
            'status' => 3,
        ]);
    }

    private function seedDocumentAndHistoricalReading(): void
    {
        $this->seedDocument();
        DB::table('km_transaksis')->insert([
            'id' => 1,
            'id_km_pengajuan' => 1,
            'status' => 3,
            'created_at' => '2026-07-01 10:00:00',
            'updated_at' => '2026-07-01 11:00:00',
        ]);
        DB::table('km_lihat_bukus')->insert([
            'id' => 1,
            'id_km_transaksi' => 1,
            'id_km_pengajuan' => 1,
            'jumlah_lihat' => 4,
        ]);
    }

    private function createLegacyKmSchema(): void
    {
        Schema::create('km_kategoris', function ($table): void {
            $table->bigInteger('id');
            $table->string('nama_kategori');
            $table->integer('poin_kategori');
            $table->timestamps();
        });
        Schema::create('km_pengajuans', function ($table): void {
            $table->integer('id');
            $table->integer('id_user')->nullable();
            $table->bigInteger('id_km_kategori')->nullable();
            $table->string('judul')->nullable();
            $table->string('keterangan', 3000)->nullable();
            $table->string('posisi')->nullable();
            $table->string('image')->nullable();
            $table->string('file')->nullable();
            $table->string('file_name')->nullable();
            $table->string('persetujuan')->nullable();
            $table->integer('status');
            $table->timestamps();
            $table->string('modified_by')->nullable();
        });
        Schema::create('km_transaksis', function ($table): void {
            $table->integer('id');
            $table->integer('id_km_pengajuan')->nullable();
            $table->integer('id_user')->nullable();
            $table->integer('poin')->nullable();
            $table->integer('level')->nullable();
            $table->integer('status');
            $table->timestamps();
            $table->integer('modified_by')->nullable();
        });
        Schema::create('km_lihat_bukus', function ($table): void {
            $table->bigInteger('id');
            $table->integer('id_km_transaksi')->nullable();
            $table->integer('id_km_pengajuan')->nullable();
            $table->string('jumlah_lihat')->nullable();
            $table->timestamps();
        });
        Schema::create('km_sukas', function ($table): void {
            $table->bigInteger('id');
            $table->integer('id_user')->nullable();
            $table->integer('id_km_pengajuan')->nullable();
            $table->bigInteger('jumlah_like')->nullable();
            $table->timestamps();
        });
        Schema::create('km_insights', function ($table): void {
            $table->integer('id');
            $table->integer('id_user')->nullable();
            $table->integer('id_km_pengajuan')->nullable();
            $table->string('content', 1200)->nullable();
            $table->timestamps();
        });
    }

    private function dropKmTables(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'km_approval_events',
            'km_insights',
            'km_sukas',
            'km_lihat_bukus',
            'km_transaksis',
            'km_pengajuans',
            'km_kategoris',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
    }

    private function testUserId(): int
    {
        $existing = DB::table('users')->orderBy('id')->value('id');
        if ($existing !== null) {
            return (int) $existing;
        }

        $columns = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', 'users')
            ->orderBy('ORDINAL_POSITION')
            ->get(['COLUMN_NAME', 'DATA_TYPE', 'COLUMN_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT', 'EXTRA']);
        $attributes = [];
        foreach ($columns as $column) {
            if ($column->COLUMN_NAME === 'id'
                || $column->IS_NULLABLE === 'YES'
                || $column->COLUMN_DEFAULT !== null
                || str_contains(strtolower((string) $column->EXTRA), 'auto_increment')) {
                continue;
            }

            $attributes[$column->COLUMN_NAME] = match (strtolower($column->DATA_TYPE)) {
                'tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'decimal', 'float', 'double' => 1,
                'date' => now()->toDateString(),
                'datetime', 'timestamp' => now()->toDateTimeString(),
                'json' => '{}',
                default => 'km-schema-test-'.uniqid('', true),
            };
        }

        $id = (int) DB::table('users')->insertGetId($attributes);
        $this->createdUserIds[] = $id;

        return $id;
    }

    private function assertDuplicateRejected(callable $insert): void
    {
        try {
            $insert();
            $this->fail('Expected the named KM unique constraint to reject the duplicate row.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }

    private function baselineMigration(): object
    {
        return require database_path('migrations/2026_07_18_100001_baseline_knowledge_management_schema.php');
    }

    private function hardeningMigration(): object
    {
        return require database_path('migrations/2026_07_18_100002_harden_knowledge_management_constraints.php');
    }

    private function approvalEventsMigration(): object
    {
        return require database_path('migrations/2026_07_18_100003_create_km_approval_events_table.php');
    }

    private function privateFileMetadataMigration(): object
    {
        return require database_path('migrations/2026_07_18_100004_add_private_file_metadata_to_km_pengajuans.php');
    }

    private function auditService(): KmSchemaAuditService
    {
        return app(KmSchemaAuditService::class);
    }

    /** @return array<string, mixed> */
    private function repairJournal(string $manifestPath): array
    {
        return json_decode(
            File::get($manifestPath.'.repair.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
