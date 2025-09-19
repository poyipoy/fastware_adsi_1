<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\LogbookVisits;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\TrsLogbookVisit;
use App\Models\TrsLogbookVisits;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SalesVisitController extends Controller
{
    public function store(Request $request)
    {
        // Validasi input, customer_name dan new_customer_name optional
        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'new_customer_name' => 'nullable|string|max:255',
            'pic_cust' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'visit_result' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png', // max 5MB
        ]);

        $logbookVisit = new LogbookVisits();
        $logbookVisit->id_user = auth()->id();

        $customerNameRaw = $validated['customer_name'] ?? $validated['new_customer_name'] ?? 'unknown_customer';
        // Hapus spasi dan karakter khusus dari nama customer
        $customerNameClean = Str::slug(str_replace(' ', '', $customerNameRaw));
        if (empty($customerNameClean)) {
            $customerNameClean = 'unknown';
        }

        $file = $request->file('attachment');

        if ($file && $file->isValid()) {
            $timestamp = now()->format('Ymd_His');
            $extension = $file->getClientOriginalExtension();

            $customerNameRaw = $validated['customer_name'] ?? $validated['new_customer_name'] ?? 'unknown_customer';
            $customerNameClean = Str::slug(str_replace(' ', '', $customerNameRaw)) ?: 'unknown_customer';

            // Path folder tujuan di dalam public/
            $folderPath = public_path('assets/sales_report/kunjungan');
            $fileName = "kunjungan_{$timestamp}.{$extension}";

            // Pastikan folder ada
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            // Simpan file di public/assets/sales_report/kunjungan
            $file->move($folderPath, $fileName);

            // Simpan hanya nama file saja
            $logbookVisit->attachment = $fileName;
        } else {
            $logbookVisit->attachment = null;
        }


        $logbookVisit->id_user = auth()->id();

        // Set data lainnya
        if (!empty($validated['new_customer_name'])) {
            $logbookVisit->customer_name = $validated['new_customer_name'];
            $logbookVisit->new_customer_name = $validated['new_customer_name'];
        } else {
            $logbookVisit->customer_name = $validated['customer_name'] ?? null;
            $logbookVisit->new_customer_name = null;
        }
        $logbookVisit->pic_cust = $validated['pic_cust'] ?? null;
        $logbookVisit->jabatan = $validated['jabatan'] ?? null;
        $logbookVisit->visit_result = $validated['visit_result'] ?? null;
        // Lokasi diisi dari input langsung
        $logbookVisit->location = $validated['location'] ?? null;
        $logbookVisit->visit_date = now(); // Diisi otomatis nanti

        // is_active diset 1
        $logbookVisit->is_active = 1;

        // visit_date otomatis current timestamp
        $logbookVisit->visit_date = now();

        $logbookVisit->save();

        return response()->json(['message' => 'Visit log saved successfully']);
    }

    public function customerList()
    {
        $customers = Customer::all();
        return response()->json($customers, 200);
    }


    /**
     * Menampilkan daftar kunjungan logbook untuk user yang sedang login.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    // Ganti fungsi index di controller Visit Anda

    public function index(Request $request)
    {
        $limit = $request->get('limit', 10);
        $page = $request->get('page', 1);
        $search = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = LogbookVisits::with('user')
            ->where('id_user', auth()->id())
            ->orderBy('created_at', 'desc');

        if ($search) {
            // --- PERBAIKAN DI SINI ---
            // Cari di kedua kolom nama customer
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%$search%")
                ->orWhere('new_customer_name', 'like', "%$search%");
            });
            // --- AKHIR PERBAIKAN ---
        }

        if ($startDate && $endDate) {
            $query->whereDate('visit_date', '>=', $startDate)
                ->whereDate('visit_date', '<=', $endDate);
        }

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    // Ganti fungsi indexplan di controller Plan Anda

    public function indexplan(Request $request)
    {
        $limit = $request->get('limit', 10);
        $page = $request->get('page', 1);
        $search = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = TrsLogbookVisits::with('user')
            ->where('id_user', auth()->id())
            ->orderBy('created_at', 'desc');

        if ($search) {
            // --- PERBAIKAN DI SINI ---
            // Sesuaikan dengan nama kolom di tabel TrsLogbookVisits
            // Asumsi nama kolomnya sama: customer_name & new_customer_name
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%$search%")
                ->orWhere('new_customer_name', 'like', "%$search%");
            });
            // --- AKHIR PERBAIKAN ---
        }

        if ($startDate && $endDate) {
            $query->whereDate('visit_date', '>=', $startDate)
                ->whereDate('visit_date', '<=', $endDate);
        }

        return $query->paginate($limit, ['*'], 'page', $page);
    }


    public function chart(Request $request)
    {
        $userId = auth()->id();

        // DIUBAH: Jaga variabel sebagai objek Carbon untuk fleksibilitas
        $startDate = $request->filled('start_date')
            ? \Carbon\Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? \Carbon\Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfMonth();

        $visits = LogbookVisits::where('id_user', $userId)
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->selectRaw('DATE(visit_date) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $planning = TrsLogbookVisits::where('id_user', $userId)
            ->whereBetween('plan_visit', [$startDate, $endDate])
            ->selectRaw('DATE(plan_visit) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        $totalCustomer = LogbookVisits::where('id_user', $userId)
            ->whereNotNull('customer_name')
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->distinct('customer_name')
            ->count('customer_name');

        // 2. Total Kunjungan (semua kunjungan dalam rentang tanggal)
        $totalKunjungan = LogbookVisits::where('id_user', $userId)
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->count();

        // 3. Total Plan (semua plan visit dalam rentang tanggal)
        $totalPlan = TrsLogbookVisits::where('id_user', $userId)
            ->whereBetween('plan_visit', [$startDate, $endDate])
            ->count();

        // 4. Follow Up (ambil dari tabel yang benar)
        $followUp = LogbookVisits::where('id_user', $userId)
            ->where('remark', 'Follow Up') // Pastikan kolom dan nilai ini ada
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->count();
        
        return response()->json([
            'visits'          => $visits,
            'planning'        => $planning,
            'total_customer'  => $totalCustomer,
            'old_customer'    => $totalPlan,      // Sesuai logika Android
            'new_customer'    => $totalKunjungan,   // Sesuai logika Android
            'follow_up'       => $followUp,
            // DIUBAH: Sekarang format() akan bekerja karena $startDate adalah objek Carbon
            'filter_start'    => $startDate->format('Y-m-d'),
            'filter_end'      => $endDate->format('Y-m-d'),
        ]);
    }


    public function getSalesPerformance(Request $request)
    {
        // Pastikan user adalah Dept Head atau admin
        $user = Auth::user();
        if ($user->role !== 'dept_head' && $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Ambil parameter dengan default bulan dan tahun sekarang
        $salesId = $request->input('sales_id', 0); // 0 berarti semua sales
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // Validasi parameter
        if ($month < 1 || $month > 12) {
            return response()->json(['error' => 'Bulan tidak valid'], 400);
        }
        
        if ($year < 2000 || $year > (now()->year + 1)) {
            return response()->json(['error' => 'Tahun tidak valid'], 400);
        }

        // Tentukan rentang tanggal berdasarkan bulan dan tahun
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // Dapatkan daftar sales ID yang memiliki data kunjungan
        $salesIdsWithVisitData = LogbookVisits::select('id_user')
            ->distinct()
            ->pluck('id_user')
            ->toArray();
            
        $salesIdsWithPlanData = TrsLogbookVisits::select('id_user')
            ->distinct()
            ->pluck('id_user')
            ->toArray();
            
        $allSalesIds = array_unique(array_merge($salesIdsWithVisitData, $salesIdsWithPlanData));

        // Jika tidak ada sales dengan data, kembalikan array kosong
        if (empty($allSalesIds)) {
            return response()->json([]);
        }

        // Filter berdasarkan sales_id jika diberikan dan bukan 0
        $filteredSalesIds = $allSalesIds;
        if ($salesId > 0) {
            $filteredSalesIds = [$salesId];
        }

        // Ambil data actual visit (dari LogbookVisits)
        $actualVisits = LogbookVisits::whereIn('id_user', $filteredSalesIds)
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->selectRaw('id_user, COUNT(*) as total')
            ->groupBy('id_user')
            ->get()
            ->keyBy('id_user');

        // Ambil data plan visit (dari TrsLogbookVisits)
        $plannedVisits = TrsLogbookVisits::whereIn('id_user', $filteredSalesIds)
            ->whereNotNull('plan_visit')
            ->whereBetween('plan_visit', [$startDate, $endDate])
            ->selectRaw('id_user, COUNT(*) as total')
            ->groupBy('id_user')
            ->get()
            ->keyBy('id_user');

        // Ambil nama sales dari tabel users
        $salesUsers = User::whereIn('id', $filteredSalesIds)->get()->keyBy('id');

        // Format data untuk response
        $performanceData = [];
        foreach ($salesUsers as $sales) {
            $performanceData[] = [
                'salesId' => $sales->id,
                'salesName' => $sales->name,
                'region' => $sales->region ?? 'N/A',
                'visitCount' => $actualVisits->get($sales->id, (object)['total' => 0])->total,
                'planVisitCount' => $plannedVisits->get($sales->id, (object)['total' => 0])->total,
            ];
        }

        return response()->json($performanceData);
    }

    /**
     * Endpoint untuk mendapatkan daftar sales untuk dropdown filter
     * Hanya menampilkan sales yang memiliki data di tabel LogbookVisits atau TrsLogbookVisits
     */
    public function getSalesUsers()
    {
        $user = Auth::user();
        if ($user->role !== 'dept_head' && $user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Dapatkan daftar sales ID yang memiliki data kunjungan
        $salesIdsWithVisitData = LogbookVisits::select('id_user')
            ->distinct()
            ->pluck('id_user')
            ->toArray();
            
        $salesIdsWithPlanData = TrsLogbookVisits::select('id_user')
            ->distinct()
            ->pluck('id_user')
            ->toArray();
            
        $allSalesIds = array_unique(array_merge($salesIdsWithVisitData, $salesIdsWithPlanData));

        // Jika tidak ada sales dengan data, kembalikan array kosong
        if (empty($allSalesIds)) {
            return response()->json([['id' => 0, 'name' => 'Semua Sales']]);
        }

        // Ambil nama sales dari tabel users
        $salesUsers = User::whereIn('id', $allSalesIds)->get();

        // Format response dengan menambahkan opsi "Semua Sales" di awal
        $response = [['id' => 0, 'name' => 'Semua Sales']];
        $response = array_merge($response, $salesUsers->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name
            ];
        })->toArray());

        return response()->json($response);
    }

     // ... fungsi-fungsi lain seperti index(), show(), dll.

    /**
     * Memperbarui hasil kunjungan (visit_result) berdasarkan ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // 2. Cari data LogbookVisits berdasarkan ID
            //    findOrFail akan otomatis mengembalikan 404 jika tidak ditemukan
            $visit = LogbookVisits::findOrFail($id);

            // 3. Perbarui hanya kolom 'visit_result'
            $visit->visit_result = $request->input('visit_result');
            
            // 4. Simpan perubahan ke database
            $visit->save();

            // 5. Beri respons sukses ke aplikasi
            return response()->json([
                'message' => 'Data berhasil diperbarui.',
                'data' => $visit
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Tangani error jika ID tidak ditemukan
            return response()->json([
                'message' => 'Data kunjungan tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            // Tangani error lain yang mungkin terjadi
            return response()->json([
                'message' => 'Terjadi kesalahan pada server.'
            ], 500);
        }
    }

      public function submitPlanning(Request $request)
    {
        $userId = $request->user()->id;

        // Ambil root array JSON
        $entries = $request->json()->all();

        foreach ($entries as $entry) {
            TrsLogbookVisits::create([
                'id_user' => $userId,
                'customer_name' => $entry['customer_name'] ?? null,
                'keterangan' => $entry['keterangan'] ?? null,
                'plan_visit' => $entry['plan_visit'] ?? null,
                'is_active' => 1,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Planning berhasil disimpan'
        ]);
    }

    public function chartadmin(Request $request)
    {
        // default minggu berjalan (Senin - Minggu)
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek   = Carbon::now()->endOfWeek();

        // Filter tanggal (opsional lewat request)
        $startDate = $request->input('start_date', $startOfWeek);
        $endDate   = $request->input('end_date', $endOfWeek);

        // Daftar sales yang mau dihitung
        $sales = ['ADMINSTRATOR', 'asep'];

        // Cari ID user yang cocok dengan nama di atas
        $salesUserIds = User::whereIn('name', $sales)->pluck('id');

        // === Hitung Data ===

        // 1. Total Sales (kunjungan oleh sales tertentu dalam minggu berjalan)
        $totalSales = LogbookVisits::whereIn('id_user', $salesUserIds)
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->count();

        // 2. Total Kunjungan (semua kunjungan dalam minggu berjalan)
        $totalKunjungan = LogbookVisits::whereBetween('visit_date', [$startDate, $endDate])
            ->count();

        // 3. Total Plan (semua plan visit minggu berjalan)
        $totalPlan = TrsLogbookVisits::whereBetween('plan_visit', [$startDate, $endDate])
            ->count();

        // 4. Follow Up (ambil dari tabel yang benar)
        $followUp = LogbookVisits::where('remark', 'Follow Up')
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->count();

       $oldCustomer = LogbookVisits::whereNull('new_customer_name')
            ->whereNotNull('customer_name')
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->count();

        $newCustomer = LogbookVisits::whereNotNull('new_customer_name')
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->count();

        $totalCustomer = $oldCustomer + $newCustomer;


        // Pastikan variabel tanggal adalah objek Carbon untuk pemformatan yang konsisten
        $startDateCarbon = \Carbon\Carbon::parse($startDate);
        $endDateCarbon   = \Carbon\Carbon::parse($endDate);

        return response()->json([
            'total_sales'     => $totalSales,
            'total_kunjungan' => $totalKunjungan,
            'total_plan'      => $totalPlan,
            'total_customer'  => $totalCustomer,
            'old_customer'    => $oldCustomer,
            'new_customer'    => $newCustomer,
            'follow_up'       => $followUp,
            'filter_start'    => $startDateCarbon->format('Y-m-d'),
            'filter_end'      => $endDateCarbon->format('Y-m-d'),
        ]);
    }

    private function getRegionMappings()
    {
        return [
            'Region 1' => ['ADMINSTRATOR'],
            'Region 2' => ['ADMINSTRATOR'],
            'Region 3' => ['ADMINSTRATOR'],
            'Region 4' => ['ADMINSTRATOR'],
        ];
    }

    public function indexsales(Request $request)
    {
        // 1. Ambil input dari request
        $search = $request->input('search');
        $limit = $request->input('limit', 10);
        $startDate = $request->input('start_date', Carbon::now()->startOfWeek()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfWeek()->toDateString());
        $requestedRegions = $request->input('regions'); // Ini adalah array nama region, misal: ['Region 1', 'Region 3']

        // Ambil pemetaan region
        $regionMappings = $this->getRegionMappings();
        $salesToQuery = [];

        // 2. DIUBAH: Logika filter berdasarkan region
        if (is_array($requestedRegions) && !empty($requestedRegions)) {
            // Jika Android mengirim filter region, kumpulkan nama sales dari region yang dipilih
            foreach ($requestedRegions as $regionName) {
                if (isset($regionMappings[$regionName])) {
                    $salesToQuery = array_merge($salesToQuery, $regionMappings[$regionName]);
                }
            }
        } else {
            // Jika tidak ada filter region (default), kumpulkan semua nama sales dari semua region
            foreach ($regionMappings as $salesInRegion) {
                $salesToQuery = array_merge($salesToQuery, $salesInRegion);
            }
        }

        // Pastikan tidak ada nama duplikat
        $salesToQuery = array_unique($salesToQuery);

        // Jika setelah filter tidak ada sales yang dipilih, kembalikan hasil kosong
        if (empty($salesToQuery)) {
            return response()->json(['data' => []]);
        }

        // 3. Mulai query dari model User
        $query = User::query()
            ->select('id', 'name', 'telp', 'email') // Kolom 'region' tidak lagi dibutuhkan
            ->withCount([
                'logbookVisits as visit_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('visit_date', [$startDate, $endDate]);
                },
                'trsLogbookVisits as plan_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('plan_visit', [$startDate, $endDate]);
                }
            ])
            // DIUBAH: Filter nama sales berdasarkan hasil logika region di atas
            ->whereIn('name', $salesToQuery);

        // 4. Terapkan filter pencarian nama jika ada
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        
        $salesPerformance = $query->orderBy('name', 'asc')->paginate($limit);

        return response()->json($salesPerformance);
    }

    /**
     * DITAMBAHKAN: Endpoint baru untuk mengambil daftar region yang unik.
     */
     public function getAvailableRegions()
    {
        // Ambil hanya "kunci" atau nama region dari array pemetaan
        $regionNames = array_keys($this->getRegionMappings());
            
        return response()->json($regionNames);
    }


}
