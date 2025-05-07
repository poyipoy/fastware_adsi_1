<?php

namespace App\Http\Controllers;

use App\Models\MstPengajuanSubcont;
use App\Models\TrsPengajuanSubcont; // Pastikan model ini diimpor
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File; // Untuk menghapus file lama
use Illuminate\Http\Request;

class PengajuanSubcontController extends Controller
{

    // Method untuk menampilkan data ke view
    public function indexSales()
    {
        // Dapatkan nama user yang sedang login
        $userName = Auth::user()->name;

        // Mengambil data dari tabel mst_pengajuan_subconts sesuai dengan nama user yang login
        $pengajuanSubconts = MstPengajuanSubcont::where('modified_at', $userName)
            ->orderByDesc('id') // Order by id in descending order as a base sorting
            ->get();

        // Sort so that status_1 = 5 is moved to the bottom
        $pengajuanSubconts = $pengajuanSubconts->sortBy(function ($item) {
            return $item->status_1 == 5 ? 1 : 0; // Move items with status_1 = 5 to the end
        })->values();

        // Mencari pengajuan dengan quotation_file yang tidak null dan status_1 = 4
        $pengajuanDenganFile = $pengajuanSubconts->firstWhere(function ($item) {
            return $item->quotation_file !== null && $item->status_1 == 4;
        });

        $namaCustomerTerakhir = $pengajuanDenganFile ? $pengajuanDenganFile->nama_customer : null;

        // Mengirim data ke view
        return view('pengajuan_subcont.index_pengajuan_sales', compact('pengajuanSubconts', 'namaCustomerTerakhir'));
    }

    public function indexProc()
    {
        // Ambil hanya pengajuan yang status_1 adalah 2, 3, 4, atau 5 dan urutkan secara descending berdasarkan created_at
        $pengajuanSubconts = MstPengajuanSubcont::whereIn('status_1', [2, 3, 4, 5])
            ->orderByDesc('created_at')
            ->get();
        return view('pengajuan_subcont.index_pengajuan_proc', compact('pengajuanSubconts'));
    }

    // Method untuk menampilkan view form tambah data
    public function create()
    {
        return view('pengajuan_subcont.create_pengajuan'); // Sesuaikan dengan nama file view
    }

    // Method untuk menampilkan form edit
    public function edit($id)
    {
        // Mengambil data berdasarkan ID
        $pengajuan = MstPengajuanSubcont::findOrFail($id);

        // Menampilkan view form edit dan mengirim data pengajuan
        return view('pengajuan_subcont.edit_pengajuan', compact('pengajuan'));
    }

    public function editProc($id)
    {
        // Mengambil data berdasarkan ID
        $pengajuan = MstPengajuanSubcont::findOrFail($id);

        // Menampilkan view form edit dan mengirim data pengajuan
        return view('pengajuan_subcont.trs_pengajuan', compact('pengajuan'));
    }

    public function viewSales($id)
    {
        // Mengambil data berdasarkan ID
        $pengajuan = MstPengajuanSubcont::findOrFail($id);

        // Menampilkan view form edit dan mengirim data pengajuan
        return view('pengajuan_subcont.view_pengajuan_sales', compact('pengajuan'));
    }

    public function getHistory($id)
    {
        // Ambil data pengajuan berdasarkan ID
        $pengajuan = MstPengajuanSubcont::with('trsPengajuanSubcont')->findOrFail($id);

        // Persiapkan data untuk dikirim sebagai response
        $historyData = $pengajuan->trsPengajuanSubcont->map(function ($item) use ($pengajuan) {
            return [
                'nama_customer' => $pengajuan->nama_customer,
                'nama_project' => $pengajuan->nama_project,
                'keterangan' => $item->keterangan,
                'status' => $item->status,
                'created_at' => $item->created_at->format('d-m-Y H:i:s'),
                'modified_at' => $item->modified_at,
                'quotation_file' => $pengajuan->quotation_file,
            ];
        });

        // Return response dalam bentuk JSON
        return response()->json($historyData);
    }

    // Method untuk menyimpan data
    public function store(Request $request)
    {
        // Simpan file jika diunggah
        $data = $request->all();

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Tentukan path di dalam folder public
            $destinationPath = public_path('assets/subcont');

            // Tentukan nama file menggunakan UUID dan ekstensi asli
            $uuidFileName = Str::uuid() . '.' . $file->getClientOriginalExtension();

            // Pindahkan file ke folder public/assets/subcont dengan nama UUID
            $file->move($destinationPath, $uuidFileName);

            // Simpan nama file dan path ke dalam array data
            $data['file'] = 'assets/subcont/' . $uuidFileName; // Simpan path relatif dari folder public
            $data['file_name'] = $file->getClientOriginalName(); // Menyimpan nama file asli (untuk referensi jika dibutuhkan)
        }

        // Simpan data lainnya ke database
        MstPengajuanSubcont::create([
            'nama_customer' => $data['nama_customer'],
            'nama_project' => $data['nama_project'],
            'qty' => $data['qty'],
            'keterangan' => $data['keterangan'],
            'jenis_proses_subcont' => $data['jenis_proses_subcont'],
            'file' => $data['file'] ?? null, // Jika ada file, simpan
            'file_name' => $data['file_name'] ?? null, // Nama asli file
            'status_1' => 1,
            'status_2' => 1,
            'modified_at' => Auth::user()->name, // Mengambil nama user yang sedang login
        ]);

