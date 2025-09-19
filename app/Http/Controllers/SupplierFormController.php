<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TrsSupplierForm;
use App\Models\MstSupplierForm;
use App\Models\MstVisitForm;
use Illuminate\Support\Facades\Storage;
use App\Models\SupplierFormToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\TrxFormSupplier;

class SupplierFormController extends Controller
{

    public function index(Request $request)
    {
        $search = $request->input('search');
        $forms = TrsSupplierForm::with(['supplier', 'visitDetail'])
            ->when($search, function ($query, $search) {
                $query->whereHas('supplier', function ($q) use ($search) {
                    $q->where('supplier_name', 'like', '%' . $search . '%')
                    ->orWhere('kategori', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->paginate(10);
        return view('supplier_forms.index_supplier', [
            'forms' => $forms,
            'is_ajax' => $request->ajax()
        ]);
    }

    public function generateLink(Request $request)
    {
        try {
            $token = Str::random(40);
            SupplierFormToken::create([
                'token' => $token,
                'is_used' => false,
                'expires_at' => now()->addDays(7)
            ]);
            $url = route('supplierform.public.show', ['token' => $token]);
            return response()->json(['success' => true, 'url' => $url]);
        } catch (\Exception $e) {
            Log::error('Error generating supplier link: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal membuat link. Silakan coba lagi.'], 500);
        }
    }

    public function downloadFile($id, $type)
    {
        $supplier = MstSupplierForm::findOrFail($id);

        $fileMapping = [
            'npwp'  => ['column' => 'npwp_file',  'folder' => 'npwp'],
            'sppkp' => ['column' => 'sppkp_file', 'folder' => 'sppkp'],
            'nib'   => ['column' => 'nib_file',   'folder' => 'nib'],
            'rek'   => ['column' => 'rek_bank',   'folder' => 'rek'], // <--- tambahan
        ];

        if (!isset($fileMapping[$type])) {
            abort(404, 'Jenis file tidak valid.');
        }

        $column = $fileMapping[$type]['column'];
        $folder = $fileMapping[$type]['folder'];
        $fileName = $supplier->$column;

        if (!$fileName) {
            return back()->with('error', 'File tidak tercatat di database.');
        }

        $filePath = public_path("assets/form_supplier/{$folder}/{$fileName}");

        if (!file_exists($filePath)) {
            abort(404, 'File fisik tidak ditemukan di server.');
        }

        return response()->file($filePath);
    }


    public function showPublicForm($token)
    {
        $tokenRecord = SupplierFormToken::where('token', $token)
                                        ->where('is_used', false)
                                        ->where('expires_at', '>', now())
                                        ->first();
        if (!$tokenRecord) {
            return view('supplier_forms.invalid_link');
        }
        return view('supplier_forms.supplier_form', ['token' => $token]);
    }


    // public function storePublicForm(Request $request)
    // {
    //     // ==============================
    //     // 1. Validasi input
    //     // ==============================
    //     $validator = Validator::make($request->all(), [
    //         'form_token'               => 'required|exists:supplier_form_tokens,token,is_used,0',
    //         'supplier_name'            => 'required|string|max:255',
    //         'alamat'                   => 'required|string',
    //         'telp'                     => 'required|string|max:50',
    //         'npwp'                     => 'nullable|string|max:255',
    //         'director'                 => 'required|string|max:255',
    //         'pic'                      => 'required|string|max:255',
    //         'has_quality_standard'     => 'required|boolean',
    //         'quality_certificate'      => 'nullable|string|max:255',
    //         'quality_certificate_from' => 'nullable|string|max:255',
    //         'has_quality_responsible'  => 'required|boolean',
    //         'quality_responsible_name' => 'nullable|string|max:255',
    //         'has_material_safety'      => 'required|boolean',
    //         'has_safety'               => 'required|boolean',
    //         'employs_underage'         => 'required|boolean',
    //         'pays_min_wage'            => 'required|boolean',
    //         'npwp_file'                => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
    //         'sppkp_file'               => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
    //         'nib_file'                 => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
    //         'lampiran_compro.*'        => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
    //         'rek_bank'                 => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
    //     ]);

    //     if ($validator->fails()) {
    //         return back()->withErrors($validator)->withInput();
    //     }

    //     // ==============================
    //     // 2. Cek token
    //     // ==============================
    //     $tokenRecord = SupplierFormToken::where('token', $request->form_token)->first();
    //     if (!$tokenRecord) {
    //         return view('supplier_forms.invalid_link');
    //     }

    //     DB::beginTransaction();

    //     try {
    //         // ==============================
    //         // 3. Upload multiple file (Compro)
    //         // ==============================
    //         $comproFiles = $this->handleMultipleUploads(
    //             $request,
    //             'lampiran_compro',
    //             public_path('assets/form_supplier/visit/compro')
    //         );

    //         // Ambil data kecuali file
    //         $dataToSave = $request->except([
    //             '_token', 'form_token',
    //             'npwp_file', 'sppkp_file', 'nib_file',
    //             'lampiran_compro', 'rek_bank'
    //         ]);
    //         $dataToSave['lampiran_compro'] = !empty($comproFiles) ? implode(',', $comproFiles) : null;

    //         // ==============================
    //         // 4. Upload file satuan
    //         // ==============================
    //         $dataToSave['npwp_file']  = $this->handleSingleUpload($request, 'npwp_file',  public_path('assets/form_supplier/npwp'),  'npwp');
    //         $dataToSave['rek_bank']   = $this->handleSingleUpload($request, 'rek_bank',   public_path('assets/form_supplier/rek'),   'rek');
    //         $dataToSave['sppkp_file'] = $this->handleSingleUpload($request, 'sppkp_file', public_path('assets/form_supplier/sppkp'), 'sppkp');
    //         $dataToSave['nib_file']   = $this->handleSingleUpload($request, 'nib_file',   public_path('assets/form_supplier/nib'),   'nib');

    //         // ==============================
    //         // 5. Simpan master supplier
    //         // ==============================
    //         $mstSupplier = MstSupplierForm::create($dataToSave);

    //         // Nonaktifkan token
    //         $tokenRecord->update(['is_used' => true]);

    //         // Simpan transaksi supplier
    //         TrsSupplierForm::create([
    //             'mst_supplier' => $mstSupplier->id,
    //             'is_active'    => 1,
    //             'status'       => 1,
    //         ]);

    //         DB::commit();

    //         return redirect()->route('supplierform.public.success');

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Gagal menyimpan form supplier: ' . $e->getMessage());

    //         return back()->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
    //     }
    // }



        public function showSuccessPage()
    {
        return view('supplier_forms.success_page');
    }


    public function reject($id)
    {
        $form = TrsSupplierForm::findOrFail($id);
        $form->status = 0;
        $form->is_active = 0; // atau kode status khusus untuk Reject
        $form->save();

        return redirect()->route('supplierform.index')
                        ->with('error', 'Form telah ditandai sebagai Reject.');
    }

    public function hapus($id)
    {
        $form = TrsSupplierForm::findOrFail($id);
        $form->delete();

        $mstSupplier = MstSupplierForm::find($form->mst_supplier);
        if ($mstSupplier) {
            $mstSupplier->delete();
        }

        return redirect()->route('supplier_forms.index_supplier')
                        ->with('error', 'Form telah dihapus.');
    }

    public function download($id)
    {
        $form = TrsSupplierForm::findOrFail($id);

        // 1. Pastikan nama file trial ada di database
        if (empty($form->trial_file)) {
            return back()->with('error', 'Data file trial tidak tercatat di database.');
        }

        // 2. Dapatkan path absolut langsung ke folder public (seperti fungsi Anda yang lain)
        $filePath = public_path('assets/form_supplier/trial/' . $form->trial_file);

        // 3. Periksa apakah file benar-benar ada di server menggunakan file_exists()
        if (file_exists($filePath)) {
            // Langsung tampilkan file menggunakan path yang sudah didapat
            return response()->file($filePath);
        }

        // 4. Jika file tidak ditemukan, kembalikan pesan error
        return back()->with('error', 'File trial tidak ditemukan di server.');
    }

    public function printReport($id)
    {
        // 1. Ambil data utama beserta relasi yang dibutuhkan
        $form = TrsSupplierForm::with(['supplier', 'visitDetail'])->findOrFail($id);

        // 2. Siapkan variabel untuk kemudahan akses di view
        $supplier = $form->supplier;
        $visit = $form->visitDetail;

        // 3. Hitung rata-rata nilai jika data visit ada
        $averageScore = 0;
        if ($visit) {
            $scores = array_filter([
                $visit->kelengkapan_apd,
                $visit->fasilitas,
                $visit->alat_ukur,
                $visit->lisensi,
                $visit->lima_r,
            ]);
            
            if (count($scores) > 0) {
                $averageScore = array_sum($scores) / count($scores);
            }
        }

        // 4. Kirim semua data yang diperlukan ke view laporan terpisah
        return view('supplier_forms.print', [
            'form' => $form,
            'supplier' => $supplier,
            'visit' => $visit,
            'averageScore' => $averageScore,
        ]);
    }

    
public function show($id)
{
    // Mengambil data form beserta relasi yang BENAR
    $form = TrsSupplierForm::with([
        'supplier', 
        'visitDetail', // DIUBAH: Sesuai dengan nama method relasi di model TrsSupplierForm
        'logs' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }
    ])->findOrFail($id);

    // Ambil data dari relasi untuk kemudahan
    $supplier = $form->supplier;
    $visit = $form->visitDetail; // DIUBAH: Sesuai dengan nama relasi yang benar

    // Hitung rata-rata nilai jika data visit ada
    $averageScore = 0;
    if ($visit) {
        $scores = [
            $visit->kelengkapan_apd,
            $visit->fasilitas,
            $visit->alat_ukur,
            $visit->lisensi,
            $visit->lima_r,
        ];
        // Filter nilai null atau 0 agar tidak ikut dalam perhitungan
        $validScores = array_filter($scores); 
        if (count($validScores) > 0) {
            $averageScore = array_sum($validScores) / count($validScores);
        }
    }

    // Kirim semua data yang diperlukan ke satu view 'show'
    // Bagian ini tidak berubah dan sudah benar
    return view('supplier_forms.show', [
        'form' => $form,
        'supplier' => $supplier,
        'visit' => $visit,
        'averageScore' => $averageScore,
    ]);
}

    public function updateCategory(Request $request, $id)
    {
        $request->validate([
            'kategori' => 'required|string|max:255',
            'type'     => 'required|in:Trade,Non Trade',
        ]);

        $form = TrsSupplierForm::with('supplier')->findOrFail($id);

        if ($form->supplier) {
            $form->supplier->update([
                'kategori' => $request->kategori,
                'type'     => $request->type,
            ]);
        }

        $form->update(['status' => 2]);

        return redirect()->back()->with('success', 'Kategori dan Type berhasil disimpan.');
    }

    public function rejectType(Request $request, $id)
    {
        $form = TrsSupplierForm::findOrFail($id);
        $form->update(['status' => 1]); // Atur status ke 0 (Rejected)

        $mst = MstSupplierForm::find($form->mst_supplier);
        $mst->update(['kategori' => null, 'type' => null]);

        return redirect()->route('supplierform.show', $form->id)->with('error', 'Kategori/Type telah ditolak.');
    }

    /**
     * Menyimpan jadwal visit/trial dan mengubah status ke 3.
     */
    public function scheduleActions(Request $request, $id)
    {
        $request->validate(['tindakan' => 'required|in:visit,trial,visit_trial,none']);

        $form = TrsSupplierForm::findOrFail($id);

        DB::transaction(function () use ($request, $form) {
            $keterangan = 'Tindakan dipilih: ';
            $updateData = [
                'visit' => 0,
                'trial' => 0,
            ];

            switch ($request->tindakan) {
                case 'visit':
                    $updateData['visit'] = 1;
                    $keterangan .= 'Visit';
                    break;
                case 'trial':
                    $updateData['trial'] = 1;
                    $keterangan .= 'Trial';
                    break;
                case 'visit_trial':
                    $updateData['visit'] = 1;
                    $updateData['trial'] = 1;
                    $keterangan .= 'Visit dan Trial';
                    break;
                case 'none':
                    $updateData['status'] = 5; // Langsung ke persetujuan akhir
                    $keterangan .= 'Tidak ada visit/trial, lanjut ke persetujuan.';
                    break;
            }

            // Jika ada visit atau trial, status maju ke tahap penjadwalan
            if ($request->tindakan !== 'none') {
                $updateData['status'] = 3;
            }
            
            $form->update($updateData);

            TrxFormSupplier::create([
                'trs_id' => $form->id,
                'keterangan' => $keterangan,
                'status' => $form->fresh()->status, // Ambil status terbaru setelah update
            ]);
        });

        return redirect()->route('supplierform.show', $form->id)->with('success', 'Pilihan tindakan berhasil disimpan.');
    }

    public function rejectSchedule(Request $request, $id)
    {
        $form = TrsSupplierForm::findOrFail($id);
        $form->update(['status' => 2,
                        'visit' => 0,
                        'trial' => 0
                    ]);

        return redirect()->route('supplierform.show', $form->id)->with('error', 'Jadwal visit/trial telah ditolak.');
    }

    public function storeVisitSchedule(Request $request, $id)
    {
        $request->validate(['visit_schedule' => 'required|date']);
        $form = TrsSupplierForm::findOrFail($id);

        DB::transaction(function () use ($request, $form) {
            $form->update(['visit_schedule' => $request->visit_schedule]);
            
            TrxFormSupplier::create([
                'trs_id' => $form->id,
                'keterangan' => 'Visit dijadwalkan pada ' . $request->visit_schedule,
                'status' => $form->status,
            ]);
        });
        
        return redirect()->route('supplierform.show', $form->id)->with('success', 'Jadwal visit berhasil disimpan.');
    }

    public function storeTrialSchedule(Request $request, $id)
    {
        $request->validate(['trial_schedule' => 'required|date']);
        $form = TrsSupplierForm::findOrFail($id);
        
        DB::transaction(function () use ($request, $form) {
            $form->update(['trial_schedule' => $request->trial_schedule]);

            TrxFormSupplier::create([
                'trs_id' => $form->id,
                'keterangan' => 'Trial dijadwalkan pada ' . $request->schedule_trial,
                'status' => $form->status,
            ]);
        });

        return redirect()->route('supplierform.show', $form->id)->with('success', 'Jadwal trial berhasil disimpan.');
    }

    /**
     * Mengunggah bukti trial dan mengubah status ke 4.
     */
    public function submitTrialEvidence(Request $request, $id)
    {
        $request->validate(['trial_file' => 'required|file|mimes:pdf,jpg,png,doc,docx|max:5120']);
        $form = TrsSupplierForm::findOrFail($id);
        
        DB::transaction(function () use ($request, $form) {
            $fileName = 'trial_' . $form->id . '_' . time() . '.' . $request->trial_file->extension();
            $request->trial_file->storeAs('private/form_supplier/trial_evidence', $fileName);
            
            $form->update(['trial_file' => $fileName, 'status' => 4]);

            TrxFormSupplier::create([
                'trs_id' => $form->id,
                'keterangan' => 'Bukti trial telah diunggah: ' . $fileName,
                'status' => 4,
            ]);
        });

        return redirect()->route('supplierform.show', $form->id)->with('success', 'Bukti trial berhasil diunggah.');
    }

    public function rejectTrial(Request $request, $id)
    {
        $form = TrsSupplierForm::findOrFail($id);
        $form->update(['status' => 3, 'trial_file' => null]);

        return redirect()->route('supplierform.show', $form->id)->with('error', 'Bukti trial telah ditolak.');
    }

    /**
     * Menyetujui supplier dan mengubah status ke 6 (Finish).
     */
    public function approve($id)
    {
        // Temukan form transaksi, dan muat relasi 'supplier'
        $form = TrsSupplierForm::with('supplier')->findOrFail($id);

        DB::transaction(function () use ($form) {
            $masterSupplier = $form->supplier;

            if (!$masterSupplier) {
                return;
            }

            // --- LOGIKA PEMBUATAN KODE SUPPLIER BARU ---
            $supplierName = $masterSupplier->supplier_name;

            // 1. Bersihkan prefix PT, CV, dll
            $cleanedName = preg_replace('/^(PT\.|PT|CV\.|CV)\s+/i', '', trim($supplierName));

            // 2. Pisahkan nama berdasarkan spasi
            $nameParts = preg_split('/\s+/', $cleanedName);

            // 3. Ambil kata pertama
            $firstWord = strtoupper($nameParts[0]);

            // 4. Ambil 3 huruf pertama dari kata pertama
            $baseCode = substr($firstWord, 0, 3);

            // 5. Jika kurang dari 3 huruf, ambil huruf tambahan dari kata berikutnya
            if (strlen($baseCode) < 3 && count($nameParts) > 1) {
                $secondWord = strtoupper($nameParts[1]);
                $needed = 3 - strlen($baseCode);
                $baseCode .= substr($secondWord, 0, $needed);
            }

            // 6. Jika masih kurang dari 3, isi dengan "X"
            $baseCode = str_pad($baseCode, 3, 'X');

            // Pastikan uppercase
            $baseCode = strtoupper($baseCode);

            // --- Cek kode unik ---
            $nextNumber = 1;
            $newSupplierCode = '';

            while (true) {
                $formattedNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT); // 001, 002, ...
                $potentialCode = $baseCode . $formattedNumber;

                $exists = MstSupplierForm::where('supplier_kode', $potentialCode)->exists();

                if (!$exists) {
                    $newSupplierCode = $potentialCode;
                    break;
                }

                $nextNumber++;
            }

            // 2. Update data master supplier dengan kode baru
            $masterSupplier->update([
                'supplier_kode' => $newSupplierCode
            ]);

            // 3. Update status form transaksi
            $form->update([
                'status' => 6,
                'approve' => '1',
                'supplier_kode' => $newSupplierCode
            ]);

            // 4. Buat log approval
            TrxFormSupplier::create([
                'trs_id' => $form->id,
                'keterangan' => 'Supplier disetujui dengan kode: ' . $newSupplierCode,
                'status' => 6
            ]);
        });

        return redirect()
            ->route('supplierform.show', $form->id)
            ->with('success', 'Supplier telah disetujui dan kode telah dibuat.');
    }

    /**
     * Menolak supplier dan mengubah status ke 0 (Rejected).
     */
    public function disapprove($id)
    {
        $form = TrsSupplierForm::findOrFail($id);
        DB::transaction(function () use ($form) {
            $form->update(['is_active' => 0]);
            $form->update(['approve' => 0]);
            TrxFormSupplier::create(['trs_id' => $form->id, 'keterangan' => 'Supplier tidak disetujui.', 'status' => 0]);
        });
        return redirect()->route('supplier_forms.show', $form->id)->with('success', 'Supplier telah ditolak.');
    }

    public function createVisitAssessment($id)
    {
        $form = TrsSupplierForm::with('supplier')->findOrFail($id);

        
        // Mengarahkan data form ke view penilaian
        return view('supplier_forms.form_visit', compact('form'));
    }

    public function editVisitAssessment($id)
    {
        $form = TrsSupplierForm::with('supplier')->findOrFail($id);

        
        // Mengarahkan data form ke view penilaian
        return view('supplier_forms.form_visit', compact('form'));
    }

    private function handleMultipleUploads(Request $request, string $inputName, string $storagePath): array
    {
        $fileNames = [];

        if ($request->hasFile($inputName)) {
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0777, true);
            }

            foreach ($request->file($inputName) as $file) {
                if ($file && $file->isValid()) {
                    $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                    $file->move($storagePath, $fileName);
                    $fileNames[] = $fileName;
                }
            }
        }

        return $fileNames;
    }

