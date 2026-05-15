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
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\DB;

class SalesVisitController extends Controller
{
    public function store(Request $request)
    {
        try {
            // VALIDASI: TANPA 'files' dan 'files.*'
            $validated = $request->validate([
                'customer_name'     => 'nullable|string|max:255',
                'new_customer_name' => 'nullable|string|max:255',
                'pic_cust'          => 'required|string|max:255',
                'jabatan'           => 'nullable|string|max:255',
                'visit_result'      => 'nullable|string',
                'location'          => 'nullable|string|max:255',
                'remark'            => 'nullable|string|max:50',
                // attachment tetap opsional & divalidasi wajar
                'attachment'        => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            ]);

            DB::beginTransaction();

            $logbookVisit = new LogbookVisits();
            $logbookVisit->id_user = auth()->id();

            $customerNameRaw   = $validated['customer_name'] ?? $validated['new_customer_name'] ?? 'unknown_customer';
            $customerNameClean = Str::slug($customerNameRaw) ?: 'unknown';

            // ===== Foto utama (opsional) -> public/assets/sales_report/kunjungan =====
            $attachmentName = null;
            if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
                $folder = public_path('assets/sales_report/kunjungan');
                if (!is_dir($folder)) mkdir($folder, 0755, true);

                $ext       = strtolower($request->file('attachment')->getClientOriginalExtension());
                $timestamp = now()->format('Ymd_His');
                $rand      = Str::random(6);
                $attachmentName = "kunjungan_{$customerNameClean}_u".auth()->id()."_{$timestamp}_{$rand}.{$ext}";

                $request->file('attachment')->move($folder, $attachmentName);
                $logbookVisit->attachment = $attachmentName; // simpan nama file saja
            } else {
                $logbookVisit->attachment = null;
            }

            // ===== Dokumen tambahan (BENAR-BENAR OPSIONAL) -> public/assets/sales_report/file =====
            $savedDocNames = [];
            $docInputs = $request->file('files'); // bisa null / array<UploadedFile>
            if (is_array($docInputs) && count($docInputs) > 0) {
                $docFolder = public_path('assets/sales_report/file');
                if (!is_dir($docFolder)) mkdir($docFolder, 0755, true);

                // Guard minimal (hapus blok ini jika ingin tanpa cek sama sekali)
                $allowedExt = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','zip','rar','7z','jpg','jpeg','png'];

                foreach ($docInputs as $idx => $doc) {
                    if (!$doc || !$doc->isValid()) continue;

                    $ext = strtolower($doc->getClientOriginalExtension());
                    if (!in_array($ext, $allowedExt, true)) {
                        // lewati tipe yang tidak diizinkan
                        continue;
                    }

                    $timestamp = now()->format('Ymd_His');
                    $rand      = Str::random(6);
                    $fileName  = "file_{$customerNameClean}_u".auth()->id()."_{$timestamp}_{$idx}_{$rand}.{$ext}";

                    $doc->move($docFolder, $fileName);
                    $savedDocNames[] = $fileName;
                }
            }

            // Kolom 'file' hanya diisi jika ada dokumen; kalau tidak, null
            $logbookVisit->file = !empty($savedDocNames)
                ? json_encode($savedDocNames, JSON_UNESCAPED_SLASHES)
                : null;

            // ===== Field lainnya =====
            if (!empty($validated['new_customer_name'])) {
                $logbookVisit->customer_name     = $validated['new_customer_name'];
                $logbookVisit->new_customer_name = $validated['new_customer_name'];
            } else {
                $logbookVisit->customer_name     = $validated['customer_name'] ?? null;
                $logbookVisit->new_customer_name = null;
            }
            $logbookVisit->pic_cust     = $validated['pic_cust'] ?? null;
            $logbookVisit->jabatan      = $validated['jabatan'] ?? null;
            $logbookVisit->visit_result = $validated['visit_result'] ?? null;
            $logbookVisit->location     = $validated['location'] ?? null;
            $logbookVisit->remark       = $validated['remark'] ?? null;

            $logbookVisit->is_active  = 1;
            $logbookVisit->visit_date = now();

            $logbookVisit->save();
            DB::commit();

            $this->forgetSalesCaches();

            // --- START NOTIFICATION LOGIC ---
            try {
                // Determine Dept Head
                $deptHeadMappings = $this->getDepartmentHeadMappings();
                $senderName = strtoupper(auth()->user()->name);
                $targetDeptHeadName = null;
                
                Log::info("DEBUG NOTIF: Sender IS [{$senderName}]");

                // Find who manages this sales
                foreach ($deptHeadMappings as $dhName => $subordinates) {
                    // Check if sender is in this dept head's list
                    // Convert both to uppercase just in case
                    $upperSubordinates = array_map('strtoupper', $subordinates);
                    
                    if (in_array($senderName, $upperSubordinates)) {
                        $targetDeptHeadName = $dhName;
                        Log::info("DEBUG NOTIF: Match found! DeptHead is [{$dhName}]");
                        break;
                    }
                }

                if (!$targetDeptHeadName) {
                    Log::info("DEBUG NOTIF: No DeptHead mapping found for [{$senderName}]");
                }

                if ($targetDeptHeadName) {
                    $deptHeadUser = User::where('name', $targetDeptHeadName)->first();
                    if ($deptHeadUser && $deptHeadUser->fcm_token) {
                        Log::info("DEBUG NOTIF: Sending to {$targetDeptHeadName} (Token Exists)");
                        $title = "Kunjungan Baru";
                        $body  = "Sales {$senderName} baru saja submit kunjungan ke {$customerNameRaw}";
                        
                        \App\Services\FcmService::sendNotification(
                            $deptHeadUser->fcm_token, 
                            $title, 
                            $body, 
                            ['type' => 'visit', 'visit_id' => $logbookVisit->id]
                        );
                        Log::info("Notif sent to DeptHead: {$targetDeptHeadName}");
                    } else {
                        $reason = !$deptHeadUser ? "User not found in DB" : "No FCM Token";
                        Log::info("DEBUG NOTIF: Failed to send to {$targetDeptHeadName}. Reason: {$reason}");
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to send notification: " . $e->getMessage());
                // Don't fail the request just because notif failed
            }
            // --- END NOTIFICATION LOGIC ---

            return response()->json([
                'success' => true,
                'message' => 'Visit log saved successfully',
                'data'    => [
                    'id'         => $logbookVisit->id,
                    'attachment' => $logbookVisit->attachment,
                    'files'      => $savedDocNames, // kosong jika tidak ada
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve; // biarkan 422 dari Laravel
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error saving visit log', ['msg' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan visit log'], 500);
        }
    }

    public function customerList(Request $request)
    {
        $limit  = (int) $request->query('limit', 10);
        $limit  = max(1, min($limit, 100));
        $page   = max(1, (int) $request->query('page', 1));
        $search = $request->query('search');

        $customersQuery = Customer::query()
            ->select([
                'id',
                'customer_code',
                'name_customer',
                'area',
                'email',
                'no_telp',
                'status',
                'created_at',
                'updated_at',
            ])
            ->orderBy('name_customer');

        if (!empty($search)) {
            $term = '%' . trim($search) . '%';
            $customersQuery->where(function ($q) use ($term) {
                $q->where('name_customer', 'like', $term)
                    ->orWhere('customer_code', 'like', $term)
                    ->orWhere('area', 'like', $term);
            });
        }

        $customers = $customersQuery->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'data'         => $customers->items(),
            'current_page' => $customers->currentPage(),
            'per_page'     => $customers->perPage(),
            'total'        => $customers->total(),
            'last_page'    => $customers->lastPage(),
        ], 200);
    }


