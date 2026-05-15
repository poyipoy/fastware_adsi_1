<?php

namespace App\Http\Controllers;

use App\Models\TcJobPosition;
use App\Models\TrsPenilaianTc;
use App\Models\TcPeopleDevelopment;
use App\Models\PoinKategori;
use App\Models\User;
use App\Models\UserJobAccess;
use App\Models\DetailTcPenilaian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

// Import the DB facade

class PenilaianTCController extends Controller
{
    private function normalizeScoreValue($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || strtolower($trimmed) === 'null') {
                return null;
            }
            $value = $trimmed;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Gabungkan posisi dari user_id + role_id di tc_user_job_accesses.
     */
    private function getAccessiblePositions(User $user): array
    {
        return collect(array_merge(
            UserJobAccess::getPositionsForUser($user->id),
            UserJobAccess::getPositionsForRole($user->role_id)
        ))->map(fn($p) => trim($p))->filter()->unique()->values()->toArray();
    }

    /**
     * Mapping Ka. Dept → Ka. Sie bawahan (user IDs).
     * Berdasarkan CSV "Employee All Dept" kolom Approval 1 & 2.
     */
    private function getKaSieIdsForKaDept(string $kaDeptName): array
    {
        $mapping = [
            'HARDI SAPUTRA'          => [102],                 // ABDUR
            'ARY RODJO PRASETYO'     => [25, 84],              // MUGI, RAGIL
            'MARTINUS CAHYO RAHASTO' => [42, 86],              // ADHI, RICHARDUS
            'YULMAI RIDO WINANDA'    => [72, 65],              // JUN, ILHAM
            'ADHI PRASETIYO'         => [86],                  // RICHARDUS
            'ANDIK TOTOK SISWOYO'    => [45],                  // TOTOK
            'JESSICA PAUNE'          => [70],                  // JESSICA
        ];
        return $mapping[$kaDeptName] ?? [];
    }

    /**
     * Halaman Penilaian — satu route untuk Ka. Sie, Ka. Dept, dan HR.
     * Gunakan ?level=kasie|kadept|hr untuk bedakan konteks.
     */
    public function indexTrs(Request $request)
    {
        $level = $request->query('level', 'kasie');
        $penilaianData = TrsPenilaianTc::all()->unique('id_job_position');
        $user = auth()->user();

        if (!in_array($user->role_id, [1, 15])) {
            if ($level === 'kadept') {
                // Sumber 1: akses langsung dari tc_user_job_accesses
                $directAccess = $this->getAccessiblePositions($user);

                // Sumber 2: posisi yang dikirim Ka. Sie bawahan (status >= 2)
                $subordinateIds = $this->getKaSieIdsForKaDept($user->name);
                $subordinateIds[] = $user->id;
                $submittedPositions = TrsPenilaianTc::whereIn('modified_at', $subordinateIds)
                    ->where('status', '>=', 2)
                    ->pluck('id_job_position')
                    ->map(fn($p) => trim($p))->filter()->unique()->values()->toArray();

                $allVisible = collect(array_merge($directAccess, $submittedPositions))
                    ->filter()->unique()->values()->toArray();

                if (!empty($allVisible)) {
                    $penilaianData = $penilaianData->filter(fn($item) => in_array(trim($item->id_job_position), $allVisible));
                }
            } elseif ($level === 'kasie') {
                // Ka. Sie: hanya posisi yang user punya akses
                $positions = $this->getAccessiblePositions($user);
                if (!empty($positions)) {
                    $penilaianData = $penilaianData->filter(fn($item) => in_array(trim($item->id_job_position), $positions));
                }
            }
            // level === 'hr': tanpa filter (lihat semua)
        }

        $positions = TcJobPosition::all();
        $employees = User::all();

        $viewName = ($level === 'kadept')
            ? 'tc_penilaian.penilaian_index_dept'
            : 'tc_penilaian.penilaian_index';

        return view($viewName, compact('penilaianData', 'positions', 'employees'));
    }

    /**
     * Backward compatibility — redirect ke /penilaian?level=kadept
     */
    public function indexTrs2()
    {
        return redirect()->route('penilaian.index', ['level' => 'kadept']);
    }

    /**
     * Backward compatibility — redirect ke /penilaian?level=hr
     */
    public function indexTrs3()
    {
        return redirect()->route('penilaian.index', ['level' => 'hr']);
    }

    public function createPenilaian()
    {
        $id_user = DB::table('users')->pluck('id')->first();
        $id_tc = DB::table('mst_tcs')->pluck('id')->first();
        $id_sk = DB::table('mst_soft_skills')->pluck('id')->first();
        $id_ad = DB::table('mst_additionals')->pluck('id')->first();

        // Ambil data employee dan posisi untuk form
        $users = User::all();

        // Tampilkan semua job positions tanpa filter user
        $jobPositions = TcJobPosition::select(DB::raw('MIN(id) as id'), 'job_position')
            ->groupBy('job_position')
            ->orderBy('job_position')
            ->get();

        $trsPenilaian = TrsPenilaianTc::all();
        $idJobPosition = optional($trsPenilaian->first())->id_job_position;

        $dataTc1 = PoinKategori::find(1);
        $dataTc2 = PoinKategori::find(2);
        $dataTc3 = PoinKategori::find(3);

        return view('tc_penilaian.sc_penilaian', compact('users', 'id_tc', 'id_sk', 'id_ad', 'jobPositions', 'trsPenilaian', 'idJobPosition', 'dataTc1', 'dataTc2', 'dataTc3'));
    }

    public function dsCompetency(
        \App\Services\Dashboard\CompetencyDashboardService $service
    ) {
        $data = $service->getDashboardData();

        return view('dashboard.dsCompetency', $data);
    }

