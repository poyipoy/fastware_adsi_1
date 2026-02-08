<?php

namespace App\Services\Dashboard;

use App\Enums\FormFppStatus;
use App\Enums\MaintenanceSection;
use App\Models\FormFPP;
use App\Models\Mesin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaintenanceDashboardService
{
    /**
     * Get dashboard data for maintenance
     * 
     * @param Request $request
     * @return array
     */
    public function getDashboardData(Request $request): array
    {
        $formperbaikans = $this->getFormFpps();
        $mesins = $this->getMesins();
        $statusCounts = $this->getStatusCounts($formperbaikans);
        $chartData = $this->getChartDataBySection();
        $summaryData = $this->getSummaryData();
        $summaryData2 = $this->getSummaryData2($request);
        $periodeWaktuPengerjaan = $this->getPeriodeWaktuPengerjaan($request);
        $periodeWaktuAlat = $this->getPeriodeWaktuAlat($request);
        $sections = $this->getSections();
        $years2 = [];

        return [
            'formperbaikans' => $formperbaikans,
            'mesins' => $mesins,
            'openCount' => $statusCounts['open'],
            'onProgressCount' => $statusCounts['on_progress'],
            'finishCount' => $statusCounts['finish'],
            'closedCount' => $statusCounts['closed'],
            'chartCutting' => $chartData['cutting'],
            'chartMachining' => $chartData['machining'],
            'chartMachiningCustom' => $chartData['machining_custom'],
            'chartHeatTreatment' => $chartData['heat_treatment'],
            'summaryData' => $summaryData,
            'summaryData2' => $summaryData2,
            'periodeWaktuAlat' => $periodeWaktuAlat,
            'periodeWaktuPengerjaan' => $periodeWaktuPengerjaan,
            'data2' => array_fill(0, 12, 0),
            'sections' => $sections,
            'years2' => $years2,
        ];
    }

    /**
     * Get FormFPPs with valid machine numbers
     * 
     * @return Collection
     */
    private function getFormFpps(): Collection
    {
        return FormFPP::whereIn('mesin', function ($query) {
            $query->select('no_mesin')
                ->from('mesin');
        })
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get all machines
     * 
     * @return Collection
     */
    private function getMesins(): Collection
    {
        return Mesin::orderBy('updated_at', 'desc')->get();
    }

    /**
     * Get status counts
     * 
     * @param Collection $formperbaikans
     * @return array
     */
    private function getStatusCounts(Collection $formperbaikans): array
    {
        return [
            'open' => $formperbaikans->where('status', FormFppStatus::OPEN->value)->count(),
            'on_progress' => $formperbaikans->where('status', FormFppStatus::ON_PROGRESS->value)->count(),
            'finish' => $formperbaikans->where('status', FormFppStatus::FINISH->value)->count(),
            'closed' => $formperbaikans->where('status', FormFppStatus::CLOSED->value)->count(),
        ];
    }

    /**
     * Get chart data by section
     * 
     * @return array
     */
    private function getChartDataBySection(): array
    {
        $sections = MaintenanceSection::cases();
        $chartData = [];

        foreach ($sections as $section) {
            $chartData[$section->value] = FormFPP::join('mesin', 'form_f_p_p_s.mesin', '=', 'mesin.no_mesin')
                ->select(
                    DB::raw('COUNT(CASE WHEN form_f_p_p_s.status_2 = 0 THEN 1 END) as total_status_2_0'),
                    DB::raw('COUNT(CASE WHEN form_f_p_p_s.status = 3 THEN 1 END) as total_status_3'),
                    DB::raw('MONTH(form_f_p_p_s.created_at) as month')
                )
                ->where('form_f_p_p_s.section', $section->value)
                ->groupBy('month')
                ->get();
        }

        return [
            'cutting' => $chartData[MaintenanceSection::CUTTING->value] ?? collect(),
            'machining' => $chartData[MaintenanceSection::MACHINING->value] ?? collect(),
            'machining_custom' => $chartData[MaintenanceSection::MACHINING_CUSTOM->value] ?? collect(),
            'heat_treatment' => $chartData[MaintenanceSection::HEAT_TREATMENT->value] ?? collect(),
        ];
    }

    /**
     * Get summary data
     * 
     * @return Collection
     */
    private function getSummaryData(): Collection
    {
        $sections = MaintenanceSection::values();

        return FormFPP::join('mesin', 'form_f_p_p_s.mesin', '=', 'mesin.no_mesin')
            ->select(
                DB::raw('MONTH(form_f_p_p_s.created_at) as month'),
                'form_f_p_p_s.section',
                DB::raw('SUM(CASE WHEN form_f_p_p_s.status_2 = 0 THEN 1 ELSE 0 END) as total_status_2_0'),
                DB::raw('SUM(CASE WHEN form_f_p_p_s.status = 3 THEN 1 ELSE 0 END) as total_status_3')
            )
            ->whereIn('form_f_p_p_s.section', $sections)
            ->groupBy('month', 'form_f_p_p_s.section')
            ->get();
    }

    /**
     * Get summary data 2
     * 
     * @param Request $request
     * @return Collection
     */
    private function getSummaryData2(Request $request): Collection
    {
        $selectedYear = $request->input('year', date('Y'));

        return FormFPP::join('mesin', 'form_f_p_p_s.mesin', '=', 'mesin.no_mesin')
            ->selectRaw('YEAR(form_f_p_p_s.created_at) as year,
            MONTH(form_f_p_p_s.created_at) as month, SUM(TIMESTAMPDIFF(SECOND, form_f_p_p_s.created_at, form_f_p_p_s.updated_at) / 60) as total_hour')
            ->whereYear('form_f_p_p_s.created_at', $selectedYear)
            ->where('form_f_p_p_s.status', FormFppStatus::CLOSED->value)
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
    }

    /**
     * Get periode waktu pengerjaan
     * 
     * @param Request $request
     * @return mixed
     */
    private function getPeriodeWaktuPengerjaan(Request $request)
    {
        $selectedYear = $request->input('year', date('Y'));
        $startMonth = Carbon::parse($request->input('start_month2', now()->startOfYear()));
        $endMonth = Carbon::parse($request->input('end_month2', now()->endOfYear()));

        return FormFPP::join('mesin', 'form_f_p_p_s.mesin', '=', 'mesin.no_mesin')
            ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, form_f_p_p_s.created_at, form_f_p_p_s.updated_at) / 60) as total_minute')
            ->whereYear('form_f_p_p_s.created_at', $selectedYear)
            ->whereBetween('form_f_p_p_s.created_at', [$startMonth, $endMonth])
            ->where('form_f_p_p_s.status', FormFppStatus::CLOSED->value)
            ->first();
    }

    /**
     * Get periode waktu alat
     * 
     * @param Request $request
     * @return mixed
     */
    private function getPeriodeWaktuAlat(Request $request)
    {
        $startMonth = Carbon::parse($request->input('start_month2', now()->startOfYear()));
        $endMonth = Carbon::parse($request->input('end_month2', now()->endOfYear()));

        return FormFPP::leftJoin('mesin', 'form_f_p_p_s.mesin', '=', 'mesin.no_mesin')
            ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, form_f_p_p_s.created_at, form_f_p_p_s.updated_at) / 60) as total_minute')
            ->whereBetween('form_f_p_p_s.created_at', [$startMonth, $endMonth])
            ->where('form_f_p_p_s.status', FormFppStatus::CLOSED->value)
            ->whereNull('mesin.no_mesin')
            ->first();
    }

    /**
     * Get sections
     * 
     * @return Collection
     */
    private function getSections(): Collection
    {
        return Mesin::where('status', 0)
            ->select('section')
            ->distinct()
            ->pluck('section');
    }
}

