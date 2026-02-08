<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\MstMaterial;
use App\Models\MstShape;
use App\Models\TrxQuartal;
use App\Models\TrsCurrency;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BOPMController extends Controller
{
    /**
     * Display BOPM dashboard
     */
    public function index(): View
    {
        $filterData = $this->getFilterData();
        $filterData['shapes'] = MstShape::query()
            ->select('id', 'name')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        return view('bopm.dashboardBOPM', $filterData);
    }

    /**
     * Get chart data via AJAX
     */
    public function getChartData(Request $request): JsonResponse
    {
        Log::info('=== BOPM getChartData START ===');
        Log::info('Request all data:', $request->all());
        
        try {
            $validated = $this->validateFilterRequest($request);
            Log::info('Validated data:', $validated);
            
            // Ensure multiplier is float
            $multiplier = isset($validated['multiplier']) ? (float) $validated['multiplier'] : 1.0;
            Log::info('Multiplier value:', ['multiplier' => $multiplier, 'type' => gettype($multiplier)]);
            
            // Get quarter range from request
            $startQuarter = $request->input('start_quarter');
            $startYear = $request->input('start_year');
            $endQuarter = $request->input('end_quarter');
            $endYear = $request->input('end_year');
            
            Log::info('Quarter Range:', [
                'start_quarter' => $startQuarter,
                'start_year' => $startYear,
                'end_quarter' => $endQuarter,
                'end_year' => $endYear
            ]);
            
            $chartData = $this->buildChartData(
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null,
                $validated['material_id'] ?? null,
                $validated['currency_id'] ?? null,
                $multiplier,
                $startQuarter,
                $startYear,
                $endQuarter,
                $endYear
            );
            
            // Get currency symbol for chart Y-axis
            $currencySymbol = '¥'; // Default YEN
            $currencyName = 'YEN';
            if ($validated['currency_id'] ?? null) {
                $currency = TrsCurrency::find($validated['currency_id']);
                if ($currency) {
                    $currencySymbol = $this->getCurrencySymbol($currency->currency);
                    $currencyName = $currency->currency;
                }
            }

            return response()->json([
                'success' => true,
                'data' => array_merge($chartData, [
                    'currency_symbol' => $currencySymbol,
                    'currency_name' => $currencyName,
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('BOPM Chart Data Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data chart: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get table data via AJAX
     */
    public function getTableData(Request $request): JsonResponse
    {
        Log::info('=== BOPM getTableData START ===');
        Log::info('Request all data:', $request->all());
        
        try {
            $validated = $this->validateFilterRequest($request);
            Log::info('Validated data:', $validated);
            
            // Ensure multiplier is float
            $multiplier = isset($validated['multiplier']) ? (float) $validated['multiplier'] : 1.0;
            
            // Get quarter range from request
            $startQuarter = $request->input('start_quarter');
            $startYear = $request->input('start_year');
            $endQuarter = $request->input('end_quarter');
            $endYear = $request->input('end_year');
            
            Log::info('Quarter Range for Table:', [
                'start_quarter' => $startQuarter,
                'start_year' => $startYear,
                'end_quarter' => $endQuarter,
                'end_year' => $endYear
            ]);
            
            $tableData = $this->buildTableData(
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null,
                $validated['material_id'] ?? null,
                $validated['currency_id'] ?? null,
                $multiplier,
                $startQuarter,
                $startYear,
                $endQuarter,
                $endYear
            );

            // Convert Collection to array for JSON response
            return response()->json([
                'success' => true,
                'data' => $tableData->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('BOPM Table Data Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data tabel: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save currency data
     */
    public function saveCurrency(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'currency' => 'required|string|max:10',
                'kurs' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/',
            ]);

            $currency = \App\Models\TrsCurrency::create([
                'currency' => strtoupper($validated['currency']),
                'kurs' => $validated['kurs'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data currency berhasil disimpan',
                'data' => $currency,
            ]);
        } catch (\Exception $e) {
            Log::error('BOPM Save Currency Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data currency: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get currency symbol based on currency code
     */
    private function getCurrencySymbol(string $currency): string
    {
        return match(strtoupper($currency)) {
            'IDR' => 'Rp',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY', 'YEN' => '¥',
            default => strtoupper($currency),
        };
    }
    
    /**
     * Get currency list
     */
    public function getCurrencyList(): JsonResponse
    {
        try {
            $currencyList = TrsCurrency::getCurrencyListByMonth();

            return response()->json([
                'success' => true,
                'data' => $currencyList,
            ]);
        } catch (\Exception $e) {
            Log::error('BOPM Get Currency List Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data currency: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store new material
     */
    public function storeMaterial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'grade' => 'required|string|max:100',
            'shape' => 'required|integer|exists:mst_shape,id',
        ]);

        $material = MstMaterial::create([
            'id_lc' => 1,
            'grade' => $validated['grade'],
            'shape' => $validated['shape'],
            'is_active' => 1,
            'update_by' => auth()->id(),
            'last_update' => now(),
        ]);

        // Clear cache so dropdown refreshes on next load
        Cache::forget('bopm:materials:list');

        $shapeName = MstShape::where('id', $validated['shape'])->value('name');
        return response()->json([
            'success' => true,
            'message' => 'Material berhasil ditambahkan',
            'data' => [
                'id' => $material->id,
                'label' => $material->grade . ' - ' . ($shapeName ?? $validated['shape']),
            ],
        ]);
    }

    /**
     * Export Quarterly Data as Excel with same format as table display
     */
    public function exportTableData(Request $request)
    {
        try {
            $validated = $this->validateFilterRequest($request);
            $multiplier = isset($validated['multiplier']) ? (float) $validated['multiplier'] : 1.0;
            
            $startQuarter = $request->input('start_quarter');
            $startYear = $request->input('start_year');
            $endQuarter = $request->input('end_quarter');
            $endYear = $request->input('end_year');
            
            $tableData = $this->buildTableData(
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null,
                $validated['material_id'] ?? null,
                $validated['currency_id'] ?? null,
                $multiplier,
                $startQuarter,
                $startYear,
                $endQuarter,
                $endYear
            );

            // Build quarters for header
            $quarters = [];
            $sYear = $startYear ?? (int) date('Y', strtotime($validated['start_date'] ?? now()));
            $eYear = $endYear ?? (int) date('Y', strtotime($validated['end_date'] ?? now()));
            $sQuarter = $startQuarter ?? 1;
            $eQuarter = $endQuarter ?? 4;
            
            for ($year = $sYear; $year <= $eYear; $year++) {
                $qStart = ($year == $sYear) ? $sQuarter : 1;
                $qEnd = ($year == $eYear) ? $eQuarter : 4;
                for ($q = $qStart; $q <= $qEnd; $q++) {
                    $quarters[] = "Q{$q} {$year}";
                }
            }

            // Build header row
            $headings = array_merge(['Grade', 'Component'], $quarters);
            $components = ['Base', 'Alloy', 'FOB', 'CNF', 'Freight'];
            
            // Build data rows
            $rows = collect();
            foreach ($tableData as $material) {
                foreach ($components as $compIndex => $component) {
                    $row = [
                        'Grade' => $compIndex === 0 ? $material['grade'] : '',
                        'Component' => $component
                    ];
                    
                    foreach ($material['quarters'] as $qIndex => $qData) {
                        $key = strtolower($component);
                        $row[$quarters[$qIndex]] = $qData[$key] ?? 0;
                    }
                    
                    $rows->push($row);
                }
            }

            $export = new class($rows, $headings) implements FromCollection, WithHeadings {
                public function __construct(private Collection $rows, private array $headings) {}

                public function collection(): Collection
                {
                    return $this->rows;
                }

                public function headings(): array
                {
                    return $this->headings;
                }
            };

            $filename = 'bopm-quarterly-data-' . date('Y-m-d') . '.xlsx';
            return Excel::download($export, $filename);
        } catch (\Exception $e) {
            Log::error('BOPM Export Table Data Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Gagal export data: ' . $e->getMessage());
        }
    }

    /**
     * Export Excel template berisi material & shape untuk tahun/quarter terpilih.
     */
    public function exportTemplate(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2099',
            'quarter' => 'required|in:Q1,Q2,Q3,Q4',
        ]);

        $year = (int) $validated['year'];
        $quarter = strtoupper($validated['quarter']);

        $headings = ['nama material', 'shape', 'tahun', 'quartal', 'base', 'alloy', 'fob', 'cnf', 'freight'];

        $qKey = strtolower($quarter); // q1, q2, q3, q4

        $materials = MstMaterial::query()
            ->leftJoin('mst_shape', 'mst_shape.id', '=', 'mst_material.shape')
            ->leftJoin('trx_quartal', function($join) use ($year) {
                $join->on('trx_quartal.id_material', '=', 'mst_material.id')
                     ->where('trx_quartal.thn', '=', $year);
            })
            ->select(
                'mst_material.id', 
                'mst_material.grade', 
                'mst_shape.name as shape_name',
                "trx_quartal.{$qKey}_base as base",
                "trx_quartal.{$qKey}_alloy as alloy",
                "trx_quartal.{$qKey}_fob as fob",
                "trx_quartal.{$qKey}_cnf as cnf",
                "trx_quartal.{$qKey}_freight as freight"
            )
            ->where('mst_material.is_active', 1)
            ->orderBy('mst_material.grade')
            ->get()
            ->map(function ($row) use ($year, $quarter) {
                return [
                    'nama material' => $row->grade ?? '',
                    'shape' => $row->shape_name ?? '',
                    'tahun' => $year,
                    'quartal' => $quarter,
                    'base' => $row->base,
                    'alloy' => $row->alloy,
                    'fob' => $row->fob,
                    'cnf' => $row->cnf,
                    'freight' => $row->freight,
                ];
            });

        $export = new class($materials, $headings) implements FromCollection, WithHeadings {
            public function __construct(private Collection $rows, private array $headings) {}

            public function collection(): Collection
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return $this->headings;
            }
        };

        $filename = 'bopm-template-' . $year . '-' . strtolower($quarter) . '.xlsx';
        return Excel::download($export, $filename);
    }

    /**
     * Import Excel data untuk isi base/alloy/fob/cnf/freight per quarter.
     */
    public function importData(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2099',
            'quarter' => 'required|in:Q1,Q2,Q3,Q4',
            'import_file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            $year = (int) $request->year;
            $quarter = strtoupper($request->quarter);

            $file = $request->file('import_file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $excelRows = $worksheet->toArray();

            // Skip header row (row 0)
            $rows = collect($excelRows)->slice(1)->filter(function ($row) {
                // Filter out empty rows
                return !empty(array_filter($row, fn($cell) => $cell !== null && $cell !== ''));
            });

            if ($rows->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File kosong atau format tidak sesuai.',
                ], 422);
            }

            $errors = [];
            $validRows = [];

            foreach ($rows as $idx => $row) {
                $rowNumber = $idx + 2; // header row = 1, first data row = 2
                
                // Column indexes: 0=nama material, 1=shape, 2=tahun, 3=quartal, 4=base, 5=alloy, 6=fob, 7=cnf, 8=freight
                $grade = trim((string)($row[0] ?? ''));
                $shapeName = trim((string)($row[1] ?? ''));

                if ($grade === '' || $shapeName === '') {
                    $errors[] = "Baris {$rowNumber}: nama material atau shape kosong";
                    continue;
                }

                $shape = MstShape::whereRaw('LOWER(name) = ?', [strtolower($shapeName)])->first();
                if (!$shape) {
                    $errors[] = "Baris {$rowNumber}: shape '{$shapeName}' tidak ditemukan";
                    continue;
                }

                $material = MstMaterial::where('grade', $grade)
                    ->where('shape', $shape->id)
                    ->first();

                if (!$material) {
                    $errors[] = "Baris {$rowNumber}: material '{$grade}' dengan shape '{$shapeName}' tidak ditemukan";
                    continue;
                }

                $quarterKey = strtolower($quarter); // q1, q2, q3, q4
                $fieldBase = $quarterKey . '_base';
                $fieldAlloy = $quarterKey . '_alloy';
                $fieldFob = $quarterKey . '_fob';
                $fieldCnf = $quarterKey . '_cnf';
                $fieldFreight = $quarterKey . '_freight';

                // Column indexes: 4=base, 5=alloy, 6=fob, 7=cnf, 8=freight
                $parsedFields = [
                    $fieldBase => $this->parseNullableNumber($row[4] ?? null),
                    $fieldAlloy => $this->parseNullableNumber($row[5] ?? null),
                    $fieldFob => $this->parseNullableNumber($row[6] ?? null),
                    $fieldCnf => $this->parseNullableNumber($row[7] ?? null),
                    $fieldFreight => $this->parseNullableNumber($row[8] ?? null),
                ];

                // Jika semua nilai kosong (base, alloy, fob, cnf, freight), lewati baris ini tanpa error
                $hasValue = collect($parsedFields)->filter(function ($v) {
                    return $v !== null && $v !== '';
                })->isNotEmpty();
                
                if (!$hasValue) {
                    // All values empty, skip this row without creating/updating
                    continue;
                }

                $validRows[] = [
                    'material' => $material,
                    'fields' => $parsedFields,
                ];
            }

            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => implode("; ", $errors),
                ], 422);
            }

            foreach ($validRows as $item) {
                $material = $item['material'];
                $fields = $item['fields'];

                $record = TrxQuartal::firstOrNew([
                    'id_material' => $material->id,
                    'thn' => $year,
                ]);

                foreach ($fields as $field => $value) {
                    // Hanya update jika value dari excel tidak kosong/null (Partial Update)
                    if ($value !== null) {
                        $record->{$field} = $value;
                    }
                }

                $record->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Import berhasil disimpan.',
            ]);
        } catch (\Exception $e) {
            Log::error('BOPM Import Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal import: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function parseNullableNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        // Handle both comma and dot as decimal separators
        // Remove any thousand separators first
        $cleaned = str_replace([',', ' ', "'"], '', (string) $value);
        // Now convert to float
        return (float) $cleaned;
    }

    // ============================================
    // PRIVATE METHODS (Logic dari Service & Request)
    // ============================================

    /**
     * Validate filter request (dari BopmDashboardFilterRequest)
     */
    private function validateFilterRequest(Request $request): array
    {
        return $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'material_id' => ['nullable', 'integer', 'exists:mst_material,id'],
            'currency_id' => ['nullable', 'integer'],
            'multiplier' => ['nullable', 'numeric', 'min:0'],
        ], [
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.date' => 'Tanggal mulai harus berupa tanggal yang valid.',
            'end_date.required' => 'Tanggal akhir wajib diisi.',
            'end_date.date' => 'Tanggal akhir harus berupa tanggal yang valid.',
            'end_date.after_or_equal' => 'Tanggal akhir harus lebih besar atau sama dengan tanggal mulai.',
            'material_id.integer' => 'Material ID harus berupa angka.',
            'material_id.exists' => 'Material yang dipilih tidak ditemukan.',
            'currency_id.integer' => 'Currency ID harus berupa angka.',
            'multiplier.numeric' => 'Multiplier harus berupa angka.',
            'multiplier.min' => 'Multiplier harus lebih besar dari 0.',
        ]);
    }

    /**
     * Get filter data for dashboard (dari BopmDashboardService)
     */
    private function getFilterData(): array
    {
        return Cache::remember('bopm:materials:list', now()->addHours(24), function () {
            return [
                'materials' => MstMaterial::query()
                    ->leftJoin('mst_shape', 'mst_shape.id', '=', 'mst_material.shape')
                    ->select('mst_material.id', 'mst_material.grade', 'mst_material.shape', 'mst_shape.name as shape_name')
                    ->where('mst_material.is_active', 1)
                    ->orderBy('mst_material.grade')
                    ->orderBy('mst_material.shape')
                    ->get()
                    ->map(fn ($material) => [
                        'id' => $material->id,
                        'label' => $material->grade . ' - ' . ($material->shape_name ?? $material->shape),
                    ]),
                'years' => $this->getAvailableYears(),
                'currency_list' => TrsCurrency::getCurrencyListByMonth(),
            ];
        });
    }

    /**
     * Get available years from trx_quartal (Last 5 years)
     */
    private function getAvailableYears(): array
    {
        $currentYear = (int) now()->year;
        $years = [];
        
        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $years[] = [
                'value' => $year,
                'label' => "Tahun {$year}",
            ];
        }
        
        return $years;
    }

    /**
     * Build chart data for Highcharts (dari BopmDashboardService)
     */
    private function buildChartData(?string $startDate, ?string $endDate, ?int $materialId, ?int $currencyId, float $multiplier = 1, ?int $startQuarter = null, ?int $startYear = null, ?int $endQuarter = null, ?int $endYear = null): array
    {
        // Use quarter range if provided, otherwise parse date range
        if ($startQuarter && $startYear && $endQuarter && $endYear) {
            $yearStart = $startYear;
            $yearEnd = $endYear;
        } else {
            [$yearStart, $yearEnd] = $this->parseDateRange($startDate, $endDate);
            // Default to full year range if no quarter specified
            $startQuarter = $startQuarter ?? 1;
            $endQuarter = $endQuarter ?? 4;
            $startYear = $startYear ?? $yearStart;
            $endYear = $endYear ?? $yearEnd;
        }
        
        
        $kurs = $currencyId ? TrsCurrency::getKursById($currencyId) : 1;
        // $finalMultiplier = $kurs * $multiplier; // OLD LOGIC: Combined
        $currencyRate = $kurs; // NEW LOGIC: Just currency rate for most fields
        
        Log::info('=== buildChartData with Quarter Range ===', [
            'multiplier' => $multiplier,
            'kurs' => $kurs,
            'currencyRate' => $currencyRate,
            'currencyId' => $currencyId,
            'startQuarter' => $startQuarter,
            'startYear' => $startYear,
            'endQuarter' => $endQuarter,
            'endYear' => $endYear
        ]);

        $cacheKey = sprintf(
            'bopm:chart:%d:%d:%d:%d:%s:%s:%s',
            $startYear,
            $startQuarter,
            $endYear,
            $endQuarter,
            $materialId ?: 'all',
            $currencyId ?: 'yen',
            $multiplier
        );

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($yearStart, $yearEnd, $materialId, $currencyRate, $multiplier, $startQuarter, $startYear, $endQuarter, $endYear) {
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
                ->whereRaw('CAST(thn AS UNSIGNED) BETWEEN ? AND ?', [$startYear, $endYear])
                ->when($materialId, fn ($q) => $q->where('id_material', $materialId))
                ->orderByRaw('CAST(thn AS UNSIGNED)')
                ->orderBy('id_material');

            $data = $query->get();

            // Prepare categories based on quarter range
            $categories = [];
            for ($year = $startYear; $year <= $endYear; $year++) {
                $qStart = ($year == $startYear) ? $startQuarter : 1;
                $qEnd = ($year == $endYear) ? $endQuarter : 4;
                
                for ($q = $qStart; $q <= $qEnd; $q++) {
                    $categories[] = "Q{$q} {$year}";
                }
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

            // Group by Year
            $groupedData = $data->groupBy(function($item) {
                return (int) $item->thn;
            });
            
            $categoryIndex = 0;
            for ($year = $startYear; $year <= $endYear; $year++) {
                $qStart = ($year == $startYear) ? $startQuarter : 1;
                $qEnd = ($year == $endYear) ? $endQuarter : 4;
                
                $yearData = $groupedData->get($year, collect());
                
                for ($q = $qStart; $q <= $qEnd; $q++) {
                    $qBase = "q{$q}_base";
                    $qAlloy = "q{$q}_alloy";
                    $qFob = "q{$q}_fob";
                    $qCnf = "q{$q}_cnf";
                    $qFreight = "q{$q}_freight";
                    
                    if ($materialId === null) {
                        // All materials - calculate sum
                        $series['base'][$categoryIndex] = $yearData->sum($qBase) * $currencyRate ?: null;
                        $series['alloy'][$categoryIndex] = $yearData->sum($qAlloy) * $currencyRate ?: null;
                        $series['fob'][$categoryIndex] = $yearData->sum($qFob) * $currencyRate ?: null;
                        // CNF gets multiplier AND currencyRate
                        $series['cnf'][$categoryIndex] = $yearData->sum($qCnf) * $currencyRate * $multiplier ?: null;
                        $series['freight'][$categoryIndex] = $yearData->sum($qFreight) * $currencyRate ?: null;
                    } else {
                        // Single material
                        $record = $yearData->firstWhere('id_material', $materialId);
                        if ($record) {
                            $series['base'][$categoryIndex] = $record->$qBase * $currencyRate ?: null;
                            $series['alloy'][$categoryIndex] = $record->$qAlloy * $currencyRate ?: null;
                            $series['fob'][$categoryIndex] = $record->$qFob * $currencyRate ?: null;
                            // CNF gets multiplier AND currencyRate
                            $series['cnf'][$categoryIndex] = $record->$qCnf * $currencyRate * $multiplier ?: null;
                            $series['freight'][$categoryIndex] = $record->$qFreight * $currencyRate ?: null;
                        }
                    }
                    
                    $categoryIndex++;
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
     * Build table data (dari BopmDashboardService)
     */
    private function buildTableData(?string $startDate, ?string $endDate, ?int $materialId, ?int $currencyId, float $multiplier = 1, ?int $startQuarter = null, ?int $startYear = null, ?int $endQuarter = null, ?int $endYear = null): Collection
    {
        // Use quarter range if provided, otherwise parse date range
        if ($startQuarter && $startYear && $endQuarter && $endYear) {
            $yearStart = $startYear;
            $yearEnd = $endYear;
        } else {
            [$yearStart, $yearEnd] = $this->parseDateRange($startDate, $endDate);
            $startQuarter = $startQuarter ?? 1;
            $endQuarter = $endQuarter ?? 4;
            $startYear = $startYear ?? $yearStart;
            $endYear = $endYear ?? $yearEnd;
        }
        
        $kurs = $currencyId ? TrsCurrency::getKursById($currencyId) : 1;
        // $finalMultiplier = $kurs * $multiplier; // OLD LOGIC
        $currencyRate = $kurs; // NEW LOGIC

        $cacheKey = sprintf(
            'bopm:table:%d:%d:%d:%d:%s:%s:%s',
            $startYear,
            $startQuarter,
            $endYear,
            $endQuarter,
            $materialId ?: 'all',
            $currencyId ?: 'yen',
            $multiplier
        );

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($yearStart, $yearEnd, $materialId, $currencyRate, $multiplier, $startQuarter, $startYear, $endQuarter, $endYear) {
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
                ->whereRaw('CAST(trx_quartal.thn AS UNSIGNED) BETWEEN ? AND ?', [$startYear, $endYear])
                ->when($materialId, fn ($q) => $q->where('trx_quartal.id_material', $materialId))
                ->where('mst_material.is_active', 1)
                ->orderBy('mst_material.grade')
                ->orderBy('mst_material.shape')
                ->orderByRaw('CAST(trx_quartal.thn AS UNSIGNED)');

            $data = $query->get();
            
            // Group by material to handle multiple years per material
            $groupedByMaterial = $data->groupBy('id_material');
            
            return $groupedByMaterial->map(function ($materialRows) use ($yearStart, $yearEnd, $currencyRate, $multiplier, $startQuarter, $startYear, $endQuarter, $endYear) {
                $firstRow = $materialRows->first();
                $quarters = [];
                
                // Build quarters only for specified range
                for ($year = $startYear; $year <= $endYear; $year++) {
                    $qStart = ($year == $startYear) ? $startQuarter : 1;
                    $qEnd = ($year == $endYear) ? $endQuarter : 4;
                    
                    $yearData = $materialRows->first(function($item) use ($year) {
                        return (int) $item->thn === $year;
                    });
                    
                    for ($q = $qStart; $q <= $qEnd; $q++) {
                        if ($yearData) {
                            $quarters[] = [
                                'period' => "Q{$q} {$year}",
                                'base' => (float) ($yearData->{"q{$q}_base"} ?: 0) * $currencyRate,
                                'alloy' => (float) ($yearData->{"q{$q}_alloy"} ?: 0) * $currencyRate,
                                'fob' => (float) ($yearData->{"q{$q}_fob"} ?: 0) * $currencyRate,
                                'cnf' => (float) ($yearData->{"q{$q}_cnf"} ?: 0) * $currencyRate * $multiplier, // Apply multiplier only to CNF
                                'freight' => (float) ($yearData->{"q{$q}_freight"} ?: 0) * $currencyRate,
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
                    'id' => $firstRow->id_material, 
                    'grade' => $firstRow->grade,
                    'material_name' => $firstRow->grade . ' - ' . $firstRow->shape,
                    'quarters' => $quarters,
                ];
            })->values();
        });
    }

    /**
     * Parse date range and extract years
     */
    private function parseDateRange(?string $startDate, ?string $endDate): array
    {
        $currentYear = (int) now()->year;
        
        if (!$startDate || !$endDate) {
            return [$currentYear, $currentYear];
        }

        $startYear = (int) date('Y', strtotime($startDate));
        $endYear = (int) date('Y', strtotime($endDate));

        if ($startYear > $endYear) {
            throw new \InvalidArgumentException('Tanggal mulai tidak boleh lebih besar dari tanggal akhir.');
        }

        return [$startYear, $endYear];
    }

    /**
     * Helper to get empty series structure
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
}