    public function dsDetailCompetency()
    {
        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga


        return view('dashboard.dsDetailCompetency', compact('dataTc1', 'dataTc2', 'dataTc3'));
    }

    public function getJobPositionData(Request $request)
    {
        $jobPosition = $request->input('id'); // Ambil parameter id dari request

        // Log nilai jobPosition yang diterima
        Log::info('Received jobPosition:', ['jobPosition' => $jobPosition]);

        // Query pertama untuk data TC
        $tcResults = DB::select('
            SELECT jp.id, jp.id_user, jp.id AS id_job_position, jp.job_position, u.name, 
                tcs.id AS id_tc, NULL AS id_sk, NULL AS id_ad, 
                tcs.keterangan_tc AS keterangan,
                tcs.id_poin_kategori, 
                COALESCE(tcs.nilai, \'N/A\') AS nilai, 
                \'tc\' AS type,
                CASE WHEN EXISTS (
                    SELECT 1 FROM trs_penilaian_tcs trs 
                    WHERE trs.id_user = jp.id_user 
                    AND trs.id_job_position = jp.job_position
                ) THEN 1 ELSE 0 END AS has_penilaian
            FROM tc_job_positions jp
            JOIN users u ON jp.id_user = u.id
            LEFT JOIN mst_tcs tcs ON jp.id = tcs.id_job_position 
            WHERE jp.job_position = ?', [$jobPosition]);

        // Log hasil dari query TC
        Log::info('TC Results:', ['tcResults' => $tcResults]);

        // Query kedua untuk data SK
        $skResults = DB::select('
            SELECT jp.id, jp.id_user, jp.id AS id_job_position, jp.job_position, u.name, 
                NULL AS id_tc, sk.id AS id_sk, NULL AS id_ad, 
                sk.keterangan_sk AS keterangan, 
                sk.id_poin_kategori,
                COALESCE(sk.nilai, \'N/A\') AS nilai, 
                \'sk\' AS type,
                CASE WHEN EXISTS (
                    SELECT 1 FROM trs_penilaian_tcs trs 
                    WHERE trs.id_user = jp.id_user 
                    AND trs.id_job_position = jp.job_position
                ) THEN 1 ELSE 0 END AS has_penilaian
            FROM tc_job_positions jp
            JOIN users u ON jp.id_user = u.id
            LEFT JOIN mst_soft_skills sk ON jp.id = sk.id_job_position 
            WHERE jp.job_position = ?', [$jobPosition]);

        // Log hasil dari query SK
        Log::info('SK Results:', ['skResults' => $skResults]);

        // Query ketiga untuk data AD
        $adResults = DB::select('
            SELECT jp.id, jp.id_user, jp.id AS id_job_position, jp.job_position, u.name, 
                NULL AS id_tc, NULL AS id_sk, ad.id AS id_ad, 
                ad.keterangan_ad AS keterangan, 
                ad.id_poin_kategori,
                COALESCE(ad.nilai, \'N/A\') AS nilai, 
                \'ad\' AS type,
                CASE WHEN EXISTS (
                    SELECT 1 FROM trs_penilaian_tcs trs 
                    WHERE trs.id_user = jp.id_user 
                    AND trs.id_job_position = jp.job_position
                ) THEN 1 ELSE 0 END AS has_penilaian
            FROM tc_job_positions jp
            JOIN users u ON jp.id_user = u.id
            LEFT JOIN mst_additionals ad ON jp.id = ad.id_job_position 
            WHERE jp.job_position = ?', [$jobPosition]);

        // Log hasil dari query AD
        Log::info('AD Results:', ['adResults' => $adResults]);

        // Gabungkan hasil dari ketiga query
        $results = array_merge($tcResults, $skResults, $adResults);

        // Log hasil gabungan
        Log::info('Final Results:', ['results' => $results]);

        // Kembalikan hasil sebagai JSON
        return response()->json($results);
    }

    public function getJobPositionDataEdit(Request $request)
    {
        $jobPosition = $request->input('id'); // Ambil parameter id dari request

        $results = DB::select('
        (
            SELECT jp.id, jp.id_user, jp.job_position, u.name, 
                tcs.id AS id_tc, NULL AS id_sk, NULL AS id_ad, 
                tcs.keterangan_tc AS keterangan, 
                COALESCE(trs.nilai_tc, 0) AS nilai_tc,  
                NULL AS nilai_sk,  
                NULL AS nilai_ad,  
                "tc" AS type
            FROM tc_job_positions jp
            JOIN users u ON jp.id_user = u.id
            LEFT JOIN mst_tcs tcs ON jp.id = tcs.id_job_position
            LEFT JOIN trs_penilaian_tcs trs ON tcs.id = trs.id_tc AND trs.id_job_position = jp.id
            WHERE jp.job_position = ?
        )
        UNION ALL
        (
            SELECT jp.id, jp.id_user, jp.job_position, u.name, 
                NULL AS id_tc, sk.id AS id_sk, NULL AS id_ad, 
                sk.keterangan_sk AS keterangan, 
                NULL AS nilai_tc,  
                COALESCE(trs.nilai_sk, 0) AS nilai_sk,  
                NULL AS nilai_ad,  
                "sk" AS type
            FROM tc_job_positions jp
            JOIN users u ON jp.id_user = u.id
            LEFT JOIN mst_soft_skills sk ON jp.id = sk.id_job_position
            LEFT JOIN trs_penilaian_tcs trs ON sk.id = trs.id_sk AND trs.id_job_position = jp.id
            WHERE jp.job_position = ?
        )
        UNION ALL
        (
            SELECT jp.id, jp.id_user, jp.job_position, u.name, 
                NULL AS id_tc, NULL AS id_sk, ad.id AS id_ad, 
                ad.keterangan_ad AS keterangan, 
                NULL AS nilai_tc,  
                NULL AS nilai_sk,  
                COALESCE(trs.nilai_ad, 0) AS nilai_ad,  
                "ad" AS type
            FROM tc_job_positions jp
            JOIN users u ON jp.id_user = u.id
            LEFT JOIN mst_additionals ad ON jp.id = ad.id_job_position
            LEFT JOIN trs_penilaian_tcs trs ON ad.id = trs.id_ad AND trs.id_job_position = jp.id
            WHERE jp.job_position = ?
        )
        ', [$jobPosition, $jobPosition, $jobPosition]);

        return response()->json($results);
    }

    public function getNilaiDataEdit(Request $request)
    {
        // Ambil nilai id_job_position dari input request
        $jobPosition = $request->input('id');

        // Query untuk mengambil data berdasarkan id_job_position
        $results = DB::table('trs_penilaian_tcs')
            ->select('id', 'id_tc', 'id_sk', 'id_ad', 'nilai_tc', 'nilai_sk', 'nilai_ad')
            ->where('id_job_position', $jobPosition)
            ->get();

        return response()->json($results);
    }

    public function getJobPointKategori(Request $request)
    {
        $jobPosition = $request->input('id'); // Ambil job_position dari request

        // Query untuk mengambil data TC
        $tcResults = DB::select('
            SELECT jp.id, pk.id AS id_poin_kategori, pk.id_tc, pk.standar_poin AS standar_nilai, pk.tujuan,
                pk.deskripsi AS deskripsi, "tc" AS type
            FROM tc_job_positions jp
            LEFT JOIN tc_poin_kategoris pk ON jp.id = pk.id_job_position
            WHERE jp.job_position = ? AND pk.id_tc IS NOT NULL
        ', [$jobPosition]);

        // Query untuk mengambil data SK
        $skResults = DB::select('
            SELECT jp.id, pk.id AS id_poin_kategori, pk.id_sk, pk.standar_poin AS standar_nilai, pk.tujuan,
                pk.deskripsi AS deskripsi, "sk" AS type
            FROM tc_job_positions jp
            LEFT JOIN tc_poin_kategoris pk ON jp.id = pk.id_job_position
            WHERE jp.job_position = ? AND pk.id_sk IS NOT NULL
        ', [$jobPosition]);

        // Query untuk mengambil data AD
        $adResults = DB::select('
            SELECT jp.id, pk.id AS id_poin_kategori, pk.id_ad, pk.standar_poin AS standar_nilai, pk.tujuan,
                pk.deskripsi AS deskripsi, "ad" AS type
            FROM tc_job_positions jp
            LEFT JOIN tc_poin_kategoris pk ON jp.id = pk.id_job_position
            WHERE jp.job_position = ? AND pk.id_ad IS NOT NULL
        ', [$jobPosition]);

        // Mengembalikan data dalam format JSON
        return response()->json([
            'tc' => $tcResults,
            'sk' => $skResults,
            'ad' => $adResults,
        ]);
    }

    public function savePenilaian(Request $request)
    {
        try {
            Log::info('Request data:', ['request_data' => $request->all()]);

            // Mengonversi semua ID menjadi integer
            $userIds = array_map('intval', $request->input('id_user', []));
            $nilaiTc = $request->input('nilai_tc', []);
            $nilaiSk = $request->input('nilai_sk', []);
            $nilaiAd = $request->input('nilai_ad', []);
            $idTc = $request->input('id_tc', []);
            $idSk = $request->input('id_sk', []);
            $idAd = $request->input('id_ad', []);
            $idJobPosition = $request->input('posisi');

            // Reset status record lama yang sudah disetujui (status >= 2) kembali ke 1
            // karena ada penambahan karyawan baru pada job position yang sama
            $existingStatuses = TrsPenilaianTc::where('id_job_position', $idJobPosition)
                ->whereIn('id_user', $userIds)
                ->pluck('status')
                ->toArray();

            $hasNewUsers = collect($userIds)->filter(function ($userId) use ($idJobPosition) {
                return !TrsPenilaianTc::where('id_job_position', $idJobPosition)
                    ->where('id_user', $userId)
                    ->exists();
            })->isNotEmpty();

            if ($hasNewUsers) {
                // Ada karyawan baru → reset semua record lama pada job position ini ke status 1
                TrsPenilaianTc::where('id_job_position', $idJobPosition)
                    ->where('status', '>', 1)
                    ->update([
                        'status' => 1,
                        'modified_at' => auth()->user()->id,
                        'modified_updated' => auth()->user()->name,
                    ]);

                Log::info("Status reset to 1 for job_position {$idJobPosition} due to new employee added.");
            }

            foreach ($userIds as $userId) {
                if (!User::find($userId)) {
                    Log::warning("User ID $userId not found, skipping.");
                    continue;
                }

                Log::info("Processing User ID: $userId");

                // Hitung jumlah index berdasarkan jumlah terbanyak dari tc, sk, ad
                $maxCount = max(
                    count($nilaiTc[$userId] ?? []),
                    count($nilaiSk[$userId] ?? []),
                    count($nilaiAd[$userId] ?? [])
                );

                for ($index = 0; $index < $maxCount; $index++) {
                    $nilaiTcValue = isset($nilaiTc[$userId][$index]) ? (int)$nilaiTc[$userId][$index] : null;
                    $nilaiSkValue = isset($nilaiSk[$userId][$index]) ? (int)$nilaiSk[$userId][$index] : null;
                    $nilaiAdValue = isset($nilaiAd[$userId][$index]) ? (int)$nilaiAd[$userId][$index] : null;

                    // Ambil id_tc, id_sk, id_ad
                    $idTcValue = isset($idTc[$userId][$index]) ? (int)$idTc[$userId][$index] : null;
                    $idSkValue = isset($idSk[$userId][$index]) ? (int)$idSk[$userId][$index] : null;
                    $idAdValue = isset($idAd[$userId][$index]) ? (int)$idAd[$userId][$index] : null;

                    // Gunakan updateOrCreate agar record yang sudah ada tidak duplikat,
                    // record baru (competency baru) tetap dibuat
                    $matchKeys = [
                        'id_user' => $userId,
                        'id_job_position' => $idJobPosition,
                        'id_tc' => $idTcValue,
                        'id_sk' => $idSkValue,
                        'id_ad' => $idAdValue,
                    ];

                    // Hanya update nilai yang tidak null agar tidak menimpa data yang sudah ada
                    $updateData = [
                        'status' => 1,
                        'modified_at' => auth()->user()->id,
                        'modified_updated' => auth()->user()->name,
                    ];
                    if ($nilaiTcValue !== null) $updateData['nilai_tc'] = $nilaiTcValue;
                    if ($nilaiSkValue !== null) $updateData['nilai_sk'] = $nilaiSkValue;
                    if ($nilaiAdValue !== null) $updateData['nilai_ad'] = $nilaiAdValue;

                    Log::info('Data to save/update:', ['match' => $matchKeys, 'values' => $updateData]);

                    $result = TrsPenilaianTc::updateOrCreate($matchKeys, $updateData);

                    Log::info('Data berhasil disimpan/diupdate untuk user ID: ' . $userId, ['saved_data' => $result->toArray()]);
                }
            }

            Log::info('Data penilaian berhasil disimpan.');
            return response()->json(['success' => 'Data penilaian berhasil disimpan.'], 200);
        } catch (\Exception $e) {
            Log::error('Error while saving penilaian:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Terjadi kesalahan saat menyimpan data.'], 500);
        }
    }

    public function editTrs($id_job_position)
    {
        // Ambil satu data penilaian berdasarkan id_job_position
        $penilaian = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user'])
            ->where('id_job_position', $id_job_position)
            ->first(); // Mengambil satu record

        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga

        // Ambil semua data dari DetailTcPenilaian yang terkait dengan id_job_position
        // Ambil data detail penilaian terkait id_job_position
        $detailPenilaian = DetailTcPenilaian::where('id_job_position', $id_job_position)
            ->orderBy('created_at', 'desc') // Urutkan berdasarkan waktu terbaru
            ->get();

        return view('tc_penilaian.edit_penilaian', compact('penilaian', 'dataTc1', 'dataTc2', 'dataTc3', 'detailPenilaian'));
    }

    public function editTrs2($id_job_position)
    {
        // Ambil satu data penilaian berdasarkan id_job_position
        $penilaian = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user'])
            ->where('id_job_position', $id_job_position)
            ->first(); // Mengambil satu record

        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga

        $detailPenilaian = DetailTcPenilaian::where('id_job_position', $id_job_position)
            ->orderBy('created_at', 'desc') // Urutkan berdasarkan waktu terbaru
            ->get();

        return view('tc_penilaian.dept_penilaian', compact('penilaian', 'dataTc1', 'dataTc2', 'dataTc3', 'detailPenilaian'));
    }

    public function viewTrs($id_job_position)
    {
        // Ambil satu data penilaian berdasarkan id_job_position
        $penilaian = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user'])
            ->where('id_job_position', $id_job_position)
            ->first(); // Mengambil satu record

        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga

        $detailPenilaian = DetailTcPenilaian::where('id_job_position', $id_job_position)
            ->orderBy('created_at', 'desc') // Urutkan berdasarkan waktu terbaru
            ->get();

        return view('tc_penilaian.view_penilaian', compact('penilaian', 'dataTc1', 'dataTc2', 'dataTc3', 'detailPenilaian'));
    }

    public function previewTrs($id_job_position)
    {
        // Ambil satu data penilaian berdasarkan id_job_position
        $penilaian = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user'])
            ->where('id_job_position', $id_job_position)
            ->first(); // Mengambil satu record

        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga

        $detailPenilaian = DetailTcPenilaian::where('id_job_position', $id_job_position)
            ->orderBy('created_at', 'desc') // Urutkan berdasarkan waktu terbaru
            ->get();


        return view('tc_penilaian.privew_penilaian', compact('penilaian', 'dataTc1', 'dataTc2', 'dataTc3', 'detailPenilaian'));
    }

    public function getDataTrs(Request $request)
    {
        // Ambil semua data penilaian berdasarkan id_job_position
        $penilaians = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user'])
            ->where('id_job_position', $request->id_job_position)
            ->get(); // Mengambil semua record yang cocok

        return response()->json($penilaians);
    }

    public function updateTrs(Request $request, $id_job_position)
    {
        // Decode HTML entities pada $id_job_position untuk menghindari perubahan karakter
        $decoded_job_position = html_entity_decode($id_job_position);

        // Ambil data JSON yang dikirim dari AJAX
        $data = $request->json()->all();

        // Ambil records yang dikirim dari frontend (sudah di-group per record ID)
        $records = $data['records'] ?? [];

        // Log data yang diterima untuk pengecekan
        Log::info('Received records:', ['records' => $records]);

        // Update status dari penilaian
        TrsPenilaianTc::where('id_job_position', $decoded_job_position)
            ->where('status', 3)
            ->update(['status' => 2]);

        // Array untuk mengumpulkan perubahan keterangan_detail per name
        $changesByName = [];

        foreach ($records as $record) {
            $recordId = $record['id'] ?? null;
            if (!$recordId) continue;

            // Cari penilaian berdasarkan record ID dan pastikan job position sesuai
            $penilaian = TrsPenilaianTc::where('id', $recordId)
                ->where('id_job_position', $decoded_job_position)
                ->first();

            if (!$penilaian) continue;

            $hasChanged = false;
            $userName = $record['name'] ?? 'Unknown';
            $currentKeteranganDetail = [];

            $nilaiTcValue = $this->normalizeScoreValue($record['nilai_tc'] ?? null);
            $nilaiSkValue = $this->normalizeScoreValue($record['nilai_sk'] ?? null);
            $nilaiAdValue = $this->normalizeScoreValue($record['nilai_ad'] ?? null);

            // Proses nilai_tc
            if ($nilaiTcValue !== null && (int) $penilaian->nilai_tc !== $nilaiTcValue) {
                $penilaian->nilai_tc = $nilaiTcValue;
                $hasChanged = true;
                $keteranganTc = $record['keterangan_tc'] ?? '-';
                $currentKeteranganDetail[] = "Technical Competency: {$keteranganTc} = {$nilaiTcValue}";
            }

            // Proses nilai_sk
            if ($nilaiSkValue !== null && (int) $penilaian->nilai_sk !== $nilaiSkValue) {
                $penilaian->nilai_sk = $nilaiSkValue;
                $hasChanged = true;
                $keteranganSk = $record['keterangan_sk'] ?? '-';
                $currentKeteranganDetail[] = "Non-Competency (Soft Skills): {$keteranganSk} = {$nilaiSkValue}";
            }

            // Proses nilai_ad
            if ($nilaiAdValue !== null && (int) $penilaian->nilai_ad !== $nilaiAdValue) {
                $penilaian->nilai_ad = $nilaiAdValue;
                $hasChanged = true;
                $keteranganAd = $record['keterangan_ad'] ?? '-';
                $currentKeteranganDetail[] = "Additional: {$keteranganAd} = {$nilaiAdValue}";
            }

            // Simpan perubahan penilaian jika ada
            if ($hasChanged) {
                $penilaian->save();

                // Gabungkan perubahan berdasarkan nama
                if (!isset($changesByName[$userName])) {
                    $changesByName[$userName] = [];
                }
                $changesByName[$userName] = array_merge($changesByName[$userName], $currentKeteranganDetail);
            }
        }

        // Handle new_records (competency baru yang belum ada record-nya)
        $newRecords = $data['new_records'] ?? [];
        foreach ($newRecords as $newRecord) {
            $userId = $newRecord['id_user'] ?? null;
            $userName = $newRecord['name'] ?? 'Unknown';
            if (!$userId) continue;

            $nilaiTcValue = $this->normalizeScoreValue($newRecord['nilai_tc'] ?? null);
            $nilaiSkValue = $this->normalizeScoreValue($newRecord['nilai_sk'] ?? null);
            $nilaiAdValue = $this->normalizeScoreValue($newRecord['nilai_ad'] ?? null);

            // Skip jika semua nilai null
            if ($nilaiTcValue === null && $nilaiSkValue === null && $nilaiAdValue === null) continue;

            $result = TrsPenilaianTc::create([
                'id_user' => $userId,
                'id_job_position' => $decoded_job_position,
                'id_tc' => $newRecord['id_tc'] ?? null,
                'id_sk' => $newRecord['id_sk'] ?? null,
                'id_ad' => $newRecord['id_ad'] ?? null,
                'nilai_tc' => $nilaiTcValue,
                'nilai_sk' => $nilaiSkValue,
                'nilai_ad' => $nilaiAdValue,
                'status' => 1,
                'modified_at' => auth()->user()->id,
                'modified_updated' => auth()->user()->name,
            ]);

            Log::info('New record created:', ['data' => $result->toArray()]);

            // Track perubahan untuk DetailTcPenilaian
            $currentKeteranganDetail = [];
            if ($nilaiTcValue !== null) {
                $currentKeteranganDetail[] = "Technical Competency: " . ($newRecord['keterangan_tc'] ?? '-') . " = {$nilaiTcValue}";
            }
            if ($nilaiSkValue !== null) {
                $currentKeteranganDetail[] = "Non-Competency (Soft Skills): " . ($newRecord['keterangan_sk'] ?? '-') . " = {$nilaiSkValue}";
            }
            if ($nilaiAdValue !== null) {
                $currentKeteranganDetail[] = "Additional: " . ($newRecord['keterangan_ad'] ?? '-') . " = {$nilaiAdValue}";
            }

            if (!empty($currentKeteranganDetail)) {
                if (!isset($changesByName[$userName])) {
                    $changesByName[$userName] = [];
                }
                $changesByName[$userName] = array_merge($changesByName[$userName], $currentKeteranganDetail);
            }
        }

        // Simpan ke DetailTcPenilaian dengan menggabungkan keterangan_detail per nama
        foreach ($changesByName as $userName => $keteranganDetails) {
            DetailTcPenilaian::create([
                'id_job_position' => $decoded_job_position,
                'name' => $userName,
                'keterangan_detail' => implode('; ', $keteranganDetails), // Gabungkan detail dengan pemisah
                'catatan' => $data['alasan_perubahan'] ?? null, // Alasan perubahan
                'modified_at' => auth()->user()->name,
            ]);

            Log::info('DetailTcPenilaian created for:', [
                'id_job_position' => $decoded_job_position,
                'name' => $userName,
                'keterangan_detail' => implode('; ', $keteranganDetails),
                'catatan' => $data['alasan_perubahan'] ?? null
            ]);
        }

        // Kembalikan respon sukses
        return response()->json(['success' => true, 'message' => 'Nilai berhasil diupdate']);
    }


    public function updateTrs2(Request $request, $id_job_position)
    {
        // Decode HTML entities pada $id_job_position untuk menghindari perubahan karakter
        $decoded_job_position = html_entity_decode($id_job_position);

        // Ambil data JSON yang dikirim dari AJAX
        $data = $request->json()->all();

        // Ambil records yang dikirim dari frontend (sudah di-group per record ID)
        $records = $data['records'] ?? [];

        // Log data yang diterima untuk pengecekan
        Log::info('Received records:', ['records' => $records]);

        // Array untuk mengumpulkan perubahan keterangan_detail per nama
        $changesByName = [];

        foreach ($records as $record) {
            $recordId = $record['id'] ?? null;
            if (!$recordId) continue;

            // Cari penilaian berdasarkan record ID dan pastikan job position sesuai
            $penilaian = TrsPenilaianTc::where('id', $recordId)
                ->where('id_job_position', $decoded_job_position)
                ->first();

            if (!$penilaian) continue;

            $hasChanged = false;
            $userName = $record['name'] ?? 'Unknown';
            $currentKeteranganDetail = [];

            $nilaiTcValue = $this->normalizeScoreValue($record['nilai_tc'] ?? null);
            $nilaiSkValue = $this->normalizeScoreValue($record['nilai_sk'] ?? null);
            $nilaiAdValue = $this->normalizeScoreValue($record['nilai_ad'] ?? null);

            // Proses nilai_tc
            if ($nilaiTcValue !== null && (int) $penilaian->nilai_tc !== $nilaiTcValue) {
                $penilaian->nilai_tc = $nilaiTcValue;
                $hasChanged = true;
                $keteranganTc = $record['keterangan_tc'] ?? '-';
                $currentKeteranganDetail[] = "Technical Competency: {$keteranganTc} = {$nilaiTcValue}";
            }

            // Proses nilai_sk
            if ($nilaiSkValue !== null && (int) $penilaian->nilai_sk !== $nilaiSkValue) {
                $penilaian->nilai_sk = $nilaiSkValue;
                $hasChanged = true;
                $keteranganSk = $record['keterangan_sk'] ?? '-';
                $currentKeteranganDetail[] = "Non-Competency (Soft Skills): {$keteranganSk} = {$nilaiSkValue}";
            }

            // Proses nilai_ad
            if ($nilaiAdValue !== null && (int) $penilaian->nilai_ad !== $nilaiAdValue) {
                $penilaian->nilai_ad = $nilaiAdValue;
                $hasChanged = true;
                $keteranganAd = $record['keterangan_ad'] ?? '-';
                $currentKeteranganDetail[] = "Additional: {$keteranganAd} = {$nilaiAdValue}";
            }

            // Simpan perubahan penilaian jika ada
            if ($hasChanged) {
                $penilaian->save();

                // Gabungkan perubahan berdasarkan nama
                if (!isset($changesByName[$userName])) {
                    $changesByName[$userName] = [];
                }
                $changesByName[$userName] = array_merge($changesByName[$userName], $currentKeteranganDetail);
            }
        }

        // Handle new_records (competency baru yang belum ada record-nya)
        $newRecords = $data['new_records'] ?? [];
        foreach ($newRecords as $newRecord) {
            $userId = $newRecord['id_user'] ?? null;
            $userName = $newRecord['name'] ?? 'Unknown';
            if (!$userId) continue;

            $nilaiTcValue = $this->normalizeScoreValue($newRecord['nilai_tc'] ?? null);
            $nilaiSkValue = $this->normalizeScoreValue($newRecord['nilai_sk'] ?? null);
            $nilaiAdValue = $this->normalizeScoreValue($newRecord['nilai_ad'] ?? null);

            if ($nilaiTcValue === null && $nilaiSkValue === null && $nilaiAdValue === null) continue;

            $result = TrsPenilaianTc::create([
                'id_user' => $userId,
                'id_job_position' => $decoded_job_position,
                'id_tc' => $newRecord['id_tc'] ?? null,
                'id_sk' => $newRecord['id_sk'] ?? null,
                'id_ad' => $newRecord['id_ad'] ?? null,
                'nilai_tc' => $nilaiTcValue,
                'nilai_sk' => $nilaiSkValue,
                'nilai_ad' => $nilaiAdValue,
                'status' => 1,
                'modified_at' => auth()->user()->id,
                'modified_updated' => auth()->user()->name,
            ]);

            Log::info('New record created:', ['data' => $result->toArray()]);

            $currentKeteranganDetail = [];
            if ($nilaiTcValue !== null) {
                $currentKeteranganDetail[] = "Technical Competency: " . ($newRecord['keterangan_tc'] ?? '-') . " = {$nilaiTcValue}";
            }
            if ($nilaiSkValue !== null) {
                $currentKeteranganDetail[] = "Non-Competency (Soft Skills): " . ($newRecord['keterangan_sk'] ?? '-') . " = {$nilaiSkValue}";
            }
            if ($nilaiAdValue !== null) {
                $currentKeteranganDetail[] = "Additional: " . ($newRecord['keterangan_ad'] ?? '-') . " = {$nilaiAdValue}";
            }

            if (!empty($currentKeteranganDetail)) {
                if (!isset($changesByName[$userName])) {
                    $changesByName[$userName] = [];
                }
                $changesByName[$userName] = array_merge($changesByName[$userName], $currentKeteranganDetail);
            }
        }

        // Simpan ke DetailTcPenilaian dengan menggabungkan keterangan_detail per nama
        foreach ($changesByName as $userName => $keteranganDetails) {
            DetailTcPenilaian::create([
                'id_job_position' => $decoded_job_position,
                'name' => $userName,
                'keterangan_detail' => implode('; ', $keteranganDetails), // Gabungkan detail dengan pemisah
                'catatan' => $data['alasan_perubahan'] ?? null, // Alasan perubahan
                'modified_at' => auth()->user()->name,
            ]);

            Log::info('DetailTcPenilaian created for:', [
                'id_job_position' => $decoded_job_position,
                'name' => $userName,
                'keterangan_detail' => implode('; ', $keteranganDetails),
                'catatan' => $data['alasan_perubahan'] ?? null
            ]);
        }

        // Kembalikan respon sukses
        return response()->json(['success' => true, 'message' => 'Nilai berhasil diupdate']);
    }

    public function updateCatatan(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'catatan' => 'nullable|string|max:255'
        ]);

        // Temukan catatan berdasarkan ID
        $detail = DetailTcPenilaian::find($id);

        // Periksa apakah catatan ditemukan
        if (!$detail) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        // Perbarui catatan
        $detail->catatan = $request->input('catatan');
        $detail->modified_at = auth()->user()->name; // Set 'modified_by' sebagai pengguna yang mengedit
        $detail->save();

        // Log pembaruan catatan
        Log::info('Catatan updated:', [
            'id' => $id,
            'catatan' => $detail->catatan,
            'modified_at' => $detail->modified_at,
        ]);

        // Redirect kembali dengan pesan sukses
        return redirect()->back()->with('success', 'Catatan berhasil diperbarui.');
    }

    public function kirimSC(Request $request, $id_job_position)
    {
        // Temukan semua entri dengan id_job_position yang sesuai
        $penilaians = TrsPenilaianTc::where('id_job_position', $id_job_position)->get();

        if ($penilaians->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data not found.'], 404);
        }

        // Ubah status menjadi 2 untuk semua entri yang ditemukan
        foreach ($penilaians as $penilaian) {
            $penilaian->status = 2;
            $penilaian->modified_at = auth()->user()->id;
            $penilaian->save();
        }

        return response()->json(['success' => true, 'message' => 'Data Competency Telah Dikirim.']);
    }

    public function kirimDept(Request $request, $id_job_position)
    {
        // Temukan semua entri dengan id_job_position yang sesuai
        $penilaians = TrsPenilaianTc::where('id_job_position', $id_job_position)->get();

        if ($penilaians->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data not found.'], 404);
        }

        // Ubah status menjadi 2 untuk semua entri yang ditemukan
        foreach ($penilaians as $penilaian) {
            $penilaian->status = 3;
            $penilaian->save();
        }

        return response()->json(['success' => true, 'message' => 'Data Competency Telah Dikirim.']);
    }
    //chartRadar
    public function getCompetencyData(Request $request)
    {
        $selectedJobPosition = $request->input('job_position');

        $competencyData = DB::table('trs_penilaian_tcs as tpt')
            ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
            ->leftJoin('mst_tcs as tc', 'tpt.id_tc', '=', 'tc.id')
            ->leftJoin('mst_soft_skills as sk', 'tpt.id_sk', '=', 'sk.id')
            ->leftJoin('mst_additionals as ad', 'tpt.id_ad', '=', 'ad.id')
            ->select(
                'tpt.id_job_position',
                'u.name',
                'tpt.id_user',
                DB::raw('GROUP_CONCAT(DISTINCT tpt.id_tc ORDER BY tpt.id_tc ASC) AS id_tcs'),
                DB::raw('GROUP_CONCAT(DISTINCT tc.keterangan_tc ORDER BY tpt.id_tc ASC) AS keterangan_tcs'),
                DB::raw('GROUP_CONCAT(DISTINCT tpt.id_sk ORDER BY tpt.id_sk ASC) AS id_sks'),
                DB::raw('GROUP_CONCAT(DISTINCT sk.keterangan_sk ORDER BY tpt.id_sk ASC) AS keterangan_sks'),
                DB::raw('GROUP_CONCAT(DISTINCT tpt.id_ad ORDER BY tpt.id_ad ASC) AS id_ads'),
                DB::raw('GROUP_CONCAT(DISTINCT ad.keterangan_ad ORDER BY tpt.id_ad ASC) AS keterangan_ads'),
                DB::raw('SUM(tpt.nilai_tc) AS total_nilai_tc'),
                DB::raw('SUM(tpt.nilai_sk) AS total_nilai_sk'),
                DB::raw('SUM(tpt.nilai_ad) AS total_nilai_ad'),
                DB::raw('SUM(tc.nilai) AS standar_nilai_tc'),
                DB::raw('SUM(sk.nilai) AS standar_nilai_sk'),
                DB::raw('SUM(ad.nilai) AS standar_nilai_ad')
            )
            ->where('tpt.id_job_position', $selectedJobPosition)
            ->groupBy('tpt.id_user', 'tpt.id_job_position', 'u.name')
            ->get();

        return response()->json($competencyData);
    }

    public function getCompetencyFilter(Request $request)
    {
        $jobPosition = $request->input('job_position');
        $dataType = $request->input('data_type');  // Ambil data_type dari request

        if ($dataType === 'total_nilai_tc') {
            // Query untuk data yang berhubungan dengan TC
            $data = DB::table('trs_penilaian_tcs as tpt')
                ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
                ->leftJoin('mst_tcs as tc', 'tpt.id_tc', '=', 'tc.id')
                ->select(
                    'tpt.id_job_position',
                    'u.name',
                    'tpt.id_user',
                    'tpt.id_tc',
                    'tc.keterangan_tc',
                    DB::raw('MAX(tc.nilai) as tc_nilai'), // Menggunakan fungsi agregasi MAX
                    DB::raw('SUM(tpt.nilai_tc) as total_nilai_tc')
                )
                ->where('tpt.id_job_position', $jobPosition)
                ->groupBy(
                    'tpt.id_user',
                    'tpt.id_job_position',
                    'u.name',
                    'tpt.id_tc',
                    'tc.keterangan_tc'
                )
                ->get();
        } elseif ($dataType === 'total_nilai_sk') {
            // Query untuk data yang berhubungan dengan SK
            $data = DB::table('trs_penilaian_tcs as tpt')
                ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
                ->leftJoin('mst_soft_skills as sk', 'tpt.id_sk', '=', 'sk.id')
                ->select(
                    'tpt.id_job_position',
                    'u.name',
                    'tpt.id_user',
                    'tpt.id_sk',
                    'sk.keterangan_sk',
                    DB::raw('MAX(sk.nilai) as sk_nilai'), // Menggunakan fungsi agregasi MAX
                    DB::raw('SUM(tpt.nilai_sk) as total_nilai_sk')
                )
                ->where('tpt.id_job_position', $jobPosition)
                ->groupBy(
                    'tpt.id_user',
                    'tpt.id_job_position',
                    'u.name',
                    'tpt.id_sk',
                    'sk.keterangan_sk'
                )
                ->get();
        } elseif ($dataType === 'total_nilai_ad') {
            // Query untuk data yang berhubungan dengan AD
            $data = DB::table('trs_penilaian_tcs as tpt')
                ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
                ->leftJoin('mst_additionals as ad', 'tpt.id_ad', '=', 'ad.id')
                ->select(
                    'tpt.id_job_position',
                    'u.name',
                    'tpt.id_user',
                    'tpt.id_ad',
                    'ad.keterangan_ad',
                    DB::raw('MAX(ad.nilai) as ad_nilai'), // Menggunakan fungsi agregasi MAX
                    DB::raw('SUM(tpt.nilai_ad) as total_nilai_ad')
                )
                ->where('tpt.id_job_position', $jobPosition)
                ->groupBy(
                    'tpt.id_user',
                    'tpt.id_job_position',
                    'u.name',
                    'tpt.id_ad',
                    'ad.keterangan_ad'
                )
                ->get();
        } else {
            // Jika data_type tidak sesuai, kembalikan respons kosong atau pesan kesalahan
            return response()->json([], 400);  // Kembalikan kode status 400 untuk permintaan tidak valid
        }

        // Mengembalikan data sebagai JSON
        return response()->json($data);
    }

    public function getDetailCompetency(Request $request)
    {
        $id_user = $request->query('id_user');

        // Query untuk data yang berhubungan dengan TC
        $tcData = DB::table('trs_penilaian_tcs as tpt')
            ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
            ->leftJoin('mst_tcs as tc', 'tpt.id_tc', '=', 'tc.id')
            ->select(
                'tpt.id_job_position',
                'u.name',
                'tpt.id_user',
                'tpt.id_tc',
                'tc.keterangan_tc',
                DB::raw('MAX(tc.nilai) as tc_nilai'), // Menggunakan fungsi agregasi MAX
                DB::raw('SUM(tpt.nilai_tc) as total_nilai_tc')
            )
            ->where('tpt.id_user', $id_user)
            ->groupBy(
                'tpt.id_user',
                'tpt.id_job_position',
                'u.name',
                'tpt.id_tc',
                'tc.keterangan_tc'
            )
            ->get();

        // Query untuk data yang berhubungan dengan SK
        $skData = DB::table('trs_penilaian_tcs as tpt')
            ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
            ->leftJoin('mst_soft_skills as sk', 'tpt.id_sk', '=', 'sk.id')
            ->select(
                'tpt.id_job_position',
                'u.name',
                'tpt.id_user',
                'tpt.id_sk',
                'sk.keterangan_sk',
                DB::raw('MAX(sk.nilai) as sk_nilai'), // Menggunakan fungsi agregasi MAX
                DB::raw('SUM(tpt.nilai_sk) as total_nilai_sk')
            )
            ->where('tpt.id_user', $id_user)
            ->groupBy(
                'tpt.id_user',
                'tpt.id_job_position',
                'u.name',
                'tpt.id_sk',
                'sk.keterangan_sk'
            )
            ->get();

        // Query untuk data yang berhubungan dengan AD
        $adData = DB::table('trs_penilaian_tcs as tpt')
            ->leftJoin('users as u', 'tpt.id_user', '=', 'u.id')
            ->leftJoin('mst_additionals as ad', 'tpt.id_ad', '=', 'ad.id')
            ->select(
                'tpt.id_job_position',
                'u.name',
                'tpt.id_user',
                'tpt.id_ad',
                'ad.keterangan_ad',
                DB::raw('MAX(ad.nilai) as ad_nilai'), // Menggunakan fungsi agregasi MAX
                DB::raw('SUM(tpt.nilai_ad) as total_nilai_ad')
            )
            ->where('tpt.id_user', $id_user)
            ->groupBy(
                'tpt.id_user',
                'tpt.id_job_position',
                'u.name',
                'tpt.id_ad',
                'ad.keterangan_ad'
            )
            ->get();

        // Query untuk TcPeopleDevelopment
        $dataTcPeopleDevelopment = TcPeopleDevelopment::where('id_user', $id_user)
            ->where('status_2', 'Done') // Add condition for status_2 to be 'Done'
            ->with('user') // Ensure the user relationship is loaded
            ->get();

        // Menggunakan model Eloquent untuk mengambil data penilaian
        $penilaians = TrsPenilaianTc::with(['tc', 'sk', 'ad', 'poinKategori', 'user'])
            ->where('id_user', $id_user)
            ->get(); // Mengambil semua record yang cocok

        // Gabungkan hasil query menjadi satu array
        $data = [
            'tc_data' => $tcData,
            'sk_data' => $skData,
            'ad_data' => $adData,
            'penilaians' => $penilaians,
            'dataTcPeopleDevelopment' => $dataTcPeopleDevelopment, // Tambahkan hasil penilaian ke dalam array data
        ];

        // Mengembalikan data sebagai JSON
        return response()->json($data);
    }
}
