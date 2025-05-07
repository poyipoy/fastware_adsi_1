<?php

namespace App\Http\Controllers;

use App\Models\MstAdditionals;
use App\Models\MstSoftSkill;
use App\Models\MstTc;
use App\Models\User;
use App\Models\PoinKategori;
use App\Models\TcJobPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Import the DB facade
use Illuminate\Support\Facades\Validator;

use function PHPSTORM_META\map;

class TcController extends Controller
{
    public function tcShow()
    {
        // Ambil nama dan role_id user yang sedang login
        $userName = auth()->user()->name;
        $roleId = auth()->user()->role_id;

        // Inisialisasi variabel untuk data yang akan difilter berdasarkan nama user
        $technicalData = [];
        $softSkillsData = [];
        $additionalData = [];

        // Cek apakah role_id adalah 1, 14, atau 15
        if (in_array($roleId, [1])) {
            // Jika ya, tampilkan semua data tanpa filter
            $technicalData = MstTc::with(['jobPosition'])->get()->unique('id_job_position');
            $softSkillsData = MstSoftSkill::with(['jobPosition'])->get()->unique('id_job_position');
            $additionalData = MstAdditionals::with(['jobPosition'])->get()->unique('id_job_position');
        } else {
            // Tentukan data yang ditampilkan berdasarkan nama user
            if ($userName == 'HARDI SAPUTRA') {
                $jobPositions = [
                    'Logistic Admin',
                    'Admin Cutting Sheet(ACS)',
                    'Delivery Staff',
                    'Feeder',
                    'Logistic Foreman',
                    'PPIC Staff'
                ];
            } elseif ($userName == 'ABDUR RAHMAN AL FAAIZ') {
                $jobPositions = [
                    'Logistic Admin',
                    'Admin Cutting Sheet(ACS)',
                    'Delivery Staff',
                    'Feeder',
                    'Logistic Foreman',
                    'PPIC Staff'
                ];
            } elseif ($userName == 'YULMAI RIDO WINANDA') {
                $jobPositions = [
                    'Sales Admin',
                    'SOH Region 1',
                    'Sales Engineer Reg 1',
                    'SOH Region 2',
                    'Sales Engineer Reg 2',
                ];
            } elseif ($userName == 'ILHAM CHOLID') {
                $jobPositions = [
                    'Sales Admin',
                    // 'SOH Region 1',
                    'Sales Engineer Reg 1',
                    // 'SOH Region 2',
                    'Sales Engineer Reg 2',
                ];
            } elseif ($userName == 'ANDIK TOTOK SISWOYO') {
                $jobPositions = [
                    // 'SOH Region 3',
                    'Sales Engineer Reg 3',
                    // 'SOH Region 4',
                    'Sales Engineer Reg 4',
                ];
            } elseif ($userName == 'ARYA RODJO PRASETYO') {
                $jobPositions = [
                    'Produksi HT Sec. Head',
                    'Foreman CT',
                    'Foreman QC',
                    'Leader HT',
                    'Operator CT',
                    'Admin HT & PPC',
                    'Operator MTN',
                    'Operator HT',
                    'Leader Cutting',
                    'Machining Custom Sec. Head',
                    'Leader MC',
                    'Operator Bubut',
                    'Operator Mc. Custom',
                    'MC Custom Staff',
                    'Operator Machining',
                    'Foreman Machining Custom',
                    'Foreman MC',
                ];
            } elseif ($userName == 'MUGI PRAMONO') {
                $jobPositions = [
                    // 'Produksi HT Sec. Head',
                    'Foreman CT',
                    'Foreman QC',
                    'Leader HT',
                    'Operator CT',
                    'Admin HT & PPC',
                    'Operator MTN',
                    'Operator HT',
                    'Leader Cutting',
                ];
            } elseif ($userName == 'RAGIL ISHA RAHMANTO') {
                $jobPositions = [
                    // 'Machining Custom Sec. Head',
                    'Leader MC',
                    'Operator Bubut',
                    'Operator Mc. Custom',
                    'MC Custom Staff',
                    'Operator Machining',
                    'Foreman Machining Custom',
                    'Foreman MC',
                ];
            } elseif ($userName == 'MARTINUS CAHYO RAHASTO') {
                $jobPositions = [
                    'HR & GA Section Head',
                    'HR & Legal Staff',
                    'HRGA & CSR Staff',
                    'Finance & Accounting Sec. Head',
                    'Finance Admin',
                    'Finance & Treasury Sec. Head',
                    'Invoicing Staff',
                    'AR Staff',
                    'PDCA, Inventory, Procurement & IT Sec. Head',
                    'Procurement Material Staff',
                    'PDCA & Procurement Non Material Staff',
                    'Procurement Administration',
                    'Inventory Staff',
                    'IT Staff',
                ];
            } elseif ($userName == 'ADHI PRASETYO') {
                $jobPositions = [
                    'Finance & Accounting Sec. Head',
                    // 'Accounting Staff & Kasir',
                    'Finance Admin',
                    'Finance & Treasury Sec. Head',
                    'Invoicing Staff',
                    'AR Staff',
                ];
            } elseif ($userName == 'JESSICA PAUNE') {
                $jobPositions = [
                    'PDCA, Inventory, Procurement & IT Sec. Head',
                    'PDCA & Procurement Non Material Staff',
                    'Procurement Material Staff',
                    'Procurement Administration',
                    'Inventory Staff',
                    'IT Staff',
                    'HR & GA Section Head',
                    'HR & Legal Staff',
                    'HRGA & CSR Staff',
                    'Finance & Accounting Sec. Head',
                    'Finance Admin',
                    'Finance & Treasury Sec. Head',
                    'Invoicing Staff',
                    'AR Staff',
                    'Produksi HT Sec. Head',
                    'Foreman CT',
                    'Foreman QC',
                    'Leader HT',
                    'Operator CT',
                    'Admin HT & PPC',
                    'Operator MTN',
                    'Operator HT',
                    'Leader Cutting',
                    'Machining Custom Sec. Head',
                    'Leader MC',
                    'Operator Bubut',
                    'Operator Mc. Custom',
                    'MC Custom Staff',
                    'Operator Machining',
                    'Foreman Machining Custom',
                    'Foreman MC',
                    'Sales Admin',
                    'SOH Region 1',
                    'Sales Engineer Reg 1',
                    'SOH Region 2',
                    'Sales Engineer Reg 2',
                    'Logistic Admin',
                    'Admin Cutting Sheet(ACS)',
                    'Delivery Staff',
                    'Feeder',
                    'Logistic Foreman',
                    'PPIC Staff',
                ];
            } elseif ($userName == 'SITI MARIA ULFA') {
                $jobPositions = [
                    'PDCA, Inventory, Procurement & IT Sec. Head',
                    'PDCA & Procurement Non Material Staff',
                    'Procurement Material Staff',
                    'Procurement Administration',
                    'Inventory Staff',
                    'IT Staff',
                    'HR & GA Section Head',
                    'HR & Legal Staff',
                    'HRGA & CSR Staff',
                    'Finance & Accounting Sec. Head',
                    'Finance Admin',
                    'Finance & Treasury Sec. Head',
                    'Invoicing Staff',
                    'AR Staff',
                    'Produksi HT Sec. Head',
                    'Foreman CT',
                    'Foreman QC',
                    'Leader HT',
                    'Operator CT',
                    'Admin HT & PPC',
                    'Operator MTN',
                    'Operator HT',
                    'Leader Cutting',
                    'Machining Custom Sec. Head',
                    'Leader MC',
                    'Operator Bubut',
                    'Operator Mc. Custom',
                    'MC Custom Staff',
                    'Operator Machining',
                    'Foreman Machining Custom',
                    'Foreman MC',
                    'Sales Admin',
                    'SOH Region 1',
                    'Sales Engineer Reg 1',
                    'SOH Region 2',
                    'Sales Engineer Reg 2',
                    'Logistic Admin',
                    'Admin Cutting Sheet(ACS)',
                    'Delivery Staff',
                    'Feeder',
                    'Logistic Foreman',
                    'PPIC Staff',
                ];
            } else {
                // Jika nama user tidak cocok dengan yang ditentukan, tampilkan semua data
                $technicalData = MstTc::with(['jobPosition'])->get()->unique('id_job_position');
                $softSkillsData = MstSoftSkill::with(['jobPosition'])->get()->unique('id_job_position');
                $additionalData = MstAdditionals::with(['jobPosition'])->get()->unique('id_job_position');

                return view('mst_tc.tc_index', compact('technicalData', 'softSkillsData', 'additionalData'));
            }

            // Filter data berdasarkan job positions yang telah ditentukan
            $technicalData = MstTc::with(['jobPosition'])
                ->whereHas('jobPosition', function ($query) use ($jobPositions) {
                    $query->whereIn('job_position', $jobPositions);
                })->get()->unique('id_job_position');

            $softSkillsData = MstSoftSkill::with(['jobPosition'])
                ->whereHas('jobPosition', function ($query) use ($jobPositions) {
                    $query->whereIn('job_position', $jobPositions);
                })->get()->unique('id_job_position');

            $additionalData = MstAdditionals::with(['jobPosition'])
                ->whereHas('jobPosition', function ($query) use ($jobPositions) {
                    $query->whereIn('job_position', $jobPositions);
                })->get()->unique('id_job_position');
        }

        return view('mst_tc.tc_index', compact('technicalData', 'softSkillsData', 'additionalData'));
    }

