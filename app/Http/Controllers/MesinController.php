<?php

namespace App\Http\Controllers;

use App\Models\FormFPP;
use Illuminate\Support\Facades\Storage;
use App\Models\Mesin;
use App\Models\Sparepart;
use App\Models\DetailPreventive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class MesinController extends Controller
{
    public function index()
    {
        $mesins = Mesin::orderBy('updated_at', 'desc')->get();

        return view('mesins.index', compact('mesins'))->with('i', (request()->input('page', 1) - 1) * 5);
    }


    public function dashboardGAMesin()
    {
        $mesins = Mesin::orderBy('updated_at', 'desc')->get();

        return view('ga.dashmesin', compact('mesins'))->with('i', (request()->input('page', 1) - 1) * 5);
    }


    public function showMesinGA(Mesin $mesin, FormFPP $formperbaikan, Sparepart $sparepart)
    {
        // Mengambil formperbaikans berdasarkan status 3 dan nomor_mesin dari mesin yang sama dengan mesin di formperbaikan
        $formperbaikans = FormFPP::where('status', '3')
            ->where('mesin', $mesin->no_mesin)
            ->orderBy('created_at', 'DESC')
            ->get();

        // Mengambil daftar sparepart berdasarkan nomor mesin
        $spareparts = Sparepart::where('nomor_mesin', $mesin->no_mesin)->get();

        return view('ga.showMesin', compact('mesin', 'formperbaikan', 'formperbaikans', 'spareparts', 'sparepart'));
    }

    public function create()
    {
        $mesins = Mesin::orderBy('updated_at', 'asc')->get();
        return view('mesins.create', compact('mesins'));
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'foto' => 'image|mimes:jpeg,png,jpg,gif|max:15360', // Hanya menerima format gambar dengan ukuran maksimal 4MB
            'sparepart' => 'image|mimes:jpeg,png,jpg,gif|max:15360', // Sesuaikan dengan kebutuhan Anda
        ]);

        // Merge nilai default untuk status ke dalam request
        $request->merge(['status' => $request->status ?? 0]);

        // Pindahkan foto ke direktori public/assets/foto
        if ($request->hasFile('foto')) {
            $foto = $request->file('foto');
            $fotoName = $foto->getClientOriginalName();
            $foto->move(public_path('assets/foto'), $fotoName);
            $fotoPath = 'assets/foto/' . $fotoName;
        } else {
            $fotoPath = null;
        }

        // Pindahkan sparepart ke direktori public/assets/sparepart
        if ($request->hasFile('sparepart')) {
            $sparepart = $request->file('sparepart');
            $sparepartName = $sparepart->getClientOriginalName();
            $sparepart->move(public_path('assets/sparepart'), $sparepartName);
            $sparepartPath = 'assets/sparepart/' . $sparepartName;
        } else {
            $sparepartPath = null;
        }

        // Simpan data mesin beserta path foto dan sparepart ke database
        Mesin::create([
            'section' => $request->section,
            'no_mesin' => $request->no_mesin,
            'tipe' => $request->tipe,
            'tanggal_dibuat' => $request->tanggal_dibuat,
            'umur' => $request->umur,
            'spesifikasi' => $request->spesifikasi,
            'lokasi' => $request->lokasi,
            'tanggal_preventif' => $request->tanggal_preventif,
            'foto' => $fotoPath,
            'sparepart' => $sparepartPath,
            'status' => $request->status // Nilai status akan diambil dari request, jika tidak ada, nilai default akan digunakan
        ]);

        return redirect()->route('mesins.index')->with('success', 'Mesin created successfully');
    }
    public function update(Request $request, Mesin $mesin, DetailPreventive $detail)
    {
        // Validasi foto
        if ($request->hasFile('foto')) {
            $request->validate([
                'foto' => 'max:10000', // Maksimal 10MB
            ]);
        }

        // Validasi sparepart
        if ($request->hasFile('sparepart')) {
            $request->validate([
                'sparepart' => 'max:10000', // Maksimal 10MB
            ]);
        }
        // Cek apakah ada file foto yang diunggah
        if ($request->hasFile('foto')) {
            // Menghapus foto lama jika ada
            if ($mesin->foto) {
                $oldFotoPath = public_path('assets/' . $mesin->foto);
                if (file_exists($oldFotoPath)) {
                    unlink($oldFotoPath);
                }
            }

            // Simpan foto baru
            $foto = $request->file('foto');
            $fotoName = $foto->getClientOriginalName();
            $fotoPath = $foto->move(public_path('assets/foto'), $fotoName);

            // Perbarui path foto di database
            $mesin->foto = 'foto/' . $fotoName;
        }

        // Cek apakah ada file foto yang diunggah
        if ($request->hasFile('sparepart')) {
            // Menghapus foto lama jika ada
            if ($mesin->sparepart) {
                $oldFotoPath = public_path('assets/' . $mesin->sparepart);
                if (file_exists($oldFotoPath)) {
                    unlink($oldFotoPath);
                }
            }
            // Simpan foto baru
            $sparepart = $request->file('sparepart');
            $sparepartName = $sparepart->getClientOriginalName();
            $sparepartPath = $sparepart->move(public_path('assets/sparepart'), $sparepartName);

            // Perbarui path foto di database
            $mesin->sparepart = 'sparepart/' . $sparepartName;
        }

        // Update data mesin
        $mesin->update([
            'section' => $request->section ?? $mesin->section,
            'no_mesin' => $request->no_mesin ?? $mesin->no_mesin,
            'tipe' => $request->tipe ?? $mesin->tipe,
            'tanggal_dibuat' => $request->tanggal_dibuat ?? $mesin->tanggal_dibuat,
            'umur' => $request->umur ?? $mesin->umur,
            'spesifikasi' => $request->spesifikasi ?? $mesin->spesifikasi,
            'lokasi' => $request->lokasi ?? $mesin->lokasi,
            'tanggal_preventif' => $request->tanggal_preventif ?? $mesin->tanggal_preventif,
            'foto' => $request->fotoPath ?? $mesin->foto,
            'sparepart' => $request->sparepartPath ?? $mesin->sparepart,
        ]);
        // Simpan perubahan ke dalam database
        $mesin->save();

        return redirect()->route('mesins.index')->with('success', 'Mesin updated successfully');
    }

    public function show(Mesin $mesin, FormFPP $formperbaikan, Sparepart $sparepart)
    {
        // Mengambil formperbaikans berdasarkan status 3 dan nomor_mesin dari mesin yang sama dengan mesin di formperbaikan
        $formperbaikans = FormFPP::where('status', '3')
            ->where('mesin', $mesin->no_mesin)
            ->orderBy('created_at', 'DESC')
            ->get();

        // Mengambil daftar sparepart berdasarkan nomor mesin
        $spareparts = Sparepart::where('nomor_mesin', $mesin->no_mesin)->get();

        return view('mesins.show', compact('mesin', 'formperbaikan', 'formperbaikans', 'spareparts', 'sparepart'));
    }

    public function edit(Mesin $mesin)
    {
        return view('mesins.edit', compact('mesin'));
    }

    public function destroy(Mesin $mesin)

    {
        $mesin->delete();
        return redirect()->route('mesins.index')->with('success', 'Mesin deleted successfully');
    }

    // public function editIssue(Mesin $mesin)
    // {
    //     // Ambil semua data issue dan issue_checked dari tabel detail_preventives
    //     $detailPreventives = DB::table('detail_preventives')
    //         ->join('mesins', 'detail_preventives.id_mesin', '=', 'mesins.id')
    //         ->where('mesins.id', $mesin->id)
    //         ->select('detail_preventives.issue', 'detail_preventives.issue_checked')
    //         ->get();

    //     // Ubah hasil query menjadi array asosiatif
    //     $issues = [];
    //     $checkedIssues = [];
    //     foreach ($detailPreventives as $detailPreventive) {
    //         $issues[] = $detailPreventive->issue;
    //         $checkedIssues[] = $detailPreventive->issue_checked;
    //     }

    //     // Kirimkan data ke view
    //     return view('maintenance.issue', compact('mesin', 'issues', 'checkedIssues'));
    // }

    // public function lihatIssue(Mesin $mesin)
    // {
    //     // Ambil nilai issue dan issue_checked berdasarkan id_mesin dari Mesin
    //     $issues = DB::table('detail_preventives')
    //         ->join('mesins', 'detail_preventives.id_mesin', '=', 'mesins.id')
    //         ->where('mesins.id', $mesin->id)
    //         ->pluck('detail_preventives.issue')
    //         ->toArray();

    //     // Ambil nilai issue_checked
    //     $checkedIssues = DB::table('detail_preventives')
    //         ->join('mesins', 'detail_preventives.id_mesin', '=', 'mesins.id')
    //         ->where('mesins.id', $mesin->id)
    //         ->pluck('detail_preventives.issue_checked')
    //         ->toArray();

    //     // Kirimkan data ke view
    //     return view('deptmtce.lihatissue', compact('mesin', 'issues', 'checkedIssues'));
    // }

    // public function lihatPerbaikan(Mesin $mesin)
    // {
    //     // Ambil nilai perbaikan berdasarkan id_mesin dari Mesin
    //     $perbaikans = DB::table('detail_preventives')
    //         ->join('mesins', 'detail_preventives.id_mesin', '=', 'mesins.id')
    //         ->where('mesins.id', $mesin->id)
    //         ->pluck('detail_preventives.perbaikan')
    //         ->toArray();

    //     // Ambil nilai issue_checked
    //     $checkedPerbaikans = DB::table('detail_preventives')
    //         ->join('mesins', 'detail_preventives.id_mesin', '=', 'mesins.id')
    //         ->where('mesins.id', $mesin->id)
    //         ->pluck('detail_preventives.perbaikan_checked')
    //         ->toArray();

    //     return view('deptmtce.lihatperbaikan', compact('mesin', 'perbaikans', 'checkedPerbaikans'));
    // }

    // public function editPerbaikan(Mesin $mesin)
    // {
    //     // Ambil nilai perbaikan berdasarkan id_mesin dari Mesin
    //     $perbaikans = DB::table('detail_preventives')
    //         ->join('mesins', 'detail_preventives.id_mesin', '=', 'mesins.id')
    //         ->where('mesins.id', $mesin->id)
    //         ->pluck('detail_preventives.perbaikan')
    //         ->toArray();

    //     // Ambil nilai issue_checked
    //     $checkedPerbaikans = DB::table('detail_preventives')
    //         ->join('mesins', 'detail_preventives.id_mesin', '=', 'mesins.id')
    //         ->where('mesins.id', $mesin->id)
    //         ->pluck('detail_preventives.perbaikan_checked')
    //         ->toArray();

    //     return view('maintenance.perbaikan', compact('mesin', 'perbaikans', 'checkedPerbaikans'));
    // }




    // public function updatePreventive(Request $request, Mesin $mesin)
    // {
    //     // Update the form data
    //     $mesin->update($request->all());
    //     $confirmed_preventive = $request->input('confirmed_preventive');

    //     // Check if 'confirmed_finish' is submitted
    //     if ($confirmed_preventive === '1') {
    //         $mesin->update(['status' => '1']);
    //         $mesin->save();
    //     }

    //     return redirect()->route('maintenance.dashpreventive')->with('success', 'Preventive updated successfully');
    // }


    // public function getIssue(Mesin $mesin)
    // {
    //     // Ambil data issue untuk mesin tertentu
    //     $issues = $mesin->issues()->get(); // Anda harus menyesuaikan dengan relasi antara Mesin dan Issue

    //     // Format data issue sesuai kebutuhan
    //     $formattedIssues = $issues->map(function ($issue) {
    //         return [
    //             'id' => $issue->id,
    //             'nama' => $issue->nama, // Sesuaikan dengan atribut yang sesuai dengan nama issue
    //             'checked' => $issue->pivot->checked // Jika Anda menggunakan tabel pivot untuk relasi many-to-many
    //         ];
    //     });

    //     // Kembalikan data dalam format JSON
    //     return response()->json($formattedIssues);
    // }
}