    /**
     * Menampilkan daftar kunjungan logbook untuk user yang sedang login.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    // Ganti fungsi index di controller Visit Anda

    public function index(Request $request)
    {
        $limit = (int) $request->get('limit', 10);
        $page = (int) $request->get('page', 1);
        $search = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $limit = max(1, min($limit, 100));
        $page = max(1, $page);
        
        // Dapatkan user yang sedang login
        $user = Auth::user(); 
        
        $query = LogbookVisits::with(['user:id,name'])
            ->select($this->getLogbookSelectColumns())
            ->orderBy('created_at', 'desc');

        // --- LOGIKA FILTER BARU YANG AMAN ---
        
        $finalSalesUserIds = [];

        if ($this->isDeptHeadUser($user)) {
            // --- KASUS 1: USER ADALAH DEPT HEAD ---

            // 1. Dapatkan daftar nama sales yang BOLEH dia lihat (dari mapping)
            $mappings = $this->getDepartmentHeadMappings();
            $mappingsKeyed = array_change_key_case($mappings, CASE_LOWER);
            $userKey = strtolower($user->name ?? '');
            $managedSalesNames = $mappingsKeyed[$userKey] ?? [];

            // 2. Dapatkan daftar nama sales yang INGIN dia lihat (dari filter Android)
            $requestedSalesNames = $request->input('sales'); // Ini adalah array

            $namesToQuery = [];
            if (is_array($requestedSalesNames) && !empty($requestedSalesNames)) {
                // Jika ada filter, kita cari irisannya (intersection)
                // Ini untuk keamanan, agar Dept Head A tidak bisa "mengintip" sales Dept Head B
                
                // Kita lakukan perbandingan case-insensitive
                $managedSalesLower = array_map('strtolower', $managedSalesNames);
                $requestedSalesLower = array_map('strtolower', array_map('trim', $requestedSalesNames));
                
                $allowedNamesLower = array_intersect($managedSalesLower, $requestedSalesLower);
                
                // Map kembali ke nama dengan case asli dari list $managedSalesNames
                $managedLookup = [];
                foreach ($managedSalesNames as $name) {
                    $managedLookup[strtolower($name)] = $name;
                }
                foreach ($allowedNamesLower as $nameLower) {
                    if (isset($managedLookup[$nameLower])) {
                        $namesToQuery[] = $managedLookup[$nameLower];
                    }
                }
            } else {
                // Jika tidak ada filter, Dept Head melihat semua sales di bawahnya
                $namesToQuery = $managedSalesNames;
            }

            // 3. Dapatkan User ID dari nama-nama yang sudah di-filter
            if (!empty($namesToQuery)) {
                $finalSalesUserIds = User::whereIn('name', $namesToQuery)->pluck('id')->all();
            }
            
        } else {
            // --- KASUS 2: USER ADALAH SALES BIASA ---
            // Abaikan semua filter 'sales[]' dan hanya tampilkan datanya sendiri
            $finalSalesUserIds = [$user->id];
        }

        // Terapkan query ID
        if (empty($finalSalesUserIds)) {
            // Jika hasilnya kosong (misal Dept Head tanpa sales), jangan tampilkan apa-apa
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('id_user', $finalSalesUserIds);
        }
        // --- AKHIR LOGIKA FILTER BARU ---


        if ($search) {
            $searchTerm = '%' . $search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('customer_name', 'like', $searchTerm)
                ->orWhere('new_customer_name', 'like', $searchTerm);
            });
        }

        if ($startDate && $endDate) {
            $query->whereDate('visit_date', '>=', $startDate)
                ->whereDate('visit_date', '<=', $endDate);
        }

        return $query->paginate($limit, ['*'], 'page', $page);
    }

    // Ganti fungsi indexplan di controller Plan Anda

    // Di dalam SalesVisitController.php

    public function indexplan(Request $request)
    {
        $limit = (int) $request->get('limit', 10);
        $page = (int) $request->get('page', 1);
        $search = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $limit = max(1, min($limit, 100));
        $page = max(1, $page);
        
        // Dapatkan user yang sedang login
        $user = Auth::user();

        // Model yang digunakan adalah TrsLogbookVisits
        $query = TrsLogbookVisits::with(['user:id,name'])
            ->select($this->getPlanSelectColumns())
            ->orderBy('created_at', 'desc');

        // --- LOGIKA FILTER BARU YANG AMAN (Disalin dari 'index') ---
        
        $finalSalesUserIds = [];

        if ($this->isDeptHeadUser($user)) {
            // --- KASUS 1: USER ADALAH DEPT HEAD ---

            // 1. Dapatkan daftar nama sales yang BOLEH dia lihat (dari mapping)
            $mappings = $this->getDepartmentHeadMappings();
            $mappingsKeyed = array_change_key_case($mappings, CASE_LOWER);
            $userKey = strtolower($user->name ?? '');
            $managedSalesNames = $mappingsKeyed[$userKey] ?? [];

            // 2. Dapatkan daftar nama sales yang INGIN dia lihat (dari filter Android)
            $requestedSalesNames = $request->input('sales'); // Ini adalah array

            $namesToQuery = [];
            if (is_array($requestedSalesNames) && !empty($requestedSalesNames)) {
                // Jika ada filter, kita cari irisannya (intersection)
                $managedSalesLower = array_map('strtolower', $managedSalesNames);
                $requestedSalesLower = array_map('strtolower', array_map('trim', $requestedSalesNames));
                $allowedNamesLower = array_intersect($managedSalesLower, $requestedSalesLower);
                
                $managedLookup = [];
                foreach ($managedSalesNames as $name) {
                    $managedLookup[strtolower($name)] = $name;
                }
                foreach ($allowedNamesLower as $nameLower) {
                    if (isset($managedLookup[$nameLower])) {
                        $namesToQuery[] = $managedLookup[$nameLower];
                    }
                }
            } else {
                // Jika tidak ada filter, Dept Head melihat semua sales di bawahnya
                $namesToQuery = $managedSalesNames;
            }

            // 3. Dapatkan User ID dari nama-nama yang sudah di-filter
            if (!empty($namesToQuery)) {
                $finalSalesUserIds = User::whereIn('name', $namesToQuery)->pluck('id')->all();
            }
            
        } else {
            // --- KASUS 2: USER ADALAH SALES BIASA ---
            // Abaikan semua filter 'sales[]' dan hanya tampilkan datanya sendiri
            $finalSalesUserIds = [$user->id];
        }

        // Terapkan query ID
        if (empty($finalSalesUserIds)) {
            // Jika hasilnya kosong (misal Dept Head tanpa sales), jangan tampilkan apa-apa
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('id_user', $finalSalesUserIds);
        }
        // --- AKHIR LOGIKA FILTER BARU ---


        if ($search) {
            $searchTerm = '%' . $search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('customer_name', 'like', $searchTerm);
            });
        }

        if ($startDate && $endDate) {
            // Pastikan menggunakan kolom 'plan_visit' untuk tabel ini
            $query->whereDate('plan_visit', '>=', $startDate)
                ->whereDate('plan_visit', '<=', $endDate);
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

        $visitBase = LogbookVisits::where('id_user', $userId)
            ->whereBetween('visit_date', [$startDate, $endDate]);

        $visits = (clone $visitBase)
            ->selectRaw('DATE(visit_date) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $visitStats = (clone $visitBase)
            ->selectRaw('COUNT(*) as total_kunjungan')
            ->selectRaw("SUM(CASE WHEN remark = 'Follow Up' THEN 1 ELSE 0 END) as follow_up")
            ->selectRaw('SUM(CASE WHEN new_customer_name IS NULL AND customer_name IS NOT NULL THEN 1 ELSE 0 END) as old_customer')
            ->selectRaw('SUM(CASE WHEN new_customer_name IS NOT NULL THEN 1 ELSE 0 END) as new_customer')
            ->selectRaw('COUNT(DISTINCT customer_name) as total_customer')
            ->first();

        $planningBase = TrsLogbookVisits::where('id_user', $userId)
            ->whereBetween('plan_visit', [$startDate, $endDate]);

        $planning = (clone $planningBase)
            ->selectRaw('DATE(plan_visit) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        $totalPlan = (clone $planningBase)->count();

        $totalKunjungan = (int) ($visitStats->total_kunjungan ?? 0);
        $followUp = (int) ($visitStats->follow_up ?? 0);
        $oldCustomer = (int) ($visitStats->old_customer ?? 0);
        $newCustomer = (int) ($visitStats->new_customer ?? 0);
        $totalCustomer = (int) ($visitStats->total_customer ?? 0);
        
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
        if (!$this->isDeptHeadUser($user) && !$this->isAdminUser($user)) {
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

        $allSalesIds = $this->getSalesIdsWithActivity();

        // Jika tidak ada sales dengan data, kembalikan array kosong
        if (empty($allSalesIds)) {
            return response()->json([]);
        }

        // Filter berdasarkan sales_id jika diberikan dan bukan 0
        $filteredSalesIds = $allSalesIds;
        if ($salesId > 0) {
            $filteredSalesIds = array_values(array_intersect($allSalesIds, [$salesId]));
            if (empty($filteredSalesIds)) {
                return response()->json([]);
            }
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
        $salesUsers = User::whereIn('id', $filteredSalesIds)
            ->select(['id', 'name', 'region'])
            ->get()
            ->keyBy('id');

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
        if (!$this->isDeptHeadUser($user) && !$this->isAdminUser($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $allSalesIds = $this->getSalesIdsWithActivity();

        // Jika tidak ada sales dengan data, kembalikan array kosong
        if (empty($allSalesIds)) {
            return response()->json([['id' => 0, 'name' => 'Semua Sales']]);
        }

        $response = Cache::remember(
            $this->getSalesUsersCacheKey(),
            now()->addMinutes(10),
            function () use ($allSalesIds) {
                $salesUsers = User::whereIn('id', $allSalesIds)
                    ->select(['id', 'name'])
                    ->orderBy('name')
                    ->get();

                $payload = [['id' => 0, 'name' => 'Semua Sales']];
                $payload = array_merge($payload, $salesUsers->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name
                    ];
                })->toArray());

                return $payload;
            }
        );

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

        $this->forgetSalesCaches();

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
        $startDateCarbon = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : $startOfWeek->copy();

        $endDateCarbon = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : $endOfWeek->copy();

        // Pakai id_user jika dikirim dari mobile agar data sesuai user login
        $forcedUserId = $request->input('id_user');

        // Tentukan daftar sales berdasarkan akun yang login
        $user = Auth::user();
        $departmentMappings = $this->getDepartmentHeadMappings();
        $allSales = [];

        if ($this->isDeptHeadUser($user)) {
            $mappingByKey = array_change_key_case($departmentMappings, CASE_LOWER);
            $deptKey = strtolower($user->name ?? '');
            $allSales = $mappingByKey[$deptKey] ?? [];
        } else {
            foreach ($departmentMappings as $salesInDept) {
                $allSales = array_merge($allSales, $salesInDept);
            }
        }

        if (empty($allSales)) {
            $allSales = User::orderBy('name')->pluck('name')->toArray();
        }

        $allSales = array_values(array_unique(array_filter($allSales)));

        // Khusus: sembunyikan nama dept head NANI SUTARMAN dari filter sales
        $excludedUpper = ['NANI SUTARMAN'];
        $allSales = array_values(array_filter($allSales, function ($n) use ($excludedUpper) {
            return !in_array(strtoupper($n), $excludedUpper, true);
        }));

        // Jika ada id_user, jadikan default pilihan tetapi tetap tampilkan daftar sales lengkap
        $forcedUser = $forcedUserId ? User::find($forcedUserId) : null;
        if ($forcedUser && !in_array($forcedUser->name, $allSales, true) && !in_array(strtoupper($forcedUser->name), $excludedUpper, true)) {
            $allSales[] = $forcedUser->name;
        }

        // Ambil daftar sales dari request (bisa berupa array atau string dipisah koma). Default: tampilkan semua sales.
        $selectedSales = $request->input('sales', $allSales);
        if (is_string($selectedSales)) {
            $selectedSales = array_filter(array_map('trim', explode(',', $selectedSales)));
        }
        if (!is_array($selectedSales)) {
            $selectedSales = [];
        }

        // Pastikan hanya sales yang diperbolehkan
        $selectedSales = array_values(array_intersect($allSales, $selectedSales));

        // Jika setelah filter hasilnya kosong (misal input string kosong), gunakan semua sales
        if (empty($selectedSales)) {
            $selectedSales = $allSales;
        }

        // Untuk backward compatibility dengan sisa fungsi, kita sebut variabelnya $sales
        $sales = $selectedSales;

        if (empty($sales)) {
            return response()->json([
                'total_sales'     => 0,
                'total_kunjungan' => 0,
                'total_plan'      => 0,
                'total_customer'  => 0,
                'old_customer'    => 0,
                'new_customer'    => 0,
                'follow_up'       => 0,
                'filter_start'    => $startDateCarbon->format('Y-m-d'),
                'filter_end'      => $endDateCarbon->format('Y-m-d'),
                'available_sales' => $allSales,
                'chart_labels'    => [],
                'chart_visits'    => [],
                'chart_plans'     => [],
            ]);
        }

        // Cari ID user yang cocok dengan nama di atas
        $users = User::whereIn('name', $sales)->get(['id', 'name']);

        if ($users->isEmpty()) {
            $users = User::orderBy('name')->get(['id', 'name']);
            $sales = $users->pluck('name')->all();
            $allSales = $sales; // pastikan available_sales hanya menampilkan user yang ada di DB
        }

        $usersMap = $users
            ->mapWithKeys(function (User $user) {
                $normalized = strtolower(trim($user->name));
                return [$normalized => (int) $user->id];
            })
            ->all();

        $salesUserIds = $users->pluck('id')->all();

        // === Hitung Data ===
        if (empty($salesUserIds)) {
            $totalSales = 0;
            $totalKunjungan = 0;
            $totalPlan = 0;
            $followUp = 0;
            $oldCustomer = 0;
            $newCustomer = 0;
            $totalCustomer = 0;
            $visitsAgg = collect();
            $plansAgg = collect();
        } else {
            $visitBase = LogbookVisits::whereIn('id_user', $salesUserIds)
                ->whereBetween('visit_date', [$startDateCarbon, $endDateCarbon]);

            $planBase = TrsLogbookVisits::whereIn('id_user', $salesUserIds)
                ->whereBetween('plan_visit', [$startDateCarbon, $endDateCarbon]);

            $visitStats = (clone $visitBase)
                ->selectRaw('COUNT(*) as total_kunjungan')
                ->selectRaw("SUM(CASE WHEN remark = 'Follow Up' THEN 1 ELSE 0 END) as follow_up")
                ->selectRaw('SUM(CASE WHEN new_customer_name IS NULL AND customer_name IS NOT NULL THEN 1 ELSE 0 END) as old_customer')
                ->selectRaw('SUM(CASE WHEN new_customer_name IS NOT NULL THEN 1 ELSE 0 END) as new_customer')
                ->selectRaw('COUNT(DISTINCT CASE WHEN customer_name IS NOT NULL THEN customer_name ELSE new_customer_name END) as total_customer')
                ->first();

            $visitsAgg = (clone $visitBase)
                ->selectRaw('id_user, COUNT(*) as c')
                ->groupBy('id_user')
                ->pluck('c', 'id_user');

            $plansAgg = (clone $planBase)
                ->selectRaw('id_user, COUNT(*) as c')
                ->groupBy('id_user')
                ->pluck('c', 'id_user');

            $totalPlan = (clone $planBase)->count();
            $totalKunjungan = (int) ($visitStats->total_kunjungan ?? 0);
            $followUp = (int) ($visitStats->follow_up ?? 0);
            $oldCustomer = (int) ($visitStats->old_customer ?? 0);
            $newCustomer = (int) ($visitStats->new_customer ?? 0);
            $totalCustomer = (int) ($visitStats->total_customer ?? ($oldCustomer + $newCustomer));
            $totalSales = $totalKunjungan;
        }

        $totalCustomer = $totalCustomer ?? ($oldCustomer + $newCustomer);

        // === Tambahan: data chart (labels, visits, plans) ===
        $visitsAgg = $visitsAgg instanceof \Illuminate\Support\Collection ? $visitsAgg : collect();
        $plansAgg  = $plansAgg instanceof \Illuminate\Support\Collection ? $plansAgg : collect();

        $chartLabels = [];
        $chartVisits = [];
        $chartPlans  = [];

        foreach ($sales as $name) {
            $normalizedName = strtolower(trim($name));
            $uid = $usersMap[$normalizedName] ?? null;
            $chartLabels[] = $this->formatFirstName($name);
            $chartVisits[] = $uid ? (int) $visitsAgg->get($uid, 0) : 0;
            $chartPlans[]  = $uid ? (int) $plansAgg->get($uid, 0) : 0;
        }

        return response()->json([
            // data lama (tetap)
            'total_sales'     => $totalSales,
            'total_kunjungan' => $totalKunjungan,
            'total_plan'      => $totalPlan,
            'total_customer'  => $totalCustomer,
            'old_customer'    => $oldCustomer,
            'new_customer'    => $newCustomer,
            'follow_up'       => $followUp,
            'filter_start'    => $startDateCarbon->format('Y-m-d'),
            'filter_end'      => $endDateCarbon->format('Y-m-d'),

            // Data untuk filter di Android
            'available_sales' => $allSales,

            // hanya yang dibutuhkan untuk Android
            'chart_labels'    => $chartLabels,
            'chart_visits'    => $chartVisits,
            'chart_plans'     => $chartPlans,
        ]);
    }


    // Ambil kata pertama agar label chart lebih ringkas
    private function formatFirstName(string $name): string
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return $name;
        }

        $parts = preg_split('/\s+/', $trimmed);
        return $parts[0] ?? $name;
    }


    private function getRegionMappings(): array
    {
        return [
            'Region 1' => [
                'YAN WELEM MANGINSELA',
                'WULYO EKO PRASETYO',
                'SENDY PRABOWO',
                'HEXAPA DARMADI',
                'YULMAI RIDO WINANDA',
                'JUN JOHAMIN PD',
            ],
            'Region 2' => [
                'HERY HERMAWAN',
                'RIFQI RAHMAT DZATNIKA',
                'SARAH EGA BUDI ASTUTI',
                'DIMAS ADITYA PRIANDANA',
                'SONY STIAWAN',
                'HEXAPA DARMADI',
                'ILHAM CHOLID',
                'YULMAI RIDO WINANDA',
            ],
            'Region 3' => [
                'DANIA ISNAWATI',
                'FISKA CHRISMAS YUDHA',
                'TOTOK SISWOYO',
                'ANDIK TOTOK SISWOYO',
                'HEXAPA DARMADI',
            ],
            'Region 4' => [
                'DWI KUNTORO',
                'YUNASIS PALGUNADI',
                'HEXAPA DARMADI',
                'ANDIK TOTOK SISWOYO',
            ],
        ];
    }

    private function getDepartmentHeadMappings(): array
    {
        return [
            'ANDIK TOTOK SISWOYO' => [
                'ANDIK TOTOK SISWOYO',
                'DANIA ISNAWATI',
                'FISKA CHRISMAS YUDHA',
                'TOTOK SISWOYO',
                'DWI KUNTORO',
                'YUNASIS PALGUNADI',
                'HEXAPA DARMADI',
            ],
            'ILHAM CHOLID' => [
                'ILHAM CHOLID',
                'HERY HERMAWAN',
                'RIFQI RAHMAT DZATNIKA',
                'SARAH EGA BUDI ASTUTI',
                'DIMAS ADITYA PRIANDANA',
                'SONY STIAWAN',
            ],
            'JUN JOHAMIN PD' => [
                'JUN JOHAMIN PD',
                'YAN WELEM MANGINSELA',
                'WULYO EKO PRASETYO',
                'SENDY PRABOWO',
            ],
            'YULMAI RIDO WINANDA' => [          
                'YULMAI RIDO WINANDA',
                'YAN WELEM MANGINSELA',
                'WULYO EKO PRASETYO',
                'SENDY PRABOWO',
                'HERY HERMAWAN',
                'RIFQI RAHMAT DZATNIKA',
                'SARAH EGA BUDI ASTUTI',
                'DIMAS ADITYA PRIANDANA',
                'SONY STIAWAN',
            ],
            'NANI SUTARMAN' => [
                'ILHAM CHOLID',
                'ANDIK TOTOK SISWOYO',
                'JUN JOHAMIN PD',
                'YULMAI RIDO WINANDA',
                'DANIA ISNAWATI',
                'DIMAS ADITYA PRIANDANA',
                'DWI KUNTORO',
                'FISKA CHRISMAS YUDHA',
                'HERY HERMAWAN',
                'HEXAPA DARMADI',
                'RIFQI RAHMAT DZATNIKA',
                'SARAH EGA BUDI ASTUTI',
                'SENDY PRABOWO',
                'SONY STIAWAN',
                'TOTOK SISWOYO',
                'WULYO EKO PRASETYO',
                'YAN WELEM MANGINSELA',
                'YUNASIS PALGUNADI',
            ],
        ];
    }

    private function getDeptHeadNames(): array
    {
        return [
            'ANDIK TOTOK SISWOYO',
            'ILHAM CHOLID',
            'JUN JOHAMIN PD',
            'YULMAI RIDO WINANDA',
            'NANI SUTARMAN',
        ];
    }

    private function getAdminNames(): array
    {
        return [
            'ADMINISTRATOR',
            'ADMINSTRATOR',
        ];
    }

    private function isDeptHeadUser(?User $user): bool
    {
        if (!$user || empty($user->name)) {
            return false;
        }

        $deptHeads = array_map('strtoupper', $this->getDeptHeadNames());
        return in_array(strtoupper($user->name), $deptHeads, true);
    }

    private function isAdminUser(?User $user): bool
    {
        if (!$user || empty($user->name)) {
            return false;
        }

        $admins = array_map('strtoupper', $this->getAdminNames());
        return in_array(strtoupper($user->name), $admins, true);
    }

    private function resolveGroupMappings(?string $category): array
    {
        $normalized = strtolower((string) $category);

        if (in_array($normalized, [
            'dept_head',
            'dept-head',
            'depthead',
            'department_head',
            'department-head',
            'departmenthead',
            'section_head',
            'section-head',
            'sectionhead',
        ], true)) {
            return $this->getDepartmentHeadMappings();
        }

        return $this->getRegionMappings();
    }

    private function getLogbookSelectColumns(): array
    {
        return [
            'id',
            'id_user',
            'customer_name',
            'new_customer_name',
            'pic_cust',
            'jabatan',
            'visit_result',
            'remark',
            'attachment',
            'file',
            'location',
            'visit_date',
            'created_at',
            'updated_at',
        ];
    }

    private function getPlanSelectColumns(): array
    {
        return [
            'id',
            'id_user',
            'customer_name',
            'keterangan',
            'plan_visit',
            'is_active',
            'created_at',
            'updated_at',
        ];
    }

    private function getSalesIdsCacheKey(): string
    {
        return 'sales_visit_controller.sales_ids_with_activity';
    }

    private function getSalesUsersCacheKey(): string
    {
        return 'sales_visit_controller.sales_users_dropdown';
    }

    private function forgetSalesCaches(): void
    {
        Cache::forget($this->getSalesIdsCacheKey());
        Cache::forget($this->getSalesUsersCacheKey());
    }

    private function getSalesIdsWithActivity(): array
    {
        return Cache::remember(
            $this->getSalesIdsCacheKey(),
            now()->addMinutes(10),
            static function () {
                $visitIds = LogbookVisits::query()->distinct()->pluck('id_user')->all();
                $planIds = TrsLogbookVisits::query()->distinct()->pluck('id_user')->all();

                return array_values(array_filter(array_unique(array_merge($visitIds, $planIds))));
            }
        );
    }

    public function indexsales(Request $request)
    {
        // 1. Ambil input dari request
        $search = $request->input('search');
        $limit = $request->input('limit', 10);
        $startDate = $request->input('start_date', Carbon::now()->startOfWeek()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfWeek()->toDateString());
        $category = $request->input('category');
        $requestedRegions = $request->input('regions'); // Bisa region, section head, atau dept head tergantung kategori

        // Ambil pemetaan sesuai kategori filter
        $regionMappings = $this->resolveGroupMappings($category);
        $salesToQuery = [];

        // 2. DIUBAH: Logika filter berdasarkan mapping (region / section head / dept head)
        if (is_array($requestedRegions) && !empty($requestedRegions)) {
            // Jika Android mengirim filter region/section head/dept head, kumpulkan nama sales dari grup yang dipilih
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
     * DITAMBAHKAN: Endpoint baru untuk mengambil daftar region atau dept head yang unik.
     */
    public function getAvailableRegions(Request $request)
    {
        $category = $request->query('category');
        $regionNames = array_keys($this->resolveGroupMappings($category));

        return response()->json($regionNames);
    }

