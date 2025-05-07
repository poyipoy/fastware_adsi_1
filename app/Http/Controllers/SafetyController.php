<?php

namespace App\Http\Controllers;

use App\Exports\SafetyExport;
use App\Models\SafetyPatrol;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SafetyController extends Controller
{
    public function listSafetyPatrol()
    {
        $patrols = SafetyPatrol::orderBy('updated_at', 'desc')->get();

        return view('safety_patrol.list', compact('patrols'))->with('i', (request()->input('page', 1) - 1) * 5);
    }

    public function listSafetyPatrolPIC()
    {
        $patrols = SafetyPatrol::orderBy('updated_at', 'desc')->get();

        // Define the areas of patrol
        $areas = [
            'HEAT TREATMENT',
            'CUTTING PRODUCTIONS',
            'MACHINING CUSTOM',
            'MACHINING',
            'DELIVERY',
            'MAINTENANCE',
            'WAREHOUSE'
        ];

        return view('safety_patrol.piclist', compact('patrols', 'areas'))->with('i', (request()->input('page', 1) - 1) * 5);
    }

    public function reportPatrol()
    {
        $patrols = SafetyPatrol::orderBy('updated_at', 'desc')->get();

        // Query untuk menghitung total entri per PIC Area
        $areaPatrolsData = SafetyPatrol::select('area_patrol', DB::raw('COUNT(*) as area_patrols'))
            ->groupBy('area_patrol')
            ->get();

        // Query untuk menghitung total entri per PIC Area
        $picAreasData = SafetyPatrol::select('pic_area', DB::raw('COUNT(*) as total_entries'))
            ->groupBy('pic_area')
            ->get();

        // Query untuk menghitung total form berdasarkan area patrol
        $areaPatrolData = SafetyPatrol::select('pic_area', 'area_patrol', DB::raw('COUNT(*) as total_forms'))
            ->groupBy('pic_area', 'area_patrol')
            ->get()
            ->groupBy('pic_area')
            ->map(function ($item) {
                return $item->pluck('total_forms', 'area_patrol');
            });

        // Siapkan data untuk digunakan oleh HighchartsJS
        $piclabels = $picAreasData->pluck('pic_area');
        $totalEntries = $picAreasData->pluck('total_entries');



        // Define category labels
        $labels = [
            'Kelengkapan Alat 5S / 5R' => 'kategori_1',
            'Kerapihan Area Kerja' => 'kategori_2',
            'Kondisi Lingkungan Kerja' => 'kategori_3',
            'Penempatan Alat / Barang' => 'kategori_4',
            'Praktik 5S / 5R Oleh Pekerja' => 'kategori_5'
        ];

        // Query to get category counts per area
        $kategoriCounts = [];

        // Loop through labels to get counts for each label
        foreach ($labels as $label => $kategori) {
            $counts = [];
            for ($i = 1; $i <= 5; $i++) {
                $counts["kategori_$i"] = SafetyPatrol::where($kategori, $i)->count();
            }
            $kategoriCounts[$label] = $counts;
        }

        // Define category labels
        $safetylabels = [
            'Checksheet APAR' => 'safety_1',
            'Penggunaan APD' => 'safety_2',
            'Potensi Bahaya' => 'safety_3',
            'Pemeliharaan APD' => 'safety_4',
            'Kelengkapan APD' => 'safety_5'
        ];

        // Query to get category counts per area
        $safetyCounts = [];

        // Loop through labels to get counts for each label
        foreach ($safetylabels as $label => $safety) {
            $counts = [];
            for ($i = 1; $i <= 5; $i++) {
                $counts["safety_$i"] = SafetyPatrol::where($safety, $i)->count();
            }
            $safetyCounts[$label] = $counts;
        }

        // Define category labels
        $lingkunganlabels = [
            'Pengelolaan Jenis & Kriteria Limbah' => 'lingkungan_1',
            'Kebersihan Lingkungan' => 'lingkungan_2',
            'Penyimpanan Limbah' => 'lingkungan_3',
            'Tempat Sampah' => 'lingkungan_4',
        ];

        // Query to get category counts per area
        $lingkunganCounts = [];

        // Loop through labels to get counts for each label
        foreach ($lingkunganlabels as $label => $lingkungan) {
            $counts = [];
            for ($i = 1; $i <= 5; $i++) {
                $counts["safety_$i"] = SafetyPatrol::where($lingkungan, $i)->count();
            }
            $lingkunganCounts[$label] = $counts;
        }

        return view('safety_patrol.report', compact(
            'patrols',
            'areaPatrolsData',
            'kategoriCounts',
            'safetyCounts',
            'areaPatrolData',
            'lingkunganCounts',
            'piclabels',
            'totalEntries'
        ));
    }


    public function getAreaPatrol()
    {
        // Query untuk menghitung total entri per PIC Area
        $areaPatrolsData = SafetyPatrol::select('area_patrol', DB::raw('COUNT(*) as area_patrols'))
            ->groupBy('area_patrol')
            ->get();

        // Siapkan data untuk digunakan oleh HighchartsJS
        $labels = $areaPatrolsData->pluck('area_patrol');
        $areaPatrols = $areaPatrolsData->pluck('area_patrols');

        // Kirim data sebagai respons JSON
        return response()->json([
            'labels' => $labels,
            'area_patrols' => $areaPatrols
        ]);
    }

    public function getKategoriPatrol()
    {
        // Define category labels
        $labels = [
            'Kelengkapan Alat 5S / 5R' => 'kategori_1',
            'Kerapihan Area Kerja' => 'kategori_2',
            'Kondisi Lingkungan Kerja' => 'kategori_3',
            'Penempatan Alat / Barang' => 'kategori_4',
            'Praktik 5S / 5R Oleh Pekerja' => 'kategori_5'
        ];

        // Query to get category counts per area
        $kategoriCounts = [];

        // Loop through labels to get counts for each label
        foreach ($labels as $label => $kategori) {
            $counts = [];
            for ($i = 1; $i <= 5; $i++) {
                $counts["kategori_$i"] = SafetyPatrol::where($kategori, $i)->count();
            }
            $kategoriCounts[$label] = $counts;
        }

        return response()->json([
            'kategori_counts' => $kategoriCounts
        ]);
    }


    public function getSafetyPatrol()
    {
        // Define category labels
        $labels = [
            'Checksheet APAR' => 'safety_1',
            'Penggunaan APD' => 'safety_2',
            'Potensi Bahaya' => 'safety_3',
            'Pemeliharaan APD' => 'safety_4',
            'Kelengkapan APD' => 'safety_5'
        ];

        // Query to get category counts per area
        $safetyCounts = [];

        // Loop through labels to get counts for each label
        foreach ($labels as $label => $safety) {
            $counts = [];
            for ($i = 1; $i <= 5; $i++) {
                $counts["safety_$i"] = SafetyPatrol::where($safety, $i)->count();
            }
            $safetyCounts[$label] = $counts;
        }

        return response()->json([
            'safety_counts' => $safetyCounts
        ]);
    }

    public function getLingkunganPatrol()
    {
        // Define category labels
        $labels = [
            'Pengelolaan Jenis & Kriteria Limbah' => 'lingkungan_1',
            'Kebersihan Lingkungan' => 'lingkungan_2',
            'Penyimpanan Limbah' => 'lingkungan_3',
            'Tempat Sampah' => 'lingkungan_4',
        ];

        // Query to get category counts per area
        $lingkunganCounts = [];

        // Loop through labels to get counts for each label
        foreach ($labels as $label => $lingkungan) {
            $counts = [];
            for ($i = 1; $i <= 5; $i++) {
                $counts["safety_$i"] = SafetyPatrol::where($lingkungan, $i)->count();
            }
            $lingkunganCounts[$label] = $counts;
        }

        return response()->json([
            'lingkungan_counts' => $lingkunganCounts
        ]);
    }

    public function getPICArea()
    {
        // Query untuk menghitung total entri per PIC Area
        $picAreasData = SafetyPatrol::select('pic_area', DB::raw('COUNT(*) as total_entries'))
            ->groupBy('pic_area')
            ->get();

        // Siapkan data untuk digunakan oleh HighchartsJS
        $labels = $picAreasData->pluck('pic_area');
        $totalEntries = $picAreasData->pluck('total_entries');

        // Query untuk menghitung total form berdasarkan area patrol
        $areaPatrolData = SafetyPatrol::select('pic_area', 'area_patrol', DB::raw('COUNT(*) as total_forms'))
            ->groupBy('pic_area', 'area_patrol')
            ->get()
            ->groupBy('pic_area')
            ->map(function ($item) {
                return $item->pluck('total_forms', 'area_patrol');
            });

        // Kirim data sebagai respons JSON
        return response()->json([
            'labels' => $labels,
            'total_entries' => $totalEntries,
            'area_patrol_data' => $areaPatrolData
        ]);
    }


    public function buatFormSafety()
    {
        return view('safety_patrol.form');
    }

    public function detailPatrol(SafetyPatrol $patrol)
    {
        return view('safety_patrol.detail', compact('patrol'));
    }

    public function simpanPatrol(Request $request)
    {
        // Simpan data mesin beserta path foto dan sparepart ke database
        SafetyPatrol::create([
            'tanggal_patrol' => $request->tanggal_patrol,
            'area_patrol' => $request->area_patrol,
            'pic_area' => $request->pic_area,
            'petugas_patrol' => $request->petugas_patrol,
            'kategori_1' => $request->kategori_1,
            'kategori_2' => $request->kategori_2,
            'kategori_3' => $request->kategori_3,
            'kategori_4' => $request->kategori_4,
            'kategori_5' => $request->kategori_5,
            'kategori_catatan' => $request->kategori_catatan,
            'safety_1' => $request->safety_1,
            'safety_2' => $request->safety_2,
            'safety_3' => $request->safety_3,
            'safety_4' => $request->safety_4,
            'safety_5' => $request->safety_5,
            'safety_catatan' => $request->safety_catatan,
            'lingkungan_1' => $request->lingkungan_1,
            'lingkungan_2' => $request->lingkungan_2,
            'lingkungan_3' => $request->lingkungan_3,
            'lingkungan_4' => $request->lingkungan_4,
            'lingkungan_catatan' => $request->lingkungan_catatan,
        ]);

        return redirect()->route('listpatrol');
    }

    public function exportData(Request $request)
    {
        // Fetch the selected area of patrol from the request
        $selectedArea = $request->input('area_patrol');

        // Fetch the patrol data from the database based on the selected area
        if ($selectedArea !== 'All') {
            $patrolData = SafetyPatrol::where('area_patrol', $selectedArea)->get();
        } else {
            $patrolData = SafetyPatrol::all();
        }

        // Structure the data for export
        $data = [
            [
                'AREA PATROL', // Add new column
                'KATEGORI', 'ITEM CHECK', 'Januari', 'Februari', 'Maret', 'April', 'Mei',
                'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ]
        ];

        $categories = [
            '5R/5S' => [
                'kategori_1' => 'Kelengkapan Alat 5R / 5S',
                'kategori_2' => 'Kerapihan Area Kerja',
                'kategori_3' => 'Kondisi Lingkungan Kerja',
                'kategori_4' => 'Penempatan Alat / Barang',
                'kategori_5' => 'Praktik 5S / 5R Oleh Pekerja'
            ],
            'SAFETY' => [
                'safety_1' => 'Checksheet APAR',
                'safety_2' => 'Penggunaan APD',
                'safety_3' => 'Potensi Bahaya',
                'safety_4' => 'Pemeliharaan APD',
                'safety_5' => 'Kelengkapan APD'
            ],
            'LINGKUNGAN' => [
                'lingkungan_1' => 'Pengelolaan Jenis & Kriteria Limbah',
                'lingkungan_2' => 'Kebersihan Lingkungan',
                'lingkungan_3' => 'Penyimpanan Limbah',
                'lingkungan_4' => 'Tempat Sampah'
            ]
        ];

        // Initialize arrays to hold sum and count data for overall averages
        $overallSums = array_fill(0, 12, 0);
        $overallCounts = array_fill(0, 12, 0);

        // Fill the data for export
        if ($patrolData->isNotEmpty()) {
            $groupedData = [];
            foreach ($patrolData as $patrol) {
                $groupedData[$patrol->area_patrol][] = $patrol;
            }

            foreach ($groupedData as $area => $areaPatrols) {
                foreach ($categories as $category => $items) {
                    $categorySums = array_fill(0, 12, 0);
                    $categoryCounts = array_fill(0, 12, 0);

                    foreach ($items as $itemKey => $itemName) {
                        $monthlyData = array_fill(0, 12, 0);
                        $counts = array_fill(0, 12, 0);

                        foreach ($areaPatrols as $patrol) {
                            $patrolMonth = Carbon::parse($patrol->tanggal_patrol)->month - 1;
                            $itemValue = (int)$patrol->{$itemKey};
                            if ($itemValue > 0) {
                                $monthlyData[$patrolMonth] += $itemValue;
                                $counts[$patrolMonth]++;
                                $categorySums[$patrolMonth] += $itemValue;
                                $categoryCounts[$patrolMonth]++;
                                $overallSums[$patrolMonth] += $itemValue;
                                $overallCounts[$patrolMonth]++;
                            }
                        }

                        for ($monthIndex = 0; $monthIndex < 12; $monthIndex++) {
                            if ($counts[$monthIndex] > 0) {
                                $monthlyData[$monthIndex] = round($monthlyData[$monthIndex] / $counts[$monthIndex], 2);
                            }
                        }

                        $data[] = array_merge([$area, $category, $itemName], $monthlyData);
                    }

                    // Calculate category averages
                    $categoryAverages = [];
                    for ($monthIndex = 0; $monthIndex < 12; $monthIndex++) {
                        if ($categoryCounts[$monthIndex] > 0) {
                            $categoryAverages[$monthIndex] = round($categorySums[$monthIndex] / $categoryCounts[$monthIndex], 2);
                        } else {
                            $categoryAverages[$monthIndex] = 0;
                        }
                    }

                    $data[] = array_merge([$area, $category, 'Total Kategori'], $categoryAverages);
                }

                // Calculate area average
                $areaAverageRow = array_fill(0, 15, '');
                $areaAverageRow[0] = $area;
                $areaAverageRow[1] = 'Total Nilai';

                for ($monthIndex = 0; $monthIndex < 12; $monthIndex++) {
                    $totalCategorySum = 0;
                    $totalCategoryCount = 3; // Karena kita membagi dengan 3 kategori: 5R/5S, SAFETY, LINGKUNGAN

                    foreach ($categories as $category => $items) {
                        $categorySum = 0;
                        $categoryCount = 0;

                        foreach ($items as $itemKey => $itemName) {
                            foreach ($areaPatrols as $patrol) {
                                $patrolMonth = Carbon::parse($patrol->tanggal_patrol)->month - 1;
                                if ($patrolMonth == $monthIndex) {
                                    $itemValue = (int)$patrol->{$itemKey};
                                    if ($itemValue > 0) {
                                        $categorySum += $itemValue;
                                        $categoryCount++;
                                    }
                                }
                            }
                        }

                        if ($categoryCount > 0) {
                            $totalCategorySum += $categorySum / $categoryCount; // Rata-rata per kategori
                        }
                    }

                    $areaAverageRow[$monthIndex + 3] = round($totalCategorySum / $totalCategoryCount, 2);
                }

                $data[] = $areaAverageRow;
                $data[] = array_fill(0, 15, '');
            }

            // Calculate overall averages if 'All' areas are selected
            if ($selectedArea === 'All') {
                $overallAverageRow = array_fill(0, 15, ''); // Initialize overall average row
                $overallAverageRow[0] = 'Total Seluruh Area';
                $overallAverageRow[1] = 'Grand Total';

                // Initialize arrays to store total values and counts for each month
                $monthTotals = array_fill(0, 12, 0);
                $monthCounts = array_fill(0, 12, 0);

                // Loop through patrol data to calculate totals for each month
                foreach ($patrolData as $patrol) {
                    $patrolMonth = Carbon::parse($patrol->tanggal_patrol)->month - 1;
                    foreach ($categories as $category => $items) {
                        foreach ($items as $itemKey => $itemName) {
                            $itemValue = (int)$patrol->{$itemKey};
                            if ($itemValue > 0) {
                                $monthTotals[$patrolMonth] += $itemValue;
                                $monthCounts[$patrolMonth]++;
                            }
                        }
                    }
                }

                // Calculate the overall average for each month
                for ($monthIndex = 0; $monthIndex < 12; $monthIndex++) {
                    if ($monthCounts[$monthIndex] > 0) {
                        $overallAverageRow[$monthIndex + 3] = number_format($monthTotals[$monthIndex] / $monthCounts[$monthIndex], 2, '.', '');
                    }
                }

                // Calculate grand total of overall averages
                // $grandTotal = array_sum(array_slice($overallAverageRow, 3)); // Summing from index 3 onwards

                // Assign grand total to the row
                // $overallAverageRow[2] = number_format($grandTotal, 2, '.', ''); // Using number_format for accurate rounding

                // Add overall average row to data
                $data[] = $overallAverageRow;
            }
        }

        // Create spreadsheet and fill the data
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($data as $rowIndex => $rowData) {
            foreach ($rowData as $colIndex => $cellData) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $cellData);
            }
        }

        // Merge cells for area patrol and categories
        $areaStartRow = null;
        $categoryStartRow = null;
        $previousArea = null;
        $previousCategory = null;

        for ($rowIndex = 1; $rowIndex < count($data); $rowIndex++) {
            $area = $data[$rowIndex][0];
            $category = $data[$rowIndex][1];

            // Handle merging for area patrol
            if ($area !== $previousArea) {
                if ($areaStartRow !== null) {
                    $sheet->mergeCells("A$areaStartRow:A" . ($rowIndex));
                    $sheet->getStyle("A$areaStartRow:A" . ($rowIndex))->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }
                $areaStartRow = $rowIndex + 1;
                $previousArea = $area;
            }

            // Handle merging for categories
            if ($category !== $previousCategory) {
                if ($categoryStartRow !== null) {
                    $sheet->mergeCells("B$categoryStartRow:B" . ($rowIndex));
                    $sheet->getStyle("B$categoryStartRow:B" . ($rowIndex))->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }
                $categoryStartRow = $rowIndex + 1;
                $previousCategory = $category;
            }
        }

        // Handle the last set of merges
        if ($areaStartRow !== null) {
            $sheet->mergeCells("A$areaStartRow:A" . (count($data)));
            $sheet->getStyle("A$areaStartRow:A" . (count($data)))->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }
        if ($categoryStartRow !== null) {
            $sheet->mergeCells("B$categoryStartRow:B" . (count($data)));
            $sheet->getStyle("B$categoryStartRow:B" . (count($data)))->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        // Apply formatting, auto-size columns, set borders, etc.
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Save the spreadsheet to a temporary file
        $writer = new Xlsx($spreadsheet);
        $filename = 'Safety-Patrol-' . date('d-F-Y') . '.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), 'phpspreadsheet');
        $writer->save($temp_file);

        // Download the file and delete it after sending
        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }

















    // public function exportPatrol()
    // {
    //     // Set locale for Carbon
    //     setlocale(LC_TIME, 'en_US.UTF-8');

    //     // Format date as "28 May 2020"
    //     $date = Carbon::now()->formatLocalized('%d %B %Y');
    //     $filename = "safety-patrol-{$date}.xlsx";

    //     return Excel::download(new SafetyExport, $filename);
    // }
}
