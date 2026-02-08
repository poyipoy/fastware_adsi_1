<?php

namespace App\Services\Dashboard;

use App\Enums\ProcurementCategory;
use App\Models\MstPoPengajuan;
use App\Models\TrsPoPengajuan;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class FpbDashboardService
{
    public function getFilterData(): array
    {
        return [
            'kategoriList' => MstPoPengajuan::query()->distinct()->pluck('kategori_po'),
            'allCategories' => ProcurementCategory::labels(),
        ];
    }

    public function getChartData(?string $startDateInput, ?string $endDateInput, ?string $kategoriPo): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($startDateInput, $endDateInput);

        $cacheKey = sprintf(
            'dashboard:fpb:%s:%s:%s',
            $startDate->format('YmdHis'),
            $endDate->format('YmdHis'),
            $kategoriPo ?: 'all'
        );

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($startDate, $endDate, $kategoriPo) {
            $table = (new MstPoPengajuan())->getTable();

            $baseQuery = fn (): Builder => MstPoPengajuan::query()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->when($kategoriPo, fn (Builder $query) => $query->where('kategori_po', $kategoriPo));

            $fpbCreatedMonthly = $baseQuery()
                ->selectRaw('MONTH(created_at) as month, COUNT(id) as total')
                ->groupBy('month')
                ->pluck('total', 'month')
                ->all();

            $fpbFinishedMonthly = TrsPoPengajuan::query()
                ->where('status', 9)
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->whereIn('id_fpb', function ($query) use ($startDate, $endDate, $kategoriPo, $table) {
                    $query->select('id')
                        ->from($table)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->when($kategoriPo, fn ($subQuery) => $subQuery->where('kategori_po', $kategoriPo));
                })
                ->selectRaw('MONTH(updated_at) as month, COUNT(DISTINCT id_fpb) as total')
                ->groupBy('month')
                ->pluck('total', 'month')
                ->all();

            $monthlyData = ['open' => [], 'finish' => []];
            for ($month = 1; $month <= 12; $month++) {
                $monthlyData['open'][] = (int) ($fpbCreatedMonthly[$month] ?? 0);
                $monthlyData['finish'][] = (int) ($fpbFinishedMonthly[$month] ?? 0);
            }

            $totalOpen = $baseQuery()->count();

            $totalFinish = TrsPoPengajuan::query()
                ->where('status', 9)
                ->whereIn('id_fpb', function ($query) use ($startDate, $endDate, $kategoriPo, $table) {
                    $query->select('id')
                        ->from($table)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->when($kategoriPo, fn ($subQuery) => $subQuery->where('kategori_po', $kategoriPo));
                })
                ->distinct('id_fpb')
                ->count('id_fpb');

            $categoryBreakdown = $baseQuery()
                ->selectRaw('kategori_po, COUNT(*) as total')
                ->groupBy('kategori_po')
                ->pluck('total', 'kategori_po')
                ->toArray();

            return [
                'monthlyData' => $monthlyData,
                'totalFPB' => $totalOpen,
                'pieStatus' => [
                    'open' => $totalOpen,
                    'finish' => (int) $totalFinish,
                ],
                'pieCategory' => $categoryBreakdown,
            ];
        });
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

