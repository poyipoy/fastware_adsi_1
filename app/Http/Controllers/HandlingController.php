<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Handling;
use App\Models\ScheduleVisit;
use App\Models\TypeMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// import Facade "Storage"

class HandlingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $data = Handling::with(['customers', 'type_materials', 'user'])
        ->whereIn('status', [0,1,2,3])
        ->orderByRaw('FIELD(status, 0,1,2,3)') // Urutkan berdasarkan urutan status yang diinginkan
        ->orderByDesc('created_at') // Urutkan berdasarkan created_at dalam setiap status
        ->get(); // Ambil semua data tanpa pagination

        return view('sales.handling', compact('data'));
    }

    public function getChartStatusHandling(Request $request)
    {
        $year = date('Y');
        $data = Handling::select(
            DB::raw('COUNT(CASE WHEN status_2 = 0 THEN 1 END) as total_status_2_0'),
            DB::raw('COUNT(CASE WHEN status = 3 THEN 1 END) as total_status_3'),
            DB::raw('MONTH(created_at) as month')
        )
        ->whereYear('created_at', $year)
        ->groupBy('month')
        ->orderBy('month')
        ->get()
        ->keyBy('month')
        ->toArray();

        $fullData = [];
        for ($i = 1; $i <= 12; ++$i) {
            $fullData[] = [
                'month' => $i,
                'total_status_2_0' => $data[$i]['total_status_2_0'] ?? 0,
                'total_status_3' => $data[$i]['total_status_3'] ?? 0,
            ];
        }

        return response()->json($fullData);
    }

    public function getChartData(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Query untuk mengambil data berdasarkan tanggal mulai dan tanggal selesai
        $data = Handling::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COUNT(CASE WHEN status_2 = 0 THEN 1 END) as Open')
            ->selectRaw('COUNT(CASE WHEN status = 3 THEN 1 END) as Close')
            ->first();

        // dd($data);
        // Mengembalikan data dalam format JSON
        return response()->json($data);
    }

    public function getDataByYear(Request $request)
    {
        // Ambil tahun dari request
        $year = $request->input('year');

        // Query untuk mengambil data berdasarkan tahun
        $data = Handling::select(
            \DB::raw('COUNT(CASE WHEN status_2 = 0 THEN 1 END) as total_status_2_0'),
            \DB::raw('COUNT(CASE WHEN status = 3 THEN 1 END) as total_status_3'),
            \DB::raw('MONTH(created_at) as month')
        )
            ->whereYear('created_at', $year) // Filter berdasarkan tahun
            ->groupBy('month')
            ->get();

        return response()->json($data); // Mengembalikan data dalam format JSON
    }

    public function changeStatus($id)
    {
        $data = Handling::findOrFail($id);
        $data->status = 3;
        $data->save();

        return response()->json(['success' => 'Status changed successfully.']);
    }

    public function showHistory($id)
    {
        // Mengambil data handling berdasarkan ID
        $handlings = Handling::with(['customers', 'type_materials'])->findOrFail($id);

        // Mengambil semua data pelanggan
        $customers = Customer::all();

        // Mengambil semua data tipe bahan
        $type_materials = TypeMaterial::all();

        // Mengambil data schedule visit berdasarkan handling_id
        $data = ScheduleVisit::where('handling_id', $id)->get();

        // Mengirim data ke view
        return view('sales.showHistory', compact('handlings', 'customers', 'type_materials', 'data'));
    }

    public function filterPieChartTipe(Request $request)
    {
        // Mendapatkan nilai yang dipilih dari dropdown
        $type_name = $request->input('type_name');
        $kategori = $request->input('kategori');
        $jenis = $request->input('jenis');
        $type = $request->input('type');
        $start_month = $request->input('start_month'); // Tambahkan parameter bulan mulai
        $end_month = $request->input('end_month'); // Tambahkan parameter bulan akhir

        $query = DB::table('handlings')
        ->join('type_materials', 'handlings.type_id', '=', 'type_materials.id')
                ->select(
                    'type_materials.id',
                    'type_materials.type_name AS type_name',
                    DB::raw('(SELECT SUM(handlings.qty) 
                            FROM handlings 
                            WHERE handlings.type_id = type_materials.id 
                                AND handlings.type_1 = "Komplain") AS qty_komplain'),
                    DB::raw('(SELECT SUM(handlings.qty) 
                            FROM handlings 
                            WHERE handlings.type_id = type_materials.id 
                                AND handlings.type_2 = "Klaim") AS qty_klaim'),
                    DB::raw('(SELECT SUM(handlings.pcs) 
                            FROM handlings 
                            WHERE handlings.type_id = type_materials.id 
                                AND handlings.type_1 = "Komplain") AS pcs_komplain'),
                    DB::raw('(SELECT SUM(handlings.pcs) 
                            FROM handlings 
                            WHERE handlings.type_id = type_materials.id 
                                AND handlings.type_2 = "Klaim") AS pcs_klaim'),
                    DB::raw('(SELECT SUM(handlings.qty) 
                            FROM handlings 
                            WHERE handlings.type_id = type_materials.id 
                                AND (handlings.type_1 = "Komplain" OR handlings.type_2 = "Klaim")) AS qty_all'),
                    DB::raw('(SELECT SUM(handlings.pcs) 
                            FROM handlings 
                            WHERE handlings.type_id = type_materials.id 
                                AND (handlings.type_1 = "Komplain" OR handlings.type_2 = "Klaim")) AS pcs_all'),
                    DB::raw('SUM(CASE WHEN handlings.type_1 = "Komplain" THEN 1 ELSE 0 END) AS total_komplain'),
                    DB::raw('SUM(CASE WHEN handlings.type_2 = "Klaim" THEN 1 ELSE 0 END) AS total_klaim'),
                    DB::raw('COALESCE(SUM(CASE WHEN handlings.type_1 = "Komplain" THEN 1 ELSE 0 END) +
                                SUM(CASE WHEN handlings.type_2 = "Klaim" THEN 1 ELSE 0 END), 0) AS kategori')
                )
            ->where(function ($query) use ($type) {
                if ($type == 'total_komplain') {
                    $query->where('handlings.type_1', 'Komplain');
                } elseif ($type == 'total_klaim') {
                    $query->where('handlings.type_2', 'Klaim');
                } elseif ($type == 'qty') {
                    $query->selectSub('total_qty', 'total');
                } elseif ($type == 'pcs') {
                    $query->selectSub('total_pcs', 'total');
                }
            })
            ->groupBy('type_materials.id', 'handlings.type_id', 'type_materials.type_name');

        if (!empty($start_month) && !empty($end_month)) {
            $query->whereBetween('handlings.created_at', [$start_month, $end_month]); // Filter berdasarkan rentang tanggal
        }

        $data = $query->get();

        // Mengembalikan data dalam format JSON
        return response()->json($data);
    }

    public function filterPieChartProses(Request $request)
    {
        $type_name2 = $request->input('type_name');
        $kategori2 = $request->input('kategori_2');
        $jenis2 = $request->input('jenis2');
        $tipe2 = $request->input('tipe2');
        $start_month3 = $request->input('start_month3');
        $end_month3 = $request->input('end_month3');

        $query2 = DB::table('handlings')
            ->select(
                'handlings.process_type AS type_name',
                DB::raw('SUM(CASE WHEN handlings.type_1 = "Komplain" THEN handlings.qty ELSE 0 END) AS qty_komplain'),
                DB::raw('SUM(CASE WHEN handlings.type_2 = "Klaim" THEN handlings.qty ELSE 0 END) AS qty_klaim'),
                DB::raw('SUM(CASE WHEN handlings.type_1 = "Komplain" THEN handlings.pcs ELSE 0 END) AS pcs_komplain'),
                DB::raw('SUM(CASE WHEN handlings.type_2 = "Klaim" THEN handlings.pcs ELSE 0 END) AS pcs_klaim'),
                DB::raw('SUM(CASE WHEN handlings.type_1 = "Komplain" OR handlings.type_2 = "Klaim" THEN handlings.qty ELSE 0 END) AS qty_all'),
                DB::raw('SUM(CASE WHEN handlings.type_1 = "Komplain" OR handlings.type_2 = "Klaim" THEN handlings.pcs ELSE 0 END) AS pcs_all'),
                DB::raw('SUM(CASE WHEN handlings.type_1 = "Komplain" THEN 1 ELSE 0 END) AS total_komplain2'),
                DB::raw('SUM(CASE WHEN handlings.type_2 = "Klaim" THEN 1 ELSE 0 END) AS total_klaim2'),
                DB::raw('COALESCE(SUM(CASE WHEN handlings.type_1 = "Komplain" THEN 1 ELSE 0 END) +
                         SUM(CASE WHEN handlings.type_2 = "Klaim" THEN 1 ELSE 0 END), 0) AS kategori_2')
            )
            ->where(function ($query2) use ($tipe2) {
                if ($tipe2 == 'total_komplain2') {
                    $query2->where('handlings.type_1', 'Komplain');
                } elseif ($tipe2 == 'total_klaim2') {
                    $query2->where('handlings.type_2', 'Klaim');
                } elseif ($tipe2 == 'qty') {
                    $query2->selectSub('total_qty', 'total');
                } elseif ($tipe2 == 'pcs') {
                    $query2->selectSub('total_pcs', 'total');
                }
            })
            ->groupBy('handlings.process_type');

        if (!empty($start_month3) && !empty($end_month3)) {
            $query2->whereBetween('handlings.created_at', [$start_month3, $end_month3]);
        }

        $data = $query2->get();

        return response()->json($data);
    }

    public function filterPieChartNG(Request $request)
    {
        $category = $request->input('category');
        $jenis = $request->input('jenis3');
        $tipe = $request->input('tipe3');
        $start_month = $request->input('start_month4');
        $end_month = $request->input('end_month4');

        $query = DB::table('handlings')
            ->select(
                'handlings.category AS category',
                DB::raw('SUM(CASE WHEN handlings.type_1 = "Komplain" THEN 1 ELSE 0 END) AS total_komplain'),
                DB::raw('SUM(CASE WHEN handlings.type_2 = "Klaim" THEN 1 ELSE 0 END) AS total_klaim'),
                DB::raw('SUM(CASE WHEN handlings.type_1 = "Komplain" OR handlings.type_2 = "Klaim" THEN 1 ELSE 0 END) AS total_all')
            )
            ->when($tipe == 'type_1', function ($query) {
                return $query->where('handlings.type_1', 'Komplain');
            })
            ->when($tipe == 'type_2', function ($query) {
                return $query->where('handlings.type_2', 'Klaim');
            })
            ->when($jenis, function ($query, $jenis) {
                return $query->where('handlings.category', $jenis);
            })
            ->groupBy('handlings.category');

        if (!empty($start_month) && !empty($end_month)) {
            $query->whereBetween('handlings.created_at', [$start_month, $end_month]);
        }

        $data = $query->get();

        return response()->json($data);
    }

    public function export(Request $request)
    {
        $startMonth = $request->input('start_month');
        $endMonth = $request->input('end_month');

        // Query data from the database with joins
        $data = DB::table('handlings')
            ->join('customers', 'handlings.customer_id', '=', 'customers.id')
            ->join('type_materials', 'handlings.type_id', '=', 'type_materials.id')
            ->whereBetween('handlings.created_at', [$startMonth, $endMonth])
            ->orderBy('handlings.created_at', 'asc')
            ->select([
                'handlings.no_wo', 'customers.customer_code', 'customers.name_customer', 'customers.area',
                'type_materials.type_name', 'handlings.thickness', 'handlings.weight', 'handlings.outer_diameter',
                'handlings.inner_diameter', 'handlings.length', 'handlings.qty', 'handlings.pcs',
                'handlings.category', 'handlings.process_type', 'handlings.type_1', 'handlings.type_2',
                'handlings.modified_by', 'handlings.created_at', 'handlings.status',
            ])
            ->get();

        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Define the columns
        $columns = [
            'No WO', 'Customer Code', 'Name Customer', 'Area',
            'Type Name', 'Thickness', 'Weight', 'Outer Diameter', 'Inner Diameter', 'Length', 'Qty', 'Pcs',
            'Category', 'Process Type', 'Type 1', 'Type 2', 'Modified By', 'Created At', 'Status',
        ];

        // Write the column headers
        foreach ($columns as $index => $column) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $column);
            // Set header style
            $sheet->getStyleByColumnAndRow($index + 1, 1)->applyFromArray([
                'font' => [
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'FFFF00',
                    ],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);
        }

        // Write the data rows
        foreach ($data as $rowIndex => $row) {
            $row = (array) $row; // Ensure row is an array
            foreach ($columns as $colIndex => $column) {
                $value = $row[strtolower(str_replace(' ', '_', $column))];
                if ($column === 'Status') {
                    switch ($value) {
                        case 0:
                            $value = 'Open';
                            break;
                        case 1:
                            $value = 'On Progress';
                            break;
                        case 2:
                            $value = 'Finish';
                            break;
                        case 3:
                            $value = 'Close';
                            break;
                    }
                }
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 2, $value);
            }
        }

        // Add filters to the header row
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        // Set number format for specific columns
        $sheet->getStyle('G2:G'.($data->count() + 1))
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        $sheet->getStyle('H2:H'.($data->count() + 1))
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        $sheet->getStyle('I2:I'.($data->count() + 1))
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        $sheet->getStyle('J2:J'.($data->count() + 1))
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        $sheet->getStyle('K2:K'.($data->count() + 1))
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        $sheet->getStyle('L2:L'.($data->count() + 1))
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER);
        $sheet->getStyle('M2:M'.($data->count() + 1))
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER);

        // Adjust column widths
        foreach (range(1, count($columns)) as $colIndex) {
            $sheet->getColumnDimensionByColumn($colIndex)->setAutoSize(true);
        }

        // Create a Writer to save the file
        $writer = new Xlsx($spreadsheet);
        $fileName = 'Report-handlings_'.'.xlsx';

        // Return the response as a download
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'.$fileName.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * create.
     */
    public function create(): View
    {
        $customers = Customer::all();
        $type_materials = TypeMaterial::all();

        return view('sales.create', compact('customers', 'type_materials'));
    }

    /**
     * store.
     *
     * @param mixed $request
     */
    public function store(Request $request): RedirectResponse
    {
        $imagePaths = []; // Array untuk menyimpan jalur gambar

        // Cek apakah file gambar diunggah
        if ($request->hasFile('image')) {
            // Validasi file gambar
            $request->validate([
                'image.*' => 'mimes:jpeg,jpg,png,pdf',
            ]);            

            // Loop melalui setiap gambar yang diunggah
            foreach ($request->file('image') as $image) {
                // Pindahkan foto ke direktori public/assets/foto
                $imagePath = $image->hashName(); // Gunakan hashname sebagai nama file
                $image->move(public_path('assets/image'), $imagePath);

                // Simpan jalur gambar ke dalam array
                $imagePaths[] = $imagePath;
            }
        }

        // Ubah array menjadi string JSON sebelum menyimpannya ke dalam database
        $imagePathsString = json_encode($imagePaths);

        // Dapatkan tahun saat ini
        $currentYear = date('Y');

        // Buat nomor WO dengan menambahkan tahun saat ini
        $no_wo = 'WO/'.$currentYear.'/'.$request->no_wo;

        // Buat data handling
        $handling = new Handling();
        $handling->no_wo = $no_wo;
        $handling->user_id = $request->user()->id;
        $handling->customer_id = $request->customer_id;
        $handling->type_id = $request->type_id;
        $handling->nama_barang = $request->nama_barang;
        $handling->notes = $request->notes;
        $handling->thickness = $request->thickness;
        $handling->weight = $request->weight;
        $handling->outer_diameter = $request->outer_diameter;
        $handling->inner_diameter = $request->inner_diameter;
        $handling->length = $request->length;
        $handling->qty = $request->qty;
        $handling->pcs = $request->pcs;
        $handling->category = $request->category;
        $handling->jenis_test = $request->jenis_test;
        $handling->results = $request->results;
        $handling->process_type = $request->process_type;
        $handling->type_1 = $request->type_1;
        $handling->type_2 = $request->type_2;
        $handling->image = $imagePathsString; // Simpan string JSON jalur gambar
        $handling->status = 0;
        $handling->status_2 = 0;
        $handling->modified_by = $request->user()->name;
        $handling->save();

        // redirect to index
        return redirect()->route('index')->with(['success' => 'Data Berhasil Disimpan!']);
    }

    /**
     * edit.
     *
     * @param mixed $id
     */
    public function edit(string $id): View
    {
        $handlings = Handling::with(['customers', 'type_materials'])->findOrFail($id);
        $customers = Customer::all();
        $type_materials = TypeMaterial::all();

        // Strip the prefix 'WO/yyyy/' from the no_wo
        $handlings->no_wo = preg_replace('/^WO\/\d{4}\//', '', $handlings->no_wo);

        return view('sales.edit', compact('handlings', 'customers', 'type_materials'));
    }

    /**
     * update.
     *
     * @param mixed $request
     */
    public function update(Request $request, $id): RedirectResponse
    {
        // Validate form
        $this->validate($request, [
            'images.*' => 'image|mimes:jpeg,jpg,png|max:10000',
        ]);

        // Get handling by ID
        $handlings = Handling::findOrFail($id);

        // Initialize an array to hold new image paths
        $newImagePaths = [];

        // Check if images are uploaded
        if ($request->hasFile('images')) {
            // Delete old images (if any)
            if ($handlings->image) {
                $oldImagePaths = json_decode($handlings->image, true);

                foreach ($oldImagePaths as $oldImagePath) {
                    $fullPath = public_path('assets/image/'.$oldImagePath);
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
            }

            foreach ($request->file('images') as $image) {
                // Move each image to the appropriate directory
                $imagePath = $image->hashName();
                $image->move(public_path('assets/image'), $imagePath);

                // Add the new image path to the array
                $newImagePaths[] = $imagePath;
            }
        } else {
            // No new images uploaded, keep the old ones
            $newImagePaths = json_decode($handlings->image, true) ?? [];
        }

        // Combine old and new image paths
        $allImagePaths = $newImagePaths;

        // Get the current year
        $currentYear = date('Y');

        // Check if the no_wo has the correct format
        if (strpos($request->no_wo, 'WO/'.$currentYear.'/') === false) {
            // Format the no_wo correctly if it doesn't have the proper prefix
            $no_wo = 'WO/'.$currentYear.'/'.$request->no_wo;
        } else {
            // Keep the existing formatted no_wo
            $no_wo = $request->no_wo;
        }

        // Update post with new image paths and other data
        $handlings->update([
            'no_wo' => $no_wo,
            'user_id' => $request->user()->id,
            'customer_id' => $request->customer_id,
            'type_id' => $request->type_id,
            'nama_barang' => $request->nama_barang,
            'notes' => $request->notes,
            'thickness' => $request->thickness,
            'weight' => $request->weight,
            'outer_diameter' => $request->outer_diameter,
            'inner_diameter' => $request->inner_diameter,
            'length' => $request->length,
            'qty' => $request->qty,
            'pcs' => $request->pcs,
            'category' => $request->category,
            'jenis_test' => $request->jenis_test,
            'results' => $request->results,
            'process_type' => $request->process_type,
            'type_1' => $request->type_1,
            'type_2' => $request->type_2,
            'image' => json_encode($allImagePaths),
            'status' => 0,
            'modified_by' => $request->user()->name,
        ]);

        // Redirect to index
        return redirect()->route('index')->with(['success' => 'Data Berhasil Diubah!']);
    }

    /**
     * destroy.
     *
     * @return void
     */
    public function delete($id)
    {
        // get post by ID
        $handlings = Handling::findOrFail($id);

        // delete old image
        // Hapus gambar lama jika ada
        if ($handlings->image) {
            $oldImagePath = public_path('assets/image/'.$handlings->image);
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        // delete post
        $handlings->delete();

        // return JSON response
        return response()->json(['success' => 'Data Berhasil Dihapus!'], 200);
    }
}
