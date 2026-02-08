<?php

namespace App\Http\Controllers;

use App\Models\MstEntertain;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class EntertainController extends Controller
{
    public function index()
    {
        // Ambil hanya user yang ada di tabel mst_ent untuk filter dropdown
        $users = User::whereIn('id', function($query) {
            $query->select('user_id')
                  ->from('mst_ent')
                  ->distinct();
        })->orderBy('name', 'asc')->get();
        
        return view('entertain.index', compact('users'));
    }

    public function getData(Request $request)
    {
        // Base query dengan join ke tabel users - hanya tampilkan yang is_active = 1
        $query = MstEntertain::with('user')
            ->select('mst_ent.*')
            ->where('is_active', 1);

        // Total records tanpa filter (hanya yang is_active = 1)
        $recordsTotal = MstEntertain::where('is_active', 1)->count();

        // Apply filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tgl', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('nama_perusahaan')) {
            $query->where('nama_perusahaan', 'like', '%' . $request->nama_perusahaan . '%');
        }

        // Search functionality - hanya untuk kolom tertentu (bukan nama_perusahaan)
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('tempat', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%")
                    ->orWhere('jenis', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%")
                    ->orWhere('jenis_usaha', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Total records setelah filter
        $recordsFiltered = $query->count();

        // Sorting
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');
        
        $columns = ['id', 'user_id', 'tgl', 'tempat', 'alamat', 'jenis', 'jumlah', 'nama', 'posisi', 'nama_perusahaan', 'jenis_usaha', 'is_active', 'status'];
        
        if (isset($columns[$orderColumnIndex])) {
            $query->orderBy($columns[$orderColumnIndex], $orderDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        if ($length != -1) {
            $query->skip($start)->take($length);
        }

        $data = $query->get();

        // Format data untuk DataTables
        $formattedData = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'user_name' => $item->user ? $item->user->name : '-',
                'tgl' => $item->tgl ? date('d-m-Y', strtotime($item->tgl)) : '-',
                'tempat' => $item->tempat ?? '-',
                'alamat' => $item->alamat ?? '-',
                'jenis' => $item->jenis ?? '-',
                'jumlah' => $item->jumlah ?? '-',
                'nama' => $item->nama ?? '-',
                'posisi' => $item->posisi ?? '-',
                'nama_perusahaan' => $item->nama_perusahaan ?? '-',
                'jenis_usaha' => $item->jenis_usaha ?? '-',
                'status' => $item->status == 1 ? '<span class="badge bg-warning">Open</span>' : '<span class="badge bg-success">Finish</span>',
                'actions' => '<button class="btn btn-sm btn-primary" onclick="downloadRow(' . $item->id . ')" title="Download"><i class="bi bi-download"></i></button>',
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $formattedData
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tgl' => 'required|date',
            'tempat' => 'required|string|max:50',
            'alamat' => 'required|string|max:255',
            'jenis' => 'required|string|max:30',
            'jumlah' => 'required|string|max:50',
            'nama' => 'required|string|max:50',
            'posisi' => 'required|string|max:25',
            'nama_perusahaan' => 'required|string|max:50',
            'jenis_usaha' => 'required|string|max:50',
        ]);

        $entertain = MstEntertain::create([
            'user_id' => Auth::id(), // Ambil dari user yang sedang login
            'tgl' => $request->tgl,
            'tempat' => $request->tempat,
            'alamat' => $request->alamat,
            'jenis' => $request->jenis,
            'jumlah' => $request->jumlah,
            'nama' => $request->nama,
            'posisi' => $request->posisi,
            'nama_perusahaan' => $request->nama_perusahaan,
            'jenis_usaha' => $request->jenis_usaha,
            'is_active' => 1,
            'status' => 1, // Status awal: 1 = Open
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil disimpan',
            'data' => $entertain
        ]);
    }

    public function download($id)
    {
        // Ambil data berdasarkan ID
        $entertain = MstEntertain::with('user')->findOrFail($id);
        
        // Jika status = 1 (Open), ubah menjadi 2 (Finish)
        if ($entertain->status == 1) {
            $entertain->update(['status' => 2]);
        }
        
        // Generate PDF untuk preview
        $tahunPajak = date('Y', strtotime($entertain->tgl));
        
        $pdf = PDF::loadView('entertain.pdf', [
            'data' => [$entertain], // Kirim sebagai array untuk konsistensi dengan template
            'tahunPajak' => $tahunPajak
        ]);

        $pdf->setPaper('A4', 'landscape');

        // Stream PDF ke browser (preview di tab baru)
        return $pdf->stream('Entertainment_' . $entertain->id . '_' . date('Y-m-d_His') . '.pdf');
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $userId = $request->input('user_id');
        $namaPerusahaan = $request->input('nama_perusahaan');

        // Query data berdasarkan filter
        $query = MstEntertain::with('user');

        if ($startDate && $endDate) {
            $query->whereBetween('tgl', [$startDate, $endDate]);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($namaPerusahaan) {
            $query->where('nama_perusahaan', 'like', '%' . $namaPerusahaan . '%');
        }

        $data = $query->orderBy('tgl', 'desc')->get();

        // Ambil tahun pajak dari filter atau tahun sekarang
        $tahunPajak = $startDate ? date('Y', strtotime($startDate)) : date('Y');

        $pdf = PDF::loadView('entertain.pdf', [
            'data' => $data,
            'tahunPajak' => $tahunPajak
        ]);

        $pdf->setPaper('A4', 'landscape');

        // Stream PDF ke browser (preview di tab baru) - bukan download
        return $pdf->stream('Daftar_Nominatif_Entertainment_' . date('Y-m-d_His') . '.pdf');
    }
}
