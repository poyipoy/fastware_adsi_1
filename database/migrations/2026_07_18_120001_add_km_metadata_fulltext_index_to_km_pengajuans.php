<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'km_pengajuans';

    private const INDEX = 'km_pengajuans_judul_keterangan_fulltext';

    private const COLUMNS = ['judul', 'keterangan'];

    public function up(): void
    {
        $this->assertMySql();

        if (! Schema::hasTable(self::TABLE)) {
            throw new RuntimeException('Preflight FULLTEXT KM gagal: tabel km_pengajuans tidak ditemukan.');
        }

        foreach (self::COLUMNS as $column) {
            if (! Schema::hasColumn(self::TABLE, $column)) {
                throw new RuntimeException(
                    "Preflight FULLTEXT KM gagal: kolom km_pengajuans.{$column} tidak ditemukan."
                );
            }
        }

        $engine = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', self::TABLE)
            ->value('ENGINE');

        if (strtolower((string) $engine) !== 'innodb') {
            throw new RuntimeException(
                'Preflight FULLTEXT KM gagal: km_pengajuans harus memakai engine InnoDB; ditemukan '
                .($engine ?: 'unknown').'.'
            );
        }

        $indexes = $this->fullTextIndexes();
        if (array_key_exists(self::INDEX, $indexes)) {
            throw new RuntimeException(
                'Schema drift FULLTEXT KM: index '.self::INDEX
                .' sudah ada tetapi migration belum tercatat. Rekonsiliasi migration history secara eksplisit.'
            );
        }

        foreach ($indexes as $name => $columns) {
            if ($columns === self::COLUMNS) {
                throw new RuntimeException(
                    "Schema drift FULLTEXT KM: index ekuivalen {$name} sudah ada. "
                    .'Jangan membuat index duplikat atau mengganti namanya diam-diam.'
                );
            }
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->fullText(self::COLUMNS, self::INDEX);
        });

        if (($this->fullTextIndexes()[self::INDEX] ?? null) !== self::COLUMNS) {
            throw new RuntimeException('Verifikasi FULLTEXT KM gagal setelah pembuatan index.');
        }
    }

    public function down(): void
    {
        $this->guardTestingRollback();

        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        $indexes = $this->fullTextIndexes();
        if (! array_key_exists(self::INDEX, $indexes)) {
            return;
        }

        if ($indexes[self::INDEX] !== self::COLUMNS) {
            throw new RuntimeException(
                'Rollback FULLTEXT KM dihentikan: named index memiliki definisi yang berbeda.'
            );
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropFullText(self::INDEX);
        });
    }

    /**
     * @return array<string, list<string>>
     */
    private function fullTextIndexes(): array
    {
        $rows = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', self::TABLE)
            ->where('INDEX_TYPE', 'FULLTEXT')
            ->orderBy('INDEX_NAME')
            ->orderBy('SEQ_IN_INDEX')
            ->get(['INDEX_NAME', 'COLUMN_NAME']);

        $indexes = [];
        foreach ($rows as $row) {
            $indexes[(string) $row->INDEX_NAME] ??= [];
            $indexes[(string) $row->INDEX_NAME][] = (string) $row->COLUMN_NAME;
        }

        return $indexes;
    }

    private function assertMySql(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            throw new RuntimeException(
                "Migration FULLTEXT KM memerlukan MySQL; driver aktif adalah {$driver}."
            );
        }
    }

    private function guardTestingRollback(): void
    {
        $database = (string) DB::connection()->getDatabaseName();
        if (! app()->environment('testing') || ! str_ends_with($database, '_testing')) {
            throw new RuntimeException(
                'Rollback FULLTEXT KM hanya diizinkan pada APP_ENV=testing dan database *_testing.'
            );
        }
    }
};
