<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MstPengajuanSubcont;
use Illuminate\Auth\Events\Validated;
use App\Models\Customer;
use App\Models\User;
use App\Models\TrsAttcCstm;
use App\Models\TrsPengajuanSubcont;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CustomRequestController extends Controller
{
    public function showCstmReq()
    {

        $query = MstPengajuanSubcont::with(['sales', 'marketing', 'production', 'finance'])
            ->whereIn('sec_line', [1, 2])
            ->orderBy('created_at', 'desc');

        
        $materials = $query->get();

        $files = TrsAttcCstm::where('status', 3)
            ->whereIn('mst_id', $materials->pluck('id'))
            ->get()
            ->keyBy('mst_id');

        $users = User::all();
        $updates = TrsPengajuanSubcont::whereIn('id_subcont', $materials->pluck('id'))
            ->get()
            ->keyBy('id_subcont');

        return view('custom_req.index_cstm_req', compact('materials', 'users', 'files', 'updates'));
    }


    public function showApprovalMarketing()
    {
        $materials = MstPengajuanSubcont::with(['sales', 'marketing', 'production', 'finance'])
            ->where('status_1', 3)
            ->where('sec_line', [1,2])
            ->where('confirm_prod', '!=', '')
            ->orderBy('created_at', 'desc')
            ->get();

        $files = TrsAttcCstm::where('status', 3)
        ->whereIn('mst_id', $materials->pluck('id'))
        ->get()
        ->keyBy('mst_id');

        $users = User::all();

        $updates = TrsPengajuanSubcont::whereIn('id_subcont', $materials->pluck('id'))
        ->get()
        ->keyBy('id_subcont');

        return view('custom_req.showApprovalMarketing', compact('materials', 'users', 'files', 'updates'));
    }

    public function approveMarketing($id)
    {
        $materials = MstPengajuanSubcont::findOrFail($id);

        $materials->status_1 = 4;
        $materials->status_2 = 4;
        $materials->approval_1 = auth()->user()->id;
        $materials->date_app_1 = now();
        $materials->save();

        $userName = auth()->user()->name;

        TrsPengajuanSubcont::create([
            'id_subcont' => $materials->id,
            'keterangan' => 'Approved by Marketing', 
            'status' => '4', 
            'modified_at' => $userName 
        ]);

       return response()->json(['message' => 'Berhasil disetujui Marketing.'], 200);
        
    }

    public function rejectMarketing($id)
    {
        $materials = MstPengajuanSubcont::findOrFail($id);

        $materials->status_1 = 3;
        $materials->status_2 = 3;
        $materials->approval_1 = '';
        $materials->date_app_1 = Null;
        $materials->save();

        $userName = auth()->user()->name;

        TrsPengajuanSubcont::create([
            'id_subcont' => $materials->id,
            'keterangan' => 'Rejected by Marketing', 
            'status' => '3', 
            'modified_at' => $userName 
        ]);

       return response()->json(['message' => 'rejected Marketing Berhasil'], 200);
        
    }

    public function approvemarketing2($id)
    {
        $materials = MstPengajuanSubcont::findOrFail($id);

        $materials->status_1 = 5;
        $materials->status_2 = 5;
        $materials->approval_1 = auth()->user()->id;
        $materials->date_app_1 = now();
        $materials->is_active = 0;
        $materials->save();

        $userName = auth()->user()->name;

        TrsPengajuanSubcont::create([
            'id_subcont' => $materials->id,
            'keterangan' => 'Approved by Marketing', 
            'status' => '5', 
            'modified_at' => $userName 
        ]);

       return response()->json(['message' => 'Berhasil disetujui Marketing.'], 200);
        
    }

    public function showApprovalFinance()
    {
        $materials = MstPengajuanSubcont::with(['sales', 'marketing', 'production', 'finance'])
            ->where('status_1', 4)
            ->where('sec_line', [1,2])
            ->where('confirm_prod', '!=', '')
            ->orderBy('created_at', 'desc')
            ->get();

        $files = TrsAttcCstm::where('status', 3)
        ->whereIn('mst_id', $materials->pluck('id'))
        ->get()
        ->keyBy('mst_id');

        $users = User::all();

        $updates = TrsPengajuanSubcont::whereIn('id_subcont', $materials->pluck('id'))
        ->get()
        ->keyBy('id_subcont');

        return view('custom_req.showApprovalFinance', compact('materials', 'users', 'files', 'updates'));
    }

    
    

    public function approveFinance($id)
    {
        $materials = MstPengajuanSubcont::findOrFail($id);

        $materials->status_1 = 5;
        $materials->status_2 = 5;
        $materials->approval_2 = auth()->user()->id;
        $materials->date_app_2 = now();
        $materials->is_active = 0;
        $materials->save();

        $userName = auth()->user()->name;

        TrsPengajuanSubcont::create([
            'id_subcont' => $materials->id,
            'keterangan' => 'Finished by Finance', 
            'status' => '5', 
            'modified_at' => $userName 
        ]);
        
        return response()->json(['message' => 'Berhasil di selesaikan'], 200);
    }

    public function rejectFinance(Request $request,$id)
    {

        $materials = MstPengajuanSubcont::findOrFail($id);

        $materials->status_1 = 3;
        $materials->status_2 = 3;
        $materials->approval_1 = '';
        $materials->date_app_1 = null;
        $materials->save();

        $userName = auth()->user()->name;

        TrsPengajuanSubcont::create([
            'id_subcont' => $materials->id,
            'keterangan' => 'Rejected by finance karena : '. $request->keterangan, 
            'status' => '3', 
            'modified_at' => $userName 
        ]);

       return response()->json(['message' => 'rejected Finance Berhasil'], 200);
        
    }

    public function createCstmReq(Request $request)
    {
        $userName = auth()->user()->name;

        // Buat pengajuan subcont dan simpan ke dalam variabel
        $pengajuan = MstPengajuanSubcont::create([
            'nama_customer' => $request->customer,
            'nama_project' => $request->nama_project,
            'qty' => 1,
            //'remark' => $request->remark,
            'tgl_permintaan' => now(),
            'status_1' => 1,
            'sec_line' => 1,
            'is_active' => 1,
            'jenis_proses_subcont' => 'Null',
            'file' => "Null",
            'file_name' => "Null",
            'status_2' => 1,
            'modified_at' => $userName,
        ]);

        // Mendapatkan ID dari pengajuan yang baru dibuat
        $mstid = $pengajuan->id; // Ambil ID dari objek yang baru saja dibuat

        // Buat log pengajuan subcont
        TrsPengajuanSubcont::create([
            'id_subcont' => $mstid, // Gunakan ID yang baru diambil
            'keterangan' => 'Pengajuan dibuat', // Deskripsi log aktivitas
            'status' => '1', // Status atau keterangan tambahan
            'modified_at' => $userName // Menyimpan nama user yang melakukan edit
        ]);

        return redirect()->back()->with('success', 'Material berhasil ditambahkan.');
    }

    public function deleteCstmReq($id)
    {
        $material = MstPengajuanSubcont::findOrFail($id);
        $material->delete();

        return redirect()->back()->with('success', 'Material berhasil dihapus.');
    }

    public function updateMarketing(Request $request, $id)
    {
        $material = MstPengajuanSubcont::findOrFail($id);

        $material->ref_so = $request->ref_so;
        $material->save();
        
        return redirect()->back()->with('success', 'Data berhasil diperbarui.');
    }

    public function inputCstmReq(Request $request, $id)
    {
        // Temukan material berdasarkan ID
        $material = MstPengajuanSubcont::findOrFail($id);
        
        // Simpan nilai sebelumnya
        $originalKeterangan = $material->keterangan;
        $originalHargaAwal = $material->harga_awal;

        // Update data material dengan data dari request hanya untuk harga_awal
        $material->harga_awal = $request->harga_awal;

        // Simpan perubahan
        $material->save();
        
        // Ambil username pengguna yang sedang login
        $userName = auth()->user()->name;

        // Menyusun keterangan aktivitas mencakup semua perubahan
        $changes = [];
        if ($originalKeterangan !== $material->keterangan) {
            $changes[] = "Keterangan diubah dari '$originalKeterangan' menjadi '{$material->keterangan}'";
        }
        if ($originalHargaAwal !== $material->harga_awal) {
            $changes[] = "Harga awal diubah dari '$originalHargaAwal' menjadi '{$material->harga_awal}'";
        }

        // Menggabungkan semua perubahan menjadi satu string
        $keteranganLog = implode(', ', $changes);

        // Menyimpan log aktivitas ke dalam tabel trs_pengajuan_subconts
        TrsPengajuanSubcont::create([
            'id_subcont' => $id, // ID subcont yang relevan
            'keterangan' => $keteranganLog ?: 'Data diupdate tanpa perubahan yang terdeteksi', // Deskripsi log aktivitas
            'status' => '2', // Status atau keterangan tambahan
            'modified_at' => $userName // Menyimpan nama user yang melakukan edit
        ]);

        return redirect()->back()->with('success', 'Material berhasil ditambahkan.');
    }

    public function inputhrgakhr(Request $request, $id)
    {
        // Temukan material berdasarkan ID
        $material = MstPengajuanSubcont::findOrFail($id);
        
        // Simpan nilai sebelumnya
        $originalhargaakhir = $material->harga_akhir;

        // Update data material dengan data dari request
        $material->harga_akhir = $request->harga_akhir;

        // Simpan perubahan
        $material->save();

        // Ambil username pengguna yang sedang login
        $userName = auth()->user()->name;

        // Menyusun keterangan aktivitas mencakup semua perubahan
        $changes = [];
        if ($originalhargaakhir !== $material->harga_akhir) {
            $changes[] = "Selling price diubah dari '$originalhargaakhir' menjadi '{$material->harga_akhir}'";
        }

        // Menggabungkan semua perubahan menjadi satu string
        $keteranganLog = implode(', ', $changes);

        // Menyimpan log aktivitas ke dalam tabel trs_pengajuan_subconts
        TrsPengajuanSubcont::create([
            'id_subcont' => $id, // ID subcont yang relevan
            'keterangan' => $keteranganLog ?: 'Data diupdate tanpa perubahan yang terdeteksi', // Deskripsi log aktivitas
            'status' => '4', // Status atau keterangan tambahan
            'modified_at' => $userName // Menyimpan nama user yang melakukan edit
        ]);

        return redirect()->back()->with('success', 'Material berhasil ditambahkan.');
    }


    public function updateCstmReq(Request $request, $id)
    {
        $material = MstPengajuanSubcont::findOrFail($id);
        $userId = auth()->user()->name;

        $material->keterangan = $request->keterangan;
        $material->jenis_proses_subcont = $request->jenis_proses_subcont;
        $material->harga_awal = $request->harga_awal;
        

        $material->save();

        return redirect()->back()->with('success', 'Data berhasil diperbarui.');
    }

    public function formCstmReq($id)
    {
        // Mengambil data MstPengajuanSubcont berdasarkan ID
    $materials = MstPengajuanSubcont::with(['sales', 'production', 'marketing', 'finance'])->findOrFail($id);
    
    // Mengambil daftar file yang terkait
    $files = TrsAttcCstm::where('mst_id', $id)
        ->orderBy('created_at', 'desc')
        ->get();

    $filesquotation = TrsAttcCstm::where('mst_id', $id)
        ->where('status', 4)
        ->get();

    // Mengambil log aktivitas yang terkait
    $activity_logs = TrsPengajuanSubcont::where('id_subcont', $id)
        ->with('mstPengajuanSubcont')
        ->orderBy('created_at', 'desc')
        ->get();
    
    // Cek apakah ada file dengan status 3
    $hasStatusThree = $files->contains(function ($file) {
        return $file->status == 3; // Memeriksa apakah status file 3
    });

    return view('custom_req.viewCstmReq', compact('materials', 'files', 'activity_logs', 'hasStatusThree', 'filesquotation'));
    }

    public function submitData(Request $request, $id)
    {
        // Mengambil data pengajuan dari model MstPengajuanSubcont
        $pengajuan = MstPengajuanSubcont::findOrFail($id);
        
        // Cek apakah ada file yang di-upload
        $quotationFilePath = $pengajuan->quotation_file; // Tetap simpan path sebelumnya jika tidak ada file baru
        if ($request->hasFile('quotation_file')) {
            $quotationFile = $request->file('quotation_file');
            $fileName = $quotationFile->getClientOriginalName();
            // Simpan file ke folder public/assets/subcont
            $savePath = $quotationFile->move(public_path('assets/subcont'), $fileName);
            // Simpan path file ke variabel
            $quotationFilePath = 'assets/subcont/' . $fileName;
        }
        
        // Update status_1, status_2, quotation_file, dan keterangan 
        $pengajuan->update([
            'status_1' => 3,
            'status_2' => 3,
            'quotation_file' => $quotationFilePath,
            'keterangan' => $request->input('keterangan'), // Update kolom keterangan
        ]);
        
        // Simpan data ke model TrsPengajuanSubcont
        TrsPengajuanSubcont::create([
            'id_subcont' => $pengajuan->id, // Mengambil id dari MstPengajuanSubcont
            'status' => 3, // Status baru
            'keterangan' => 'Keterangan : ' . $request->input('keterangan'), // Menyimpan keterangan
            'modified_at' => Auth::user()->name, // Mengambil nama user yang sedang login
        ]);

        // Redirect kembali ke halaman form dengan id
        return redirect()->route('CustomRequest.form', ['id' => $id])->with('message', 'Status dan keterangan berhasil disimpan.');
    }

    public function formCstmReqSales($id)
    {
        // Mengambil data MstPengajuanSubcont berdasarkan ID
        $materials = MstPengajuanSubcont::with(['sales', 'production', 'marketing', 'finance'])->findOrFail($id);
        
        // Mengambil daftar file yang terkait
        $files = TrsAttcCstm::where('mst_id', $id)
        ->orderBy('created_at', 'desc')
        ->get();

        

        // Mengambil log aktivitas yang terkait
        $activity_logs = TrsPengajuanSubcont::where('id_subcont', $id)
            ->with('mstPengajuanSubcont')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('custom_req.viewCstmReqSales', compact('materials', 'files', 'activity_logs'));
    }

    public function upload(Request $request, $id)
    {
        // Validasi file upload
        $request->validate([
            'file' => 'required|mimes:pdf',
            'harga_awal' => 'nullable|numeric', // Validasi untuk harga_awal sebagai angka
        ]);

        $userName = auth()->user()->name;

        // Cek apakah ada file dengan mst_id yang sama dengan status = 3
        $hasStatusThree = TrsAttcCstm::where('mst_id', $id)->where('status', 3)->exists();
        
        // Simpan file yang di-upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();

            // Mendapatkan waktu saat ini dalam format yang diinginkan
            $timestamp = now()->format('dmy_His'); // Format: hari_bulan_tahun_jam_menit_detik
            // Membuat nama file baru
            $filename = $originalFilename . '_' . $timestamp . '.' . $extension;
            
            // Tentukan path penyimpanan
            $destinationPath = public_path('assets/custom_request');
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            // Pindahkan file ke direktori tujuan
            if ($file->move($destinationPath, $filename)) {
                // Menyimpan data ke TrsAttcCstm dengan status yang telah ditentukan
                TrsAttcCstm::create([
                    'mst_id' => $id,
                    'file_name' => $filename,
                    'status' => $hasStatusThree ? 4 : 2, // Menggunakan status yang ditentukan
                    'create_by' => $userName
                ]);

                // Menambahkan catatan aktivitas ke dalam tabel trs_pengajuan_subconts
                TrsPengajuanSubcont::create([
                    'id_subcont' => $id,
                    'keterangan' => 'Uploaded file: ' . $filename,
                    'status' => '1', // Status atau keterangan tambahan
                    'modified_at' => $userName
                ]);

                // Jika ada file dengan status 3, simpan harga_awal dari input
                if ($request->harga_awal !== null) {
                    $material = MstPengajuanSubcont::findOrFail($id);
                    $material->harga_awal = $request->harga_awal; // Simpan nilai harga_awal dari input
                    $material->save(); // Simpan perubahan

                    TrsPengajuanSubcont::create([
                    'id_subcont' => $id,
                    'keterangan' => 'Cost Process : ' . $request->harga_awal,
                    'status' => '2', // Status atau keterangan tambahan
                    'modified_at' => $userName
                ]);
                }
            } else {
                return response()->json(['message' => 'File upload failed'], 500);
            }
        } else {
            return response()->json(['message' => 'No file uploaded'], 400);
        }

        // Ambil semua file yang terkait dengan mst_id
        $allFiles = TrsAttcCstm::where('mst_id', $id)->pluck('file_name')->toArray();

        // Return ke halaman sebelumnya dengan pesan dan data
        return redirect()->back()->with([
            'message' => 'File uploaded successfully',
            'uploadedFiles' => $allFiles
        ]);
    }

    public function fileapprove($id)
    {

        $file = TrsAttcCstm::findOrFail($id);
        $file->status = 3;
        $file->save();

        $mst = $file->mst_id;

        $fileoke = MstPengajuanSubcont::findOrFail($mst);
        $fileoke->file = 'assets/custom_request/' . $file->file_name;
        $fileoke->file_name = $file->file_name;
        $fileoke->save(); 

        $filename = $file->file_name;
        

        $userName = auth()->user()->name;


        TrsPengajuanSubcont::create([
            'id_subcont' => $id,
            'keterangan' => 'disetujui Produksi' . ' file : '. $filename,
            'status' => '3',
            'modified_at' => $userName
        ]);
        
        return redirect()->back()->with('message', 'File berhasil di approve');
    }

    public function filerejected($id)
    {
        $file = TrsAttcCstm::findOrFail($id);
        $file->status = 1; // Ubah status menjadi "rejected"
        $file->save(); // Simpan perubahan ke database

        $mstid = $file->mst_id; // Ambil mst_id dari file

        $filename = $file->file_name;

        $userName = auth()->user()->name;

        $pengajuan = MstPengajuanSubcont::findOrFail($mstid);
        $pengajuan->status_1 = 1;
        $pengajuan->status_2 = 1;
        $pengajuan->confirm_prod = '';
        $pengajuan->save();

        TrsPengajuanSubcont::create([
            'id_subcont' => $mstid, // Atau ID yang relevan sesuai dengan konteks Anda
            'keterangan' => 'ditolak Custom,' . ' file : '. $filename, // Deskripsi aktivitas
            'status' => '1', // Status atau keterangan tambahan
            'modified_at' => $userName // Menyimpan nama user
        ]);

        return redirect()->back()->with('message', 'File berhasil di reject');
    }

    public function filerejectedsales($id)
    {
        $file = TrsAttcCstm::findOrFail($id);
        $file->status = 1; // Ubah status menjadi "rejected"
        $file->save(); // Simpan perubahan ke database

        $mstid = $file->mst_id; // Ambil mst_id dari file

        $filename = $file->file_name;

        $userName = auth()->user()->name;

        $pengajuan = MstPengajuanSubcont::findOrFail($mstid);
        $pengajuan->status_1 = 2;
        $pengajuan->status_2 = 2;
        $pengajuan->confirm_prod = '';
        $pengajuan->save();

        TrsPengajuanSubcont::create([
            'id_subcont' => $mstid, // Atau ID yang relevan sesuai dengan konteks Anda
            'keterangan' => 'ditolak Sales,' . ' file : '. $filename, // Deskripsi aktivitas
            'status' => '2', // Status atau keterangan tambahan
            'modified_at' => $userName // Menyimpan nama user
        ]);

        return redirect()->back()->with('message', 'File berhasil di reject');
    }

    public function download($id)
    {
        $file = TrsAttcCstm::findOrFail($id);
        
        // Path ke file yang disimpan
        $path = public_path('assets/custom_request/' . $file->file_name);
        
        // Cek apakah file ada
        if (!file_exists($path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return response()->download($path);
    }

    public function kirimproduction($id)
    {
        $pengajuan = MstPengajuanSubcont::findOrFail($id);
        $pengajuan->status_1 = 2;
        $pengajuan->status_2 = 2;
        $pengajuan->save();

        $userName = auth()->user()->name;


        TrsPengajuanSubcont::create([
            'id_subcont' => $id,
            'keterangan' => 'dikirim ke Custom',
            'status' => '2',
            'modified_at' => $userName
        ]);

        return response()->json(['message' => 'Berhasil dikirim ke Custom.'], 200);
    }

    public function rejectproduction($id)
    {
        $pengajuan = MstPengajuanSubcont::findOrFail($id);
        $pengajuan->status_1 = 1;
        $pengajuan->status_2 = 1;
        $pengajuan->save();

        $userName = auth()->user()->name;


        TrsPengajuanSubcont::create([
            'id_subcont' => $id,
            'keterangan' => 'Di reject oleh Custom',
            'status' => '1',
            'modified_at' => $userName
        ]);

        return response()->json(['message' => 'Rejected'], 200);
    }

    public function submitproduction($id)
    {
        $userName = auth()->user()->id;

        $pengajuan = MstPengajuanSubcont::findOrFail($id);
        $pengajuan->status_1 = 3;
        $pengajuan->status_2 = 3;
        $pengajuan->confirm_prod = auth()->user()->id;
        $pengajuan->save();

        $userName = auth()->user()->name;


        TrsPengajuanSubcont::create([
            'id_subcont' => $id,
            'keterangan' => 'disubmit ke marketing',
            'status' => '3',
            'modified_at' => $userName
        ]);

        return response()->json(['message' => 'Berhasil dikirim ke Marketing.'], 200);
    }

    public function kirimsubcont(Request $request, $id)
    {
        $pengajuan = MstPengajuanSubcont::findOrFail($id);

        // Validasi input
        $request->validate([
            'keterangan' => 'required',
            'jenis_process_subcont' => 'required',
        ]);

        // Update status dan sec_line
        $pengajuan->status_1 = 2;
        $pengajuan->status_2 = 2;
        $pengajuan->sec_line = 2;
        $pengajuan->jenis_proses_subcont = $request->jenis_process_subcont; // Simpan jenis proses subcont
        $pengajuan->keterangan = $request->keterangan; // Simpan keterangan
        $pengajuan->save();


        // Simpan informasi ke TrsPengajuanSubcont
        $userName = auth()->user()->name;
        TrsPengajuanSubcont::create([
            'id_subcont' => $id,
            'keterangan' => 'Menginput Keterangan :' . $request->keterangan . ' dan jenis proses: ' . $request->jenis_process_subcont,
            'status' => '2',
            'modified_at' => $userName 
        ]);

        return response()->json(['message' => 'Berhasil dikirim ke Subcont'], 200);
    }

    public function kirimsales(Request $request, $id)
    {
        $pengajuan = MstPengajuanSubcont::findOrFail($id);

        // Simpan jenis proses subcont
        $pengajuan->approval_1 = 'Waiting'; // Simpan keterangan
        $pengajuan->save();


        // Simpan informasi ke TrsPengajuanSubcont
        $userName = auth()->user()->name;
        TrsPengajuanSubcont::create([
            'id_subcont' => $id,
            'keterangan' => 'mengirim ke Dept Head Marketing',
            'status' => '3',
            'modified_at' => $userName 
        ]);

        return response()->json(['message' => 'Berhasil dikirim ke dept head marketing'], 200);
    }

    public function approveProduction($id)
    {

        $userName = auth()->user()->name;

        $pengajuan = MstPengajuanSubcont::findOrFail($id);
        $pengajuan->status_1 = 3;
        $pengajuan->status_2 = 3;
        $pengajuan->confirm_prod = auth()->user()->id;
        $pengajuan->save();

        TrsPengajuanSubcont::create([
            'id_subcont' => $id, // Atau ID yang relevan sesuai dengan konteks Anda
            'keterangan' => 'disetujui Custom', // Deskripsi aktivitas
            'status' => '3', // Status atau keterangan tambahan
            'modified_at' => $userName // Menyimpan nama user
        ]);


        return redirect()->back()->with('message', 'Berhasil disetujui oleh produksi');
    }

}
