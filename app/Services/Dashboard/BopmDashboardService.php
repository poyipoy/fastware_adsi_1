<?php

namespace App\Services\Dashboard;

use App\Models\TrxQuartal;
use App\Models\MstMaterial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BopmDashboardService
{
    /**
     * Get filter data for dashboard (materials list)
     * * @return array
     */
    public function getFilterData(): array
    {
        return Cache::remember('bopm:materials:list', now()->addHours(24), function () {
            return [
                'materials' => MstMaterial::query()
                    ->select('id', 'grade', 'shape')
                    ->where('is_active', 1)
                    ->orderBy('grade')
                    ->orderBy('shape')
                    ->get()
                    ->map(fn ($material) => [
                        'id' => $material->id,
                        'label' => $material->grade . ' - ' . $material->shape,
                    ]),
                'years' => $this->getAvailableYears(),
            ];
        });
    }

    /**
     * Get available years from trx_quartal
     * * @return Collection
     */
    private function getAvailableYears(): Collection
    {
        return Cache::remember('bopm:years:list', now()->addHours(24), function () {
            return TrxQuartal::query()
                ->select('thn')
                ->distinct()
                ->orderBy('thn', 'desc')
                ->pluck('thn');
        });
    }

    /**
     * Get chart data for Highcharts
     * * @param int|null $startYear
     * @param int|null $endYear
     * @param int|null $materialId
     * @return array
     */
    public function getChartData(?int $startYear, ?int $endYear, ?int $materialId): array
    {
        [$startYear, $endYear] = $this->resolveYearRange($startYear, $endYear);

        $cacheKey = sprintf(
            'bopm:chart:%d:%d:%s',
            $startYear,
            $endYear,
            $materialId ?: 'all'
        );

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($startYear, $endYear, $materialId) {
            $query = TrxQuartal::query()
                ->select(
                    'id',
                    'id_material',
                    'thn',
                    'q1_base', 'q1_alloy', 'q1_fob', 'q1_cnf', 'q1_freight',
                    'q2_base', 'q2_alloy', 'q2_fob', 'q2_cnf', 'q2_freight',
                    'q3_base', 'q3_alloy', 'q3_fob', 'q3_cnf', 'q3_freight',
                    'q4_base', 'q4_alloy', 'q4_fob', 'q4_cnf', 'q4_freight'
                )
                ->with(['material:id,grade,shape'])
                ->whereBetween('thn', [$startYear, $endYear])
                ->when($materialId, fn ($q) => $q->where('id_material', $materialId))
                ->orderBy('thn')
                ->orderBy('id_material');

            $data = $query->get();

            // Prepare categories (X-axis labels)
            $categories = [];
            for ($year = $startYear; $year <= $endYear; $year++) {
                $categories[] = "Q1 {$year}";
                $categories[] = "Q2 {$year}";
                $categories[] = "Q3 {$year}";
                $categories[] = "Q4 {$year}";
            }

            // If no data, return empty structure
            if ($data->isEmpty()) {
                return [
                    'categories' => $categories,
                    'series' => $this->getEmptySeries(count($categories)),
                ];
            }

            // Prepare series data placeholders
            $series = [
                'base' => array_fill(0, count($categories), null),
                'fob' => array_fill(0, count($categories), null),
                'cnf' => array_fill(0, count($categories), null),
                'alloy' => array_fill(0, count($categories), null),
                'freight' => array_fill(0, count($categories), null),
            ];

            // Group by Year to handle aggregations
            $groupedData = $data->groupBy('thn');
            
            foreach ($groupedData as $year => $yearData) {
                // Calculate index offset based on year
                $yearIndex = ($year - $startYear) * 4;
                
                for ($q = 1; $q <= 4; $q++) {
                    $quarterIndex = $yearIndex + ($q - 1);
                    $qBase = "q{$q}_base";
                    $qAlloy = "q{$q}_alloy";
                    $qFob = "q{$q}_fob";
                    $qCnf = "q{$q}_cnf";
                    $qFreight = "q{$q}_freight";
                    
                    if ($materialId === null) {
                        // NOTE: Menggunakan SUM. Jika ini harga satuan, pertimbangkan menggunakan avg() (rata-rata).
                        $series['base'][$quarterIndex] = (float) $yearData->sum($qBase) ?: null;
                        $series['alloy'][$quarterIndex] = (float) $yearData->sum($qAlloy) ?: null;
                        $series['fob'][$quarterIndex] = (float) $yearData->sum($qFob) ?: null;
                        $series['cnf'][$quarterIndex] = (float) $yearData->sum($qCnf) ?: null;
                        $series['freight'][$quarterIndex] = (float) $yearData->sum($qFreight) ?: null;
                    } else {
                        // Single material - take first (should be unique per year/material)
                        $row = $yearData->first();
                        $series['base'][$quarterIndex] = isset($row->$qBase) ? (float) $row->$qBase : null;
                        $series['alloy'][$quarterIndex] = isset($row->$qAlloy) ? (float) $row->$qAlloy : null;
                        $series['fob'][$quarterIndex] = isset($row->$qFob) ? (float) $row->$qFob : null;
                        $series['cnf'][$quarterIndex] = isset($row->$qCnf) ? (float) $row->$qCnf : null;
                        $series['freight'][$quarterIndex] = isset($row->$qFreight) ? (float) $row->$qFreight : null;
                    }
                }
            }

            return [
                'categories' => $categories,
                'series' => [
                    ['name' => 'Base', 'data' => $series['base'], 'yAxis' => 0],
                    ['name' => 'FOB', 'data' => $series['fob'], 'yAxis' => 0],
                    ['name' => 'CNF', 'data' => $series['cnf'], 'yAxis' => 0],
                    ['name' => 'Alloy', 'data' => $series['alloy'], 'yAxis' => 1],
                    ['name' => 'Freight', 'data' => $series['freight'], 'yAxis' => 1],
                ],
            ];
        });
    }

    /**
     * Get table data
     * * @param int|null $startYear
     * @param int|null $endYear
     * @param int|null $materialId
     * @return Collection
     */
    public function getTableData(?int $startYear, ?int $endYear, ?int $materialId): Collection
    {
        [$startYear, $endYear] = $this->resolveYearRange($startYear, $endYear);

        $cacheKey = sprintf(
            'bopm:table:%d:%d:%s',
            $startYear,
            $endYear,
            $materialId ?: 'all'
        );

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($startYear, $endYear, $materialId) {
            $query = TrxQuartal::query()
                ->select(
                    'trx_quartal.id',
                    'trx_quartal.id_material',
                    'trx_quartal.thn',
                    'mst_material.grade',
                    'mst_material.shape',
                    'trx_quartal.q1_base', 'trx_quartal.q1_alloy', 'trx_quartal.q1_fob', 'trx_quartal.q1_cnf', 'trx_quartal.q1_freight',
                    'trx_quartal.q2_base', 'trx_quartal.q2_alloy', 'trx_quartal.q2_fob', 'trx_quartal.q2_cnf', 'trx_quartal.q2_freight',
                    'trx_quartal.q3_base', 'trx_quartal.q3_alloy', 'trx_quartal.q3_fob', 'trx_quartal.q3_cnf', 'trx_quartal.q3_freight',
                    'trx_quartal.q4_base', 'trx_quartal.q4_alloy', 'trx_quartal.q4_fob', 'trx_quartal.q4_cnf', 'trx_quartal.q4_freight'
                )
                ->join('mst_material', 'trx_quartal.id_material', '=', 'mst_material.id')
                ->whereBetween('trx_quartal.thn', [$startYear, $endYear])
                ->when($materialId, fn ($q) => $q->where('trx_quartal.id_material', $materialId))
                ->where('mst_material.is_active', 1)
                ->orderBy('mst_material.grade')
                ->orderBy('mst_material.shape')
                ->orderBy('trx_quartal.thn');

            $data = $query->get();
            
            // Group by material to handle multiple years per material
            $groupedByMaterial = $data->groupBy('id_material');
            
            return $groupedByMaterial->map(function ($materialRows, $materialId) use ($startYear, $endYear) {
                $firstRow = $materialRows->first();
                $quarters = [];
                
                // Build quarters for all years in range
                for ($year = $startYear; $year <= $endYear; $year++) {
                    $yearData = $materialRows->firstWhere('thn', $year);
                    
                    for ($q = 1; $q <= 4; $q++) {
                        if ($yearData) {
                            $quarters[] = [
                                'period' => "Q{$q} {$year}",
                                'base' => (float) ($yearData->{"q{$q}_base"} ?: 0),
                                'alloy' => (float) ($yearData->{"q{$q}_alloy"} ?: 0),
                                'fob' => (float) ($yearData->{"q{$q}_fob"} ?: 0),
                                'cnf' => (float) ($yearData->{"q{$q}_cnf"} ?: 0),
                                'freight' => (float) ($yearData->{"q{$q}_freight"} ?: 0),
                            ];
                        } else {
                            // Fill empty quarters for years without data
                            $quarters[] = [
                                'period' => "Q{$q} {$year}",
                                'base' => 0, 'alloy' => 0, 'fob' => 0, 'cnf' => 0, 'freight' => 0,
                            ];
                        }
                    }
                }

                return [
                    // FIX: Gunakan id_material sebagai ID baris, bukan ID transaksi
                    'id' => $firstRow->id_material, 
                    'grade' => $firstRow->grade,
                    'material_name' => $firstRow->grade . ' - ' . $firstRow->shape,
                    'quarters' => $quarters,
                ];
            })->values();
        });
    }

    /**
     * Helper to get empty series structure
     * * @param int $count
     * @return array
     */
    private function getEmptySeries(int $count): array
    {
        $emptyData = array_fill(0, $count, null);
        return [
            ['name' => 'Base', 'data' => $emptyData, 'yAxis' => 0],
            ['name' => 'FOB', 'data' => $emptyData, 'yAxis' => 0],
            ['name' => 'CNF', 'data' => $emptyData, 'yAxis' => 0],
            ['name' => 'Alloy', 'data' => $emptyData, 'yAxis' => 1],
            ['name' => 'Freight', 'data' => $emptyData, 'yAxis' => 1],
        ];
    }

    /**
     * Resolve year range with defaults
     * * @param int|null $startYear
     * @param int|null $endYear
     * @return array
     */
    private function resolveYearRange(?int $startYear, ?int $endYear): array
    {
        $currentYear = (int) now()->year;
        
        if ($startYear === null || $endYear === null) {
            // Default: 1 year (current year)
            return [$currentYear, $currentYear];
        }

        if ($startYear > $endYear) {
            throw new \InvalidArgumentException('Tahun mulai tidak boleh lebih besar dari tahun akhir.');
        }

        return [$startYear, $endYear];
    }
}