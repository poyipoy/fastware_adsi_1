<?php

namespace App\Http\Controllers;

use App\Models\MstAdditionals;
use App\Models\MstSoftSkill;
use App\Models\MstTc;
use App\Models\PoinKategori;
use App\Models\TcJobPosition;
use App\Models\UserJobAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TcController extends Controller
{
    private const FULL_ACCESS_USERS = [
        'ADMINSTRATOR',
        'JESSICA PAUNE',
        'SITI MARIA ULFA',
    ];

    /**
     * Ambil daftar job_position yang boleh dilihat user saat ini.
     * Return null jika full access (tampilkan semua).
     */
    private function getAllowedJobPositions(): ?array
    {
        $userName = auth()->user()->name ?? '';

        if (in_array($userName, self::FULL_ACCESS_USERS)) {
            return null;
        }

        $userId = auth()->id();
        $roleId = auth()->user()->role_id ?? null;

        $positions = UserJobAccess::getPositionsForUser($userId);

        if ($roleId) {
            $rolePositions = UserJobAccess::getPositionsForRole($roleId);
            $positions = array_unique(array_merge($positions, $rolePositions));
        }

        return $positions;
    }

    public function tcShow()
    {
        $allowedPositions = $this->getAllowedJobPositions();

        $technicalData = MstTc::with(['jobPosition'])->get()
            ->unique(fn($item) => optional($item->jobPosition)->job_position);
        $softSkillsData = MstSoftSkill::with(['jobPosition'])->get()
            ->unique(fn($item) => optional($item->jobPosition)->job_position);
        $additionalData = MstAdditionals::with(['jobPosition'])->get()
            ->unique(fn($item) => optional($item->jobPosition)->job_position);

        if ($allowedPositions !== null) {
            $technicalData = $technicalData->filter(
                fn($item) => in_array(optional($item->jobPosition)->job_position, $allowedPositions)
            );
            $softSkillsData = $softSkillsData->filter(
                fn($item) => in_array(optional($item->jobPosition)->job_position, $allowedPositions)
            );
            $additionalData = $additionalData->filter(
                fn($item) => in_array(optional($item->jobPosition)->job_position, $allowedPositions)
            );
        }

        return view('mst_tc.tc_index', compact('technicalData', 'softSkillsData', 'additionalData'));
    }

    public function createTC()
    {
        $uniquejobPositions = TcJobPosition::all();
        $dataTc1 = PoinKategori::find(1);
        $dataTc2 = PoinKategori::find(2);
        $dataTc3 = PoinKategori::find(3);

        $jobPositions = $uniquejobPositions->unique('job_position')->sortBy('job_position');

        return view('mst_tc.tc_create', compact('jobPositions', 'dataTc1', 'dataTc2', 'dataTc3'));
    }

    public function summaryData()
    {
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
        $data = $request->json()->all();

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
            // Ambil job_position name dari ID yang dipilih, lalu cari SEMUA tc_job_positions dengan nama yang sama
            $selectedJobPosition = TcJobPosition::findOrFail($data['tc']['id_job_position']);
            $allJobPositionIds = TcJobPosition::where('job_position', $selectedJobPosition->job_position)
                ->pluck('id')
                ->toArray();

            // Menyimpan data TC untuk SEMUA job position records
            foreach ($allJobPositionIds as $jpId) {
                foreach ($data['tc']['keterangan_tc'] as $index => $keterangan_tc) {
                    MstTc::create([
                        'id_job_position' => $jpId,
                        'keterangan_tc' => $keterangan_tc,
                        'deskripsi_tc' => $data['tc']['deskripsi_tc'][$index],
                        'nilai' => $data['tc']['nilai'][$index],
                        'id_poin_kategori' => $data['tc']['id_poin_kategori'][$index],
                    ]);
                }
            }

            // Menyimpan data Soft Skills untuk SEMUA job position records
            foreach ($allJobPositionIds as $jpId) {
                foreach ($data['soft_skills']['keterangan_sk'] as $index => $keterangan_sk) {
                    MstSoftSkill::create([
                        'id_job_position' => $jpId,
                        'keterangan_sk' => $keterangan_sk,
                        'deskripsi_sk' => $data['soft_skills']['deskripsi_sk'][$index],
                        'nilai' => $data['soft_skills']['nilai'][$index],
                        'id_poin_kategori' => $data['soft_skills']['id_poin_kategori'][$index],
                    ]);
                }
            }

            // Menyimpan data Additional untuk SEMUA job position records
            foreach ($allJobPositionIds as $jpId) {
                foreach ($data['additional']['keterangan_ad'] as $index => $keterangan_ad) {
                    MstAdditionals::create([
                        'id_job_position' => $jpId,
                        'keterangan_ad' => $keterangan_ad,
                        'deskripsi_ad' => $data['additional']['deskripsi_ad'][$index],
                        'nilai' => $data['additional']['nilai'][$index],
                        'id_poin_kategori' => $data['additional']['id_poin_kategori'][$index],
                    ]);
                }
            }

            // Commit transaksi jika semua berjalan lancar
            DB::commit();

            // Auto-add job position ke UserJobAccess user saat ini agar muncul di listing
            UserJobAccess::firstOrCreate(
                ['user_id' => auth()->id(), 'job_position' => $selectedJobPosition->job_position],
                ['role_id' => auth()->user()->role_id ?? null]
            );

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
        $tc = MstTc::with(['jobPosition'])->findOrFail($id);

        $idJobPosition = $tc->id_job_position;

        $sameJobPositionData = MstTc::where('id_job_position', $idJobPosition)
            ->with(['jobPosition'])
            ->get();

        $jobPositions = TcJobPosition::all();

        $dataTc1 = PoinKategori::find(1);
        $dataTc2 = PoinKategori::find(2);
        $dataTc3 = PoinKategori::find(3);

        return view('mst_tc.edit_tc', compact('tc', 'jobPositions', 'sameJobPositionData', 'dataTc1', 'dataTc2', 'dataTc3'));
    }

    public function editSoftSKills($id)
    {
        $softSkill = MstSoftSkill::with(['jobPosition'])->findOrFail($id);

        $idJobPosition = $softSkill->id_job_position;

        $sameJobPositionData = MstSoftSkill::where('id_job_position', $idJobPosition)
            ->with(['jobPosition'])
            ->get();

        $jobPositions = TcJobPosition::all();

        $dataTc1 = PoinKategori::find(1);
        $dataTc2 = PoinKategori::find(2);
        $dataTc3 = PoinKategori::find(3);

        return view('mst_tc.edit_sk', compact('softSkill', 'jobPositions', 'sameJobPositionData', 'dataTc1', 'dataTc2', 'dataTc3'));
    }

    public function editAdditional($id)
    {
        $additional = MstAdditionals::with(['jobPosition'])->findOrFail($id);

        $idJobPosition = $additional->id_job_position;

        $sameJobPositionData = MstAdditionals::where('id_job_position', $idJobPosition)
            ->with(['jobPosition'])
            ->get();

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

            // Ambil job_position name untuk sync ke semua sibling records
            $jobPositionRecord = TcJobPosition::findOrFail($idJobPosition);
            $allSiblingIds = TcJobPosition::where('job_position', $jobPositionRecord->job_position)
                ->pluck('id')
                ->toArray();

            // Update/create untuk SEMUA sibling job position records
            foreach ($allSiblingIds as $jpId) {
                $sameJobPositionData = MstTc::where('id_job_position', $jpId)->get();

                foreach ($validatedData['tc']['keterangan_tc'] as $index => $keteranganTc) {
                    $nilai = $validatedData['tc']['nilai'][$index] ?? null;
                    $deskripsiTc = $validatedData['tc']['deskripsi_tc'][$index] ?? null;
                    $idPoinKategori = $validatedData['tc']['id_poin_kategori'][$index] ?? null;

                    if (isset($sameJobPositionData[$index])) {
                        $data = $sameJobPositionData[$index];
                        $data->keterangan_tc = $keteranganTc;
                        $data->nilai = $nilai;
                        $data->deskripsi_tc = $deskripsiTc;
                        $data->id_poin_kategori = $idPoinKategori;
                        $data->save();
                    } else {
                        MstTc::create([
                            'id_job_position' => $jpId,
                            'keterangan_tc' => $keteranganTc,
                            'nilai' => $nilai,
                            'deskripsi_tc' => $deskripsiTc,
                            'id_poin_kategori' => $idPoinKategori,
                        ]);
                    }
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

            // Ambil job_position name untuk sync ke semua sibling records
            $jobPositionRecord = TcJobPosition::findOrFail($idJobPosition);
            $allSiblingIds = TcJobPosition::where('job_position', $jobPositionRecord->job_position)
                ->pluck('id')
                ->toArray();

            // Update/create untuk SEMUA sibling job position records
            foreach ($allSiblingIds as $jpId) {
                $sameJobPositionData = MstSoftSkill::where('id_job_position', $jpId)->get();

                foreach ($validatedData['sk']['keterangan_sk'] as $index => $keteranganSk) {
                    $nilai = $validatedData['sk']['nilai'][$index] ?? null;
                    $deskripsiSk = $validatedData['sk']['deskripsi_sk'][$index] ?? null;
                    $idPoinKategori = $validatedData['sk']['id_poin_kategori'][$index] ?? null;

                    if (isset($sameJobPositionData[$index])) {
                        $data = $sameJobPositionData[$index];
                        $data->keterangan_sk = $keteranganSk;
                        $data->nilai = $nilai;
                        $data->deskripsi_sk = $deskripsiSk;
                        $data->id_poin_kategori = $idPoinKategori;
                        $data->save();
                    } else {
                        MstSoftSkill::create([
                            'id_job_position' => $jpId,
                            'keterangan_sk' => $keteranganSk,
                            'nilai' => $nilai,
                            'deskripsi_sk' => $deskripsiSk,
                            'id_poin_kategori' => $idPoinKategori,
                        ]);
                    }
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

            // Ambil job_position name untuk sync ke semua sibling records
            $jobPositionRecord = TcJobPosition::findOrFail($idJobPosition);
            $allSiblingIds = TcJobPosition::where('job_position', $jobPositionRecord->job_position)
                ->pluck('id')
                ->toArray();

            // Update/create untuk SEMUA sibling job position records
            foreach ($allSiblingIds as $jpId) {
                $sameJobPositionData = MstAdditionals::where('id_job_position', $jpId)->get();

                foreach ($validatedData['additional']['keterangan_ad'] as $index => $keteranganAdditionals) {
                    $nilai = $validatedData['additional']['nilai'][$index] ?? null;
                    $deskripsi = $validatedData['additional']['deskripsi_ad'][$index] ?? null;
                    $idPoinKategori = $validatedData['additional']['id_poin_kategori'][$index] ?? null;

                    if (isset($sameJobPositionData[$index])) {
                        $data = $sameJobPositionData[$index];
                        $data->keterangan_ad = $keteranganAdditionals;
                        $data->deskripsi_ad = $deskripsi;
                        $data->nilai = $nilai;
                        $data->id_poin_kategori = $idPoinKategori;
                        $data->save();
                    } else {
                        MstAdditionals::create([
                            'id_job_position' => $jpId,
                            'keterangan_ad' => $keteranganAdditionals,
                            'deskripsi_ad' => $deskripsi,
                            'nilai' => $nilai,
                            'id_poin_kategori' => $idPoinKategori,
                        ]);
                    }
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