    public function createTC()
    {
        // Ambil semua data job position
        $uniquejobPositions = TcJobPosition::all();
        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga

        // Ambil role_id dan nama user yang sedang login
        $roleId = auth()->user()->role_id;
        $userName = auth()->user()->name;

        // Inisialisasi array job positions
        $jobPositions = [];

        // Cek apakah role_id adalah 1, 14, atau 15
        if (in_array($roleId, [1, 14, 15])) {
            // Jika ya, tampilkan semua data job_position
            $jobPositions = $uniquejobPositions->unique('job_position');
        } else {
            // Jika tidak, tentukan job_position berdasarkan nama user
            if ($userName == 'ABDUR RAHMAN AL FAAIZ') {
                $jobPositions = $uniquejobPositions->whereIn('job_position', [
                    'Logistic Admin',
                    'Admin Cutting Sheet(ACS)',
                    'Delivery Staff',
                    'Feeder',
                    'Logistic Foreman',
                    'PPIC Staff'
                ])->unique('job_position');
            } elseif ($userName == 'ILHAM CHOLID') {
                $jobPositions = $uniquejobPositions->whereIn('job_position', [
                    'Sales Admin',
                    'SOH Region 1',
                    'Sales Engineer Reg 1',
                    'SOH Region 2',
                    'Sales Engineer Reg 2',
                ])->unique('job_position')
                    ->sortBy('job_position');
            } elseif ($userName == 'ANDIK TOTOK SISWOYO') {
                $jobPositions = $uniquejobPositions->whereIn('job_position', [
                    'SOH Region 3',
                    'Sales Engineer Reg 3',
                    'SOH Region 4',
                    'Sales Engineer Reg 4',
                ])->unique('job_position');
            } elseif ($userName == 'MUGI PRAMONO') {
                $jobPositions = $uniquejobPositions->whereIn('job_position', [
                    // 'Produksi HT Sec. Head',
                    'Foreman CT',
                    'Foreman QC',
                    'Leader HT',
                    'Operator CT',
                    'Admin HT & PPC',
                    'Operator MTN',
                    'Operator HT',
                    'Leader Cutting',
                ])->unique('job_position')
                    ->sortBy('job_position');
            } elseif ($userName == 'RAGIL ISHA RAHMANTO') {
                $jobPositions = $uniquejobPositions->whereIn('job_position', [
                    // 'Machining Custom Sec. Head',
                    'Leader MC',
                    'Operator Bubut',
                    'Operator Mc. Custom',
                    'MC Custom Staff',
                    'Operator Machining',
                    'Foreman Machining Custom',
                    'Foreman MC',
                ])->unique('job_position');
            } elseif ($userName == 'MARTINUS CAHYO RAHASTO') {
                $jobPositions = $uniquejobPositions->whereIn('job_position', [
                    'HR & GA Section Head',
                    'HR & Legal Staff',
                    'HRGA & CSR Staff',
                    'Finance & Accounting Sec. Head',
                    'Finance & Treasury Sec. Head',
                    'PDCA, Inventory, Procurement & IT Sec. Head',
                ])->unique('job_position');
            } elseif ($userName == 'ADHI PRASETYO') {
                $jobPositions = $uniquejobPositions->whereIn('job_position', [
                    // 'Finance & Accounting Sec. Head',
                    'Finance Admin',
                    'Finance & Treasury Sec. Head',
                    'Invoicing Staff',
                    'AR Staff',
                ])->unique('job_position');
            } elseif ($userName == 'JESSICA PAUNE') {
                $jobPositions = $uniquejobPositions->whereIn('job_position', [
                    'PDCA, Inventory, Procurement & IT Sec. Head',
                    'PDCA & Procurement Non Material Staff',
                    'Procurement Material Staff',
                    'Procurement Administration',
                    'Inventory Staff',
                    'IT Staff',
                ])->unique('job_position');
            } else {
                // Jika nama user tidak cocok dengan yang ditentukan, tampilkan semua data job_position
                null;
            }
        }

        return view('mst_tc.tc_create', compact('jobPositions', 'dataTc1', 'dataTc2', 'dataTc3'));
    }

