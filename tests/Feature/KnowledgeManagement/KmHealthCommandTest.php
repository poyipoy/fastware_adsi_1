<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Models\KmPengajuan;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

final class KmHealthCommandTest extends KmTestCase
{
    public function test_pass_and_runtime_warnings_exit_zero_and_cover_mandatory_checks(): void
    {
        Config::set('database.connections.mysql.password', 'health-super-secret-password');
        Config::set('database.connections.mysql.url', 'mysql://secret-user:secret-pass@secret-host/private');

        $exitCode = Artisan::call('km:health', ['--json' => true]);
        $payload = json_decode(trim(Artisan::output()), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertSame('WARN', $payload['overall']);
        $this->assertSame('PASS', $payload['checks']['database:connection']['status']);
        $this->assertSame('PASS', $payload['checks']['database:driver']['status']);
        $this->assertSame(
            'PASS',
            $payload['checks']['index:km_pengajuans.km_pengajuans_judul_keterangan_fulltext']['status'],
        );
        $this->assertSame(
            'PASS',
            $payload['checks']['foreign:km_approval_events.km_approval_events_document_foreign']['status'],
        );
        $this->assertSame('PASS', $payload['checks']['route:km.approvals.bulk']['status']);
        $this->assertSame('PASS', $payload['checks']['route:km.analytics.popular.export.pdf']['status']);
        $this->assertSame('PASS', $payload['checks']['storage:km_private']['status']);
        $this->assertSame('WARN', $payload['checks']['runtime:queue']['status']);
        $this->assertSame('WARN', $payload['checks']['runtime:worker']['status']);
        $this->assertSame('WARN', $payload['checks']['runtime:scheduler']['status']);

        $output = Artisan::output();
        foreach ([
            'health-super-secret-password',
            'secret-user',
            'secret-pass',
            'secret-host',
            DB::connection()->getDatabaseName(),
        ] as $secret) {
            $this->assertStringNotContainsString($secret, $output);
        }
    }

    public function test_missing_fulltext_is_a_mandatory_failure_with_nonzero_exit(): void
    {
        DB::statement(
            'ALTER TABLE km_pengajuans DROP INDEX km_pengajuans_judul_keterangan_fulltext'
        );

        $exitCode = Artisan::call('km:health', ['--json' => true]);
        $payload = json_decode(trim(Artisan::output()), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(1, $exitCode);
        $this->assertSame('FAIL', $payload['overall']);
        $this->assertSame(
            'FAIL',
            $payload['checks']['index:km_pengajuans.km_pengajuans_judul_keterangan_fulltext']['status'],
        );
    }

    public function test_private_storage_inside_public_is_a_mandatory_failure(): void
    {
        $original = Config::get('filesystems.disks.km_private');
        Config::set('filesystems.disks.km_private', [
            'driver' => 'local',
            'root' => public_path('km-private-invalid'),
            'visibility' => 'private',
        ]);

        try {
            $exitCode = Artisan::call('km:health', ['--json' => true]);
            $payload = json_decode(trim(Artisan::output()), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(1, $exitCode);
            $this->assertSame('FAIL', $payload['checks']['storage:km_private']['status']);
        } finally {
            Config::set('filesystems.disks.km_private', $original);
        }
    }

    public function test_health_command_only_issues_read_queries_and_changes_no_row_file_cache_or_config(): void
    {
        $owner = User::factory()->create([
            'name' => 'Health Owner',
            'km_total_poin' => 0,
        ]);
        $document = KmPengajuan::factory()->published()->create([
            'id_user' => $owner->getKey(),
            'judul' => 'Health Sentinel Document',
            'posisi' => 'All Employee',
        ]);
        Cache::put('km-health-sentinel', 'unchanged', 60);

        $document->refresh();
        $beforeAttributes = $document->getRawOriginal();
        $beforeConfig = [
            'queue.default' => Config::get('queue.default'),
            'filesystems.disks.km_private' => Config::get('filesystems.disks.km_private'),
        ];
        $beforeConfigHash = hash_file('sha256', config_path('filesystems.php'));
        $beforeRootEntries = glob(storage_path('app/private/km/*')) ?: [];
        $queries = [];
        DB::listen(static function ($query) use (&$queries): void {
            $queries[] = trim((string) $query->sql);
        });

        $this->assertSame(0, Artisan::call('km:health', ['--json' => true]));

        $this->assertNotEmpty($queries);
        foreach ($queries as $query) {
            $this->assertMatchesRegularExpression(
                '/^(select|show|describe|explain)\b/i',
                $query,
                "Health command menjalankan query non-read-only: {$query}",
            );
        }
        $this->assertSame($beforeAttributes, $document->fresh()->getRawOriginal());
        $this->assertSame('unchanged', Cache::get('km-health-sentinel'));
        $this->assertSame($beforeConfig['queue.default'], Config::get('queue.default'));
        $this->assertSame(
            $beforeConfig['filesystems.disks.km_private'],
            Config::get('filesystems.disks.km_private'),
        );
        $this->assertSame($beforeConfigHash, hash_file('sha256', config_path('filesystems.php')));
        $this->assertSame($beforeRootEntries, glob(storage_path('app/private/km/*')) ?: []);
    }
}