    /**
     * Handle single file upload (return file name or null).
     */
    private function handleSingleUpload(Request $request, string $inputName, string $storagePath, string $prefix = null): ?string
    {
        if ($request->hasFile($inputName)) {
            $file = $request->file($inputName);

            if ($file && $file->isValid()) {
                if (!file_exists($storagePath)) {
                    mkdir($storagePath, 0777, true);
                }

                $prefix   = $prefix ? $prefix . '_' : '';
                $fileName = $prefix . Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();

                $file->move($storagePath, $fileName);

                return $fileName;
            }
        }

        return null;
    }

    /**
     * Store public form.
     */
    public function storePublicForm(Request $request)
{
    // Validasi input
    $validator = Validator::make($request->all(), [
        'form_token' => 'required|exists:supplier_form_tokens,token,is_used,0',
        'supplier_name' => 'required|string|max:255',
        'alamat' => 'required|string',
        'telp' => 'required|string|max:50',
        'npwp' => 'nullable|string|max:255',
        'director' => 'required|string|max:255',
        'pic' => 'required|string|max:255',
        'has_quality_standard' => 'required|boolean',
        'quality_certificate' => 'nullable|string|max:255',
        'quality_certificate_from' => 'nullable|string|max:255',
        'has_quality_responsible' => 'required|boolean',
        'quality_responsible_name' => 'nullable|string|max:255',
        'has_material_safety' => 'required|boolean',
        'has_safety' => 'required|boolean',
        'employs_underage' => 'required|boolean',
        'pays_min_wage' => 'required|boolean',

        // File rules
        'npwp_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        'sppkp_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        'nib_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        'lampiran_compro.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        'rek_bank' => 'nullable',
        'rek_bank.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput();
    }

    $tokenRecord = SupplierFormToken::where('token', $request->form_token)->first();
    if (!$tokenRecord) {
        return view('supplier_forms.invalid_link');
    }

    try {
        DB::beginTransaction();

        // === HANDLE MULTIPLE FILES ===
        $comproFiles = $this->handleMultipleUploads(
            $request,
            'lampiran_compro',
            public_path('assets/form_supplier/visit/compro')
        );

        // === HANDLE SINGLE FILES ===
        $npwpFile = $this->handleSingleUpload($request, 'npwp_file', public_path('assets/form_supplier/npwp'), 'npwp');
        $sppkpFile = $this->handleSingleUpload($request, 'sppkp_file', public_path('assets/form_supplier/sppkp'), 'sppkp');
        $nibFile = $this->handleSingleUpload($request, 'nib_file', public_path('assets/form_supplier/nib'), 'nib');

        // === HANDLE rek_bank ===
        $rekBankFiles = [];
        if ($request->hasFile('rek_bank')) {
            $rekBankFiles = $this->handleMultipleUploads(
                $request,
                'rek_bank',
                public_path('assets/form_supplier/rek')
            );
        }

        // === SIMPAN DATA ===
        $dataToSave = $request->except([
            '_token', 'form_token', 'npwp_file', 'sppkp_file', 'nib_file', 'lampiran_compro', 'rek_bank'
        ]);

        $dataToSave['lampiran_compro'] = !empty($comproFiles) ? implode(',', $comproFiles) : null;
        $dataToSave['npwp_file']  = $npwpFile;
        $dataToSave['sppkp_file'] = $sppkpFile;
        $dataToSave['nib_file']   = $nibFile;
        $dataToSave['rek_bank']   = !empty($rekBankFiles) ? implode(',', $rekBankFiles) : null;

        $mstSupplier = MstSupplierForm::create($dataToSave);

        // Tandai token sudah dipakai
        $tokenRecord->update(['is_used' => true]);

        // Buat entry TrsSupplierForm
        TrsSupplierForm::create([
            'mst_supplier' => $mstSupplier->id,
            'is_active'    => 1,
            'status'       => 1,
        ]);

        DB::commit();

        return redirect()->route('supplierform.public.success');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Gagal menyimpan form supplier: ' . $e->getMessage());
        return back()->with('error', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
    }
}


    public function downloadTemplateRek()
    {
        $filePath = public_path('assets/form_supplier/Surat Pernyataan Rekening Perusahaan - ADSI.docx');
        
        if (!file_exists($filePath)) {
            abort(404, 'File tidak ditemukan.');
        }

        return response()->download($filePath, 'Template_Rekening_ADSI.docx');
    }


    /**
     * Store visit.
     */
    public function storeVisit(Request $request, $id)
    {
        $form = TrsSupplierForm::with('supplier')->findOrFail($id);
        $type = $form->supplier->type ?? 'Trade';

        // Tentukan field validasi sesuai type
        $rules = [
            'tanggal_visit' => 'required|date',
            'lokasi'        => 'required|string|max:255',
            'catatan'       => 'nullable|string',
            'lampiran_foto.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];

        if ($type === 'Trade') {
            $tradeFields = ['kelengkapan_apd','fasilitas','alat_ukur','lisensi','lima_r'];
            foreach ($tradeFields as $field) {
                $rules[$field] = 'required|integer|between:1,5';
            }
        } else {
            $nonTradeFields = ['kualitas_baja','stok','waktu_kirim','responsif','office_wh','mesin','safety'];
            foreach ($nonTradeFields as $field) {
                $rules[$field] = 'required|integer|between:1,5';
            }
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Upload foto
            $photoFiles = $this->handleMultipleUploads(
                $request,
                'lampiran_foto',
                public_path('assets/form_supplier/visit/photos')
            );

            $visitData = $request->except(['_token','lampiran_foto']);
            $visitData['type'] = $type;
            $visitData['supplier_id'] = $form->id;
            $visitData['lampiran_foto'] = !empty($photoFiles) ? implode(',', $photoFiles) : null;

            $newVisit = MstVisitForm::create($visitData);

            $form->update([
                'mst_visit'  => $newVisit->id,
                'visit'      => 1,
                'visit_file' => $newVisit->lampiran_foto,
            ]);

            // Update status form
            if ($form->trial == 1) {
                $newStatus = 4;
                $form->update(['status' => $newStatus]);

                TrxFormSupplier::create([
                    'trs_id'     => $form->id,
                    'keterangan' => 'Laporan visit selesai. Proses dilanjutkan ke tahap trial.',
                    'status'     => $newStatus,
                ]);

                DB::commit();
                return redirect()->route('supplierform.show', $form->id)
                    ->with('success','Laporan visit berhasil disimpan. Silakan lanjutkan dengan mengunggah bukti trial.');
            } else {
                $newStatus = 5;
                $form->update(['status' => $newStatus]);

                TrxFormSupplier::create([
                    'trs_id'     => $form->id,
                    'keterangan' => 'Laporan visit selesai. Tidak ada trial, proses dilanjutkan ke persetujuan akhir.',
                    'status'     => $newStatus,
                ]);

                DB::commit();
                return redirect()->route('supplierform.show', $form->id)
                    ->with('success','Laporan visit berhasil disimpan. Proses lanjut ke persetujuan akhir.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal simpan visit form ID {$id}: ".$e->getMessage());
            return back()->with('error','Terjadi kesalahan internal saat menyimpan laporan visit.')->withInput();
        }
    }

    public function updateVisit(Request $request, $id)
    {
        $visit = MstVisitForm::findOrFail($id);
        $trs   = TrsSupplierForm::where('mst_visit',$id)->with('supplier')->first();
        $type  = $trs->supplier->type ?? 'Trade';

        // Tentukan field validasi sesuai type
        $rules = [
            'tanggal_visit'   => 'required|date',
            'lokasi'          => 'required|string|max:255',
            'catatan'         => 'nullable|string',
            'lampiran_foto.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'hapus_foto'      => 'nullable|array',
            'hapus_foto.*'    => 'string',
        ];

        if ($type === 'Trade') {
            $tradeFields = ['kelengkapan_apd','fasilitas','alat_ukur','lisensi','lima_r'];
            foreach ($tradeFields as $field) {
                $rules[$field] = 'required|integer|between:1,5';
            }
        } else {
            $nonTradeFields = ['kualitas_baja','stok','waktu_kirim','responsif','office_wh','mesin','safety'];
            foreach ($nonTradeFields as $field) {
                $rules[$field] = 'required|integer|between:1,5';
            }
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $existingPhotos = $visit->lampiran_foto
                ? explode(',', $visit->lampiran_foto)
                : [];

            // hapus foto lama jika dipilih
            if ($request->filled('hapus_foto')) {
                foreach ($request->hapus_foto as $photo) {
                    $path = public_path('assets/form_supplier/visit/photos/'.$photo);
                    if (file_exists($path)) unlink($path);
                    $existingPhotos = array_filter($existingPhotos, fn($p) => $p !== $photo);
                }
            }

            // upload foto baru
            $newPhotos = $this->handleMultipleUploads(
                $request,
                'lampiran_foto',
                public_path('assets/form_supplier/visit/photos')
            );

            $allPhotos = array_merge($existingPhotos,$newPhotos);
            $allPhotos = array_values($allPhotos);

            $updateData = $request->except(['_token','lampiran_foto','hapus_foto']);
            $updateData['lampiran_foto'] = !empty($allPhotos) ? implode(',', $allPhotos) : null;

            $visit->update($updateData);

            DB::commit();

            if ($trs) {
                return redirect()->route('supplierform.form_visit',$trs->id)
                    ->with('success','Laporan visit berhasil diperbarui.');
            }

            return redirect()->route('assessment.index')
                ->with('success','Laporan visit berhasil diperbarui, tapi form induk tidak ditemukan.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Gagal update visit ID {$id}: ".$e->getMessage());
            return back()->with('error','Terjadi kesalahan saat memperbarui laporan visit.')->withInput();
        }
    }


    /**
     * Store trial upload.
     */
    public function storeTrialUpload(Request $request, $id)
    {
        $request->validate([
            'trial_actual' => 'required|date',
            'trial_file'   => 'required|file|mimes:pdf|max:10240',
        ]);

        $form = TrsSupplierForm::findOrFail($id);

        try {
            $fileName = $this->handleSingleUpload(
                $request,
                'trial_file',
                public_path('assets/form_supplier/trial'),
                'trial_' . $form->id
            );

            $form->update([
                'trial_file'   => $fileName,
                'trial_actual' => $request->trial_actual,
                'status'       => 5,
            ]);

            TrxFormSupplier::create([
                'trs_id'     => $form->id,
                'keterangan' => 'Bukti trial telah diunggah.',
                'status'     => 5,
            ]);

            return redirect()->route('supplierform.show', $form->id)
                ->with('success', 'Bukti trial berhasil diunggah. Proses selesai.');

        } catch (\Exception $e) {
            Log::error('Gagal mengunggah bukti trial: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat mengunggah file.');
        }
    }

    public function updateTrialUpload(Request $request, $id)
    {
        $request->validate([
            'trial_actual' => 'required|date',
            'trial_file'   => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:10240',
        ]);

        $form = TrsSupplierForm::findOrFail($id);

        try {
            $fileName = $form->trial_file; // default file lama

            if ($request->hasFile('trial_file')) {
                // hapus file lama jika ada
                if ($form->trial_file && file_exists(public_path('assets/form_supplier/trial/' . $form->trial_file))) {
                    unlink(public_path('assets/form_supplier/trial/' . $form->trial_file));
                }

                $fileName = $this->handleSingleUpload(
                    $request,
                    'trial_file',
                    public_path('assets/form_supplier/trial'),
                    'trial_' . $form->id
                );
            }

            $form->update([
                'trial_file'   => $fileName,
                'trial_actual' => $request->trial_actual,
            ]);

            TrxFormSupplier::create([
                'trs_id'     => $form->id,
                'keterangan' => 'Bukti trial diperbarui.',
                'status'     => 5,
            ]);

            return redirect()->route('supplierform.show', $form->id)
                ->with('success', 'Bukti trial berhasil diperbarui.');

        } catch (\Exception $e) {
            Log::error('Gagal memperbarui bukti trial: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memperbarui file.');
        }
    }

    public function visitApproval(Request $request, $id)
    {
        $data = TrsSupplierForm::findOrFail($id);
        $data->visit_approval = $request->visit_approval; // 1=approve,0=reject
        $data->visit_ket = $request->visit_ket;
        $data->save();

        return back()->with('success', 'Visit updated!');
    }

    public function trialApproval(Request $request, $id)
    {
        $data = TrsSupplierForm::findOrFail($id);
        $data->trial_approval = $request->trial_approval; // 1=approve,0=reject
        $data->trial_ket = $request->trial_ket;
        $data->save();

        return back()->with('success', 'Trial updated!');
    }

    public function downloadTrial($id)
    {
        $form = TrsSupplierForm::findOrFail($id);

        // Validasi ada file
        if (!$form->trial_file) {
            abort(404, 'File tidak ditemukan.');
        }

        // Path file di public/assets
        $filePath = public_path('assets/form_supplier/trial/' . $form->trial_file);

        // Cek apakah file ada
        if (!file_exists($filePath)) {
            abort(404, 'File tidak tersedia di server.');
        }

        // Kirim file ke browser agar langsung bisa dilihat (inline)
        return response()->file($filePath, [
            'Content-Type' => mime_content_type($filePath),
            'Content-Disposition' => 'inline; filename="' . $form->trial_file . '"'
        ]);
    }



    public function deleteTrialUpload(Request $request, $id)
    {
        $form = TrsSupplierForm::findOrFail($id);

        try {
            if ($form->trial_file && file_exists(public_path('assets/form_supplier/trial/' . $form->trial_file))) {
                unlink(public_path('assets/form_supplier/trial/' . $form->trial_file));
            }

            $form->update([
                'trial_file' => null,
            ]);

            TrxFormSupplier::create([
                'trs_id'     => $form->id,
                'keterangan' => 'Bukti trial dihapus.',
                'status'     => 5,
            ]);

            return redirect()->route('supplierform.show', $form->id)
                ->with('success', 'Bukti trial berhasil dihapus.');

        } catch (\Exception $e) {
            Log::error('Gagal menghapus bukti trial: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat menghapus file.');
        }
    }






}