    public function summaryData()
    {
        // Ambil data unik berdasarkan job_position dan pilih ID terkecil (MIN) untuk setiap job_position
        $jobPositions = TcJobPosition::selectRaw('MIN(id) as id, job_position')
            ->groupBy('job_position')
            ->pluck('job_position', 'id');

        return view('mst_tc.summary_view', compact('jobPositions'));
    }


    public function fetchDetails(Request $request)
    {

        $idJobPosition = $request->input('id');

        // Fetch data from models with poinKategori
        $tcs = MstTc::where('id_job_position', $idJobPosition)
            ->with('poinKategori')
            ->get();

        $softSkills = MstSoftSkill::where('id_job_position', $idJobPosition)
            ->with('poinKategori')
            ->get();

        $additionals = MstAdditionals::where('id_job_position', $idJobPosition)
            ->with('poinKategori')
            ->get();

        return response()->json([
            'tcs' => $tcs,
            'softSkills' => $softSkills,
            'additionals' => $additionals,
        ]);
    }

    public function fetchDetails2($job_position)
    {
        // Cari id_job_position berdasarkan job_position
        $idJobPosition = TcJobPosition::where('job_position', $job_position)->value('id');

        if (!$idJobPosition) {
            return response()->json([
                'message' => 'Job Position tidak ditemukan',
            ], 404);
        }

        // Fetch data berdasarkan id_job_position
        $tcs = MstTc::where('id_job_position', $idJobPosition)
            ->with('poinKategori')
            ->get();

        $softSkills = MstSoftSkill::where('id_job_position', $idJobPosition)
            ->with('poinKategori')
            ->get();

        $additionals = MstAdditionals::where('id_job_position', $idJobPosition)
            ->with('poinKategori')
            ->get();

        // Return response dalam format JSON
        return response()->json([
            'tcs' => $tcs,
            'softSkills' => $softSkills,
            'additionals' => $additionals,
        ]);
    }


