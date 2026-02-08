<?php

namespace App\Services\Dashboard;

use App\Enums\LeadTimeCategory;
use App\Models\MstPoPengajuan;
use App\Models\TrsPoPengajuan;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeadTimeDashboardService
{
    public function getChartData(?string $startDateInput, ?string $endDateInput): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($startDateInput, $endDateInput);

        $cacheKey = sprintf(
            'dashboard:leadtime:%s:%s',
            $startDate->format('YmdHis'),
            $endDate->format('YmdHis')
        );

        $leadTimeData = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($startDate, $endDate) {
            $table = (new MstPoPengajuan())->getTable();

            $confirmSub = TrsPoPengajuan::query()
                ->select('id_fpb', DB::raw('MIN(updated_at) as confirmed_at'))
                ->where('status', 6)
                ->groupBy('id_fpb');

            $finishSub = TrsPoPengajuan::query()
                ->select('id_fpb', DB::raw('MIN(updated_at) as finished_at'))
                ->where('status', 9)
                ->groupBy('id_fpb');

            $categoryRows = MstPoPengajuan::query()
                ->select(
                    "{$table}.kategori_po",
                    DB::raw("AVG(CASE WHEN confirm.confirmed_at IS NOT NULL THEN DATEDIFF(confirm.confirmed_at, {$table}.created_at) END) as avg_first"),
                    DB::raw("AVG(CASE WHEN confirm.confirmed_at IS NOT NULL AND finish.finished_at IS NOT NULL THEN DATEDIFF(finish.finished_at, confirm.confirmed_at) END) as avg_second")
                )
                ->leftJoinSub($confirmSub, 'confirm', fn ($join) => $join->on('confirm.id_fpb', '=', "{$table}.id"))
                ->leftJoinSub($finishSub, 'finish', fn ($join) => $join->on('finish.id_fpb', '=', "{$table}.id"))
                ->whereBetween("{$table}.created_at", [$startDate, $endDate])
                ->groupBy("{$table}.kategori_po")
                ->get()
                ->keyBy('kategori_po');

            $overall = MstPoPengajuan::query()
                ->select(
                    DB::raw("AVG(CASE WHEN confirm.confirmed_at IS NOT NULL THEN DATEDIFF(confirm.confirmed_at, {$table}.created_at) END) as avg_first"),
                    DB::raw("AVG(CASE WHEN confirm.confirmed_at IS NOT NULL AND finish.finished_at IS NOT NULL THEN DATEDIFF(finish.finished_at, confirm.confirmed_at) END) as avg_second")
                )
                ->leftJoinSub($confirmSub, 'confirm', fn ($join) => $join->on('confirm.id_fpb', '=', "{$table}.id"))
                ->leftJoinSub($finishSub, 'finish', fn ($join) => $join->on('finish.id_fpb', '=', "{$table}.id"))
                ->whereBetween("{$table}.created_at", [$startDate, $endDate])
                ->first();

            $result = [];
            foreach (LeadTimeCategory::labels() as $category) {
                if ($category === LeadTimeCategory::Total->value) {
                    $result[$category] = [
                        'average_lead_days_first' => $overall ? (int) round($overall->avg_first ?? 0) : 0,
                        'average_lead_days_second' => $overall ? (int) round($overall->avg_second ?? 0) : 0,
                    ];
                    continue;
                }

                $row = $categoryRows->get($category);
                $result[$category] = [
                    'average_lead_days_first' => $row ? (int) round($row->avg_first ?? 0) : 0,
                    'average_lead_days_second' => $row ? (int) round($row->avg_second ?? 0) : 0,
                ];
            }

            return $result;
        });

        return ['leadTimeData' => $leadTimeData];
    }

    /**
     * @return CarbonInterface[]
     */
    private function resolveDateRange(?string $startDate, ?string $endDate): array
    {
        try {
            $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->copy()->subYear()->startOfYear();
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Format tanggal mulai tidak valid.', previous: $exception);
        }

        try {
            $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Format tanggal akhir tidak valid.', previous: $exception);
        }

        if ($start->gt($end)) {
            throw new InvalidArgumentException('Rentang tanggal tidak valid.');
        }

        return [$start, $end];
    }
}

