<?php

namespace App\Services\Dashboard;

use App\Enums\MonthLabel;
use App\Enums\ProcurementCategory;
use App\Models\MstDboCrp;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CrpDashboardService
{
    public function getChartData(User $user): array
    {
        $allCategories = ProcurementCategory::labels();
        $categories = array_merge(['Total'], $allCategories);
        $monthLabels = MonthLabel::labels();

        $cacheKey = sprintf('dashboard:crp:%s', $user->id);

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user, $categories, $allCategories, $monthLabels) {
            $aggregated = MstDboCrp::query()
                ->where('partner_user', $user->id)
                ->select('nm_category', 'plan_actual')
                ->selectRaw('SUM(month_1) as month_1, SUM(month_2) as month_2, SUM(month_3) as month_3')
                ->selectRaw('SUM(month_4) as month_4, SUM(month_5) as month_5, SUM(month_6) as month_6')
                ->selectRaw('SUM(month_7) as month_7, SUM(month_8) as month_8, SUM(month_9) as month_9')
                ->selectRaw('SUM(month_10) as month_10, SUM(month_11) as month_11, SUM(month_12) as month_12')
                ->selectRaw('SUM(grand_tot) as grand_total')
                ->groupBy('nm_category', 'plan_actual')
                ->get();

            $monthlyActuals = [];
            $monthlyPlans = [];
            $grandTotalComparison = [];

            foreach ($categories as $category) {
                $monthlyActuals[$category] = array_fill(0, 12, 0);
                $monthlyPlans[$category] = array_fill(0, 12, 0);
                $grandTotalComparison[$category] = ['Plan' => 0, 'Actual' => 0];
            }

            foreach ($aggregated as $row) {
                $category = $row->nm_category;
                if (!in_array($category, $allCategories, true)) {
                    continue;
                }

                for ($month = 1; $month <= 12; $month++) {
                    $value = (float) ($row->{'month_' . $month} ?? 0);
                    if ($row->plan_actual === 'Plan') {
                        $monthlyPlans[$category][$month - 1] += $value;
                        $monthlyPlans['Total'][$month - 1] += $value;
                    } else {
                        $monthlyActuals[$category][$month - 1] += $value;
                        $monthlyActuals['Total'][$month - 1] += $value;
                    }
                }

                if ($row->plan_actual === 'Plan') {
                    $grandTotalComparison[$category]['Plan'] += (float) $row->grand_total;
                    $grandTotalComparison['Total']['Plan'] += (float) $row->grand_total;
                } else {
                    $grandTotalComparison[$category]['Actual'] += (float) $row->grand_total;
                    $grandTotalComparison['Total']['Actual'] += (float) $row->grand_total;
                }
            }

            $allMonthlyData = [];
            foreach ($categories as $category) {
                $cumulativePlan = 0;
                $cumulativeActual = 0;
                $monthlyPlanCumulative = [];
                $monthlyActualCumulative = [];

                for ($month = 0; $month < 12; $month++) {
                    $cumulativePlan += $monthlyPlans[$category][$month];
                    $cumulativeActual += $monthlyActuals[$category][$month];
                    $monthlyPlanCumulative[] = $cumulativePlan;
                    $monthlyActualCumulative[] = $cumulativeActual;
                }

                $allMonthlyData[$category] = [
                    'plan' => $monthlyPlanCumulative,
                    'actual' => $monthlyActualCumulative,
                ];
            }

            return [
                'bulanList' => $monthLabels,
                'monthlyActuals' => $monthlyActuals,
                'monthlyPlans' => $monthlyPlans,
                'grandTotalComparison' => $grandTotalComparison,
                'allMonthlyData' => $allMonthlyData,
            ];
        });
    }
}