    private function parseDateRange(Request $request): array
{
    $request->validate([
        'start_date' => ['nullable', 'date'],
        'end_date'   => ['nullable', 'date'],
        'days'       => ['nullable', 'integer', 'min:1', 'max:366'], // opsional
    ]);

    $days  = (int) $request->input('days', 7);
    $start = $request->input('start_date');
    $end   = $request->input('end_date');

    if (!$start && !$end) {
        // DEFAULT: pekan berjalan (Senin–Minggu)
        $startDate = Carbon::now()->startOfWeek()->startOfDay();
        $endDate   = Carbon::now()->endOfWeek()->endOfDay();
    } elseif ($start && !$end) {
        $startDate = Carbon::parse($start)->startOfDay();
        $endDate   = $startDate->copy()->addDays($days - 1)->endOfDay();
    } elseif (!$start && $end) {
        $endDate   = Carbon::parse($end)->endOfDay();
        $startDate = $endDate->copy()->subDays($days - 1)->startOfDay();
    } else {
        $startDate = Carbon::parse($start)->startOfDay();
        $endDate   = Carbon::parse($end)->endOfDay();
    }

    if ($endDate->lt($startDate)) {
        [$startDate, $endDate] = [
            $endDate->copy()->startOfDay(),
            $startDate->copy()->endOfDay(),
        ];
    }

    return [$startDate, $endDate];
}

/**
 * GET /reports/visits/summary
 * Ringkasan total visit & total plan pada rentang tanggal.
 * (Optional) Filter user via ?sales[]=Nama1&sales[]=Nama2
 */
public function summary(Request $request): JsonResponse
{
    [$startDate, $endDate] = $this->parseDateRange($request);

    $salesNames   = (array) $request->query('sales', []);
    $salesUserIds = collect();
    if (!empty($salesNames)) {
        $salesUserIds = User::whereIn('name', $salesNames)->pluck('id');
    }

    $visitQuery = LogbookVisits::whereBetween('visit_date', [$startDate, $endDate]);
    if ($salesUserIds->isNotEmpty()) $visitQuery->whereIn('id_user', $salesUserIds);
    $totalVisit = $visitQuery->count();

    $planQuery = TrsLogbookVisits::whereBetween('plan_visit', [$startDate, $endDate]);
    if ($salesUserIds->isNotEmpty()) $planQuery->whereIn('id_user', $salesUserIds);
    $totalPlan = $planQuery->count();

    return response()->json([
        'total_visit'   => $totalVisit,
        'total_plan'    => $totalPlan,
        'filter_start'  => $startDate->format('Y-m-d'),
        'filter_end'    => $endDate->format('Y-m-d'),
    ]);
}

/**
 * GET /sales-summary
 * Ringkasan untuk satu sales + daftar kunjungan (paging).
 * Query: ?sales_id=123 | ?sales_name=Nama  (&page=1&limit=15)
 */
    public function salesSummary(Request $request): JsonResponse
    {
        // Validasi ringan untuk paging & identitas sales
        $request->validate([
            'sales_name' => ['required', 'string'],
            'page'       => ['nullable', 'integer', 'min:1'],
            'limit'      => ['nullable', 'integer', 'min:1', 'max:100'],
            // start_date, end_date, days divalidasi di parseDateRange()
        ]);

        [$startDate, $endDate] = $this->parseDateRange($request);

        $salesName = $request->query('sales_name');
        $salesUser = User::where('name', $salesName)->first();

        if (!$salesUser) {
            return response()->json(['message' => 'Sales tidak ditemukan.'], 404);
        }

        // Agregasi
        $totalVisit = LogbookVisits::where('id_user', $salesUser->id)
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->count();

        $totalPlan = TrsLogbookVisits::where('id_user', $salesUser->id)
            ->whereBetween('plan_visit', [$startDate, $endDate])
            ->count();

        // Paging
        $page  = max((int) $request->query('page', 1), 1);
        $limit = min(max((int) $request->query('limit', 15), 1), 100);

        // Listing
        $visitsQuery = LogbookVisits::with('user')
            ->where('id_user', $salesUser->id)
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->orderBy('visit_date', 'desc');

        $totalRows = (clone $visitsQuery)->count();

        $visits = $visitsQuery->forPage($page, $limit)
            ->get([
                'id','id_user','customer_name','new_customer_name','pic_cust','jabatan',
                'visit_result','attachment','location','visit_date','remark','file','created_at'
            ])
            ->map(function ($v) {
                return [
                    'id'            => $v->id,
                    'customer_name' => $v->customer_name ?? $v->new_customer_name,
                    'pic_cust'      => $v->pic_cust,
                    'jabatan'       => $v->jabatan,
                    'visit_result'  => $v->visit_result,
                    'attachment'    => $v->attachment,
                    'location'      => $v->location,
                    'remark'        => $v->remark,
                    'visit_date'    => $v->visit_date ? Carbon::parse($v->visit_date)->format('Y-m-d H:i:s') : null,
                    'created_at'    => optional($v->created_at)->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'filter' => [
                'start' => $startDate->format('Y-m-d'),
                'end'   => $endDate->format('Y-m-d'),
            ],
            'sales' => [
                'id'    => $salesUser->id,
                'name'  => $salesUser->name,
                'email' => $salesUser->email,
                'telp'  => $salesUser->telp ?? null,
            ],
            'totals' => [
                'visit' => $totalVisit,
                'plan'  => $totalPlan,
            ],
            'visits' => [
                'data'         => $visits,
                'current_page' => $page,
                'per_page'     => $limit,
                'total'        => $totalRows,
                'last_page'    => (int) ceil($totalRows / $limit),
            ],
        ]);
    }

    // Parse "lat,lng" atau "lat lng" -> [lat, lng] | null
    private function parseLatLng(?string $raw): ?array
    {
        if (!$raw) return null;
        $s = trim($raw);
        $delim = str_contains($s, ',') ? ',' : (str_contains($s, ' ') ? ' ' : null);
        if ($delim === null) return null;

        $parts = array_values(array_filter(array_map('trim', explode($delim, $s)), fn($v) => $v !== ''));
        if (count($parts) < 2) return null;

        $lat = filter_var($parts[0], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_SCIENTIFIC);
        $lng = filter_var($parts[1], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_SCIENTIFIC);

        if (!is_numeric($lat) || !is_numeric($lng)) return null;
        $lat = (float)$lat; $lng = (float)$lng;
        if (abs($lat) > 90 || abs($lng) > 180) return null;

        return [$lat, $lng];
    }

    // Reverse geocode pakai Nominatim + cache 1 hari
    private function reverseGeocode(float $lat, float $lng): ?string
    {
        $key = sprintf('revgeo:%f,%f', $lat, $lng);
        return Cache::remember($key, now()->addDay(), function () use ($lat, $lng) {
            // Nominatim minta User-Agent yang jelas
            $resp = Http::withHeaders([
                    'User-Agent' => 'SalesReport/1.0 (contact: admin@yourdomain.com)'
                ])
                ->timeout(8)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'jsonv2',
                    'accept-language' => 'id'
                ]);

            if (!$resp->ok()) return null;
            $json = $resp->json();
            return $json['display_name'] ?? null;
        });
    }


    /**
     * GET /reports/visits/download
     * Unduh CSV kunjungan pada rentang tanggal (opsional filter sales[]).
     */
    public function downloadVisits(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        [$startDate, $endDate] = $this->parseDateRange($request);

        // 1) Baca tiga varian input:
        //    - sales_ids[]    : array ID user (preferensi utama)
        //    - sales_names[]  : array nama user (akan di-resolve ke ID)
        //    - sales[]        : array campuran ID/nama (kompatibel dengan kode lama)
        $ids   = array_filter(array_map('intval', (array) $request->query('sales_ids', [])));
        $names = array_filter(array_map('strval', (array) $request->query('sales_names', [])));

        // Backward compatible & mixed input
        $mixed = (array) $request->query('sales', []);
        if (!empty($mixed)) {
            $mixedIds   = array_filter($mixed, fn($v) => ctype_digit((string) $v));
            $mixedNames = array_diff($mixed, $mixedIds);
            $ids        = array_merge($ids, array_map('intval', $mixedIds));
            $names      = array_merge($names, array_map('strval', $mixedNames));
        }

        // Resolve nama -> ID hanya bila perlu
        $idsFromNames = !empty($names)
            ? User::whereIn('name', $names)->pluck('id')->all()
            : [];

        // Gabungkan & dedup
        $salesUserIds = array_values(array_unique(array_map('intval', array_merge($ids, $idsFromNames))));

        $filename = sprintf('visits_%s_to_%s.xlsx', $startDate->format('Ymd'), $endDate->format('Ymd'));

        $parseLatLng = \Closure::fromCallable([$this, 'parseLatLng']);
        $reverseGeo  = \Closure::fromCallable([$this, 'reverseGeocode']);

        $export = new class($startDate, $endDate, $salesUserIds, $parseLatLng, $reverseGeo)
            implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
        {
            public function __construct(
                private Carbon   $startDate,
                private Carbon   $endDate,
                private array    $salesUserIds,
                private \Closure $parseLatLng,
                private \Closure $reverseGeocode,
            ) {}

            public function query()
            {
                $q = LogbookVisits::query()
                    ->leftJoin('users', 'users.id', '=', 'logbook_visits.id_user')
                    ->select([
                        'logbook_visits.visit_date',
                        'users.name as sales_name',
                        // Jika Anda juga ingin cetak Sales ID di Excel, tambahkan baris ini:
                        // 'users.id as sales_id',
                        'logbook_visits.pic_cust',
                        'logbook_visits.jabatan',
                        'logbook_visits.customer_name',
                        'logbook_visits.remark',
                        'logbook_visits.visit_result',
                        'logbook_visits.location',
                    ])
                    ->whereBetween('logbook_visits.visit_date', [$this->startDate, $this->endDate])
                    ->orderBy('logbook_visits.visit_date');

                if (!empty($this->salesUserIds)) {
                    $q->whereIn('logbook_visits.id_user', $this->salesUserIds);
                }
                return $q;
            }

            public function headings(): array
            {
                // Jika ingin menampilkan Sales ID, sisipkan 'Sales ID' setelah 'Sales'
                return ['Tanggal','Sales','Pic','Jabatan','Customer','Remark','Hasil','Lokasi','Alamat'];
                // return ['Tanggal','Sales ID','Sales','Pic','Jabatan','Customer','Remark','Hasil','Lokasi','Alamat'];
            }

            public function map($r): array
            {
                $alamat = '';
                if (!empty($r->location)) {
                    $coords = ($this->parseLatLng)($r->location);
                    if (is_array($coords)) {
                        [$lat, $lng] = $coords;
                        $alamat = ($this->reverseGeocode)($lat, $lng) ?? '';
                    }
                }

                // Jika Anda menambah 'Sales ID' di headings(), mapping-nya juga tambahkan $r->sales_id
                return [
                    Carbon::parse($r->visit_date)->format('Y-m-d'),
                    // $r->sales_id ?? '',    // aktifkan bila headings ikut menampilkan Sales ID
                    $r->sales_name ?? '',
                    $r->pic_cust ?? '',
                    $r->jabatan ?? '',
                    $r->customer_name ?? '',
                    $r->remark ?? '',
                    $r->visit_result ?? '',
                    $r->location ?? '',
                    $alamat,
                ];
            }
        };

        return Excel::download($export, $filename);
    }



    

    /**
     * GET /logbook-visits
     * Filter daftar kunjungan (dept head / admin bisa melihat semua).
     * Query: ?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD&sales_id=&search=&page=&limit=
     */
    public function getFilteredVisits(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->parseDateRange($request);

        $page   = max((int) $request->query('page', 1), 1);
        $limit  = min(max((int) $request->query('limit', 15), 1), 100);
        $salesId = $request->query('sales_id');
        $search  = $request->query('search');

        $q = LogbookVisits::with(['user:id,name'])
            ->select($this->getLogbookSelectColumns())
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->orderBy('visit_date','desc');

        if ($salesId) $q->where('id_user', $salesId);

        if ($search) {
            $q->where(function ($w) use ($search) {
                $w->where('customer_name','like',"%$search%")
                ->orWhere('new_customer_name','like',"%$search%")
                ->orWhere('visit_result','like',"%$search%");
            });
        }

        $total = (clone $q)->count();
        $rows  = $q->forPage($page, $limit)->get();

        return response()->json([
            'data'         => $rows,
            'current_page' => $page,
            'per_page'     => $limit,
            'total'        => $total,
            'last_page'    => (int) ceil($total / $limit),
        ]);
    }

    /**
     * GET /depthead/data
     * Data ringkas untuk Dept Head (mingguan by default).
     * (Total visit, total plan, total unique customer, follow up).
     */
    public function getDeptHeadData(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->parseDateRange($request);

        $totalVisit = LogbookVisits::whereBetween('visit_date', [$startDate, $endDate])->count();

        $totalPlan  = TrsLogbookVisits::whereBetween('plan_visit', [$startDate, $endDate])->count();

        $totalCustomer = LogbookVisits::whereBetween('visit_date', [$startDate, $endDate])
            ->whereNotNull('customer_name')
            ->distinct('customer_name')
            ->count('customer_name');

        $followUp = LogbookVisits::whereBetween('visit_date', [$startDate, $endDate])
            ->where('remark', 'Follow Up')
            ->count();

        return response()->json([
            'total_visit'   => $totalVisit,
            'total_plan'    => $totalPlan,
            'total_customer'=> $totalCustomer,
            'follow_up'     => $followUp,
            'filter_start'  => $startDate->format('Y-m-d'),
            'filter_end'    => $endDate->format('Y-m-d'),
        ]);
    }

    /**
     * GET /{visit}/files/download
     * Zip & download semua file terkait sebuah visit.
     * Catatan: saat ini field file yang dipakai: `attachment` (nama file).
     */
    public function downloadAll(LogbookVisits $visit, Request $request)
    {
        $baseDir = public_path('assets/sales_report/file');

        // --- Kumpulkan nama file yang diizinkan dari kolom `file` (array JSON / JSON string / CSV)
        $names = [];
        $raw = $visit->file;

        if (is_array($raw)) {
            foreach ($raw as $v) {
                $t = trim((string) $v);
                if ($t !== '') $names[] = $t;
            }
        } else {
            $str = trim((string) $raw);
            if ($str !== '') {
                $decoded = json_decode($str, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    foreach ($decoded as $v) {
                        $t = trim((string) $v);
                        if ($t !== '') $names[] = $t;
                    }
                } else {
                    foreach (explode(',', $str) as $v) {
                        $t = trim($v);
                        if ($t !== '') $names[] = $t;
                    }
                }
            }
        }

        // --- Validasi file yang benar-benar ada di disk (hindari path traversal)
        $files = [];
        foreach ($names as $name) {
            $safe = basename($name);
            $path = $baseDir . DIRECTORY_SEPARATOR . $safe;
            if (is_file($path)) {
                $files[$safe] = $path; // [nama_dalam_zip => path_penuh]
            }
        }

        if (empty($files)) {
            return response()->json(['message' => 'Tidak ada file untuk diunduh.'], 404);
        }

        // --- Jika hanya 1 file: stream langsung file tersebut (bukan ZIP)
        if (count($files) === 1) {
            $safeName = array_key_first($files);
            $path     = $files[$safeName];

            $mime = @mime_content_type($path) ?: 'application/octet-stream';
            $size = @filesize($path) ?: 0;

            $headers = [
                'Content-Type'              => $mime,
                'Content-Length'            => (string) $size,
                'Content-Disposition'       => 'attachment; filename="' . $safeName . '"',
                'Content-Transfer-Encoding' => 'binary',
                'Accept-Ranges'             => 'bytes',
                'Cache-Control'             => 'private, max-age=0, must-revalidate',
                'Pragma'                    => 'public',
            ];

            if (function_exists('ob_get_level')) {
                while (ob_get_level() > 0) { @ob_end_clean(); }
            }

            return new StreamedResponse(function () use ($path) {
                $fp = fopen($path, 'rb');
                if ($fp) {
                    fpassthru($fp);
                    fclose($fp);
                }
            }, 200, $headers);
        }

        // --- Jika >1 file: buat ZIP sementara lalu kirim
        $zipName = sprintf('visit_%d_files_%s.zip', $visit->id, now()->format('Ymd_His'));
        $tmpZip  = tempnam(sys_get_temp_dir(), 'zip');

        $zip = new \ZipArchive();
        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpZip);
            return response()->json(['message' => 'Gagal membuat arsip ZIP.'], 500);
        }
        foreach ($files as $safe => $path) {
            $zip->addFile($path, $safe);
        }
        $zip->close();

        $headers = [
            'Content-Type'              => 'application/zip',
            'Content-Length'            => (string) @filesize($tmpZip),
            'Content-Disposition'       => 'attachment; filename="' . $zipName . '"',
            'Content-Transfer-Encoding' => 'binary',
            'Accept-Ranges'             => 'bytes',
            'Cache-Control'             => 'private, max-age=0, must-revalidate',
            'Pragma'                    => 'public',
        ];

        return response()->download($tmpZip, $zipName, $headers)->deleteFileAfterSend(true);
    }


    // GET /{visit}/files/{fileName}
    public function downloadSingle(LogbookVisits $visit, string $fileName)
{
    $safeName = basename($fileName);
    $baseDir  = public_path('assets/sales_report/file');
    $path     = $baseDir . DIRECTORY_SEPARATOR . $safeName;

    // Ambil daftar file yang diizinkan dari kolom `file` (JSON array atau CSV)
    $allowed = [];
    if (!empty($visit->file)) {
        $decoded = json_decode($visit->file, true);
        if (is_array($decoded)) {
            $allowed = array_map('basename', $decoded);
        } else {
            $parts = array_map('trim', explode(',', (string) $visit->file));
            $allowed = array_map('basename', array_filter($parts));
        }
    }

    if (!in_array($safeName, $allowed, true) || !is_file($path)) {
        return response()->json(['message' => 'File tidak ditemukan.'], 404);
    }

    // Header yang ramah DownloadManager (tanpa FileFacade)
    $mime = @mime_content_type($path) ?: 'application/octet-stream';
    $size = @filesize($path) ?: 0;

    $headers = [
        'Content-Type'              => $mime,
        'Content-Length'            => (string) $size,
        'Content-Disposition'       => 'attachment; filename="' . $safeName . '"',
        'Content-Transfer-Encoding' => 'binary',
        'Accept-Ranges'             => 'bytes',
        'Cache-Control'             => 'private, max-age=0, must-revalidate',
        'Pragma'                    => 'public',
    ];

    // Bersihkan output buffer agar stream bersih
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) { @ob_end_clean(); }
    }

    return new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($path) {
        $fp = fopen($path, 'rb');
        if ($fp) {
            fpassthru($fp);
            fclose($fp);
        }
    }, 200, $headers);
}

}
