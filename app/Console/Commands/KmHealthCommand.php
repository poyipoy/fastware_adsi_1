<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class KmHealthCommand extends Command
{
    protected $signature = 'km:health {--json : Output JSON untuk automation}';

    protected $description = 'Periksa config, schema, route, storage, dan runtime prerequisite KM secara read-only.';

    /**
     * @var array<string, array{status: 'PASS'|'WARN'|'FAIL', detail: string}>
     */
    private array $results = [];

    /**
     * @var list<string>
     */
    private const TABLES = [
        'km_kategoris',
        'km_pengajuans',
        'km_transaksis',
        'km_lihat_bukus',
        'km_sukas',
        'km_insights',
        'km_approval_events',
        'km_bookmarks',
        'km_tags',
        'km_document_tag',
        'km_document_authors',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const COLUMNS = [
        'km_pengajuans' => [
            'id',
            'id_user',
            'id_km_kategori',
            'judul',
            'keterangan',
            'persetujuan',
            'status',
            'file_disk',
            'file_path',
            'file_original_name',
            'file_mime_type',
            'file_size_bytes',
            'file_checksum_sha256',
            'reading_minutes',
        ],
        'km_transaksis' => [
            'id',
            'id_user',
            'id_km_pengajuan',
            'status',
            'completed_at',
            'points_awarded_at',
        ],
        'km_lihat_bukus' => ['id', 'id_km_pengajuan', 'jumlah_lihat'],
        'km_sukas' => ['id', 'id_user', 'id_km_pengajuan'],
        'km_insights' => ['id', 'id_user', 'id_km_pengajuan', 'content'],
        'km_approval_events' => [
            'id',
            'km_pengajuan_id',
            'actor_id',
            'actor_name',
            'action',
            'from_status',
            'to_status',
            'reason',
            'metadata',
            'acted_at',
        ],
        'km_tags' => ['id', 'name', 'slug'],
        'km_document_tag' => ['km_pengajuan_id', 'km_tag_id'],
        'km_document_authors' => ['id', 'km_pengajuan_id', 'user_id'],
    ];

    /**
     * @var list<array{string, string, list<string>, bool, string}>
     */
    private const INDEXES = [
        ['km_transaksis', 'km_transaksis_user_document_unique', ['id_user', 'id_km_pengajuan'], true, 'BTREE'],
        ['km_sukas', 'km_sukas_user_document_unique', ['id_user', 'id_km_pengajuan'], true, 'BTREE'],
        ['km_lihat_bukus', 'km_lihat_bukus_document_unique', ['id_km_pengajuan'], true, 'BTREE'],
        ['km_bookmarks', 'km_bookmarks_user_document_unique', ['user_id', 'km_pengajuan_id'], true, 'BTREE'],
        ['km_tags', 'km_tags_slug_unique', ['slug'], true, 'BTREE'],
        ['km_document_tag', 'km_document_tag_unique', ['km_pengajuan_id', 'km_tag_id'], true, 'BTREE'],
        ['km_document_authors', 'km_document_authors_unique', ['km_pengajuan_id', 'user_id'], true, 'BTREE'],
        ['km_pengajuans', 'km_pengajuans_judul_keterangan_fulltext', ['judul', 'keterangan'], false, 'FULLTEXT'],
    ];

    /**
     * @var list<array{string, string, list<string>, string, list<string>, string}>
     */
    private const FOREIGN_KEYS = [
        ['km_pengajuans', 'km_pengajuans_user_foreign', ['id_user'], 'users', ['id'], 'SET NULL'],
        ['km_pengajuans', 'km_pengajuans_category_foreign', ['id_km_kategori'], 'km_kategoris', ['id'], 'SET NULL'],
        ['km_transaksis', 'km_transaksis_user_foreign', ['id_user'], 'users', ['id'], 'CASCADE'],
        ['km_transaksis', 'km_transaksis_document_foreign', ['id_km_pengajuan'], 'km_pengajuans', ['id'], 'CASCADE'],
        ['km_sukas', 'km_sukas_user_foreign', ['id_user'], 'users', ['id'], 'CASCADE'],
        ['km_sukas', 'km_sukas_document_foreign', ['id_km_pengajuan'], 'km_pengajuans', ['id'], 'CASCADE'],
        ['km_insights', 'km_insights_user_foreign', ['id_user'], 'users', ['id'], 'CASCADE'],
        ['km_insights', 'km_insights_document_foreign', ['id_km_pengajuan'], 'km_pengajuans', ['id'], 'CASCADE'],
        ['km_lihat_bukus', 'km_lihat_bukus_document_foreign', ['id_km_pengajuan'], 'km_pengajuans', ['id'], 'CASCADE'],
        ['km_approval_events', 'km_approval_events_document_foreign', ['km_pengajuan_id'], 'km_pengajuans', ['id'], 'RESTRICT'],
        ['km_approval_events', 'km_approval_events_actor_foreign', ['actor_id'], 'users', ['id'], 'SET NULL'],
        ['km_bookmarks', 'km_bookmarks_user_id_foreign', ['user_id'], 'users', ['id'], 'CASCADE'],
        ['km_bookmarks', 'km_bookmarks_km_pengajuan_id_foreign', ['km_pengajuan_id'], 'km_pengajuans', ['id'], 'CASCADE'],
        ['km_document_tag', 'km_document_tag_km_pengajuan_id_foreign', ['km_pengajuan_id'], 'km_pengajuans', ['id'], 'CASCADE'],
        ['km_document_tag', 'km_document_tag_km_tag_id_foreign', ['km_tag_id'], 'km_tags', ['id'], 'CASCADE'],
        ['km_document_authors', 'km_document_authors_km_pengajuan_id_foreign', ['km_pengajuan_id'], 'km_pengajuans', ['id'], 'CASCADE'],
        ['km_document_authors', 'km_document_authors_user_id_foreign', ['user_id'], 'users', ['id'], 'RESTRICT'],
    ];

    /**
     * @var list<string>
     */
    private const ROUTES = [
        'pengajuanKM',
        'persetujuanKM',
        'dsKnowlege',
        'storeKM',
        'updateKM',
        'editKM',
        'showPersetujuan',
        'approveKM',
        'updateStatusKM',
        'kirimKM',
        'kmTransaksi.markAsRead',
        'kmTransaksi.saveTransaction',
        'kmSuka.like',
        'kmSuka.unlike',
        'insights.add',
        'km.documents.preview',
        'km.documents.download',
        'km.approvals.bulk',
        'km.analytics.popular',
        'km.analytics.popular.export.xlsx',
        'km.analytics.popular.export.pdf',
        'km.bookmarks.store',
        'km.bookmarks.destroy',
        'km.documents.autosave',
        'km.documents.thumbnail',
        'km.co-authors.options',
    ];

    public function handle(): int
    {
        $this->results = [];

        $this->checkConnection();
        $this->checkTablesAndColumns();
        $this->checkIndexes();
        $this->checkForeignKeys();
        $this->checkRoutes();
        $this->checkPrivateStorage();
        $this->checkRuntimeWarnings();

        $overall = $this->overallStatus();
        if ($this->option('json')) {
            $this->line((string) json_encode([
                'generated_at' => now('Asia/Jakarta')->toIso8601String(),
                'overall' => $overall,
                'checks' => $this->results,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->renderTable($overall);
        }

        return $overall === 'FAIL' ? self::FAILURE : self::SUCCESS;
    }

    private function checkConnection(): void
    {
        try {
            $connection = DB::connection();
            $connection->getPdo();
            $this->pass('database:connection', 'Koneksi database dapat digunakan.');

            $driver = $connection->getDriverName();
            if ($driver === 'mysql') {
                $this->pass('database:driver', 'Driver database adalah MySQL.');
            } else {
                $this->fail('database:driver', 'KM memerlukan driver MySQL.');
            }
        } catch (Throwable) {
            $this->fail('database:connection', 'Koneksi database tidak dapat digunakan.');
            $this->fail('database:driver', 'Driver MySQL tidak dapat diverifikasi.');
        }
    }

    private function checkTablesAndColumns(): void
    {
        foreach (self::TABLES as $table) {
            try {
                if (Schema::hasTable($table)) {
                    $this->pass("table:{$table}", "Tabel {$table} tersedia.");
                } else {
                    $this->fail("table:{$table}", "Tabel {$table} tidak tersedia.");
                }
            } catch (Throwable) {
                $this->fail("table:{$table}", "Tabel {$table} tidak dapat diverifikasi.");
            }
        }

        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $key = "column:{$table}.{$column}";
                try {
                    if (Schema::hasColumn($table, $column)) {
                        $this->pass($key, "Kolom {$table}.{$column} tersedia.");
                    } else {
                        $this->fail($key, "Kolom {$table}.{$column} tidak tersedia.");
                    }
                } catch (Throwable) {
                    $this->fail($key, "Kolom {$table}.{$column} tidak dapat diverifikasi.");
                }
            }
        }
    }

    private function checkIndexes(): void
    {
        foreach (self::INDEXES as [$table, $name, $columns, $unique, $type]) {
            $key = "index:{$table}.{$name}";
            try {
                $rows = DB::select(
                    'SELECT COLUMN_NAME, NON_UNIQUE, INDEX_TYPE '
                    .'FROM information_schema.STATISTICS '
                    .'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? '
                    .'ORDER BY SEQ_IN_INDEX',
                    [DB::connection()->getDatabaseName(), $table, $name],
                );

                $actualColumns = array_map(
                    static fn (object $row): string => (string) $row->COLUMN_NAME,
                    $rows,
                );
                $actualUnique = $rows !== [] && (int) $rows[0]->NON_UNIQUE === 0;
                $actualType = $rows === [] ? '' : strtoupper((string) $rows[0]->INDEX_TYPE);

                if ($actualColumns === $columns && $actualUnique === $unique && $actualType === $type) {
                    $this->pass($key, "Named index {$name} sesuai.");
                } else {
                    $this->fail($key, "Named index {$name} hilang atau definisinya tidak sesuai.");
                }
            } catch (Throwable) {
                $this->fail($key, "Named index {$name} tidak dapat diverifikasi.");
            }
        }
    }

    private function checkForeignKeys(): void
    {
        foreach (self::FOREIGN_KEYS as [$table, $name, $columns, $parent, $parentColumns, $deleteRule]) {
            $key = "foreign:{$table}.{$name}";
            try {
                $rows = DB::select(
                    'SELECT k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME, '
                    .'r.DELETE_RULE '
                    .'FROM information_schema.KEY_COLUMN_USAGE k '
                    .'INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS r '
                    .'ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA '
                    .'AND r.TABLE_NAME = k.TABLE_NAME '
                    .'AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME '
                    .'WHERE k.CONSTRAINT_SCHEMA = ? AND k.TABLE_NAME = ? AND k.CONSTRAINT_NAME = ? '
                    .'ORDER BY k.ORDINAL_POSITION',
                    [DB::connection()->getDatabaseName(), $table, $name],
                );

                $actualColumns = array_map(
                    static fn (object $row): string => (string) $row->COLUMN_NAME,
                    $rows,
                );
                $actualParentColumns = array_map(
                    static fn (object $row): string => (string) $row->REFERENCED_COLUMN_NAME,
                    $rows,
                );
                $actualParent = $rows === [] ? '' : (string) $rows[0]->REFERENCED_TABLE_NAME;
                $actualDeleteRule = $rows === [] ? '' : strtoupper((string) $rows[0]->DELETE_RULE);

                if ($actualColumns === $columns
                    && $actualParent === $parent
                    && $actualParentColumns === $parentColumns
                    && $actualDeleteRule === $deleteRule) {
                    $this->pass($key, "Named foreign key {$name} sesuai.");
                } else {
                    $this->fail($key, "Named foreign key {$name} hilang atau definisinya tidak sesuai.");
                }
            } catch (Throwable) {
                $this->fail($key, "Named foreign key {$name} tidak dapat diverifikasi.");
            }
        }
    }

    private function checkRoutes(): void
    {
        foreach (self::ROUTES as $name) {
            if (Route::has($name)) {
                $this->pass("route:{$name}", "Route {$name} terdaftar.");
            } else {
                $this->fail("route:{$name}", "Route {$name} tidak terdaftar.");
            }
        }
    }

    private function checkPrivateStorage(): void
    {
        $key = 'storage:km_private';
        $disk = Config::get('filesystems.disks.km_private');

        if (! is_array($disk)) {
            $this->fail($key, 'Disk private KM tidak dikonfigurasi.');

            return;
        }

        $root = $disk['root'] ?? null;
        if (($disk['driver'] ?? null) !== 'local'
            || ($disk['visibility'] ?? null) !== 'private'
            || ! is_string($root)
            || trim($root) === '') {
            $this->fail($key, 'Disk private KM harus memakai local driver, private visibility, dan root eksplisit.');

            return;
        }

        $canonicalRoot = $this->canonicalPath($root);
        $canonicalExpected = $this->canonicalPath(storage_path('app/private/km'));
        $canonicalPublic = rtrim($this->canonicalPath(public_path()), DIRECTORY_SEPARATOR);
        $insidePublic = $canonicalRoot === $canonicalPublic
            || str_starts_with($canonicalRoot, $canonicalPublic.DIRECTORY_SEPARATOR);

        if ($canonicalRoot !== $canonicalExpected || $insidePublic) {
            $this->fail($key, 'Root disk private KM tidak canonical atau berada di dalam public.');

            return;
        }

        $this->pass($key, 'Root disk private KM canonical dan berada di luar public.');
    }

    private function checkRuntimeWarnings(): void
    {
        $connection = (string) Config::get('queue.default', 'sync');
        $driver = (string) Config::get("queue.connections.{$connection}.driver", 'unknown');

        if ($driver === 'sync') {
            $this->warnResult('runtime:queue', 'Queue memakai driver sync; job KM berjalan dalam request.');
        } else {
            $this->pass('runtime:queue', 'Queue dikonfigurasi untuk pemrosesan asynchronous.');
        }

        $this->warnResult(
            'runtime:worker',
            'Status queue worker tidak dapat dibuktikan oleh pemeriksaan read-only ini.',
        );
        $this->warnResult(
            'runtime:scheduler',
            'Status scheduler tidak dapat dibuktikan oleh pemeriksaan read-only ini.',
        );
    }

    private function canonicalPath(string $path): string
    {
        $resolved = realpath($path);
        $candidate = $resolved === false ? $path : $resolved;
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);

        return rtrim($normalized, DIRECTORY_SEPARATOR);
    }

    private function pass(string $key, string $detail): void
    {
        $this->results[$key] = ['status' => 'PASS', 'detail' => $detail];
    }

    private function warnResult(string $key, string $detail): void
    {
        $this->results[$key] = ['status' => 'WARN', 'detail' => $detail];
    }

    private function fail(string $key, string $detail): void
    {
        $this->results[$key] = ['status' => 'FAIL', 'detail' => $detail];
    }

    /**
     * @return 'PASS'|'WARN'|'FAIL'
     */
    private function overallStatus(): string
    {
        if (collect($this->results)->contains('status', 'FAIL')) {
            return 'FAIL';
        }

        if (collect($this->results)->contains('status', 'WARN')) {
            return 'WARN';
        }

        return 'PASS';
    }

    /**
     * @param  'PASS'|'WARN'|'FAIL'  $overall
     */
    private function renderTable(string $overall): void
    {
        $rows = collect($this->results)
            ->map(static fn (array $result, string $key): array => [
                $key,
                $result['status'],
                $result['detail'],
            ])
            ->values()
            ->all();

        $this->table(['Pemeriksaan', 'Status', 'Detail'], $rows);

        match ($overall) {
            'PASS' => $this->info('Semua pemeriksaan wajib KM lulus.'),
            'WARN' => $this->warn('Pemeriksaan wajib lulus dengan warning runtime.'),
            'FAIL' => $this->error('Ada pemeriksaan wajib KM yang gagal.'),
        };
    }
}