    public function storeTC(Request $request)
    {
        // Mengambil data JSON dari body request
        $data = $request->json()->all();

        // Log data yang masuk untuk debugging
        Log::info('Request data:', $data);

        // Mengatur aturan validasi
        $validator = Validator::make($data, [
            'tc.id_job_position' => 'required|exists:tc_job_positions,id',
            'tc.keterangan_tc.*' => 'required|string',
            'tc.deskripsi_tc.*' => 'required|string',
            'tc.nilai.*' => 'required|integer|between:1,4',
            'tc.id_poin_kategori.*' => 'required|exists:tc_poin_kategoris,id',
            'soft_skills.keterangan_sk.*' => 'required|string',
            'soft_skills.deskripsi_sk.*' => 'required|string',
            'soft_skills.nilai.*' => 'required|integer|between:1,4',
            'soft_skills.id_poin_kategori.*' => 'required|exists:tc_poin_kategoris,id',
            'additional.keterangan_ad.*' => 'nullable|string',
            'additional.deskripsi_ad.*' => 'nullable|string',
            'additional.nilai.*' => 'nullable|integer|between:1,4',
            'additional.id_poin_kategori.*' => 'nullable|exists:tc_poin_kategoris,id',
        ]);

        // Mengecek apakah validasi gagal
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Memulai transaksi untuk memastikan keutuhan data
        DB::beginTransaction();

        try {
            // Menyimpan data TC
            foreach ($data['tc']['keterangan_tc'] as $index => $keterangan_tc) {
                MstTc::create([
                    'id_job_position' => $data['tc']['id_job_position'],
                    'keterangan_tc' => $keterangan_tc,
                    'deskripsi_tc' => $data['tc']['deskripsi_tc'][$index],
                    'nilai' => $data['tc']['nilai'][$index],
                    'id_poin_kategori' => $data['tc']['id_poin_kategori'][$index], // Menyimpan id_poin_kategori
                ]);
            }

            // Menyimpan data Soft Skills
            foreach ($data['soft_skills']['keterangan_sk'] as $index => $keterangan_sk) {
                MstSoftSkill::create([
                    'id_job_position' => $data['tc']['id_job_position'],
                    'keterangan_sk' => $keterangan_sk,
                    'deskripsi_sk' => $data['soft_skills']['deskripsi_sk'][$index],
                    'nilai' => $data['soft_skills']['nilai'][$index],
                    'id_poin_kategori' => $data['soft_skills']['id_poin_kategori'][$index], // Menyimpan id_poin_kategori
                ]);
            }

            // Menyimpan data Additional
            foreach ($data['additional']['keterangan_ad'] as $index => $keterangan_ad) {
                MstAdditionals::create([
                    'id_job_position' => $data['tc']['id_job_position'],
                    'keterangan_ad' => $keterangan_ad,
                    'deskripsi_ad' => $data['additional']['deskripsi_ad'][$index],
                    'nilai' => $data['additional']['nilai'][$index],
                    'id_poin_kategori' => $data['additional']['id_poin_kategori'][$index], // Menyimpan id_poin_kategori
                ]);
            }

            // Commit transaksi jika semua berjalan lancar
            DB::commit();
            // Mengembalikan respons sukses
            return response()->json(['success' => 'Data berhasil disimpan'], 200);
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi error
            DB::rollback();
            Log::error('Error storing data:', ['error' => $e->getMessage()]);

            // Mengembalikan respons error
            return response()->json(['error' => 'Terjadi kesalahan saat menyimpan data'], 500);
        }
    }