        // Redirect kembali ke halaman form atau halaman lain yang kamu inginkan
        return redirect()->route('indexSales')->with('success', 'Data berhasil ditambahkan');
    }

    // Method untuk update data
    public function update(Request $request, $id)
    {
        // Mengambil data pengajuan berdasarkan ID
        $pengajuan = MstPengajuanSubcont::findOrFail($id);

        // Mengambil semua data dari form
        $data = $request->all();

        // Proses file jika diunggah
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Tentukan path di dalam folder public
            $destinationPath = public_path('assets/subcont');

            // Tentukan nama file menggunakan UUID dan ekstensi asli
            $uuidFileName = Str::uuid() . '.' . $file->getClientOriginalExtension();

            // Hapus file lama jika ada
            if ($pengajuan->file && File::exists(public_path($pengajuan->file))) {
                File::delete(public_path($pengajuan->file));
            }

            // Pindahkan file ke folder public/assets/subcont
            $file->move($destinationPath, $uuidFileName);

            // Simpan nama file dan path ke dalam array data
            $data['file'] = 'assets/subcont/' . $uuidFileName; // Simpan path relatif dari folder public
            $data['file_name'] = $file->getClientOriginalName(); // Menyimpan nama file asli
        }

        // Update data pengajuan
        $pengajuan->update([
            'nama_customer' => $data['nama_customer'],
            'nama_project' => $data['nama_project'],
            'qty' => $data['qty'],
            'keterangan' => $data['keterangan'],
            'jenis_proses_subcont' => $data['jenis_proses_subcont'],
            'file' => $data['file'] ?? $pengajuan->file, // Jika tidak ada file baru, tetap gunakan file lama
            'file_name' => $data['file_name'] ?? $pengajuan->file_name, // Jika tidak ada file baru, tetap gunakan file_name lama
            'modified_at' => Auth::user()->name, // Mengambil nama user yang sedang login
        ]);

        // Redirect ke halaman yang diinginkan dengan pesan sukses
        return redirect()->route('indexSales')->with('success', 'Data berhasil diperbarui');
    }

    public function delete($id)
    {
        // Mengambil data pengajuan
        $pengajuan = MstPengajuanSubcont::findOrFail($id);

        // Hapus file jika ada
        if ($pengajuan->file && File::exists(public_path($pengajuan->file))) {
            File::delete(public_path($pengajuan->file));
        }

        // Hapus data dari database
        $pengajuan->delete();

        // Return response untuk AJAX
        return response()->json(['message' => 'Data berhasil dihapus'], 200);
    }

    public function kirimSales($id)
    {
        // Mengambil data pengajuan dari model MstPengajuanSubcont
        $pengajuan = MstPengajuanSubcont::findOrFail($id);

        // Update status_1 dan status_2 menjadi 2
        $pengajuan->update([
            'status_1' => 2,
            'status_2' => 2
        ]);

        // Simpan data ke model TrsPengajuanSubcont
        TrsPengajuanSubcont::create([
            'id_subcont' => $pengajuan->id, // Mengambil id dari MstPengajuanSubcont
            'status' => 2, // Status baru
            'modified_at' => Auth::user()->name // Mengambil nama user yang sedang login
        ]);

        // Return response untuk AJAX
        return response()->json(['message' => 'Status berhasil diubah'], 200);
    }

    public function kirimProc($id)
    {
        // Mengambil data pengajuan dari model MstPengajuanSubcont
        $pengajuan = MstPengajuanSubcont::findOrFail($id);

        // Update status_1 dan status_2 menjadi 2
        $pengajuan->update([
            'status_1' => 3,
            'status_2' => 3
        ]);

        // Simpan data ke model TrsPengajuanSubcont
        TrsPengajuanSubcont::create([
            'id_subcont' => $pengajuan->id, // Mengambil id dari MstPengajuanSubcont
            'status' => 3, // Status baru
            'modified_at' => Auth::user()->name // Mengambil nama user yang sedang login
        ]);

        // Return response untuk AJAX
        return response()->json(['message' => 'Status berhasil diubah'], 200);
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

        // Update status_1, status_2, dan quotation_file dalam model MstPengajuanSubcont
        $pengajuan->update([
            'status_1' => 4,
            'status_2' => 4,
            'quotation_file' => $quotationFilePath // Update kolom quotation_file dengan path file
        ]);

        // Simpan data ke model TrsPengajuanSubcont, termasuk keterangan dari request
        TrsPengajuanSubcont::create([
            'id_subcont' => $pengajuan->id, // Mengambil id dari MstPengajuanSubcont
            'status' => 4, // Status baru
            'keterangan' => $request->input('keterangan'), // Menyimpan keterangan yang dikirim dari AJAX
            'modified_at' => Auth::user()->name, // Mengambil nama user yang sedang login
            'quotation_file_path' => $quotationFilePath // Menyimpan path file jika ada
        ]);

        // Return response untuk AJAX
        return response()->json(['message' => 'Status dan keterangan berhasil disimpan' . ($quotationFilePath ? ' dengan file' : '')], 200);
    }

    public function FinishProc($id)
    {
        // Mengambil data pengajuan dari model MstPengajuanSubcont
        $pengajuan = MstPengajuanSubcont::findOrFail($id);

        // Update status_1 dan status_2 menjadi 2
        $pengajuan->update([
            'status_1' => 5,
            'status_2' => 5
        ]);

        // Simpan data ke model TrsPengajuanSubcont
        TrsPengajuanSubcont::create([
            'id_subcont' => $pengajuan->id, // Mengambil id dari MstPengajuanSubcont
            'status' => 5, // Status baru
            'modified_at' => Auth::user()->name // Mengambil nama user yang sedang login
        ]);

        // Return response untuk AJAX
        return response()->json(['message' => 'Status berhasil diubah'], 200);
    }
}
