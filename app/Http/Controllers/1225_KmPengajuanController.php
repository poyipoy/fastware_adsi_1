<?php

namespace App\Http\Controllers;

use App\Models\Insight;
use App\Models\KmKategori;
use App\Models\KmLihatBuku;
use App\Models\KmPengajuan;
use App\Models\KmSuka;
use App\Models\KmTransaksi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KmPengajuanController extends Controller
{
    public function pengajuanKM()
    {
        $km = KmPengajuan::with('user')->get();

        return view('knowlege_management.pengajuanKM', compact('km'));
    }

    public function persetujuanKM()
    {
        $km = KmPengajuan::with('user', 'kmKategori')->get();

        return view('knowlege_management.persetujuanKM', compact('km'));
    }

    public function storeKM(Request $request)
    {
        $request->validate([
            'judul' => 'required',
            'keterangan' => 'required',
            'file' => 'required|file|mimes:pdf,ppt,pptx|max:12048',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:12048', // validation for image
        ]);

        // Ensure the directory exists
        $destinationPath = public_path('assets/image');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $hashName = $file->hashName();
            $file->move($destinationPath, $hashName);
            $originalName = $file->getClientOriginalName();
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageHashName = $image->hashName();
            $image->move($destinationPath, $imageHashName);
        }

        KmPengajuan::create([
            'id_user' => auth()->id(),
            'judul' => $request->judul,
            'keterangan' => $request->keterangan,
            'file' => $hashName ?? null,
            'file_name' => $originalName ?? null,
            'image' => $imageHashName ?? null, // save image name to image field
            'persetujuan' => 1, // Set default value for status_baca
            'status' => 1, // Set default value for status
        ]);

        return redirect()->route('pengajuanKM')->with('success', 'Knowledge Management created successfully.');
    }

    public function edit($id)
    {
        // Temukan KM berdasarkan id
        $km = KmPengajuan::findOrFail($id);

        // Mengirim data dalam respons JSON
        return response()->json($km);
    }

    public function update(Request $request)
    {
        // Validasi request
        $request->validate([
            'id' => 'required|exists:km_pengajuans,id',
            'judul' => 'required',
            'keterangan' => 'required',
            'file' => 'nullable|mimes:ppt,pptx,pdf', // tambahkan validasi jika ada file baru diunggah
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // validation for image
        ]);

        // Cari KM berdasarkan ID
        $km = KmPengajuan::findOrFail($request->id);

        // Simpan perubahan judul dan keterangan
        $km->judul = $request->judul;
        $km->keterangan = $request->keterangan;

        // Proses jika ada file yang diunggah baru
        if ($request->hasFile('file')) {
            // Hapus file yang sudah ada jika ada
            if ($km->file) {
                $existingFilePath = public_path('assets/image/'.$km->file);
                if (file_exists($existingFilePath)) {
                    unlink($existingFilePath);
                }
            }

            // Upload file baru
            $file = $request->file('file');
            $fileName = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('assets/image'), $fileName);

            // Simpan informasi file baru
            $km->file_name = $file->getClientOriginalName();
            $km->file = $fileName;
        }

        // Proses jika ada image baru yang diunggah
        if ($request->hasFile('image')) {
            // Hapus image yang sudah ada jika ada
            if ($km->image) {
                $existingThumbnailPath = public_path('assets/image/'.$km->image);
                if (file_exists($existingThumbnailPath)) {
                    unlink($existingThumbnailPath);
                }
            }

            // Upload image baru
            $image = $request->file('image');
            $imageHashName = time().'_'.$image->getClientOriginalName();
            $image->move(public_path('assets/image'), $imageHashName);

            // Simpan informasi image baru
            $km->image = $imageHashName;
        }

        // Simpan perubahan ke database
        $km->save();

        // Redirect atau response jika berhasil disimpan
        return redirect()->back()->with('success', 'Data KM berhasil diperbarui.');
    }

    public function updateStatus($id)
    {
        try {
            // Cari data KM berdasarkan ID
            $km = KmPengajuan::findOrFail($id);

            // Update status menjadi 0
            $km->update(['persetujuan' => 0]);
            $km->update(['status' => 0]);

            return response()->json(['message' => 'Status data berhasil diperbarui'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui status data: '.$e->getMessage()], 500);
        }
    }

    public function kirimKM($id)
    {
        try {
            // Find the item by ID
            $km = KmPengajuan::find($id);

            if (!$km) {
                return response()->json(['message' => 'Item not found'], 404);
            }

            // Update the status to '2'
            $km->status = 2;
            $km->save();

            return response()->json(['message' => 'Status updated successfully'], 200);
        } catch (\Exception $e) {
            // Log the error message for debugging
            \Log::error('Error updating status: '.$e->getMessage());

            // Return a JSON response with an error message
            return response()->json(['message' => 'Terjadi kesalahan saat mengupdate status.'], 500);
        }
    }

    public function showPersetujuan($id)
    {
        // Temukan KM berdasarkan id
        $km = KmPengajuan::with('kmKategori')->findOrFail($id);
        $kategoris = KmKategori::all();

        // Mengirim data dalam respons JSON
        return response()->json([
            'km' => $km,
            'kategoris' => $kategoris,
        ]);
    }

    public function approveKM(Request $request)
    {
        // Validasi request
        $request->validate([
            'id' => 'required|exists:km_pengajuans,id',
            'posisi' => 'required',
            'id_km_kategori' => 'required',
            'judul' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        // Cari KM berdasarkan ID
        $km = KmPengajuan::findOrFail($request->id);

        // Simpan perubahan posisi, kategori, judul, dan keterangan
        $km->posisi = $request->posisi;
        $km->id_km_kategori = $request->id_km_kategori;
        $km->judul = $request->judul;
        $km->keterangan = $request->keterangan;

        // Debug: Log request data
        \Log::info('Request Data:', $request->all());

        // Set status berdasarkan tombol yang diklik
        if ($request->has('approve')) {
            $km->status = 3; // Disetujui
            $km->persetujuan = 2; // Disetujui
            \Log::info('Approved KM:', ['id' => $request->id]);
        } elseif ($request->has('reject')) {
            $km->status = 1; // Ditolak
            \Log::info('Rejected KM:', ['id' => $request->id]);
        } else {
            \Log::info('No Action Taken:', ['id' => $request->id]);
        }

        // Simpan perubahan ke database
        $km->save();

        // Redirect atau response jika berhasil disimpan
        return redirect()->back()->with('success', 'Data KM berhasil diperbarui.');
    }

    public function dsKnowlege()
    {
        $user = auth()->user();
        $roleId = $user->role_id;

        // Base query
        $query = KmPengajuan::with(['kmKategori', 'insights.user', 'kmLihatBukus'])
            ->with(['kmTransaksi' => function ($query) use ($user) {
                $query->where('id_user', $user->id);
            }])
            ->withCount(['kmTransaksi' => function ($query) use ($user) {
                $query->where('id_user', $user->id);
            }])
            ->withCount('kmSukas');

        // Modify query based on role_id
        if (in_array($roleId, [2, 5, 10, 11])) {
            $pengajuans = $query->whereIn('posisi', ['Dept. Head', 'Sec. Head', 'All Employee'])
                ->where('status', 3)
                ->get();
        } elseif (in_array($roleId, [3, 9, 12, 14, 22, 30, 31, 32])) {
            $pengajuans = $query->whereIn('posisi', ['Sec. Head', 'All Employee'])
                ->where('status', 3)
                ->get();
        } elseif (in_array($roleId, [1, 14, 15])) {
            $pengajuans = $query->where('status', 3)
                ->get();
        } else {
            $pengajuans = $query->whereIn('posisi', ['All Employee'])
                ->where('status', 3)
                ->get();
        }

        $leaderboard = User::select('name', 'km_total_poin')
            ->orderBy('km_total_poin', 'desc')
            ->get();

        return view('dashboard.dsKnowlege', compact('pengajuans', 'leaderboard'));
    }

    public function downloadPdf($id)
    {
        $pengajuan = KmPengajuan::findOrFail($id);
        $filePath = storage_path('app/'.$pengajuan->file); // Pastikan path ini sesuai dengan lokasi file Anda

        return response()->file($filePath);
    }

    public function markAsRead(Request $request)
    {
        $id_km_pengajuan = $request->input('id_km_pengajuan');
        $user_id = auth()->id();

        // Periksa apakah rekaman KmTransaksi sudah ada untuk pengguna dan pengajuan
        $existingRecord = KmTransaksi::where('id_km_pengajuan', $id_km_pengajuan)
                                     ->where('id_user', $user_id)
                                     ->first();

        if (!$existingRecord) {
            // Buat rekaman KmTransaksi baru jika tidak ada
            KmTransaksi::create([
                'id_km_pengajuan' => $id_km_pengajuan,
                'id_user' => $user_id,
                'status' => 2,
                'modified_by' => $user_id,
            ]);
        } elseif ($existingRecord->status != 3) {
            // Perbarui status menjadi 2 hanya jika status saat ini bukan 3
            $existingRecord->update([
                'status' => 2,
                'modified_by' => $user_id,
            ]);
        }

        // Periksa apakah rekaman KmLihatBuku ada untuk id_km_pengajuan yang diberikan
        $lihatBukuRecord = KmLihatBuku::where('id_km_pengajuan', $id_km_pengajuan)->first();

        if ($lihatBukuRecord) {
            // Tambahkan nilai jumlah_lihat jika rekaman ada
            $lihatBukuRecord->increment('jumlah_lihat');
        } else {
            // Buat rekaman KmLihatBuku baru jika tidak ada
            KmLihatBuku::create([
                'id_km_pengajuan' => $id_km_pengajuan,
                'jumlah_lihat' => 1,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function saveTransaction(Request $request)
    {
        $id_km_pengajuan = $request->input('id_km_pengajuan');
        $user_id = Auth::id(); // Assuming the user is authenticated

        // Fetch the pengajuan and its associated category
        $kmPengajuan = KmPengajuan::find($id_km_pengajuan);

        if (!$kmPengajuan) {
            return response()->json(['success' => false, 'message' => 'Pengajuan not found.']);
        }

        // Assuming KmPengajuan has a 'kmKategori' relationship
        $kategori = $kmPengajuan->kmKategori;

        if (!$kategori) {
            return response()->json(['success' => false, 'message' => 'Category not found.']);
        }

        $poin_kategori = $kategori->poin_kategori;

        // Find the existing KmTransaksi record
        $kmTransaksi = KmTransaksi::where('id_km_pengajuan', $id_km_pengajuan)
                                  ->where('id_user', $user_id)
                                  ->first();

        if ($kmTransaksi) {
            // Update the existing record
            $kmTransaksi->status = 3; // Status indicating that the PDF was read
            $kmTransaksi->modified_by = $user_id;
            $kmTransaksi->save();
        } else {
            // Create a new record
            $kmTransaksi = new KmTransaksi();
            $kmTransaksi->id_km_pengajuan = $id_km_pengajuan;
            $kmTransaksi->id_user = $user_id;
            $kmTransaksi->level = 0; // Set default level or calculate based on your logic
            $kmTransaksi->status = 3; // Status indicating that the PDF was read
            $kmTransaksi->modified_by = $user_id;

            // Save the new record to the database
            $kmTransaksi->save();
        }

        // Update the user's total points
        $user = User::find($user_id);
        $user->km_total_poin += $poin_kategori;
        $user->save();

        return response()->json(['success' => true]);
    }

    public function like(Request $request)
    {
        $request->validate([
            'id_km_pengajuan' => 'required|integer|exists:km_pengajuans,id',
        ]);

        $like = KmSuka::firstOrCreate([
            'id_user' => Auth::id(),
            'id_km_pengajuan' => $request->id_km_pengajuan,
        ]);

        if ($like->wasRecentlyCreated) {
            return response()->json(['message' => 'Liked successfully', 'like_count' => $this->getLikeCount($request->id_km_pengajuan)], 201);
        } else {
            return response()->json(['message' => 'Already liked', 'like_count' => $this->getLikeCount($request->id_km_pengajuan)], 200);
        }
    }

    public function unlike(Request $request)
    {
        $request->validate([
            'id_km_pengajuan' => 'required|integer|exists:km_pengajuans,id',
        ]);

        $deleted = KmSuka::where('id_user', Auth::id())
                          ->where('id_km_pengajuan', $request->id_km_pengajuan)
                          ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Unliked successfully', 'like_count' => $this->getLikeCount($request->id_km_pengajuan)], 200);
        } else {
            return response()->json(['message' => 'Not liked yet', 'like_count' => $this->getLikeCount($request->id_km_pengajuan)], 400);
        }
    }

    private function getLikeCount($id_km_pengajuan)
    {
        return KmSuka::where('id_km_pengajuan', $id_km_pengajuan)->count();
    }

    public function addInsight(Request $request)
    {
        $request->validate([
            'id_km_pengajuan' => 'required|exists:km_pengajuans,id',
            'content' => 'required|string',
        ]);

        $user = auth()->user();

        Insight::create([
            'id_user' => $user->id,
            'id_km_pengajuan' => $request->id_km_pengajuan,
            'content' => $request->content,
        ]);

        return back()->with('success', 'Insight added successfully');
    }
}