    public function edit($id)
    {
        // Dapatkan data yang ingin diedit
        $tc = MstTc::with(['jobPosition'])->findOrFail($id);

        // Ambil id_job_position dari data yang sedang diedit
        $idJobPosition = $tc->id_job_position;

        // Ambil semua data dengan id_job_position yang sama
        $sameJobPositionData = MstTc::where('id_job_position', $idJobPosition)
            ->with(['jobPosition'])
            ->get();

        // Ambil semua job positions
        $jobPositions = TcJobPosition::all();

        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga

        // Kirimkan data ke view
        return view('mst_tc.edit_tc', compact('tc', 'jobPositions', 'sameJobPositionData', 'dataTc1', 'dataTc2', 'dataTc3'));
    }

    public function editSoftSKills($id)
    {
        // Dapatkan data yang ingin diedit
        $softSkill = MstSoftSkill::with(['jobPosition'])->findOrFail($id);

        // Ambil id_job_position dari data yang sedang diedit
        $idJobPosition = $softSkill->id_job_position;

        // Ambil semua data dengan id_job_position yang sama
        $sameJobPositionData = MstSoftSkill::where('id_job_position', $idJobPosition)
            ->with(['jobPosition'])
            ->get();

        // Ambil semua job positions
        $jobPositions = TcJobPosition::all();

        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga


        // Kirimkan data ke view
        return view('mst_tc.edit_sk', compact('softSkill', 'jobPositions', 'sameJobPositionData', 'dataTc1', 'dataTc2', 'dataTc3'));
    }

    public function editAdditional($id)
    {
        // Dapatkan data yang ingin diedit
        $additional = MstAdditionals::with(['jobPosition'])->findOrFail($id);

        // Ambil id_job_position dari data yang sedang diedit
        $idJobPosition = $additional->id_job_position;

        // Ambil semua data dengan id_job_position yang sama
        $sameJobPositionData = MstAdditionals::where('id_job_position', $idJobPosition)
            ->with(['jobPosition'])
            ->get();

        // Ambil semua job positions
        $jobPositions = TcJobPosition::all();

        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga

        // Kirimkan data ke view
        return view('mst_tc.edit_ad', compact('additional', 'jobPositions', 'sameJobPositionData', 'dataTc1', 'dataTc2', 'dataTc3'));
    }

