<?php

namespace App\Http\Controllers;

use App\Models\MstAdditionals;
use App\Models\MstSoftSkill;
use App\Models\MstTc;
use App\Models\PoinKategori;
use App\Models\MstJobPosition;
use App\Models\UserJobAccess;
use App\Services\HR\JobPositionAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TcController extends Controller
{
    public function __construct(private JobPositionAccessService $jobPositionAccess)
    {
    }

    private function getAllowedJobPositionKeys()
    {
        $user = auth()->user();

        if (!$user || $this->jobPositionAccess->hasFullAccess($user)) {
            return null;
        }

        return $this->jobPositionAccess->getAccessibleJobPositionNames($user)
            ->mapWithKeys(fn($jobPosition) => [$this->normalizeJobPosition($jobPosition) => true]);
    }

    private function normalizeJobPosition(?string $jobPosition): string
    {
        return mb_strtoupper(trim((string) $jobPosition));
    }

    private function canAccessJobPosition(MstJobPosition $jobPosition): bool
    {
        $user = auth()->user();

        return $user && $this->jobPositionAccess->canAccessJobPosition($user, $jobPosition->position_name);
    }

    private function inaccessibleJobPositionResponse()
    {
        return response()->json(['error' => 'Anda tidak memiliki akses untuk job position ini.'], 403);
    }

    public function tcShow(Request $request)
    {
        $user = auth()->user();
        $query = MstJobPosition::with(['department', 'section']);

        $selectedDept = $request->query('department');
        $selectedSection = $request->query('section');
        $selectedJobPosition = $request->query('job_position');

        // Jika user bukan full access, filter secara ketat berdasarkan scope approval
        if (!$this->jobPositionAccess->hasFullAccess($user)) {
            $scope = $this->jobPositionAccess->getUserApprovalScope($user);
            $query->where(function($q) use ($scope) {
                $q->whereIn('section_id', $scope['section_ids'])
                  ->orWhereIn('department_id', $scope['dept_ids'])
                  ->orWhereIn('department_id', $scope['div_dept_ids']);
            });
        }

        if ($selectedDept !== null && $selectedDept !== '') {
            $query->where('department_id', $selectedDept);
        }

        if ($selectedSection !== null && $selectedSection !== '') {
            $query->where('section_id', $selectedSection);
        }

        if ($selectedJobPosition !== null && $selectedJobPosition !== '') {
            $query->where('id', $selectedJobPosition);
        }

        // Get all unique job positions based on filters
        $jobPositions = $query->get()->map(function($jp) {
            $jp->job_position = $jp->position_name;
            return $jp;
        });

        // Fetch counts of each competency type for the master ID
        foreach ($jobPositions as $jp) {
            $jp->tc_count = MstTc::where('id_job_position', $jp->id)->count();
            $jp->sk_count = MstSoftSkill::where('id_job_position', $jp->id)->count();
            $jp->ad_count = MstAdditionals::where('id_job_position', $jp->id)->count();
        }

        // Filter to only show job positions that have at least one competency set
        $jobPositions = $jobPositions->filter(
            fn($jp) => ($jp->tc_count + $jp->sk_count + $jp->ad_count) > 0
        );

        $departments = \App\Models\MstDepartment::orderBy('name')->get();
        $sections = \App\Models\MstSection::orderBy('name')->get();

        $jobPositionOptionsQuery = \App\Models\MstJobPosition::orderBy('position_name');
        if (!$this->jobPositionAccess->hasFullAccess($user)) {
            $scope = $this->jobPositionAccess->getUserApprovalScope($user);
            $jobPositionOptionsQuery->where(function($q) use ($scope) {
                $q->whereIn('section_id', $scope['section_ids'])
                  ->orWhereIn('department_id', $scope['dept_ids'])
                  ->orWhereIn('department_id', $scope['div_dept_ids']);
            });
        }
        $jobPositionOptions = $jobPositionOptionsQuery->get();
        
        return view('mst_tc.tc_index', compact('jobPositions', 'departments', 'sections', 'jobPositionOptions', 'selectedDept', 'selectedSection', 'selectedJobPosition'));
    }

    public function createTC()
    {
        $user = auth()->user();
        $jobPositions = $user ? $this->jobPositionAccess->getAccessibleJobPositionOptions($user) : collect();
        $dataTc1 = PoinKategori::find(1) ?? (object) [
            'judul_keterangan' => 'Technical Competency',
            'deskripsi_1' => '',
            'deskripsi_2' => '',
            'deskripsi_3' => '',
            'deskripsi_4' => '',
        ];
        $dataTc2 = PoinKategori::find(2) ?? (object) [
            'judul_keterangan' => 'Soft Skills',
            'deskripsi_1' => '',
            'deskripsi_2' => '',
            'deskripsi_3' => '',
            'deskripsi_4' => '',
        ];
        $dataTc3 = PoinKategori::find(3) ?? (object) [
            'judul_keterangan' => 'Additional',
            'deskripsi_1' => '',
            'deskripsi_2' => '',
            'deskripsi_3' => '',
            'deskripsi_4' => '',
        ];

        return view('mst_tc.tc_create', compact('jobPositions', 'dataTc1', 'dataTc2', 'dataTc3'));
    }

    public function summaryData()
    {
        $jobPositions = MstJobPosition::pluck('position_name', 'id');

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
        // Jika parameter yang dikirim berupa ID (angka), gunakan langsung
        if (is_numeric($job_position)) {
            $idJobPosition = (int) $job_position;
        } else {
            // Cari id_job_position berdasarkan nama job_position menggunakan Master ID (min ID)
            $idJobPosition = MstJobPosition::where('position_name', $job_position)->min('id');
        }

        if (!$idJobPosition || !MstJobPosition::where('id', $idJobPosition)->exists()) {
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

        $validator = Validator::make($data, [
            'tc.id_job_position' => 'required|exists:mst_job_positions,id',
            'tc.sub_kategori.*' => 'nullable|string',
            'tc.keterangan_tc.*' => 'required|string',
            'tc.deskripsi_tc.*' => 'nullable|string',
            'tc.nilai.*' => 'required|integer|between:1,4',
            'tc.id_poin_kategori.*' => 'required|exists:tc_poin_kategoris,id',
            
            'soft_skills.keterangan_sk.*' => 'required|string',
            'soft_skills.deskripsi_sk.*' => 'nullable|string',
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

        $selectedJobPosition = MstJobPosition::findOrFail($data['tc']['id_job_position']);

        if (!$this->canAccessJobPosition($selectedJobPosition)) {
            return $this->inaccessibleJobPositionResponse();
        }

        // Memulai transaksi untuk memastikan keutuhan data
        DB::beginTransaction();

        try {
            $masterJpId = $selectedJobPosition->id;
            $allJobPositionIds = [$masterJpId];

            // Menyimpan data TC untuk SEMUA job position records
            foreach ($allJobPositionIds as $jpId) {
                foreach ($data['tc']['keterangan_tc'] as $index => $keterangan_tc) {
                    MstTc::create([
                        'id_job_position' => $jpId,
                        'sub_kategori' => $data['tc']['sub_kategori'][$index] ?? null,
                        'keterangan_tc' => $keterangan_tc,
                        'deskripsi_tc' => $data['tc']['deskripsi_tc'][$index] ?? null,
                        'nilai' => $data['tc']['nilai'][$index] ?? null,
                        'id_poin_kategori' => $data['tc']['id_poin_kategori'][$index],
                    ]);
                }
            }

            // Menyimpan data Soft Skills untuk SEMUA job position records
            foreach ($allJobPositionIds as $jpId) {
                if (!empty($data['soft_skills']['keterangan_sk'])) {
                    foreach ($data['soft_skills']['keterangan_sk'] as $index => $keterangan_sk) {
                        MstSoftSkill::create([
                            'id_job_position' => $jpId,
                            'keterangan_sk' => $keterangan_sk,
                            'deskripsi_sk' => $data['soft_skills']['deskripsi_sk'][$index] ?? null,
                            'nilai' => $data['soft_skills']['nilai'][$index] ?? null,
                            'id_poin_kategori' => $data['soft_skills']['id_poin_kategori'][$index],
                        ]);
                    }
                }
            }

            // Menyimpan data Additional (jika ada) untuk SEMUA job position records
            if (isset($data['additional']['keterangan_ad'])) {
                foreach ($allJobPositionIds as $jpId) {
                    foreach ($data['additional']['keterangan_ad'] as $index => $keterangan_ad) {
                        if (!empty($keterangan_ad)) {
                            MstAdditionals::create([
                                'id_job_position' => $jpId,
                                'keterangan_ad' => $keterangan_ad,
                                'deskripsi_ad' => $data['additional']['deskripsi_ad'][$index] ?? null,
                                'nilai' => $data['additional']['nilai'][$index] ?? null,
                                'id_poin_kategori' => $data['additional']['id_poin_kategori'][$index] ?? null,
                            ]);
                        }
                    }
                }
            }

            // Commit transaksi jika semua berjalan lancar
            DB::commit();

            // DISABLED - LEGACY OVERRIDE ACCESS
            // UserJobAccess::firstOrCreate(
            //     ['user_id' => auth()->id(), 'job_position' => $selectedJobPosition->position_name],
            //     ['role_id' => auth()->user()->role_id ?? null]
            // );

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

        $jobPositions = MstJobPosition::all();

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

        $jobPositions = MstJobPosition::all();

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

        $jobPositions = MstJobPosition::all();

        $dataTc1 = PoinKategori::find(1);  // Misalnya TcModel adalah model untuk tabel pertama
        $dataTc2 = PoinKategori::find(2);  // Misalnya TcModel adalah model untuk tabel kedua
        $dataTc3 = PoinKategori::find(3);  // Misalnya TcModel adalah model untuk tabel ketiga

        // Kirimkan data ke view
        return view('mst_tc.edit_ad', compact('additional', 'jobPositions', 'sameJobPositionData', 'dataTc1', 'dataTc2', 'dataTc3'));
    }

    public function editAll($id)
    {
        $jobPosition = MstJobPosition::findOrFail($id);

        if (!$this->canAccessJobPosition($jobPosition)) {
            abort(403, 'Anda tidak memiliki akses untuk job position ini.');
        }

        $masterJpId = $jobPosition->id;

        $tcs = MstTc::where('id_job_position', $masterJpId)
            ->with(['jobPosition', 'poinKategori'])
            ->get();

        $softSkills = MstSoftSkill::where('id_job_position', $masterJpId)
            ->with(['jobPosition', 'poinKategori'])
            ->get();

        $additionals = MstAdditionals::where('id_job_position', $masterJpId)
            ->with(['jobPosition', 'poinKategori'])
            ->get();

        $jobPositions = MstJobPosition::all()->map(function($jp) {
            $jp->job_position = $jp->position_name;
            return $jp;
        });

        $dataTc1 = PoinKategori::find(1);
        $dataTc2 = PoinKategori::find(2);
        $dataTc3 = PoinKategori::find(3);

        return view('mst_tc.edit_all', compact('jobPosition', 'masterJpId', 'tcs', 'softSkills', 'additionals', 'jobPositions', 'dataTc1', 'dataTc2', 'dataTc3'));
    }

    public function updateAll(Request $request, $id)
    {
        Log::info('Received Update All Data:', $request->all());

        // Validate payload containing tc, sk, ad items
        $validatedData = $request->validate([
            'tc.id_job_position' => 'required|exists:mst_job_positions,id',
            'tc.keterangan_tc.*' => 'required|string',
            'tc.deskripsi_tc.*' => 'nullable|string',
            'tc.id_poin_kategori.*' => 'nullable|integer|exists:tc_poin_kategoris,id',
            'tc.nilai.*' => 'required|integer|between:1,4',

            'sk.keterangan_sk.*' => 'required|string',
            'sk.deskripsi_sk.*' => 'nullable|string',
            'sk.id_poin_kategori.*' => 'nullable|integer|exists:tc_poin_kategoris,id',
            'sk.nilai.*' => 'required|integer|between:1,4',

            'ad.keterangan_ad.*' => 'nullable|string',
            'ad.deskripsi_ad.*' => 'nullable|string',
            'ad.id_poin_kategori.*' => 'nullable|integer|exists:tc_poin_kategoris,id',
            'ad.nilai.*' => 'nullable|integer|between:1,4',
        ]);

        try {
            // Cek akses user terhadap posisi lama (sebelum diubah)
            $oldJobPositionRecord = MstJobPosition::findOrFail($id);
            if (!$this->canAccessJobPosition($oldJobPositionRecord)) {
                return $this->inaccessibleJobPositionResponse();
            }

            // Dapatkan Master ID dari posisi lama
            $oldMasterJpId = $oldJobPositionRecord->id;

            // Dapatkan Master ID dari posisi baru (yang dipilih di dropdown)
            $newJpId = $validatedData['tc']['id_job_position'];
            $newJobPositionRecord = MstJobPosition::findOrFail($newJpId);

            // Cek akses user terhadap posisi baru
            if (!$this->canAccessJobPosition($newJobPositionRecord)) {
                return $this->inaccessibleJobPositionResponse();
            }

            $masterJpId = $newJobPositionRecord->id;

            DB::beginTransaction();

            // 1. Process Technical Competencies
            $existingTcs = MstTc::where('id_job_position', $oldMasterJpId)->get();
            if (isset($validatedData['tc']['keterangan_tc'])) {
                foreach ($validatedData['tc']['keterangan_tc'] as $index => $keteranganTc) {
                    $nilai = $validatedData['tc']['nilai'][$index] ?? null;
                    $idPoinKategori = $validatedData['tc']['id_poin_kategori'][$index] ?? null;

                    if (isset($existingTcs[$index])) {
                        $data = $existingTcs[$index];
                        $data->id_job_position = $masterJpId;
                        $data->keterangan_tc = $keteranganTc;
                        $data->deskripsi_tc = $validatedData['tc']['deskripsi_tc'][$index] ?? null;
                        $data->nilai = $nilai;
                        $data->id_poin_kategori = $idPoinKategori;
                        $data->save();
                    } else {
                        MstTc::create([
                            'id_job_position' => $masterJpId,
                            'keterangan_tc' => $keteranganTc,
                            'deskripsi_tc' => $validatedData['tc']['deskripsi_tc'][$index] ?? null,
                            'nilai' => $nilai,
                            'id_poin_kategori' => $idPoinKategori,
                        ]);
                    }
                }
            }

            // 2. Process Soft Skills
            $existingSks = MstSoftSkill::where('id_job_position', $oldMasterJpId)->get();
            if (isset($validatedData['sk']['keterangan_sk'])) {
                foreach ($validatedData['sk']['keterangan_sk'] as $index => $keteranganSk) {
                    $nilai = $validatedData['sk']['nilai'][$index] ?? null;
                    $idPoinKategori = $validatedData['sk']['id_poin_kategori'][$index] ?? null;

                    if (isset($existingSks[$index])) {
                        $data = $existingSks[$index];
                        $data->id_job_position = $masterJpId;
                        $data->keterangan_sk = $keteranganSk;
                        $data->deskripsi_sk = $validatedData['sk']['deskripsi_sk'][$index] ?? null;
                        $data->nilai = $nilai;
                        $data->id_poin_kategori = $idPoinKategori;
                        $data->save();
                    } else {
                        MstSoftSkill::create([
                            'id_job_position' => $masterJpId,
                            'keterangan_sk' => $keteranganSk,
                            'deskripsi_sk' => $validatedData['sk']['deskripsi_sk'][$index] ?? null,
                            'nilai' => $nilai,
                            'id_poin_kategori' => $idPoinKategori,
                        ]);
                    }
                }
            }

            // 3. Process Additionals
            $existingAds = MstAdditionals::where('id_job_position', $oldMasterJpId)->get();
            if (isset($validatedData['ad']['keterangan_ad'])) {
                foreach ($validatedData['ad']['keterangan_ad'] as $index => $keteranganAd) {
                    if (empty($keteranganAd)) continue;
                    $nilai = $validatedData['ad']['nilai'][$index] ?? null;
                    $idPoinKategori = $validatedData['ad']['id_poin_kategori'][$index] ?? null;

                    if (isset($existingAds[$index])) {
                        $data = $existingAds[$index];
                        $data->id_job_position = $masterJpId;
                        $data->keterangan_ad = $keteranganAd;
                        $data->deskripsi_ad = $validatedData['ad']['deskripsi_ad'][$index] ?? null;
                        $data->nilai = $nilai;
                        $data->id_poin_kategori = $idPoinKategori;
                        $data->save();
                    } else {
                        MstAdditionals::create([
                            'id_job_position' => $masterJpId,
                            'keterangan_ad' => $keteranganAd,
                            'deskripsi_ad' => $validatedData['ad']['deskripsi_ad'][$index] ?? null,
                            'nilai' => $nilai,
                            'id_poin_kategori' => $idPoinKategori,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Update All Error:', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        Log::info('Received Update Data:', $request->all());

        // Validasi input
        $validatedData = $request->validate([
            'tc.id_job_position' => 'required|exists:mst_job_positions,id',
            'tc.keterangan_tc.*' => 'required|string',
            'tc.id_poin_kategori.*' => 'nullable|integer|exists:tc_poin_kategoris,id',
            'tc.nilai.*' => 'required|integer|between:1,4',
        ]);

        try {
            // Dapatkan data yang ingin diedit
            $tc = MstTc::findOrFail($id);
            $oldJobPositionRecord = MstJobPosition::findOrFail($tc->id_job_position);
            
            if (!$this->canAccessJobPosition($oldJobPositionRecord)) {
                return $this->inaccessibleJobPositionResponse();
            }
            $oldMasterJpId = $oldJobPositionRecord->id;

            // Ambil id_job_position dari input (posisi baru)
            $newJpId = $validatedData['tc']['id_job_position'];
            $newJobPositionRecord = MstJobPosition::findOrFail($newJpId);

            if (!$this->canAccessJobPosition($newJobPositionRecord)) {
                return $this->inaccessibleJobPositionResponse();
            }

            $masterJpId = $newJobPositionRecord->id;

            // Ambil data lama berdasarkan old master id
            $sameJobPositionData = MstTc::where('id_job_position', $oldMasterJpId)->get();

            foreach ($validatedData['tc']['keterangan_tc'] as $index => $keteranganTc) {
                $nilai = $validatedData['tc']['nilai'][$index] ?? null;
                $idPoinKategori = $validatedData['tc']['id_poin_kategori'][$index] ?? null;

                if (isset($sameJobPositionData[$index])) {
                    $data = $sameJobPositionData[$index];
                    $data->id_job_position = $masterJpId;
                    $data->keterangan_tc = $keteranganTc;
                    $data->nilai = $nilai;
                    $data->id_poin_kategori = $idPoinKategori;
                    $data->save();
                } else {
                    MstTc::create([
                        'id_job_position' => $masterJpId,
                        'keterangan_tc' => $keteranganTc,
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

    public function updateSoftSkills(Request $request, $id)
    {
        Log::info('Received Update Data:', $request->all());

        // Validasi input
        $validatedData = $request->validate([
            'sk.id_job_position' => 'required|exists:mst_job_positions,id',
            'sk.keterangan_sk.*' => 'required|string',
            'sk.nilai.*' => 'required|integer|between:1,4',
            'sk.id_poin_kategori.*' => 'nullable|integer|exists:tc_poin_kategoris,id',
        ]);

        try {
            // Dapatkan data yang ingin diedit
            $softSkill = MstSoftSkill::findOrFail($id);
            $oldJobPositionRecord = MstJobPosition::findOrFail($softSkill->id_job_position);
            
            if (!$this->canAccessJobPosition($oldJobPositionRecord)) {
                return $this->inaccessibleJobPositionResponse();
            }
            $oldMasterJpId = $oldJobPositionRecord->id;

            // Ambil id_job_position dari input (posisi baru)
            $newJpId = $validatedData['sk']['id_job_position'];
            $newJobPositionRecord = MstJobPosition::findOrFail($newJpId);

            if (!$this->canAccessJobPosition($newJobPositionRecord)) {
                return $this->inaccessibleJobPositionResponse();
            }

            $masterJpId = $newJobPositionRecord->id;

            // Ambil data lama berdasarkan old master id
            $sameJobPositionData = MstSoftSkill::where('id_job_position', $oldMasterJpId)->get();

            foreach ($validatedData['sk']['keterangan_sk'] as $index => $keteranganSk) {
                $nilai = $validatedData['sk']['nilai'][$index] ?? null;
                $idPoinKategori = $validatedData['sk']['id_poin_kategori'][$index] ?? null;

                if (isset($sameJobPositionData[$index])) {
                    $data = $sameJobPositionData[$index];
                    $data->id_job_position = $masterJpId;
                    $data->keterangan_sk = $keteranganSk;
                    $data->nilai = $nilai;
                    $data->id_poin_kategori = $idPoinKategori;
                    $data->save();
                } else {
                    MstSoftSkill::create([
                        'id_job_position' => $masterJpId,
                        'keterangan_sk' => $keteranganSk,
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

    public function updateAdditionals(Request $request, $id)
    {
        Log::info('Received Update Data:', $request->all());

        // Validasi input
        $validatedData = $request->validate([
            'additional.id_job_position' => 'required|exists:mst_job_positions,id',
            'additional.keterangan_ad.*' => 'required|string',
            'additional.id_poin_kategori.*' => 'required|integer|between:1,3',
            'additional.nilai.*' => 'required|integer|between:1,4',
        ]);

        try {
            // Dapatkan data yang ingin diedit
            $additional = MstAdditionals::findOrFail($id);
            $oldJobPositionRecord = MstJobPosition::findOrFail($additional->id_job_position);
            
            if (!$this->canAccessJobPosition($oldJobPositionRecord)) {
                return $this->inaccessibleJobPositionResponse();
            }
            $oldMasterJpId = $oldJobPositionRecord->id;

            // Ambil id_job_position dari input (posisi baru)
            $newJpId = $validatedData['additional']['id_job_position'];
            $newJobPositionRecord = MstJobPosition::findOrFail($newJpId);

            if (!$this->canAccessJobPosition($newJobPositionRecord)) {
                return $this->inaccessibleJobPositionResponse();
            }

            $masterJpId = $newJobPositionRecord->id;

            // Ambil data lama berdasarkan old master id
            $sameJobPositionData = MstAdditionals::where('id_job_position', $oldMasterJpId)->get();

            foreach ($validatedData['additional']['keterangan_ad'] as $index => $keteranganAd) {
                $nilai = $validatedData['additional']['nilai'][$index] ?? null;
                $idPoinKategori = $validatedData['additional']['id_poin_kategori'][$index] ?? null;

                if (isset($sameJobPositionData[$index])) {
                    $data = $sameJobPositionData[$index];
                    $data->id_job_position = $masterJpId;
                    $data->keterangan_ad = $keteranganAd;
                    $data->nilai = $nilai;
                    $data->id_poin_kategori = $idPoinKategori;
                    $data->save();
                } else {
                    MstAdditionals::create([
                        'id_job_position' => $masterJpId,
                        'keterangan_ad' => $keteranganAd,
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

            // Authorization: pastikan user memiliki akses ke job position yang bersangkutan
            $jobPositionRecord = MstJobPosition::findOrFail($tcRow->id_job_position);
            if (!$this->canAccessJobPosition($jobPositionRecord)) {
                return $this->inaccessibleJobPositionResponse();
            }

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

            // Authorization: pastikan user memiliki akses ke job position yang bersangkutan
            $jobPositionRecord = MstJobPosition::findOrFail($skRow->id_job_position);
            if (!$this->canAccessJobPosition($jobPositionRecord)) {
                return $this->inaccessibleJobPositionResponse();
            }

            $skRow->delete();

            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting SK row:', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat menghapus data.'], 500);
        }
    }

    public function deleteAdRow($id)
    {
        try {
            $adRow = MstAdditionals::findOrFail($id);

            // Authorization: pastikan user memiliki akses ke job position yang bersangkutan
            $jobPositionRecord = MstJobPosition::findOrFail($adRow->id_job_position);
            if (!$this->canAccessJobPosition($jobPositionRecord)) {
                return $this->inaccessibleJobPositionResponse();
            }

            $adRow->delete();

            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
        } catch (\Exception $e) {
            Log::error('Error deleting AD row:', ['error' => $e->getMessage()]);

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
        $jobPosition = DB::table('mst_job_positions')
            ->where('id', $jobPositionId)
            ->first();

        if (!$jobPosition) {
            return response()->json([
                'success' => false,
                'message' => 'Job position not found.'
            ]);
        }

        // Cari semua users berdasarkan job_position yang sama
        $employees = DB::table('user_job_positions')
            ->join('users', 'user_job_positions.user_id', '=', 'users.id')
            ->where('user_job_positions.mst_job_position_id', $jobPositionId)
            ->select('users.name')
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
