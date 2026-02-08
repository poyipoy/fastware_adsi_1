<?php

namespace App\Services\Dashboard;

use App\Models\InquirySales;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class InquiryDashboardService
{
    public function getChartData(?string $startDateInput, ?string $endDateInput): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($startDateInput, $endDateInput);

        $cacheKey = sprintf(
            'dashboard:inquiry:%s:%s',
            $startDate->format('YmdHis'),
            $endDate->format('YmdHis')
        );

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($startDate, $endDate) {
            $baseQuery = fn () => InquirySales::query()
                ->whereBetween('created_at', [$startDate, $endDate]);

            $openCounts = $baseQuery()
                ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                ->groupBy('month')
                ->pluck('total', 'month')
                ->all();

            $finishCounts = $baseQuery()
                ->where('status', 6)
                ->selectRaw('MONTH(updated_at) as month, COUNT(*) as total')
                ->groupBy('month')
                ->pluck('total', 'month')
                ->all();

            $onProgressCounts = $baseQuery()
                ->whereIn('status', [5, 7, 8, 9])
                ->selectRaw('MONTH(updated_at) as month, COUNT(*) as total')
                ->groupBy('month')
                ->pluck('total', 'month')
                ->all();

            $monthlyData = [
                'open' => [],
                'onprogress' => [],
                'finish' => [],
            ];

            for ($month = 1; $month <= 12; $month++) {
                $monthlyData['open'][] = (int) ($openCounts[$month] ?? 0);
                $monthlyData['onprogress'][] = (int) ($onProgressCounts[$month] ?? 0);
                $monthlyData['finish'][] = (int) ($finishCounts[$month] ?? 0);
            }

            $totalInquiries = $baseQuery()->count();

            return [
                'monthlyData1' => $monthlyData,
                'totalinquiry' => $totalInquiries,
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