    public function update(Request $request, $id)
    {
        Log::info('Received Update Data:', $request->all());

        // Validasi input
        $validatedData = $request->validate([
            'tc.id_job_position' => 'required|exists:tc_job_positions,id',
            'tc.keterangan_tc.*' => 'required|string',
            'tc.deskripsi_tc.*' => 'required|string',
            'tc.id_poin_kategori.*' => 'nullable|integer|exists:tc_poin_kategoris,id',
            'tc.nilai.*' => 'required|integer|between:1,4',
        ]);

        try {
            // Dapatkan data yang ingin diedit
            $tc = MstTc::findOrFail($id);

            // Ambil id_job_position dari data yang sedang diedit
            $idJobPosition = $validatedData['tc']['id_job_position'];

            // Ambil semua data dengan id_job_position yang sama
            $sameJobPositionData = MstTc::where('id_job_position', $idJobPosition)->get();

            foreach ($validatedData['tc']['keterangan_tc'] as $index => $keteranganTc) {
                $nilai = $validatedData['tc']['nilai'][$index] ?? null;
                $deskripsiTc = $validatedData['tc']['deskripsi_tc'][$index] ?? null;
                $idPoinKategori = $validatedData['tc']['id_poin_kategori'][$index] ?? null;

                Log::info('Processing data with index:', [
                    'index' => $index,
                    'keterangan_tc' => $keteranganTc,
                    'nilai' => $nilai,
                    'deskripsi_tc' => $deskripsiTc,
                    'id_poin_kategori' => $idPoinKategori
                ]);

                if (isset($sameJobPositionData[$index])) {
                    // Update data jika sudah ada
                    $data = $sameJobPositionData[$index];
                    $data->keterangan_tc = $keteranganTc;
                    $data->nilai = $nilai;
                    $data->deskripsi_tc = $deskripsiTc; // Update deskripsi_tc
                    $data->id_poin_kategori = $idPoinKategori; // Update id_poin_kategori
                    $data->save();
                } else {
                    // Buat record baru jika belum ada
                    MstTc::create([
                        'id_job_position' => $idJobPosition,
                        'keterangan_tc' => $keteranganTc,
                        'nilai' => $nilai,
                        'deskripsi_tc' => $deskripsiTc, // Save deskripsi_tc
                        'id_poin_kategori' => $idPoinKategori, // Save id_poin_kategori
                    ]);
                }
            }

            return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui']);
        } catch (\Exception $e) {
            Log::error('Update Error:', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSoftSkills(Request $request, $id)
    {
        Log::info('Received Update Data:', $request->all());

        // Validasi input
        $validatedData = $request->validate([
            'sk.id_job_position' => 'required|exists:tc_job_positions,id',
            'sk.keterangan_sk.*' => 'required|string',
            'sk.nilai.*' => 'required|integer|between:1,4',
            'sk.deskripsi_sk.*' => 'required|string', // Deskripsi sk, bukan deskripsi_poin
            'sk.id_poin_kategori.*' => 'nullable|integer|exists:tc_poin_kategoris,id', // Validasi id_poin_kategori jika diperlukan
        ]);

        try {
            // Dapatkan data yang ingin diedit
            $softSkill = MstSoftSkill::findOrFail($id);

            // Ambil id_job_position dari data yang sedang diedit
            $idJobPosition = $validatedData['sk']['id_job_position'];

            // Ambil semua data dengan id_job_position yang sama
            $sameJobPositionData = MstSoftSkill::where('id_job_position', $idJobPosition)->get();

            foreach ($validatedData['sk']['keterangan_sk'] as $index => $keteranganSk) {
                $nilai = $validatedData['sk']['nilai'][$index] ?? null;
                $deskripsiSk = $validatedData['sk']['deskripsi_sk'][$index] ?? null;
                $idPoinKategori = $validatedData['sk']['id_poin_kategori'][$index] ?? null;

                Log::info('Processing data with index:', [
                    'index' => $index,
                    'keterangan_sk' => $keteranganSk,
                    'nilai' => $nilai,
                    'deskripsi_sk' => $deskripsiSk,
                    'id_poin_kategori' => $idPoinKategori
                ]);

                if (isset($sameJobPositionData[$index])) {
                    // Update data jika sudah ada
                    $data = $sameJobPositionData[$index];
                    $data->keterangan_sk = $keteranganSk;
                    $data->nilai = $nilai;
                    $data->deskripsi_sk = $deskripsiSk; // Update deskripsi_sk
                    $data->id_poin_kategori = $idPoinKategori; // Update id_poin_kategori
                    $data->save();
                } else {
                    // Buat record baru jika belum ada
                    MstSoftSkill::create([
                        'id_job_position' => $idJobPosition,
                        'keterangan_sk' => $keteranganSk,
                        'nilai' => $nilai,
                        'deskripsi_sk' => $deskripsiSk, // Save deskripsi_sk
                        'id_poin_kategori' => $idPoinKategori, // Save id_poin_kategori
                    ]);
                }
            }

            return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui']);
        } catch (\Exception $e) {
            Log::error('Update Error:', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateAdditionals(Request $request, $id)
    {
        Log::info('Received Update Data:', $request->all());

        // Validasi input
        $validatedData = $request->validate([
            'additional.id_job_position' => 'required|exists:tc_job_positions,id',
            'additional.keterangan_ad.*' => 'required|string',
            'additional.deskripsi_ad.*' => 'required|string',
            'additional.id_poin_kategori.*' => 'required|integer|between:1,3',
            'additional.nilai.*' => 'required|integer|between:1,4',
        ]);

        try {
            // Dapatkan data yang ingin diedit
            $additional = MstAdditionals::findOrFail($id);

            // Ambil id_job_position dari data yang sedang diedit
            $idJobPosition = $validatedData['additional']['id_job_position'];

            // Ambil semua data dengan id_job_position yang sama
            $sameJobPositionData = MstAdditionals::where('id_job_position', $idJobPosition)->get();

            foreach ($validatedData['additional']['keterangan_ad'] as $index => $keteranganAdditionals) {
                $nilai = $validatedData['additional']['nilai'][$index] ?? null;
                $deskripsi = $validatedData['additional']['deskripsi_ad'][$index] ?? null;
                $idPoinKategori = $validatedData['additional']['id_poin_kategori'][$index] ?? null;

                Log::info('Processing data with index:', ['index' => $index, 'keterangan_ad' => $keteranganAdditionals, 'nilai' => $nilai, 'deskripsi_ad' => $deskripsi, 'id_poin_kategori' => $idPoinKategori]);

                if (isset($sameJobPositionData[$index])) {
                    // Update data jika sudah ada
                    $data = $sameJobPositionData[$index];
                    $data->keterangan_ad = $keteranganAdditionals;
                    $data->deskripsi_ad = $deskripsi;
                    $data->nilai = $nilai;
                    $data->id_poin_kategori = $idPoinKategori;
                    $data->save();
                } else {
                    // Buat record baru jika belum ada
                    MstAdditionals::create([
                        'id_job_position' => $idJobPosition,
                        'keterangan_ad' => $keteranganAdditionals,
                        'deskripsi_ad' => $deskripsi,
                        'nilai' => $nilai,
                        'id_poin_kategori' => $idPoinKategori,
                    ]);
                }
            }

            return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui']);
        } catch (\Exception $e) {
            Log::error('Update Error:', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteTcRow($id)
    {
        try {
            $tcRow = MstTc::findOrFail($id);
            $tcRow->delete();

            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting TC row:', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat menghapus data.'], 500);
        }
    }

    public function deleteSkRow($id)
    {
        try {
            $skRow = MstSoftSkill::findOrFail($id);
            $skRow->delete();

            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting TC row:', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat menghapus data.'], 500);
        }
    }

    public function deleteAdRow($id)
    {
        try {
            $adRow = MstAdditionals::findOrFail($id);
            $adRow->delete();

            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting TC row:', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat menghapus data.'], 500);
        }
    }

    public function fetchEmployeesByJobPosition(Request $request)
    {
        // Mengambil 'id' job position yang dipilih dari request
        $jobPositionId = $request->input('id'); // ID job position

        // Pastikan bahwa ID valid dan tidak null
        if (!$jobPositionId) {
            return response()->json([
                'success' => false,
                'message' => 'Job position not selected.'
            ]);
        }

        // Ambil data job position berdasarkan id
        $jobPosition = DB::table('tc_job_positions')
            ->where('id', $jobPositionId)
            ->first();

        if (!$jobPosition) {
            return response()->json([
                'success' => false,
                'message' => 'Job position not found.'
            ]);
        }

        // Cari semua users berdasarkan job_position yang sama
        $employees = DB::table('tc_job_positions')
            ->join('users', 'tc_job_positions.id_user', '=', 'users.id')
            ->where('tc_job_positions.job_position', $jobPosition->job_position) // Filter berdasarkan job_position
            ->select('users.name', 'tc_job_positions.job_position')
            ->get();

        if ($employees->isEmpty()) {
            // Jika tidak ada data yang ditemukan
            return response()->json([
                'success' => false,
                'message' => 'No employees found for this job position.'
            ]);
        }

        // Return data dalam format JSON
        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }
}
