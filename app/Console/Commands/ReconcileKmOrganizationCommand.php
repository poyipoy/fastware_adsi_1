<?php

namespace App\Console\Commands;

use App\Models\UserJobPosition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReconcileKmOrganizationCommand extends Command
{
    protected $signature = 'km:reconcile-organization {--json}';

    protected $description = 'Laporkan anomali mapping organisasi yang memengaruhi akses dan target KM.';

    public function handle(): int
    {
        if (! Schema::hasTable('user_job_positions')) {
            $this->components->error('Tabel user_job_positions tidak tersedia.');
            return self::FAILURE;
        }

        $today = today()->toDateString();
        $active = UserJobPosition::query()->activeAt($today);
        $report = [
            'login_enabled_without_active_mapping' => DB::table('users')
                ->where('is_active', false)
                ->whereNotIn('id', (clone $active)->select('user_id'))
                ->count(),
            'duplicate_current_assignments' => DB::query()->fromSub(
                (clone $active)->select('user_id', 'mst_job_position_id')
                    ->groupBy('user_id', 'mst_job_position_id')->havingRaw('COUNT(*) > 1'),
                'duplicates',
            )->count(),
            'inactive_position_assignments' => (clone $active)
                ->join('mst_job_positions', 'mst_job_positions.id', '=', 'user_job_positions.mst_job_position_id')
                ->where('mst_job_positions.is_active', false)->count(),
            'legacy_section_mismatches' => (clone $active)
                ->join('users', 'users.id', '=', 'user_job_positions.user_id')
                ->join('mst_job_positions', 'mst_job_positions.id', '=', 'user_job_positions.mst_job_position_id')
                ->leftJoin('mst_sections', 'mst_sections.id', '=', 'mst_job_positions.section_id')
                ->whereNotNull('users.section')->where('users.section', '<>', '')
                ->where(static fn ($q) => $q->whereNull('mst_sections.name')
                    ->orWhereColumn('users.section', '<>', 'mst_sections.name'))
                ->count(),
        ];
        $report['total_anomalies'] = array_sum($report);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            $this->table(['Pemeriksaan', 'Jumlah'], collect($report)->map(
                static fn ($count, $key): array => [$key, $count],
            )->values()->all());
        }

        return $report['total_anomalies'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
