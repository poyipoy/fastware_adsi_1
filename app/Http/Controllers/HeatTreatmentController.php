<?php

namespace App\Http\Controllers;

use App\Models\HeatTreatment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class HeatTreatmentController extends Controller
{
    public function dashboardImportWO()
    {
        $heattreatments = HeatTreatment::orderBy('updated_at', 'desc')->get();

        return view('wo_heat.importWO', compact('heattreatments'))->with('i', (request()->input('page', 1) - 1) * 5);
    }

    public function filterWO(Request $request)
    {
        $statuses = ['Draft', 'Ready', 'Finished', 'Cancelled'];
        $counts = [];
        $fromDate = $request->query('fromDate'); // Expecting format 'd-m-y'
        $toDate = $request->query('toDate');     // Expecting format 'd-m-y'
    
        // Log the incoming date formats
        \Log::info('Incoming Dates:', ['fromDate' => $fromDate, 'toDate' => $toDate]);
    
        if ($fromDate && $toDate) {
            try {
                // Convert input dates from 'd-m-y' to 'Y-m-d' for comparison
                $fromDateConverted = \Carbon\Carbon::createFromFormat('d-m-y', $fromDate)->format('Y-m-d');
                $toDateConverted = \Carbon\Carbon::createFromFormat('d-m-y', $toDate)->format('Y-m-d');
            } catch (\Carbon\Exceptions\InvalidFormatException $e) {
                \Log::error('Date format error: ', ['fromDate' => $fromDate, 'toDate' => $toDate, 'exception' => $e->getMessage()]);
                return response()->json(['error' => 'Invalid date format provided. Please use d-m-y format.'], 400);
            }
    
            // Query to get total count of work orders within the date range
            $totalCount = DB::table('wo_heat')
                ->whereRaw('STR_TO_DATE(tgl_wo, "%Y-%m-%d") BETWEEN ? AND ?', [$fromDateConverted, $toDateConverted])
                ->count();
    
            // Raw SQL query to get counts and sums using converted dates for date comparison
            $results = DB::select('
                SELECT 
                    status_wo,
                    COUNT(*) AS wo,
                    SUM(pcs) AS pcs,
                    SUM(kg) AS kg
                FROM 
                    wo_heat
                WHERE 
                    STR_TO_DATE(tgl_wo, "%Y-%m-%d") BETWEEN ? AND ?
                GROUP BY 
                    status_wo
            ', [$fromDateConverted, $toDateConverted]);
    
            // Initialize the counts array for each status
            foreach ($statuses as $status) {
                $counts[$status] = [
                    'wo' => 0,
                    'pcs' => 0,
                    'kg' => 0,
                    'percentage' => 0,
                ];
            }
    
            // Populate the counts array with results and calculate percentages
            foreach ($results as $row) {
                $counts[$row->status_wo] = [
                    'wo' => $row->wo,
                    'pcs' => $row->pcs,
                    'kg' => $row->kg,
                    'percentage' => $totalCount > 0 ? round(($row->wo / $totalCount) * 100, 2) : 0,
                ];
            }
        } else {
            \Log::warning('Date range is missing or incomplete.');
    
            return response()->json(['error' => 'Please provide both fromDate and toDate.'], 400);
        }
    
        // Debugging: log the response
        \Log::info('Counts:', ['counts' => $counts]);
    
        return response()->json(['counts' => $counts]);
    }

    public function dashboardTracingWO(Request $request)
    {
        $statuses = ['Draft', 'Ready', 'Finished', 'Cancelled'];
        $counts = [];

        // Base query without date filtering
        $queryBase = HeatTreatment::query();

        // Get total number of WOs
        $totalWo = $queryBase->count();

        // Initialize counts for each status
        foreach ($statuses as $status) {
            // Clone base query to apply status filter
            $filteredQuery = clone $queryBase;
            $woCount = $filteredQuery->where('status_wo', $status)->count();
            $counts[$status] = [
                'wo' => $woCount,
                'pcs' => $filteredQuery->sum('pcs'),
                'kg' => $filteredQuery->sum('kg'),
                'percentage' => $totalWo > 0 ? round(($woCount / $totalWo) * 100, 2) : 0,
            ];
        }

        // Check if the request is an AJAX request
        if ($request->ajax()) {
            return response()->json([
                'counts' => $counts,
                'totalWo' => $totalWo,
            ]);
        }

        // Pass data to the view
        return view('wo_heat.tracingWO', compact('counts', 'totalWo'));
    }

    public function WOHeat(Request $request)
    {
        $request->validate([
            'excelFile' => 'required|mimes:xlsx,xls', // Validasi file hanya boleh berformat Excel
        ]);

        try {
            $file = $request->file('excelFile');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            foreach ($rows as $index => $row) {
                if ($index < 2) {
                    // Skip the first two rows
                    continue;
                }

                // Normalize numeric values to remove unnecessary decimals
                foreach ($row as $key => $value) {
                    if (is_numeric($value)) {
                        $row[$key] = (string) intval($value);
                    }
                }

                // Check if record exists
                $existingRecord = HeatTreatment::where('no_wo', $row[0])->where('no_so', $row[1])->first();

                $data = [
                    'no_wo' => $row[0],
                    'no_so' => $row[1],
                    'tgl_wo' => $row[2],
                    'deskripsi' => $row[3],
                    'area' => $row[4],
                    'kode' => $row[5],
                    'cust' => $row[6],
                    'proses' => $row[7],
                    'pcs' => $row[8],
                    'kg' => $row[9],
                    'batch_heating' => $row[10],
                    'mesin_heating' => $row[11],
                    'tgl_heating' => $row[12],
                    'batch_temper1' => $row[13],
                    'mesin_temper1' => $row[14],
                    'tgl_temper1' => $row[15],
                    'batch_temper2' => $row[16],
                    'mesin_temper2' => $row[17],
                    'tgl_temper2' => $row[18],
                    'batch_temper3' => $row[19],
                    'mesin_temper3' => $row[20],
                    'tgl_temper3' => $row[21],
                    'status_wo' => $row[22],
                    'no_do' => $row[23],
                    'status_do' => $row[24],
                    'tgl_st' => $row[25],
                    'supir' => $row[26],
                    'penerima' => $row[27],
                    'tgl_terima' => $row[28],
                ];

                if ($existingRecord) {
                    // Update the existing record
                    $existingRecord->update($data);
                } else {
                    // Create a new record
                    HeatTreatment::create($data);
                }
            }

            return response()->json(['success' => true, 'message' => 'Import WO berhasil']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: '.$e->getMessage()], 500);
        }
    }

    public function searchWO(Request $request)
    {
        // Mengambil nilai input dari request
        $searchWO = $request->input('searchWO');
        $searchKodeOrNoWO = $request->input('searchKodeOrNoWO');
        $searchStatusWO = $request->input('searchStatusWO');
        $searchStatusDO = $request->input('searchStatusDO');
        $startMonth = $request->input('startMonth');
        $endMonth = $request->input('endMonth');

        // Membuat query dasar untuk model HeatTreatment
        $query = HeatTreatment::query();

        // Tambahkan kondisi pencarian
        $query->where(function ($q) use ($searchWO, $searchKodeOrNoWO, $searchStatusWO, $searchStatusDO, $startMonth, $endMonth) {
            if ($searchWO) {
                $q->where('cust', 'LIKE', '%'.$searchWO.'%');
            }
            if ($searchKodeOrNoWO) {
                $q->where(function ($q2) use ($searchKodeOrNoWO) {
                    $q2->where('kode', 'LIKE', '%'.$searchKodeOrNoWO.'%')
                        ->orWhere('no_wo', 'LIKE', '%'.$searchKodeOrNoWO.'%');
                });
            }
            if ($searchStatusWO && $searchStatusWO != 'All') {
                $q->where('status_wo', 'LIKE', '%'.$searchStatusWO.'%');
            }
            if ($searchStatusDO && $searchStatusDO != 'All') {
                $q->where('status_do', 'LIKE', '%'.$searchStatusDO.'%');
            }
            // Tambahkan kondisi untuk menangani filter berdasarkan bulan (ambil bagian bulan dari format dd-mm)
            if ($startMonth && $endMonth) {
                $q->whereBetween(DB::raw('SUBSTRING(tgl_wo, 4, 2)'), [$startMonth, $endMonth]);
            } elseif ($startMonth) {
                $q->where(DB::raw('SUBSTRING(tgl_wo, 4, 2)'), $startMonth);
            } elseif ($endMonth) {
                $q->where(DB::raw('SUBSTRING(tgl_wo, 4, 2)'), $endMonth);
            }
        });

        // Tambahkan distinct() untuk menghindari duplikat
        $query->distinct();

        // Mendapatkan data berdasarkan kondisi pencarian dan mengelompokkannya berdasarkan customer
        $data = $query->get()->groupBy('cust');

        // Transformasi data yang dikelompokkan ke dalam struktur yang diinginkan
        $response = $data->map(function ($items, $key) {
            return $items->map(function ($item) {
                return [
                    'no_wo' => $item->no_wo,
                    'kode' => $item->kode,
                    'cust' => $item->cust,
                    'deskripsi' => $item->deskripsi,
                    'tgl_wo' => $item->tgl_wo,
                    'status_wo' => $item->status_wo,
                    'status_do' => $item->status_do,
                    'proses' => $item->proses,
                    'batch' => $item->batch_heating,
                    'mesin_heating' => $item->mesin_heating,
                    'tgl_heating' => $item->tgl_heating,
                    'batch_temper1' => $item->batch_temper1,
                    'mesin_temper1' => $item->mesin_temper1,
                    'tgl_temper1' => $item->tgl_temper1,
                    'batch_temper2' => $item->batch_temper2,
                    'mesin_temper2' => $item->mesin_temper2,
                    'tgl_temper2' => $item->tgl_temper2,
                    'batch_temper3' => $item->batch_temper3,
                    'mesin_temper3' => $item->mesin_temper3,
                    'tgl_temper3' => $item->tgl_temper3,
                    'no_do' => $item->no_do,
                    'tgl_st' => $item->tgl_st,
                    'supir' => $item->supir,
                    'penerima' => $item->penerima,
                    'tgl_terima' => $item->tgl_terima,
                    'pcs' => $item->pcs,
                    'kg' => $item->kg,
                ];
            });
        });

        // Mengembalikan data dalam format JSON
        return response()->json($response);
    }

    public function getBatchData(Request $request)
    {
        $batch = $request->input('batch');

        $workOrders = collect([]);

        $heatingQuery = HeatTreatment::where('batch_heating', $batch);
        $temper1Query = HeatTreatment::where('batch_temper1', $batch);
        $temper2Query = HeatTreatment::where('batch_temper2', $batch);
        $temper3Query = HeatTreatment::where('batch_temper3', $batch);

        $workOrders->push($heatingQuery->get());
        $workOrders->push($temper1Query->get());
        $workOrders->push($temper2Query->get());
        $workOrders->push($temper3Query->get());

        $mergedWorkOrders = $workOrders->collapse();

        if ($mergedWorkOrders->isEmpty()) {
            return response()->json(['message' => 'No data found for the batch '.$batch], 404);
        }

        return response()->json($mergedWorkOrders);
    }
}
