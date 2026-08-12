<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class KmEngagementSchemaMigrationTest extends KmTestCase
{
    public function test_engagement_migrations_roll_back_and_migrate_again_on_testing_database(): void
    {
        $migrations = $this->migrations();

        foreach (array_reverse($migrations) as $migration) {
            $migration->down();
        }

        $this->assertFalse(Schema::hasTable('km_notifications'));
        $this->assertFalse(Schema::hasTable('km_point_ledger'));
        $this->assertFalse(Schema::hasTable('km_insight_reactions'));
        $this->assertFalse(Schema::hasColumn('km_transaksis', 'progress_percent'));
        $this->assertFalse(Schema::hasColumn('km_insights', 'parent_id'));

        foreach ($migrations as $migration) {
            $migration->up();
            $migration->up();
        }

        $this->assertTrue(Schema::hasTable('km_notifications'));
        $this->assertTrue(Schema::hasTable('km_point_ledger'));
        $this->assertTrue(Schema::hasTable('km_insight_mentions'));
        $this->assertTrue(Schema::hasColumn('km_transaksis', 'progress_percent'));
        $this->assertTrue(Schema::hasColumn('km_insights', 'featured_at'));
    }

    public function test_progress_migration_rejects_partial_legacy_shape(): void
    {
        $migration = $this->migrations()[1];
        $migration->down();
        Schema::table('km_transaksis', function (Blueprint $table): void {
            $table->unsignedInteger('last_page')->nullable();
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('hanya sebagian kolom tersedia');
        $migration->up();
    }

    public function test_point_ledger_opening_balance_is_idempotent_and_prefers_active_department(): void
    {
        $migration = $this->migrations()[3];
        $migration->down();
        $user = User::factory()->create([
            'name' => 'Opening Balance User',
            'section' => 'Legacy Section',
            'km_total_poin' => 45,
        ]);
        $departmentId = DB::table('mst_departments')->insertGetId([
            'name' => 'Corporate Quality',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $positionId = DB::table('mst_job_positions')->insertGetId([
            'position_name' => 'Quality Specialist',
            'department_id' => $departmentId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_job_positions')->insert([
            'user_id' => $user->getKey(),
            'mst_job_position_id' => $positionId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->up();
        $migration->up();

        $this->assertSame(1, DB::table('km_point_ledger')->count());
        $this->assertDatabaseHas('km_point_ledger', [
            'user_id' => $user->getKey(),
            'event_type' => 'opening_balance',
            'event_key' => 'opening_balance:'.$user->getKey(),
            'points' => 45,
            'department_snapshot' => 'Corporate Quality',
        ]);
    }

    /** @return list<object> */
    private function migrations(): array
    {
        return [
            require database_path('migrations/2026_07_27_130001_create_km_notifications_table.php'),
            require database_path('migrations/2026_07_27_130002_add_km_reading_progress_to_km_transaksis.php'),
            require database_path('migrations/2026_07_27_130003_extend_km_insights_social.php'),
            require database_path('migrations/2026_07_27_130004_create_km_point_ledger_table.php'),
        ];
    }
}
