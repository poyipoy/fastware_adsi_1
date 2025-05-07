<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\MstPoPengajuan;
use App\Models\TrsPoPengajuan;

class PoPengajuanController extends Controller
{
    //
    // Method untuk menampilkan data ke view
    public function indexPoPengajuan()
    {
        // Mendapatkan role_id dari pengguna yang sedang login
        $roleId = auth()->user()->role_id;

        // Jika role_id adalah 1, 14, atau 15, ambil semua data
        if (in_array($roleId, [1, 14, 15, 41, 54])) {
            $data = MstPoPengajuan::leftJoin('trs_po_pengajuans as trs', function ($join) {
                $join->on('trs.id_fpb', '=', 'mst_po_pengajuans.id')
                    ->whereRaw('trs.updated_at = (SELECT MAX(updated_at) FROM trs_po_pengajuans WHERE trs_po_pengajuans.id_fpb = mst_po_pengajuans.id)');
            }) // LEFT JOIN dengan filter subquery untuk mengambil baris terbaru
                ->select(
                    'mst_po_pengajuans.no_fpb',
                    'mst_po_pengajuans.id',
                    'mst_po_pengajuans.modified_at',
                    'mst_po_pengajuans.kategori_po',
                    'mst_po_pengajuans.nama_barang',
                    'mst_po_pengajuans.catatan',
                    'mst_po_pengajuans.status_1',
                    'mst_po_pengajuans.status_2',
                    DB::raw('COALESCE(trs.updated_at, "-") as trs_updated_at')
                )
                ->orderBy('mst_po_pengajuans.status_1', 'asc')
                ->orderBy('mst_po_pengajuans.no_fpb', 'desc') // Urutan berdasarkan no_fpb
                ->orderBy('trs.updated_at', 'asc') // Urutan berdasarkan trs.updated_at
                ->get();
        } elseif ($roleId == 48) {
            // Jika role_id adalah 48, ambil data dengan modified_at bernilai 'MUGI PRAMONO'
            $data = MstPoPengajuan::where('mst_po_pengajuans.modified_at', 'MUGI PRAMONO')
                ->leftJoin('trs_po_pengajuans as trs', function ($join) {
                    $join->on('trs.id_fpb', '=', 'mst_po_pengajuans.id')
                        ->whereRaw('trs.updated_at = (SELECT MAX(updated_at) FROM trs_po_pengajuans WHERE trs_po_pengajuans.id_fpb = mst_po_pengajuans.id)');
                }) // LEFT JOIN dengan filter subquery untuk mengambil baris terbaru
                ->select(
                    'mst_po_pengajuans.no_fpb',
                    'mst_po_pengajuans.id',
                    'mst_po_pengajuans.modified_at',
                    'mst_po_pengajuans.kategori_po',
                    'mst_po_pengajuans.nama_barang',
                    'mst_po_pengajuans.catatan',
                    'mst_po_pengajuans.status_1',
                    'mst_po_pengajuans.status_2',
                    DB::raw('COALESCE(trs.updated_at, "-") as trs_updated_at')
                )
                ->orderBy('mst_po_pengajuans.status_1', 'asc')
                ->orderBy('mst_po_pengajuans.no_fpb', 'desc') // Urutan berdasarkan no_fpb
                ->orderBy('trs.updated_at', 'asc') // Urutan berdasarkan trs.updated_at
                ->get();
        } elseif ($roleId == 5) {
            // Jika role_id adalah 48, ambil data dengan modified_at bernilai 'MUGI PRAMONO'
            $data = MstPoPengajuan::where('mst_po_pengajuans.modified_at', ['MUGI PRAMONO', 'RAGIL ISHA RAHMANTO', 'ABDUR RAHMAN AL FAAIZ'])
                ->leftJoin('trs_po_pengajuans as trs', function ($join) {
                    $join->on('trs.id_fpb', '=', 'mst_po_pengajuans.id')
                        ->whereRaw('trs.updated_at = (SELECT MAX(updated_at) FROM trs_po_pengajuans WHERE trs_po_pengajuans.id_fpb = mst_po_pengajuans.id)');
                }) // LEFT JOIN dengan filter subquery untuk mengambil baris terbaru
                ->select(
                    'mst_po_pengajuans.no_fpb',
                    'mst_po_pengajuans.id',
                    'mst_po_pengajuans.modified_at',
                    'mst_po_pengajuans.kategori_po',
                    'mst_po_pengajuans.nama_barang',
                    'mst_po_pengajuans.catatan',
                    'mst_po_pengajuans.status_1',
                    'mst_po_pengajuans.status_2',
                    DB::raw('COALESCE(trs.updated_at, "-") as trs_updated_at')
                )
                ->orderBy('mst_po_pengajuans.status_1', 'asc')
                ->orderBy('mst_po_pengajuans.no_fpb', 'desc') // Urutan berdasarkan no_fpb
                ->orderBy('trs.updated_at', 'asc') // Urutan berdasarkan trs.updated_at
                ->get();
        } else {
            // Mendapatkan nama user yang sedang login
            $loggedInUserName = auth()->user()->name;

            $data = MstPoPengajuan::where('mst_po_pengajuans.modified_at', $loggedInUserName)
                ->leftJoin('trs_po_pengajuans as trs', function ($join) {
                    $join->on('trs.id_fpb', '=', 'mst_po_pengajuans.id')
                        ->whereRaw('trs.updated_at = (SELECT MAX(updated_at) FROM trs_po_pengajuans WHERE trs_po_pengajuans.id_fpb = mst_po_pengajuans.id)');
                }) // LEFT JOIN dengan filter subquery untuk mengambil baris terbaru
                ->select(
                    'mst_po_pengajuans.no_fpb',
                    'mst_po_pengajuans.id',
                    'mst_po_pengajuans.modified_at',
                    'mst_po_pengajuans.kategori_po',
                    'mst_po_pengajuans.nama_barang',
                    'mst_po_pengajuans.catatan',
                    'mst_po_pengajuans.status_1',
                    'mst_po_pengajuans.status_2',
                    DB::raw('COALESCE(trs.updated_at, "-") as trs_updated_at')
                )
                ->orderBy('mst_po_pengajuans.status_1', 'asc')
                ->orderBy('mst_po_pengajuans.no_fpb', 'desc') // Urutan berdasarkan no_fpb
                ->orderBy('trs.updated_at', 'asc') // Urutan berdasarkan trs.updated_at
                ->get();
        }

        // Mengirim data ke view
        return view('po_pengajuan.index_po_pengajuan', compact('data'));
    }

    public function indexPoDeptHead()
    {
        // Mendapatkan role_id dari pengguna yang sedang login
        $roleId = auth()->user()->role_id;

        // Jika role_id adalah 1, 14, atau 15, ambil semua data
        if (in_array($roleId, [1, 14, 15])) {
            $data = MstPoPengajuan::join('trs_po_pengajuans as trs', 'trs.id_fpb', '=', 'mst_po_pengajuans.id') // Join dengan TrsPoPengajuan untuk mendapatkan updated_at
                ->select(
                    'mst_po_pengajuans.no_fpb',
                    DB::raw('MAX(mst_po_pengajuans.id) as id'),
                    DB::raw('MAX(mst_po_pengajuans.modified_at) as modified_at'),
                    DB::raw('MAX(mst_po_pengajuans.kategori_po) as kategori_po'),
                    DB::raw('MAX(mst_po_pengajuans.catatan) as catatan_po'),
                    DB::raw('MAX(mst_po_pengajuans.status_1) as status_1'),
                    DB::raw('MAX(mst_po_pengajuans.status_2) as status_2'),
                    DB::raw('MAX(trs.updated_at) as trs_updated_at')
                )
                ->groupBy('mst_po_pengajuans.no_fpb') // Menambahkan GROUP BY untuk no_fpb
                ->orderBy(DB::raw('MAX(mst_po_pengajuans.status_1)'), 'asc') // Urutan berdasarkan status_1
                ->orderBy(DB::raw('MAX(trs.created_at)'), 'asc')
                ->get();
        } else {
            // Array mapping antara role_id dan nama yang diizinkan untuk ditampilkan
            $allowedNames = [];

            // Logika pemilihan nama berdasarkan role_id
            if ($roleId == 11 || $roleId == 14) {
                $allowedNames = ['JESSICA PAUNE', 'SITI MARIA ULFA', 'MUHAMMAD DINAR FARISI', 'MEDI KRISNANTO', 'VIVIAN ANGELIKA'];
            } elseif ($roleId == 5) {
                $allowedNames = ['MUGI PRAMONO', 'ABDUR RAHMAN AL FAAIZ', 'RAGIL ISHA RAHMANTO'];
            } elseif ($roleId == 2) {
                $allowedNames = ['ILHAM CHOLID', 'JUN JOHAMIN PD', 'HERY HERMAWAN', 'WULYO EKO PRASETYO', 'SENDY PRABOWO', 'YAN WELEM MANGINSELA', 'HEXAPA DARMADI', 'SARAH EGA BUDI ASTUTI', 'SONY STIAWAN', 'DIMAS ADITYA PRIANDANA', 'DANIA ISNAWATI', 'FRISILIA CLAUDIA HUTAMA', 'DWI KUNTORO', 'YUNASIS PALGUNADI', 'RISFAN FAISAL'];
            } elseif ($roleId == 7 || $roleId == 30) {
                $allowedNames = ['RANGGA FADILLAH', 'NURSALIM'];
            }

            // Mengambil data dari model MstPoPengajuan berdasarkan role_id dan nama yang diperbolehkan
            if (!empty($allowedNames)) {
                $data = MstPoPengajuan::whereIn('mst_po_pengajuans.modified_at', $allowedNames)
                    ->whereIn('mst_po_pengajuans.id', function ($query) {
                        $query->select(DB::raw('MAX(id)'))
                            ->from('mst_po_pengajuans')
                            ->groupBy('no_fpb');
                    })
                    ->join('trs_po_pengajuans as trs', 'trs.id_fpb', '=', 'mst_po_pengajuans.id')
                    ->select(
                        'mst_po_pengajuans.no_fpb',
                        DB::raw('MAX(mst_po_pengajuans.id) as id'),
                        DB::raw('MAX(mst_po_pengajuans.modified_at) as modified_at'),
                        DB::raw('MAX(mst_po_pengajuans.kategori_po) as kategori_po'),
                        DB::raw('MAX(mst_po_pengajuans.catatan) as catatan_po'),
                        DB::raw('MAX(mst_po_pengajuans.status_1) as status_1'),
                        DB::raw('MAX(mst_po_pengajuans.status_2) as status_2'),
                        DB::raw('MAX(trs.updated_at) as trs_updated_at')
                    )
                    ->groupBy('mst_po_pengajuans.no_fpb')
                    ->orderBy(DB::raw('MAX(mst_po_pengajuans.status_1)'), 'asc') // Urutan berdasarkan status_1
                    ->orderBy(DB::raw('MAX(trs.created_at)'), 'asc') // Urutan berdasarkan created_at
                    ->get();
            } else {
                $data = collect(); // atau bisa return redirect dengan pesan error
            }
        }

        // Mengirim data ke view
        return view('po_pengajuan.index_po_deptHead', compact('data'));
    }

    public function indexPoUser()
    {
        // Mendapatkan role_id dari pengguna yang sedang login
        $roleId = auth()->user()->role_id;

        // Jika role_id adalah 1, 14, atau 15, ambil semua data
        if (in_array($roleId, [1, 14, 15])) {
            $data = MstPoPengajuan::leftJoin('trs_po_pengajuans as trs', 'trs.id_fpb', '=', 'mst_po_pengajuans.id') // LEFT JOIN
                ->select(
                    'mst_po_pengajuans.no_fpb',
                    DB::raw('MAX(mst_po_pengajuans.id) as id'),
                    DB::raw('MAX(mst_po_pengajuans.no_po) as no_po'),
                    DB::raw('MAX(mst_po_pengajuans.kategori_po) as kategori_po'),
                    DB::raw('MAX(mst_po_pengajuans.nama_barang) as nama_barang'),
                    DB::raw('MAX(mst_po_pengajuans.qty) as qty'),
                    DB::raw('MAX(mst_po_pengajuans.pcs) as pcs'),
                    DB::raw('MAX(mst_po_pengajuans.price_list) as price_list'),
                    DB::raw('MAX(mst_po_pengajuans.total_harga) as total_harga'),
                    DB::raw('MAX(mst_po_pengajuans.spesifikasi) as spesifikasi'),
                    DB::raw('MAX(mst_po_pengajuans.file) as file'),
                    DB::raw('MAX(mst_po_pengajuans.file_name) as file_name'),
                    DB::raw('MAX(mst_po_pengajuans.amount) as amount'),
                    DB::raw('MAX(mst_po_pengajuans.rekomendasi) as rekomendasi'),
                    DB::raw('MAX(mst_po_pengajuans.due_date) as due_date'),
                    DB::raw('MAX(mst_po_pengajuans.target_cost) as target_cost'),
                    DB::raw('MAX(mst_po_pengajuans.lead_time) as lead_time'),
                    DB::raw('MAX(mst_po_pengajuans.nama_customer) as nama_customer'),
                    DB::raw('MAX(mst_po_pengajuans.nama_project) as nama_project'),
                    DB::raw('MAX(mst_po_pengajuans.catatan) as catatan'),
                    DB::raw('MAX(mst_po_pengajuans.status_1) as status_1'),
                    DB::raw('MAX(mst_po_pengajuans.status_2) as status_2'),
                    DB::raw('MAX(mst_po_pengajuans.created_at) as created_at'),
                    DB::raw('MAX(mst_po_pengajuans.modified_at) as modified_at'),
                    DB::raw('COALESCE(MAX(trs.updated_at), "-") as trs_updated_at')
                )
                ->groupBy('mst_po_pengajuans.no_fpb')
                ->orderBy(DB::raw('MAX(mst_po_pengajuans.status_1)'), 'asc') // Urutan berdasarkan status_1
                ->orderBy(DB::raw('MAX(trs.created_at)'), 'asc')
                ->get();
        } else {
            // Array untuk menyimpan kategori yang diperbolehkan untuk ditampilkan
            $allowedCategories = [];

            // Logika pemilihan kategori_po berdasarkan role_id
            if (in_array($roleId, [50, 30])) {
                $allowedCategories = ['Consumable', 'Spareparts', 'Indirect Material'];
            } elseif (in_array($roleId, [40, 14])) {
                $allowedCategories = ['IT'];
            } elseif (in_array($roleId, [39, 14])) {
                $allowedCategories = ['GA'];
            }

            // Mengambil data dari model MstPoPengajuan berdasarkan role_id dan kategori_po yang diperbolehkan
            if (!empty($allowedCategories)) {
                $data = MstPoPengajuan::whereIn('mst_po_pengajuans.kategori_po', $allowedCategories)
                    ->whereIn('mst_po_pengajuans.id', function ($query) {
                        $query->select(DB::raw('MAX(id)'))
                            ->from('mst_po_pengajuans')
                            ->groupBy('no_fpb');
                    })
                    ->leftJoin('trs_po_pengajuans as trs', 'trs.id_fpb', '=', 'mst_po_pengajuans.id')
                    ->select(
                        'mst_po_pengajuans.no_fpb',
                        DB::raw('MAX(mst_po_pengajuans.id) as id'),
                        DB::raw('MAX(mst_po_pengajuans.no_po) as no_po'),
                        DB::raw('MAX(mst_po_pengajuans.kategori_po) as kategori_po'),
                        DB::raw('MAX(mst_po_pengajuans.nama_barang) as nama_barang'),
                        DB::raw('MAX(mst_po_pengajuans.qty) as qty'),
                        DB::raw('MAX(mst_po_pengajuans.pcs) as pcs'),
                        DB::raw('MAX(mst_po_pengajuans.price_list) as price_list'),
                        DB::raw('MAX(mst_po_pengajuans.total_harga) as total_harga'),
                        DB::raw('MAX(mst_po_pengajuans.spesifikasi) as spesifikasi'),
                        DB::raw('MAX(mst_po_pengajuans.file) as file'),
                        DB::raw('MAX(mst_po_pengajuans.file_name) as file_name'),
                        DB::raw('MAX(mst_po_pengajuans.amount) as amount'),
                        DB::raw('MAX(mst_po_pengajuans.rekomendasi) as rekomendasi'),
                        DB::raw('MAX(mst_po_pengajuans.due_date) as due_date'),
                        DB::raw('MAX(mst_po_pengajuans.target_cost) as target_cost'),
                        DB::raw('MAX(mst_po_pengajuans.lead_time) as lead_time'),
                        DB::raw('MAX(mst_po_pengajuans.nama_customer) as nama_customer'),
                        DB::raw('MAX(mst_po_pengajuans.nama_project) as nama_project'),
                        DB::raw('MAX(mst_po_pengajuans.catatan) as catatan'),
                        DB::raw('MAX(mst_po_pengajuans.status_1) as status_1'),
                        DB::raw('MAX(mst_po_pengajuans.status_2) as status_2'),
                        DB::raw('MAX(mst_po_pengajuans.created_at) as created_at'),
                        DB::raw('MAX(mst_po_pengajuans.modified_at) as modified_at'),
                        DB::raw('COALESCE(MAX(trs.updated_at), "-") as trs_updated_at')
                    )
                    ->groupBy('mst_po_pengajuans.no_fpb')
                    ->orderBy(DB::raw('MAX(mst_po_pengajuans.status_1)'), 'asc') // Urutan berdasarkan status_1
                    ->orderBy(DB::raw('MAX(trs.created_at)'), 'asc') // Urutan berdasarkan created_at
                    ->get();

                // Jika terdapat banyak entri untuk no_fpb yang sama, ambil hanya yang pertama
                $data = $data->unique('no_fpb');
            } else {
                $data = collect(); // atau bisa return redirect dengan pesan error
            }
        }

        // Mengirim data ke view
        return view('po_pengajuan.index_po_user', compact('data'));
    }

    public function indexPoFinance()
    {
        // Mengambil data dari MstPoPengajuan dan TrsPoPengajuan
        $data = MstPoPengajuan::join('trs_po_pengajuans as trs', 'trs.id_fpb', '=', 'mst_po_pengajuans.id')
            ->select(
                'mst_po_pengajuans.no_fpb',
                DB::raw('MAX(mst_po_pengajuans.id) as id'), // Ambil ID maksimal dari MstPoPengajuan
                DB::raw('MAX(mst_po_pengajuans.no_po) as no_po'),
                DB::raw('MAX(mst_po_pengajuans.kategori_po) as kategori_po'),
                DB::raw('MAX(mst_po_pengajuans.nama_barang) as nama_barang'),
                DB::raw('MAX(mst_po_pengajuans.qty) as qty'),
                DB::raw('MAX(mst_po_pengajuans.pcs) as pcs'),
                DB::raw('MAX(mst_po_pengajuans.price_list) as price_list'),
                DB::raw('MAX(mst_po_pengajuans.total_harga) as total_harga'),
                DB::raw('MAX(mst_po_pengajuans.spesifikasi) as spesifikasi'),
                DB::raw('MAX(mst_po_pengajuans.file) as file'),
                DB::raw('MAX(mst_po_pengajuans.file_name) as file_name'),
                DB::raw('MAX(mst_po_pengajuans.amount) as amount'),
                DB::raw('MAX(mst_po_pengajuans.rekomendasi) as rekomendasi'),
                DB::raw('MAX(mst_po_pengajuans.due_date) as due_date'),
                DB::raw('MAX(mst_po_pengajuans.target_cost) as target_cost'),
                DB::raw('MAX(mst_po_pengajuans.lead_time) as lead_time'),
                DB::raw('MAX(mst_po_pengajuans.nama_customer) as nama_customer'),
                DB::raw('MAX(mst_po_pengajuans.nama_project) as nama_project'),
                DB::raw('MAX(mst_po_pengajuans.catatan) as catatan'),
                DB::raw('MAX(mst_po_pengajuans.status_1) as status_1'),
                DB::raw('MAX(mst_po_pengajuans.status_2) as status_2'),
                DB::raw('MAX(mst_po_pengajuans.created_at) as created_at'),
                DB::raw('MAX(mst_po_pengajuans.modified_at) as modified_at'),
                DB::raw('MAX(trs.updated_at) as trs_updated_at') // Ambil updated_at terbaru dari TrsPoPengajuan
            )
            ->groupBy('mst_po_pengajuans.no_fpb')
            ->orderBy(DB::raw('MAX(mst_po_pengajuans.status_1)'), 'asc') // Urutan berdasarkan status_1
            ->orderBy(DB::raw('MAX(trs.created_at)'), 'asc') // Urutan berdasarkan created_at  // Kelompokkan berdasarkan no_fpb untuk mendapatkan data unik
            ->get();

        // Mengirim data ke view
        return view('po_pengajuan.index_po_finance', compact('data'));
    }

    public function indexPoProcurement()
    {
        // Mengambil data dari MstPoPengajuan dan TrsPoPengajuan
        $data = MstPoPengajuan::join('trs_po_pengajuans as trs', 'trs.id_fpb', '=', 'mst_po_pengajuans.id')
            ->select(
                'mst_po_pengajuans.no_fpb',
                DB::raw('MAX(mst_po_pengajuans.id) as id'), // Ambil ID maksimal dari MstPoPengajuan
                DB::raw('MAX(mst_po_pengajuans.no_po) as no_po'),
                DB::raw('MAX(mst_po_pengajuans.kategori_po) as kategori_po'),
                DB::raw('MAX(mst_po_pengajuans.nama_barang) as nama_barang'),
                DB::raw('MAX(mst_po_pengajuans.qty) as qty'),
                DB::raw('MAX(mst_po_pengajuans.pcs) as pcs'),
                DB::raw('MAX(mst_po_pengajuans.price_list) as price_list'),
                DB::raw('MAX(mst_po_pengajuans.total_harga) as total_harga'),
                DB::raw('MAX(mst_po_pengajuans.spesifikasi) as spesifikasi'),
                DB::raw('MAX(mst_po_pengajuans.file) as file'),
                DB::raw('MAX(mst_po_pengajuans.file_name) as file_name'),
                DB::raw('MAX(mst_po_pengajuans.amount) as amount'),
                DB::raw('MAX(mst_po_pengajuans.rekomendasi) as rekomendasi'),
                DB::raw('MAX(mst_po_pengajuans.due_date) as due_date'),
                DB::raw('MAX(mst_po_pengajuans.target_cost) as target_cost'),
                DB::raw('MAX(mst_po_pengajuans.lead_time) as lead_time'),
                DB::raw('MAX(mst_po_pengajuans.nama_customer) as nama_customer'),
                DB::raw('MAX(mst_po_pengajuans.nama_project) as nama_project'),
                DB::raw('MAX(mst_po_pengajuans.catatan) as catatan'),
                DB::raw('MAX(mst_po_pengajuans.status_1) as status_1'),
                DB::raw('MAX(mst_po_pengajuans.status_2) as status_2'),
                DB::raw('MAX(mst_po_pengajuans.created_at) as created_at'),
                DB::raw('MAX(mst_po_pengajuans.modified_at) as modified_at'),
                DB::raw('MAX(trs.updated_at) as trs_updated_at') // Ambil updated_at terbaru dari TrsPoPengajuan
            )
            ->groupBy('mst_po_pengajuans.no_fpb')
            ->orderBy(DB::raw('MAX(mst_po_pengajuans.status_1)'), 'asc') // Urutan berdasarkan status_1
            ->orderBy(DB::raw('MAX(trs.created_at)'), 'asc') // Urutan berdasarkan created_at
            ->get();

        // Mencari data yang memiliki catatan "Terdapat Reject Item"
        $pengajuanCancel = $data->filter(function ($item) {
            return strpos($item->catatan, 'Terdapat Reject Item') !== false;
        })->pluck('no_fpb')->toArray(); // Mengambil no_fpb untuk data yang memiliki catatan "Cancel Item"

        // Mencari pengajuan terbaru dengan status_1 = 5
        $pengajuanTerbaru = $data->firstWhere('status_1', 5); // Mencari pengajuan terbaru dari data yang sudah diambil
        $noFpbTerbaru = $pengajuanTerbaru ? $pengajuanTerbaru->no_fpb : null;

        $showNamaBarang = false;

        // Mengirim data ke view
        return view('po_pengajuan.index_po_procurment', compact('data', 'pengajuanCancel', 'noFpbTerbaru', 'showNamaBarang'));
    }

    public function indexPoProcurement2()
    {
        $data = MstPoPengajuan::join('trs_po_pengajuans as trs', 'trs.id_fpb', '=', 'mst_po_pengajuans.id')
            ->select(
                'mst_po_pengajuans.no_fpb',
                'mst_po_pengajuans.id',
                'mst_po_pengajuans.no_po',
                'mst_po_pengajuans.kategori_po',
                'mst_po_pengajuans.nama_barang',
                'mst_po_pengajuans.qty',
                'mst_po_pengajuans.pcs',
                'mst_po_pengajuans.price_list',
                'mst_po_pengajuans.total_harga',
                'mst_po_pengajuans.spesifikasi',
                'mst_po_pengajuans.file',
                'mst_po_pengajuans.file_name',
                'mst_po_pengajuans.quotation_file',
                'mst_po_pengajuans.amount',
                'mst_po_pengajuans.rekomendasi',
                'mst_po_pengajuans.due_date',
                'mst_po_pengajuans.target_cost',
                'mst_po_pengajuans.lead_time',
                'mst_po_pengajuans.nama_customer',
                'mst_po_pengajuans.nama_project',
                'mst_po_pengajuans.catatan',
                'mst_po_pengajuans.status_1',
                'mst_po_pengajuans.status_2',
                'mst_po_pengajuans.created_at',
                'mst_po_pengajuans.updated_at',
                'mst_po_pengajuans.modified_at',
                DB::raw('(SELECT MAX(updated_at) FROM trs_po_pengajuans WHERE id_fpb = mst_po_pengajuans.id) as trs_updated_at')
            )
            ->where('mst_po_pengajuans.status_2', '!=', 8) // Filter out records with status_2 = 8
            ->groupBy(
                'mst_po_pengajuans.no_fpb',
                'mst_po_pengajuans.id',
                'mst_po_pengajuans.no_po',
                'mst_po_pengajuans.kategori_po',
                'mst_po_pengajuans.nama_barang',
                'mst_po_pengajuans.qty',
                'mst_po_pengajuans.pcs',
                'mst_po_pengajuans.price_list',
                'mst_po_pengajuans.total_harga',
                'mst_po_pengajuans.spesifikasi',
                'mst_po_pengajuans.file',
                'mst_po_pengajuans.file_name',
                'mst_po_pengajuans.quotation_file',
                'mst_po_pengajuans.amount',
                'mst_po_pengajuans.rekomendasi',
                'mst_po_pengajuans.due_date',
                'mst_po_pengajuans.target_cost',
                'mst_po_pengajuans.lead_time',
                'mst_po_pengajuans.nama_customer',
                'mst_po_pengajuans.nama_project',
                'mst_po_pengajuans.catatan',
                'mst_po_pengajuans.status_1',
                'mst_po_pengajuans.status_2',
                'mst_po_pengajuans.created_at',
                'mst_po_pengajuans.updated_at',
                'mst_po_pengajuans.modified_at'
            )
            ->orderBy(DB::raw('MAX(mst_po_pengajuans.status_1)'), 'asc') // Urutan berdasarkan status_1
            ->orderBy(DB::raw('MAX(mst_po_pengajuans.created_at)'), 'asc') // Urutan berdasarkan created_at
            ->get();

        // Mencari data yang memiliki catatan "Terdapat Reject Item" dan status_1 != 9
        $pengajuanCancel = $data->filter(function ($item) {
            return strpos($item->catatan, 'Terdapat Reject Item') !== false && $item->status_1 != 9;
        })->pluck('no_fpb')->toArray(); // Mengambil no_fpb untuk data yang memiliki catatan "Cancel Item"

        // Mencari pengajuan terbaru dengan status_1 = 5 dari data yang sudah diambil
        $pengajuanTerbaru = $data->firstWhere('status_1', 5);
        $noFpbTerbaru = $pengajuanTerbaru ? $pengajuanTerbaru->no_fpb : null;

        $showNamaBarang = true;

        // Mengirim data ke view
        return view('po_pengajuan.index_po_procurment', compact('data', 'pengajuanCancel', 'noFpbTerbaru', 'showNamaBarang'));
    }

    public function showFPBForm($id)
    {
        // Mengambil data berdasarkan id
        $poPengajuan = MstPoPengajuan::find($id);

        if (!$poPengajuan) {
            return abort(404); // Jika data dengan ID tersebut tidak ditemukan
        }

        // Mengambil semua data berdasarkan no_fpb yang sama
        $mstPoPengajuans = MstPoPengajuan::where('no_fpb', $poPengajuan->no_fpb)->get();

        // Mencari modified_at dari TrsPoPengajuan dengan status 3
        $trsPoPengajuanStatus3 = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
            ->where('status', 3)
            ->select('modified_at')
            ->first();

        // Mencari modified_at dari TrsPoPengajuan dengan status 3
        $trsPoPengajuanStatus4 = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
            ->where('status', 5)
            ->select('modified_at')
            ->first();

        // Tentukan Dept. Head berdasarkan nilai modified_at dari TrsPoPengajuan
        $deptHead = '';
        if ($trsPoPengajuanStatus3) {
            $deptHead = $trsPoPengajuanStatus3->modified_at;
        } else {
            // Fallback logic jika tidak ada TrsPoPengajuan dengan status 3
            if (in_array($poPengajuan->modified_at, ['JESSICA PAUNE', 'SITI MARIA ULFA', 'MUHAMMAD DINAR FARISI', 'MEDI KRISNANTO', 'VIVIAN ANGELIKA'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? $poPengajuan->modified_at;
            } elseif (in_array($poPengajuan->modified_at, ['MUGI PRAMONO', 'ABDUR RAHMAN AL FAAIZ', 'RAGIL ISHA RAHMANTO'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'ARY RODJO PRASETYO';
            } elseif (in_array($poPengajuan->modified_at, ['ILHAM CHOLID', 'JUN JOHAMIN PD', 'HERY HERMAWAN', 'WULYO EKO PRASETYO', 'SENDY PRABOWO', 'YAN WELEM MANGINSELA', 'HEXAPA DARMADI', 'SARAH EGA BUDI ASTUTI', 'SONY STIAWAN', 'DIMAS ADITYA PRIANDANA'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'YULMAI RIDO WINANDA';
            } elseif (in_array($poPengajuan->modified_at, ['DANIA ISNAWATI', 'FRISILIA CLAUDIA HUTAMA', 'DWI KUNTORO', 'YUNASIS PALGUNADI', 'RISFAN FAISAL'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'ANDIK TOTOK SISWOYO';
            } elseif (in_array($poPengajuan->modified_at, ['RANGGA FADILLAH', 'NURSALIM'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'VITRI HANDAYANI';
            }
        }

        // Tentukan User Acc berdasarkan kategori_po
        $userAccHeader = '';
        $userAccbody = '';

        if ($poPengajuan->kategori_po == 'IT') {
            $userAccHeader = 'IT';

            // Mengambil modified_at dari TrsPoPengajuan dengan status 3
            $trsPoPengajuanIT = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                ->where('status', 4)
                ->select('modified_at')
                ->first();

            $userAccbody = $trsPoPengajuanIT ? $trsPoPengajuanIT->modified_at : '';
        } elseif ($poPengajuan->kategori_po == 'GA') {
            $userAccHeader = 'GA';

            // Mengambil modified_at dari TrsPoPengajuan dengan status 3
            $trsPoPengajuanGA = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                ->where('status', 4)
                ->select('modified_at')
                ->first();

            $userAccbody = $trsPoPengajuanGA ? $trsPoPengajuanGA->modified_at : '';
        } elseif (in_array($poPengajuan->kategori_po, ['Consumable', 'Spareparts', 'Indirect Material'])) {
            $userAccHeader = 'Warehouse';

            // Mengambil data dari TrsPoPengajuan berdasarkan status 4 dan id_fpb yang diambil dari id di $poPengajuan
            $trsPoPengajuan = TrsPoPengajuan::join('mst_po_pengajuans', 'trs_po_pengajuans.id_fpb', '=', 'mst_po_pengajuans.id')
                ->where('trs_po_pengajuans.status', 4)
                ->where('trs_po_pengajuans.id_fpb', $poPengajuan->id)
                ->select('trs_po_pengajuans.*', 'mst_po_pengajuans.kategori_po')
                ->first();

            // Logging the query and results
            Log::info('Fetching TrsPoPengajuan for Warehouse', [
                'kategori_po' => $poPengajuan->kategori_po,
                'status' => 4,
                'id_fpb' => $poPengajuan->id,
                'modified_at' => $trsPoPengajuan ? $trsPoPengajuan->modified_at : null
            ]);

            if ($trsPoPengajuan) {
                // Set userAccbody to modified_at field for status 4 without any other text
                $userAccbody = $trsPoPengajuan->modified_at;
            } else {
                // Set userAccbody to empty if no record with status 4 is found
                $userAccbody = '';
            }
        }

        $matchingTrsPoPengajuans = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
            ->select('created_at', 'status') // Pastikan kolom 'status' benar-benar ada
            ->get();

        // Mengirimkan data ke view, termasuk $trsPoPengajuan
        return view('po_pengajuan.view_form_FPB', compact('mstPoPengajuans', 'deptHead', 'userAccHeader', 'userAccbody', 'matchingTrsPoPengajuans', 'trsPoPengajuanStatus4'));
    }

    public function showFPBForm2($id)
    {
        // Mengambil data berdasarkan id
        $poPengajuan = MstPoPengajuan::find($id);

        if (!$poPengajuan) {
            return abort(404); // Jika data dengan ID tersebut tidak ditemukan
        }

        // Mengambil semua data berdasarkan no_fpb yang sama
        $mstPoPengajuans = MstPoPengajuan::where('no_fpb', $poPengajuan->no_fpb)->get();

        // Mencari modified_at dari TrsPoPengajuan dengan status 3
        $trsPoPengajuanStatus3 = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
            ->where('status', 3)
            ->select('modified_at')
            ->first();

        // Mencari modified_at dari TrsPoPengajuan dengan status 3
        $trsPoPengajuanStatus4 = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
            ->where('status', 5)
            ->select('modified_at')
            ->first();

        // Tentukan Dept. Head berdasarkan nilai modified_at dari TrsPoPengajuan
        $deptHead = '';
        if ($trsPoPengajuanStatus3) {
            $deptHead = $trsPoPengajuanStatus3->modified_at;
        } else {
            // Fallback logic jika tidak ada TrsPoPengajuan dengan status 3
            if (in_array($poPengajuan->modified_at, ['JESSICA PAUNE', 'SITI MARIA ULFA', 'MUHAMMAD DINAR FARISI', 'MEDI KRISNANTO', 'VIVIAN ANGELIKA'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? $poPengajuan->modified_at;
            } elseif (in_array($poPengajuan->modified_at, ['MUGI PRAMONO', 'ABDUR RAHMAN AL FAAIZ', 'RAGIL ISHA RAHMANTO'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'ARY RODJO PRASETYO';
            } elseif (in_array($poPengajuan->modified_at, ['ILHAM CHOLID', 'JUN JOHAMIN PD', 'HERY HERMAWAN', 'WULYO EKO PRASETYO', 'SENDY PRABOWO', 'YAN WELEM MANGINSELA', 'HEXAPA DARMADI', 'SARAH EGA BUDI ASTUTI', 'SONY STIAWAN', 'DIMAS ADITYA PRIANDANA'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'YULMAI RIDO WINANDA';
            } elseif (in_array($poPengajuan->modified_at, ['DANIA ISNAWATI', 'FRISILIA CLAUDIA HUTAMA', 'DWI KUNTORO', 'YUNASIS PALGUNADI', 'RISFAN FAISAL'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'ANDIK TOTOK SISWOYO';
            } elseif (in_array($poPengajuan->modified_at, ['RANGGA FADILLAH', 'NURSALIM'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'VITRI HANDAYANI';
            }
        }

        // Tentukan User Acc berdasarkan kategori_po
        $userAccHeader = '';
        $userAccbody = '';

        if ($poPengajuan->kategori_po == 'IT') {
            $userAccHeader = 'IT';

            // Mengambil modified_at dari TrsPoPengajuan dengan status 3
            $trsPoPengajuanIT = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                ->where('status', 4)
                ->select('modified_at')
                ->first();

            $userAccbody = $trsPoPengajuanIT ? $trsPoPengajuanIT->modified_at : '';
        } elseif ($poPengajuan->kategori_po == 'GA') {
            $userAccHeader = 'GA';

            // Mengambil modified_at dari TrsPoPengajuan dengan status 3
            $trsPoPengajuanGA = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                ->where('status', 4)
                ->select('modified_at')
                ->first();

            $userAccbody = $trsPoPengajuanGA ? $trsPoPengajuanGA->modified_at : '';
        } elseif (in_array($poPengajuan->kategori_po, ['Consumable', 'Spareparts', 'Indirect Material'])) {
            $userAccHeader = 'Warehouse';

            // Mengambil data dari TrsPoPengajuan berdasarkan status 4 dan id_fpb yang diambil dari id di $poPengajuan
            $trsPoPengajuan = TrsPoPengajuan::join('mst_po_pengajuans', 'trs_po_pengajuans.id_fpb', '=', 'mst_po_pengajuans.id')
                ->where('trs_po_pengajuans.status', 4)
                ->where('trs_po_pengajuans.id_fpb', $poPengajuan->id)
                ->select('trs_po_pengajuans.*', 'mst_po_pengajuans.kategori_po')
                ->first();

            // Logging the query and results
            Log::info('Fetching TrsPoPengajuan for Warehouse', [
                'kategori_po' => $poPengajuan->kategori_po,
                'status' => 4,
                'id_fpb' => $poPengajuan->id,
                'modified_at' => $trsPoPengajuan ? $trsPoPengajuan->modified_at : null
            ]);

            if ($trsPoPengajuan) {
                // Set userAccbody to modified_at field for status 4 without any other text
                $userAccbody = $trsPoPengajuan->modified_at;
            } else {
                // Set userAccbody to empty if no record with status 4 is found
                $userAccbody = '';
            }
        }

        $matchingTrsPoPengajuans = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
            ->select('created_at', 'status') // Pastikan kolom 'status' benar-benar ada
            ->get();

        // Mengirimkan data ke view
        return view('po_pengajuan.view_form_FPB_dept', compact('mstPoPengajuans', 'deptHead', 'userAccHeader', 'userAccbody', 'matchingTrsPoPengajuans', 'trsPoPengajuanStatus4'));
    }

    public function showFPBForm3($id)
    {
        // Mengambil data berdasarkan id
        $poPengajuan = MstPoPengajuan::find($id);

        if (!$poPengajuan) {
            return abort(404); // Jika data dengan ID tersebut tidak ditemukan
        }

        // Mengambil semua data berdasarkan no_fpb yang sama
        $mstPoPengajuans = MstPoPengajuan::where('no_fpb', $poPengajuan->no_fpb)->get();

        // Mencari modified_at dari TrsPoPengajuan dengan status 3
        $trsPoPengajuanStatus3 = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
            ->where('status', 3)
            ->select('modified_at')
            ->first();

        // Mencari modified_at dari TrsPoPengajuan dengan status 3
        $trsPoPengajuanStatus4 = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
            ->where('status', 5)
            ->select('modified_at')
            ->first();

        // Tentukan Dept. Head berdasarkan nilai modified_at dari TrsPoPengajuan
        $deptHead = '';
        if ($trsPoPengajuanStatus3) {
            $deptHead = $trsPoPengajuanStatus3->modified_at;
        } else {
            // Fallback logic jika tidak ada TrsPoPengajuan dengan status 3
            if (in_array($poPengajuan->modified_at, ['JESSICA PAUNE', 'SITI MARIA ULFA', 'MUHAMMAD DINAR FARISI', 'MEDI KRISNANTO', 'VIVIAN ANGELIKA'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? $poPengajuan->modified_at;
            } elseif (in_array($poPengajuan->modified_at, ['MUGI PRAMONO', 'ABDUR RAHMAN AL FAAIZ', 'RAGIL ISHA RAHMANTO'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'ARY RODJO PRASETYO';
            } elseif (in_array($poPengajuan->modified_at, ['ILHAM CHOLID', 'JUN JOHAMIN PD', 'HERY HERMAWAN', 'WULYO EKO PRASETYO', 'SENDY PRABOWO', 'YAN WELEM MANGINSELA', 'HEXAPA DARMADI', 'SARAH EGA BUDI ASTUTI', 'SONY STIAWAN', 'DIMAS ADITYA PRIANDANA'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'YULMAI RIDO WINANDA';
            } elseif (in_array($poPengajuan->modified_at, ['DANIA ISNAWATI', 'FRISILIA CLAUDIA HUTAMA', 'DWI KUNTORO', 'YUNASIS PALGUNADI', 'RISFAN FAISAL'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'ANDIK TOTOK SISWOYO';
            } elseif (in_array($poPengajuan->modified_at, ['RANGGA FADILLAH', 'NURSALIM'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'VITRI HANDAYANI';
            }
        }

        // Tentukan User Acc berdasarkan kategori_po
        $userAccHeader = '';
        $userAccbody = '';

        if ($poPengajuan->kategori_po == 'IT') {
            $userAccHeader = 'IT';

            // Mengambil modified_at dari TrsPoPengajuan dengan status 3
            $trsPoPengajuanIT = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                ->where('status', 4)
                ->select('modified_at')
                ->first();

            $userAccbody = $trsPoPengajuanIT ? $trsPoPengajuanIT->modified_at : '';
        } elseif ($poPengajuan->kategori_po == 'GA') {
            $userAccHeader = 'GA';

            // Mengambil modified_at dari TrsPoPengajuan dengan status 3
            $trsPoPengajuanGA = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                ->where('status', 4)
                ->select('modified_at')
                ->first();

            $userAccbody = $trsPoPengajuanGA ? $trsPoPengajuanGA->modified_at : '';
        } elseif (in_array($poPengajuan->kategori_po, ['Consumable', 'Spareparts', 'Indirect Material'])) {
            $userAccHeader = 'Warehouse';

            // Mengambil data dari TrsPoPengajuan berdasarkan status 4 dan id_fpb yang diambil dari id di $poPengajuan
            $trsPoPengajuan = TrsPoPengajuan::join('mst_po_pengajuans', 'trs_po_pengajuans.id_fpb', '=', 'mst_po_pengajuans.id')
                ->where('trs_po_pengajuans.status', 4)
                ->where('trs_po_pengajuans.id_fpb', $poPengajuan->id)
                ->select('trs_po_pengajuans.*', 'mst_po_pengajuans.kategori_po')
                ->first();

            // Logging the query and results
            Log::info('Fetching TrsPoPengajuan for Warehouse', [
                'kategori_po' => $poPengajuan->kategori_po,
                'status' => 4,
                'id_fpb' => $poPengajuan->id,
                'modified_at' => $trsPoPengajuan ? $trsPoPengajuan->modified_at : null
            ]);

            if ($trsPoPengajuan) {
                // Set userAccbody to modified_at field for status 4 without any other text
                $userAccbody = $trsPoPengajuan->modified_at;
            } else {
                // Set userAccbody to empty if no record with status 4 is found
                $userAccbody = '';
            }
        }

        $matchingTrsPoPengajuans = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
            ->select('created_at', 'status') // Pastikan kolom 'status' benar-benar ada
            ->get();

        // Mengirimkan data ke view
        return view('po_pengajuan.view_form_FPB_user', compact('mstPoPengajuans', 'deptHead', 'userAccHeader', 'userAccbody', 'matchingTrsPoPengajuans', 'trsPoPengajuanStatus4'));
    }

    public function showFPBForm4($id)
    {
        // Mengambil data berdasarkan id
        $poPengajuan = MstPoPengajuan::find($id);

        if (!$poPengajuan) {
            return abort(404); // Jika data dengan ID tersebut tidak ditemukan
        }

        // Mengambil semua data berdasarkan no_fpb yang sama
        $mstPoPengajuans = MstPoPengajuan::where('no_fpb', $poPengajuan->no_fpb)->get();

        // Mencari modified_at dari TrsPoPengajuan dengan status 3
        $trsPoPengajuanStatus3 = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
            ->where('status', 3)
            ->select('modified_at')
            ->first();

        // Mencari modified_at dari TrsPoPengajuan dengan status 3
        $trsPoPengajuanStatus4 = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
            ->where('status', 5)
            ->select('modified_at')
            ->first();

        // Tentukan Dept. Head berdasarkan nilai modified_at dari TrsPoPengajuan
        $deptHead = '';
        if ($trsPoPengajuanStatus3) {
            $deptHead = $trsPoPengajuanStatus3->modified_at;
        } else {
            // Fallback logic jika tidak ada TrsPoPengajuan dengan status 3
            if (in_array($poPengajuan->modified_at, ['JESSICA PAUNE', 'SITI MARIA ULFA', 'MUHAMMAD DINAR FARISI', 'MEDI KRISNANTO', 'VIVIAN ANGELIKA'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? $poPengajuan->modified_at;
            } elseif (in_array($poPengajuan->modified_at, ['MUGI PRAMONO', 'ABDUR RAHMAN AL FAAIZ', 'RAGIL ISHA RAHMANTO'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'ARY RODJO PRASETYO';
            } elseif (in_array($poPengajuan->modified_at, ['ILHAM CHOLID', 'JUN JOHAMIN PD', 'HERY HERMAWAN', 'WULYO EKO PRASETYO', 'SENDY PRABOWO', 'YAN WELEM MANGINSELA', 'HEXAPA DARMADI', 'SARAH EGA BUDI ASTUTI', 'SONY STIAWAN', 'DIMAS ADITYA PRIANDANA'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'YULMAI RIDO WINANDA';
            } elseif (in_array($poPengajuan->modified_at, ['DANIA ISNAWATI', 'FRISILIA CLAUDIA HUTAMA', 'DWI KUNTORO', 'YUNASIS PALGUNADI', 'RISFAN FAISAL'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'ANDIK TOTOK SISWOYO';
            } elseif (in_array($poPengajuan->modified_at, ['RANGGA FADILLAH', 'NURSALIM'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'VITRI HANDAYANI';
            }
        }

        // Tentukan User Acc berdasarkan kategori_po
        $userAccHeader = '';
        $userAccbody = '';

        if ($poPengajuan->kategori_po == 'IT') {
            $userAccHeader = 'IT';

            // Mengambil modified_at dari TrsPoPengajuan dengan status 3
            $trsPoPengajuanIT = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                ->where('status', 4)
                ->select('modified_at')
                ->first();

            $userAccbody = $trsPoPengajuanIT ? $trsPoPengajuanIT->modified_at : '';
        } elseif ($poPengajuan->kategori_po == 'GA') {
            $userAccHeader = 'GA';

            // Mengambil modified_at dari TrsPoPengajuan dengan status 3
            $trsPoPengajuanGA = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                ->where('status', 4)
                ->select('modified_at')
                ->first();

            $userAccbody = $trsPoPengajuanGA ? $trsPoPengajuanGA->modified_at : '';
        } elseif (in_array($poPengajuan->kategori_po, ['Consumable', 'Spareparts', 'Indirect Material'])) {
            $userAccHeader = 'Warehouse';

            // Mengambil data dari TrsPoPengajuan berdasarkan status 4 dan id_fpb yang diambil dari id di $poPengajuan
            $trsPoPengajuan = TrsPoPengajuan::join('mst_po_pengajuans', 'trs_po_pengajuans.id_fpb', '=', 'mst_po_pengajuans.id')
                ->where('trs_po_pengajuans.status', 4)
                ->where('trs_po_pengajuans.id_fpb', $poPengajuan->id)
                ->select('trs_po_pengajuans.*', 'mst_po_pengajuans.kategori_po')
                ->first();

            // Logging the query and results
            Log::info('Fetching TrsPoPengajuan for Warehouse', [
                'kategori_po' => $poPengajuan->kategori_po,
                'status' => 4,
                'id_fpb' => $poPengajuan->id,
                'modified_at' => $trsPoPengajuan ? $trsPoPengajuan->modified_at : null
            ]);

            if ($trsPoPengajuan) {
                // Set userAccbody to modified_at field for status 4 without any other text
                $userAccbody = $trsPoPengajuan->modified_at;
            } else {
                // Set userAccbody to empty if no record with status 4 is found
                $userAccbody = '';
            }
        }

        $matchingTrsPoPengajuans = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
            ->select('created_at', 'status') // Pastikan kolom 'status' benar-benar ada
            ->get();
        // Mengirimkan data ke view
        return view('po_pengajuan.view_form_FPB_finn', compact('mstPoPengajuans', 'deptHead', 'userAccHeader', 'userAccbody', 'matchingTrsPoPengajuans', 'trsPoPengajuanStatus4'));
    }

    public function showFPBProc($id)
    {
        // Mengambil data berdasarkan id
        $poPengajuan = MstPoPengajuan::find($id);

        if (!$poPengajuan) {
            return abort(404); // Jika data dengan ID tersebut tidak ditemukan
        }

        // Mengambil semua data berdasarkan no_fpb yang sama
        $mstPoPengajuans = MstPoPengajuan::where('no_fpb', $poPengajuan->no_fpb)->get();

        // Mencari modified_at dari TrsPoPengajuan dengan status 3
        $trsPoPengajuanStatus3 = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
            ->where('status', 3)
            ->select('modified_at')
            ->first();

        // Mencari modified_at dari TrsPoPengajuan dengan status 3
        $trsPoPengajuanStatus4 = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
            ->where('status', 5)
            ->select('modified_at')
            ->first();

        // Tentukan Dept. Head berdasarkan nilai modified_at dari TrsPoPengajuan
        $deptHead = '';
        if ($trsPoPengajuanStatus3) {
            $deptHead = $trsPoPengajuanStatus3->modified_at;
        } else {
            // Fallback logic jika tidak ada TrsPoPengajuan dengan status 3
            if (in_array($poPengajuan->modified_at, ['JESSICA PAUNE', 'SITI MARIA ULFA', 'MUHAMMAD DINAR FARISI', 'MEDI KRISNANTO', 'VIVIAN ANGELIKA'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? $poPengajuan->modified_at;
            } elseif (in_array($poPengajuan->modified_at, ['MUGI PRAMONO', 'ABDUR RAHMAN AL FAAIZ', 'RAGIL ISHA RAHMANTO'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'ARY RODJO PRASETYO';
            } elseif (in_array($poPengajuan->modified_at, ['ILHAM CHOLID', 'JUN JOHAMIN PD', 'HERY HERMAWAN', 'WULYO EKO PRASETYO', 'SENDY PRABOWO', 'YAN WELEM MANGINSELA', 'HEXAPA DARMADI', 'SARAH EGA BUDI ASTUTI', 'SONY STIAWAN', 'DIMAS ADITYA PRIANDANA'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'YULMAI RIDO WINANDA';
            } elseif (in_array($poPengajuan->modified_at, ['DANIA ISNAWATI', 'FRISILIA CLAUDIA HUTAMA', 'DWI KUNTORO', 'YUNASIS PALGUNADI', 'RISFAN FAISAL'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'ANDIK TOTOK SISWOYO';
            } elseif (in_array($poPengajuan->modified_at, ['RANGGA FADILLAH', 'NURSALIM'])) {
                $deptHead = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                    ->where('status', 3)
                    ->select('modified_at')
                    ->value('modified_at') ?? 'VITRI HANDAYANI';
            }
        }

        // Tentukan User Acc berdasarkan kategori_po
        $userAccHeader = '';
        $userAccbody = '';

        if ($poPengajuan->kategori_po == 'IT') {
            $userAccHeader = 'IT';

            // Mengambil modified_at dari TrsPoPengajuan dengan status 3
            $trsPoPengajuanIT = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                ->where('status', 4)
                ->select('modified_at')
                ->first();

            $userAccbody = $trsPoPengajuanIT ? $trsPoPengajuanIT->modified_at : '';
        } elseif ($poPengajuan->kategori_po == 'GA') {
            $userAccHeader = 'GA';

            // Mengambil modified_at dari TrsPoPengajuan dengan status 3
            $trsPoPengajuanGA = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
                ->where('status', 4)
                ->select('modified_at')
                ->first();

            $userAccbody = $trsPoPengajuanGA ? $trsPoPengajuanGA->modified_at : '';
        } elseif (in_array($poPengajuan->kategori_po, ['Consumable', 'Spareparts', 'Indirect Material'])) {
            $userAccHeader = 'Warehouse';

            // Mengambil data dari TrsPoPengajuan berdasarkan status 4 dan id_fpb yang diambil dari id di $poPengajuan
            $trsPoPengajuan = TrsPoPengajuan::join('mst_po_pengajuans', 'trs_po_pengajuans.id_fpb', '=', 'mst_po_pengajuans.id')
                ->where('trs_po_pengajuans.status', 4)
                ->where('trs_po_pengajuans.id_fpb', $poPengajuan->id)
                ->select('trs_po_pengajuans.*', 'mst_po_pengajuans.kategori_po')
                ->first();

            // Logging the query and results
            Log::info('Fetching TrsPoPengajuan for Warehouse', [
                'kategori_po' => $poPengajuan->kategori_po,
                'status' => 4,
                'id_fpb' => $poPengajuan->id,
                'modified_at' => $trsPoPengajuan ? $trsPoPengajuan->modified_at : null
            ]);

            if ($trsPoPengajuan) {
                // Set userAccbody to modified_at field for status 4 without any other text
                $userAccbody = $trsPoPengajuan->modified_at;
            } else {
                // Set userAccbody to empty if no record with status 4 is found
                $userAccbody = '';
            }
        }

        $matchingTrsPoPengajuans = TrsPoPengajuan::where('id_fpb', $poPengajuan->id)
            ->select('created_at', 'status') // Pastikan kolom 'status' benar-benar ada
            ->get();

        // Mengirimkan data ke view
        return view('po_pengajuan.trs_po_procurment', compact('mstPoPengajuans', 'deptHead',  'userAccHeader', 'userAccbody', 'matchingTrsPoPengajuans', 'trsPoPengajuanStatus4'));
    }

    public function createPoPengajuan()
    {

        // Mengirim data ke view
        return view('po_pengajuan.create_po_pengajuan');
    }

    public function edit($id)
    {
        // Find the record by id
        $pengajuanPo = MstPoPengajuan::findOrFail($id);

        // Fetch all records with the same no_fpb
        $pengajuanPoList = MstPoPengajuan::where('no_fpb', $pengajuanPo->no_fpb)->get();

        return view('po_pengajuan.edit_po_pengajuan', compact('pengajuanPo', 'pengajuanPoList'));
    }

    public function editDept($id)
    {
        // Find the record by id
        $pengajuanPo = MstPoPengajuan::findOrFail($id);

        // Fetch all records with the same no_fpb
        $pengajuanPoList = MstPoPengajuan::where('no_fpb', $pengajuanPo->no_fpb)->get();

        return view('po_pengajuan.edit_po_deptHead', compact('pengajuanPo', 'pengajuanPoList'));
    }

    public function store(Request $request)
    {
        try {
            Log::info('Received request data for purchase order:', $request->all()); // Log all incoming data

            $validatedData = $request->validate([
                'kategori_po' => 'required|string',
                'nama_barang.*' => 'required|string',
                'spesifikasi.*' => 'required|string',
                'pcs.*' => 'required|integer',
                'file.*' => 'nullable|file|max:11048',
                'price_list.*' => 'nullable|numeric',
                'total_harga.*' => 'nullable|numeric',
                'target_cost.*' => 'nullable|numeric',
                'lead_time.*' => 'nullable|date',
                'rekomendasi.*' => 'nullable|string',
                'nama_customer.*' => 'nullable|string',
                'nama_project.*' => 'nullable|string',
                'no_so.*' => 'nullable|string',
            ]);

            Log::info('Validated data:', $validatedData); // Log the validated data

            // Generate no_fpb
            $currentYear = date('Y');
            $latestPo = MstPoPengajuan::whereYear('created_at', $currentYear)
                ->orderBy('id', 'desc')
                ->first();

            $newPoNumber = 1; // Default value if no existing PO
            if ($latestPo) {
                $lastPoNumber = (int)substr($latestPo->no_fpb, strrpos($latestPo->no_fpb, '/') + 1);
                $newPoNumber = $lastPoNumber + 1;
            }

            $no_fpb = 'FPB/' . $currentYear . '/' . str_pad($newPoNumber, 5, '0', STR_PAD_LEFT); // Format to 00001
            Log::info('Generated PO number: ' . $no_fpb); // Log the generated PO number

            // Check if the generated no_fpb already exists in the database
            while (MstPoPengajuan::where('no_fpb', $no_fpb)->exists()) {
                Log::warning('Duplicate PO number found, generating a new one.');
                // If it exists, generate a new no_fpb
                $newPoNumber++;
                $no_fpb = 'FPB/' . $currentYear . '/' . str_pad($newPoNumber, 5, '0', STR_PAD_LEFT);
                Log::info('Newly generated PO number: ' . $no_fpb); // Log the newly generated PO number
            }

            foreach ($validatedData['nama_barang'] as $index => $nama_barang) {
                $purchaseOrder = new MstPoPengajuan();
                $purchaseOrder->no_fpb = $no_fpb; // Store generated no_fpb
                $purchaseOrder->kategori_po = $validatedData['kategori_po'];
                $purchaseOrder->nama_barang = $nama_barang;
                $purchaseOrder->spesifikasi = $validatedData['spesifikasi'][$index];
                $purchaseOrder->pcs = $validatedData['pcs'][$index];
                $purchaseOrder->price_list = isset($validatedData['price_list'][$index]) ?
                    str_replace(',', '', $validatedData['price_list'][$index]) : null;

                // Menghitung total harga (pcs * price_list)
                if (isset($validatedData['pcs'][$index]) && isset($validatedData['price_list'][$index])) {
                    $purchaseOrder->total_harga = $validatedData['pcs'][$index] * str_replace(',', '', $validatedData['price_list'][$index]);
                } else {
                    $purchaseOrder->total_harga = null;
                }

                Log::info('Creating purchase order for item:', [
                    'no_fpb' => $no_fpb,
                    'kategori_po' => $validatedData['kategori_po'],
                    'nama_barang' => $nama_barang,
                    'spesifikasi' => $validatedData['spesifikasi'][$index],
                ]); // Removed qty reference from log

                // Handle file upload with UUID
                if ($request->hasFile('file.' . $index)) {
                    $file = $request->file('file.' . $index);
                    // Generate a unique file name
                    $hashedName = uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('assets/pre_order'), $hashedName);
                    $purchaseOrder->file = $hashedName; // Store the hashed file name
                    $purchaseOrder->file_name = $file->getClientOriginalName(); // Original file name (optional)
                    Log::info('Uploaded file for item:', [
                        'file_name' => $file->getClientOriginalName(),
                        'hashed_name' => $hashedName,
                    ]);
                }

                // Handle Subcont fields if they are set
                if ($validatedData['kategori_po'] === 'Subcont') {
                    $purchaseOrder->target_cost = isset($validatedData['target_cost'][$index]) ?
                        str_replace(',', '', $validatedData['target_cost'][$index]) : null;
                    $purchaseOrder->lead_time = $validatedData['lead_time'][$index] ?? null;
                    $purchaseOrder->rekomendasi = $validatedData['rekomendasi'][$index] ?? null;
                    $purchaseOrder->nama_customer = $validatedData['nama_customer'][$index] ?? null;
                    $purchaseOrder->nama_project = $validatedData['nama_project'][$index] ?? null;

                    // Generate and insert no_so
                    $so_number = 'SO/' . $currentYear . '/' . ($validatedData['no_so'][$index] ?? ''); // Format SO/{year}/{value from view}
                    $purchaseOrder->no_so = $so_number;

                    Log::info('Subcont fields for item:', [
                        'target_cost' => $purchaseOrder->target_cost,
                        'lead_time' => $purchaseOrder->lead_time,
                        'rekomendasi' => $purchaseOrder->rekomendasi,
                        'nama_customer' => $purchaseOrder->nama_customer,
                        'nama_project' => $purchaseOrder->nama_project,
                        'no_so' => $so_number,
                    ]);
                }

                $purchaseOrder->status_1 = 1;
                $purchaseOrder->modified_at = $request->user()->name;

                // Attempt to save the purchase order
                try {
                    $purchaseOrder->save();
                    Log::info('Purchase order saved successfully:', ['no_fpb' => $no_fpb]);
                } catch (\Exception $e) {
                    Log::error('Failed to save purchase order: ' . $e->getMessage(), [
                        'no_fpb' => $no_fpb,
                        'data' => json_encode($purchaseOrder->toArray()), // Convert to string for logging
                    ]);
                    return redirect()->route('index.PO')->with('error', 'Data failed to save: ' . $e->getMessage());
                }
            }

            return redirect()->route('index.PO')->with('success', 'Data successfully saved!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors())); // Convert errors to string
            return redirect()->route('index.PO')->with('error', 'Validation failed: ' . implode(', ', $e->errors()));
        } catch (\Exception $e) {
            Log::error('Unexpected error: ' . $e->getMessage());
            return redirect()->route('index.PO')->with('error', 'An unexpected error occurred: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        // Retrieve PO submission data by ID
        $pengajuanPo = MstPoPengajuan::findOrFail($id);

        // Update MstPoPengajuan data (kategori_po column)
        $pengajuanPo->kategori_po = $request->kategori_po;
        $pengajuanPo->save();

        // Loop to update or add new items via addRow
        if ($request->has('id')) {
            foreach ($request->id as $index => $itemId) {
                if (!empty($itemId)) {
                    // If item has an ID, update the existing data
                    $pengajuanPoItem = MstPoPengajuan::find($itemId);
                    if ($pengajuanPoItem) {
                        $pengajuanPoItem->kategori_po = $pengajuanPo->kategori_po; // Update kategori_po
                        $pengajuanPoItem->nama_barang = $request->nama_barang[$index];
                        $pengajuanPoItem->spesifikasi = $request->spesifikasi[$index];
                        $pengajuanPoItem->pcs = $request->pcs[$index];

                        // Allow null price_list if kategori_po is 'Subcont'
                        if ($pengajuanPo->kategori_po === 'Subcont') {
                            $pengajuanPoItem->price_list = $request->price_list[$index] ?? null;
                        } else {
                            $pengajuanPoItem->price_list = $request->price_list[$index];
                        }

                        // Calculate total_harga if price_list is available
                        if ($pengajuanPoItem->price_list !== null) {
                            $pengajuanPoItem->total_harga = $request->pcs[$index] * $pengajuanPoItem->price_list;
                        } else {
                            $pengajuanPoItem->total_harga = null; // Or handle this as you prefer
                        }

                        // Handle file uploads
                        if ($request->hasFile('file.' . $index)) {
                            if ($pengajuanPoItem->file) {
                                Storage::delete('public/assets/pre_order/' . $pengajuanPoItem->file);
                            }
                            $file = $request->file('file.' . $index);
                            $hashedName = uniqid() . '.' . $file->getClientOriginalExtension();
                            $file->move(public_path('assets/pre_order'), $hashedName);

                            $pengajuanPoItem->file = $hashedName;
                            $pengajuanPoItem->file_name = $file->getClientOriginalName();
                        }

                        // Special handling for Subcont category
                        if ($pengajuanPo->kategori_po === 'Subcont') {
                            $pengajuanPoItem->target_cost = $request->target_cost[$index] ?? null;
                            $pengajuanPoItem->lead_time = $request->lead_time[$index] ?? null;
                            $pengajuanPoItem->rekomendasi = $request->rekomendasi[$index] ?? null;
                            $pengajuanPoItem->nama_customer = $request->nama_customer[$index] ?? null;
                            $pengajuanPoItem->nama_project = $request->nama_project[$index] ?? null;
                            $pengajuanPoItem->no_so = $request->no_so[$index] ?? null;
                        }

                        $pengajuanPoItem->save();
                    }
                } else {
                    // Add new items if no ID is provided
                    $pengajuanPoItem = new MstPoPengajuan();
                    $pengajuanPoItem->no_fpb = $pengajuanPo->no_fpb;
                    $pengajuanPoItem->kategori_po = $pengajuanPo->kategori_po;
                    $pengajuanPoItem->nama_barang = $request->nama_barang[$index];
                    $pengajuanPoItem->spesifikasi = $request->spesifikasi[$index];
                    $pengajuanPoItem->pcs = $request->pcs[$index];

                    // Allow null price_list if kategori_po is 'Subcont'
                    if ($pengajuanPo->kategori_po === 'Subcont') {
                        $pengajuanPoItem->price_list = $request->price_list[$index] ?? null;
                    } else {
                        $pengajuanPoItem->price_list = $request->price_list[$index];
                    }

                    // Calculate total_harga if price_list is available
                    if ($pengajuanPoItem->price_list !== null) {
                        $pengajuanPoItem->total_harga = $request->pcs[$index] * $pengajuanPoItem->price_list;
                    } else {
                        $pengajuanPoItem->total_harga = null; // Or handle this as you prefer
                    }

                    $pengajuanPoItem->status_1 = 1;
                    $pengajuanPoItem->modified_at = $request->user()->name;

                    // Handle file upload for new item
                    if ($request->hasFile('file.' . $index)) {
                        $file = $request->file('file.' . $index);
                        $hashedName = uniqid() . '.' . $file->getClientOriginalExtension();
                        $file->move(public_path('assets/pre_order'), $hashedName);

                        $pengajuanPoItem->file = $hashedName;
                        $pengajuanPoItem->file_name = $file->getClientOriginalName();
                    }

                    // Special handling for Subcont category
                    if ($pengajuanPo->kategori_po === 'Subcont') {
                        $pengajuanPoItem->target_cost = $request->target_cost[$index] ?? null;
                        $pengajuanPoItem->lead_time = $request->lead_time[$index] ?? null;
                        $pengajuanPoItem->rekomendasi = $request->rekomendasi[$index] ?? null;
                        $pengajuanPoItem->nama_customer = $request->nama_customer[$index] ?? null;
                        $pengajuanPoItem->nama_project = $request->nama_project[$index] ?? null;
                        $pengajuanPoItem->no_so = $request->no_so[$index] ?? null;
                    }

                    $pengajuanPoItem->save();
                }
            }
        }

        return redirect()->route('index.PO')->with('success', 'Data PO berhasil diperbarui.');
    }

    public function deletePoPengajuanMultiple(Request $request)
    {
        $ids = $request->ids; // Get the array of IDs from the request

        if (empty($ids)) {
            return response()->json(['message' => 'No items selected'], 400); // If no IDs were sent
        }

        // Find the records by IDs
        $pengajuanItems = MstPoPengajuan::whereIn('id', $ids)->get();

        if ($pengajuanItems->isEmpty()) {
            return response()->json(['message' => 'Data not found'], 404);
        }

        // Perform the deletion
        MstPoPengajuan::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Selected items deleted successfully']);
    }

    public function updateDept(Request $request, $id)
    {
        // Retrieve PO submission data by ID
        $pengajuanPo = MstPoPengajuan::findOrFail($id);

        // Update MstPoPengajuan data (kategori_po column)
        $pengajuanPo->kategori_po = $request->kategori_po;
        $pengajuanPo->save();

        // Loop to update or add new items via addRow
        if ($request->has('id')) {
            foreach ($request->id as $index => $itemId) {
                if (!empty($itemId)) {
                    // If item has an ID, update the existing data
                    $pengajuanPoItem = MstPoPengajuan::find($itemId);
                    if ($pengajuanPoItem) {
                        $pengajuanPoItem->kategori_po = $pengajuanPo->kategori_po; // Update kategori_po
                        $pengajuanPoItem->nama_barang = $request->nama_barang[$index];
                        $pengajuanPoItem->spesifikasi = $request->spesifikasi[$index];
                        $pengajuanPoItem->pcs = $request->pcs[$index];

                        // Allow null price_list if kategori_po is 'Subcont'
                        if ($pengajuanPo->kategori_po === 'Subcont') {
                            $pengajuanPoItem->price_list = $request->price_list[$index] ?? null;
                        } else {
                            $pengajuanPoItem->price_list = $request->price_list[$index];
                        }

                        // Calculate total_harga if price_list is available
                        if ($pengajuanPoItem->price_list !== null) {
                            $pengajuanPoItem->total_harga = $request->pcs[$index] * $pengajuanPoItem->price_list;
                        } else {
                            $pengajuanPoItem->total_harga = null; // Or handle this as you prefer
                        }

                        // Handle file uploads
                        if ($request->hasFile('file.' . $index)) {
                            if ($pengajuanPoItem->file) {
                                Storage::delete('public/assets/pre_order/' . $pengajuanPoItem->file);
                            }
                            $file = $request->file('file.' . $index);
                            $hashedName = uniqid() . '.' . $file->getClientOriginalExtension();
                            $file->move(public_path('assets/pre_order'), $hashedName);

                            $pengajuanPoItem->file = $hashedName;
                            $pengajuanPoItem->file_name = $file->getClientOriginalName();
                        }

                        // Special handling for Subcont category
                        if ($pengajuanPo->kategori_po === 'Subcont') {
                            $pengajuanPoItem->target_cost = $request->target_cost[$index] ?? null;
                            $pengajuanPoItem->lead_time = $request->lead_time[$index] ?? null;
                            $pengajuanPoItem->rekomendasi = $request->rekomendasi[$index] ?? null;
                            $pengajuanPoItem->nama_customer = $request->nama_customer[$index] ?? null;
                            $pengajuanPoItem->nama_project = $request->nama_project[$index] ?? null;
                            $pengajuanPoItem->no_so = $request->no_so[$index] ?? null;
                        }

                        $pengajuanPoItem->save();
                    }
                } else {
                    // Add new items if no ID is provided
                    $pengajuanPoItem = new MstPoPengajuan();
                    $pengajuanPoItem->no_fpb = $pengajuanPo->no_fpb;
                    $pengajuanPoItem->kategori_po = $pengajuanPo->kategori_po;
                    $pengajuanPoItem->nama_barang = $request->nama_barang[$index];
                    $pengajuanPoItem->spesifikasi = $request->spesifikasi[$index];
                    $pengajuanPoItem->pcs = $request->pcs[$index];

                    // Allow null price_list if kategori_po is 'Subcont'
                    if ($pengajuanPo->kategori_po === 'Subcont') {
                        $pengajuanPoItem->price_list = $request->price_list[$index] ?? null;
                    } else {
                        $pengajuanPoItem->price_list = $request->price_list[$index];
                    }

                    // Calculate total_harga if price_list is available
                    if ($pengajuanPoItem->price_list !== null) {
                        $pengajuanPoItem->total_harga = $request->pcs[$index] * $pengajuanPoItem->price_list;
                    } else {
                        $pengajuanPoItem->total_harga = null; // Or handle this as you prefer
                    }

                    $pengajuanPoItem->status_1 = 2;
                    $pengajuanPoItem->status_2 = 2;
                    // Cari data terakhir berdasarkan no_fpb yang sama
                    $previousPengajuan = MstPoPengajuan::where('no_fpb', $pengajuanPoItem->no_fpb)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($previousPengajuan) {
                        // Jika ditemukan, gunakan nilai modified_at dari data sebelumnya
                        $pengajuanPoItem->modified_at = $previousPengajuan->modified_at;
                    } else {
                        // Jika tidak ditemukan, Anda dapat menetapkan nilai default atau tetap menggunakan user yang login
                        $pengajuanPoItem->modified_at = $request->user()->name;
                    }

                    // Handle file upload for new item
                    if ($request->hasFile('file.' . $index)) {
                        $file = $request->file('file.' . $index);
                        $hashedName = uniqid() . '.' . $file->getClientOriginalExtension();
                        $file->move(public_path('assets/pre_order'), $hashedName);

                        $pengajuanPoItem->file = $hashedName;
                        $pengajuanPoItem->file_name = $file->getClientOriginalName();
                    }

                    // Special handling for Subcont category
                    if ($pengajuanPo->kategori_po === 'Subcont') {
                        $pengajuanPoItem->target_cost = $request->target_cost[$index] ?? null;
                        $pengajuanPoItem->lead_time = $request->lead_time[$index] ?? null;
                        $pengajuanPoItem->rekomendasi = $request->rekomendasi[$index] ?? null;
                        $pengajuanPoItem->nama_customer = $request->nama_customer[$index] ?? null;
                        $pengajuanPoItem->nama_project = $request->nama_project[$index] ?? null;
                        $pengajuanPoItem->no_so = $request->no_so[$index] ?? null;
                    }

                    $pengajuanPoItem->save();

                    // Menambahkan data ke model TrsPoPengajuan
                    $trsPoPengajuan = new TrsPoPengajuan();
                    $trsPoPengajuan->id_fpb = $pengajuanPoItem->id; // Mengisi id_fpb dari MstPoPengajuan
                    $trsPoPengajuan->keterangan = "Item Telah ditambahkan"; // Mengisi field keterangan
                    $trsPoPengajuan->modified_at = $request->user()->name;
                    $trsPoPengajuan->status = 2; // Mengisi field status
                    $trsPoPengajuan->save();
                }
            }
        }

        return redirect()->route('index.PO.Dept')->with('success', 'Data PO berhasil diperbarui.');
    }

    //Sec.Head
    public function updateStatusByNoFPB($no_fpb)
    {
        // Log no_fpb yang diterima
        \Log::info('Received no_fpb: ' . $no_fpb);

        // Ubah kembali tanda minus (-) menjadi garis miring (/)
        $no_fpb = str_replace('-', '/', $no_fpb);
        \Log::info('Transformed no_fpb: ' . $no_fpb);

        // Cari data berdasarkan no_fpb yang telah diubah
        $pengajuanList = MstPoPengajuan::where('no_fpb', $no_fpb)->get();

        if ($pengajuanList->isEmpty()) {
            \Log::error('No data found for no_fpb: ' . $no_fpb); // Log jika data tidak ditemukan
            return redirect()->back()->with('error', 'Data not found');
        }

        // Update status data yang sesuai
        foreach ($pengajuanList as $pengajuan) {
            // Cek jika status_1 atau status_2 bernilai 8
            if ($pengajuan->status_1 == 8 || $pengajuan->status_2 == 8) {
                \Log::warning('Skipping update for no_fpb: ' . $no_fpb . ' because status is 8');
                continue; // Lewati iterasi ini jika status_1 atau status_2 adalah 8
            }

            // Update status_1 dan status_2 menjadi 2
            $pengajuan->update(['status_1' => 2, 'status_2' => 2]);

            // Tambah data ke dalam model TrsPoPengajuan
            TrsPoPengajuan::create([
                'id_fpb' => $pengajuan->id,
                'nama_barang' => $pengajuan->nama_barang, // Menggunakan id dari setiap data MstPoPengajuan
                'modified_at' => auth()->user()->name, // Menyimpan nama user yang login
                'status' => 2,
            ]);
        }

        return redirect()->back()->with('success', 'Status updated successfully for no_fpb: ' . $no_fpb);
    }

    //Dept.Head
    public function updateStatusByDeptHead($no_fpb)
    {
        // Log no_fpb yang diterima
        \Log::info('Received no_fpb: ' . $no_fpb);

        // Ubah kembali tanda minus (-) menjadi garis miring (/)
        $no_fpb = str_replace('-', '/', $no_fpb);
        \Log::info('Transformed no_fpb: ' . $no_fpb);

        // Cari data berdasarkan no_fpb yang telah diubah
        $pengajuanList = MstPoPengajuan::where('no_fpb', $no_fpb)->get();

        if ($pengajuanList->isEmpty()) {
            \Log::error('No data found for no_fpb: ' . $no_fpb); // Log jika data tidak ditemukan
            return redirect()->back()->with('error', 'Data not found');
        }

        // Update status data yang sesuai
        foreach ($pengajuanList as $pengajuan) {
            // Tambahkan kondisi jika kategori_po adalah 'Subcont'
            if ($pengajuan->kategori_po == 'Subcont') {
                // Update status_1 menjadi 4 jika kategori_po adalah Subcont
                $pengajuan->update(['status_1' => 4]);
                \Log::info('Updated status_1 to 4 for no_fpb: ' . $no_fpb . ' because kategori_po is Subcont');

                // Tambah data ke dalam model TrsPoPengajuan
                TrsPoPengajuan::create([
                    'id_fpb' => $pengajuan->id,
                    'nama_barang' => $pengajuan->nama_barang, // Menggunakan id dari setiap data MstPoPengajuan
                    'modified_at' => auth()->user()->name, // Menyimpan nama user yang login
                    'status' => 4, // Set status menjadi 4 karena kategori_po adalah Subcont
                ]);
            } else {
                if ($pengajuan->status_2 == 8) {
                    // Jika status_2 adalah 8, update hanya status_1
                    $pengajuan->update(['status_1' => 3]);

                    \Log::info('Only updated status_1 for no_fpb: ' . $no_fpb . ' because status_2 is 8');
                } else {
                    // Jika status_2 bukan 8, update status_1
                    $pengajuan->update(['status_1' => 3]);
                    \Log::info('Updated status_1 for no_fpb: ' . $no_fpb);

                    // Tambah data ke dalam model TrsPoPengajuan
                    TrsPoPengajuan::create([
                        'id_fpb' => $pengajuan->id,
                        'nama_barang' => $pengajuan->nama_barang, // Menggunakan id dari setiap data MstPoPengajuan
                        'modified_at' => auth()->user()->name, // Menyimpan nama user yang login
                        'status' => 3,
                    ]);
                }
            }
        }
    }

    //user
    public function updateStatusByUser($no_fpb)
    {
        // Log no_fpb yang diterima
        \Log::info('Received no_fpb: ' . $no_fpb);

        // Ubah kembali tanda minus (-) menjadi garis miring (/)
        $no_fpb = str_replace('-', '/', $no_fpb);
        \Log::info('Transformed no_fpb: ' . $no_fpb);

        // Cari data berdasarkan no_fpb yang telah diubah
        $pengajuanList = MstPoPengajuan::where('no_fpb', $no_fpb)->get();

        if ($pengajuanList->isEmpty()) {
            \Log::error('No data found for no_fpb: ' . $no_fpb); // Log jika data tidak ditemukan
            return redirect()->back()->with('error', 'Data not found');
        }

        // Update status data yang sesuai
        foreach ($pengajuanList as $pengajuan) {
            if ($pengajuan->status_2 == 8) {
                // Jika status_2 adalah 8, update hanya status_1
                $pengajuan->update(['status_1' => 4]);

                \Log::info('Only updated status_1 for no_fpb: ' . $no_fpb . ' because status_2 is 8');
            } else {
                // Jika status_2 bukan 8, update status_1
                $pengajuan->update(['status_1' => 4]);
                \Log::info('Updated status_1 for no_fpb: ' . $no_fpb);

                // Tambah data ke dalam model TrsPoPengajuan
                TrsPoPengajuan::create([
                    'id_fpb' => $pengajuan->id,
                    'nama_barang' => $pengajuan->nama_barang, // Menggunakan id dari setiap data MstPoPengajuan
                    'modified_at' => auth()->user()->name, // Menyimpan nama user yang login
                    'status' => 4,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Status updated successfully for no_fpb: ' . $no_fpb);
    }

    //Finance
    public function updateStatusByFinance($no_fpb)
    {
        // Log no_fpb yang diterima
        \Log::info('Received no_fpb: ' . $no_fpb);

        // Ubah kembali tanda minus (-) menjadi garis miring (/)
        $no_fpb = str_replace('-', '/', $no_fpb);
        \Log::info('Transformed no_fpb: ' . $no_fpb);

        // Cari data berdasarkan no_fpb yang telah diubah
        $pengajuanList = MstPoPengajuan::where('no_fpb', $no_fpb)->get();

        if ($pengajuanList->isEmpty()) {
            \Log::error('No data found for no_fpb: ' . $no_fpb); // Log jika data tidak ditemukan
            return redirect()->back()->with('error', 'Data not found');
        }

        // Update status data yang sesuai
        foreach ($pengajuanList as $pengajuan) {
            if ($pengajuan->status_2 == 8) {
                // Jika status_2 adalah 8, update hanya status_1
                $pengajuan->update(['status_1' => 5]);

                \Log::info('Only updated status_1 for no_fpb: ' . $no_fpb . ' because status_2 is 8');
            } else {
                // Jika status_2 bukan 8, update status_1
                $pengajuan->update(['status_1' => 5]);
                \Log::info('Updated status_1 for no_fpb: ' . $no_fpb);

                // Tambah data ke dalam model TrsPoPengajuan
                TrsPoPengajuan::create([
                    'id_fpb' => $pengajuan->id,
                    'nama_barang' => $pengajuan->nama_barang, // Menggunakan id dari setiap data MstPoPengajuan
                    'modified_at' => auth()->user()->name, // Menyimpan nama user yang login
                    'status' => 5,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Status updated successfully for no_fpb: ' . $no_fpb);
    }

    //1 procurment
    public function updateStatusByProcurement($no_fpb)
    {
        // Log no_fpb yang diterima
        \Log::info('Received no_fpb: ' . $no_fpb);

        // Ubah kembali tanda minus (-) menjadi garis miring (/)
        $no_fpb = str_replace('-', '/', $no_fpb);
        \Log::info('Transformed no_fpb: ' . $no_fpb);

        // Cari data berdasarkan no_fpb yang telah diubah
        $pengajuanList = MstPoPengajuan::where('no_fpb', $no_fpb)->get();

        if ($pengajuanList->isEmpty()) {
            \Log::error('No data found for no_fpb: ' . $no_fpb); // Log jika data tidak ditemukan
            return redirect()->back()->with('error', 'Data not found');
        }

        // Update status data yang sesuai
        foreach ($pengajuanList as $pengajuan) {
            if ($pengajuan->status_2 == 8) {
                // Jika status_2 adalah 8, update hanya status_1
                $pengajuan->update(['status_1' => 6]);

                \Log::info('Only updated status_1 for no_fpb: ' . $no_fpb . ' because status_2 is 8');
            } else {
                // Jika status_2 bukan 8, update status_1
                $pengajuan->update(['status_1' => 6]);
                \Log::info('Updated status_1 for no_fpb: ' . $no_fpb);

                // Tambah data ke dalam model TrsPoPengajuan
                TrsPoPengajuan::create([
                    'id_fpb' => $pengajuan->id,
                    'nama_barang' => $pengajuan->nama_barang, // Menggunakan id dari setiap data MstPoPengajuan
                    'modified_at' => auth()->user()->name, // Menyimpan nama user yang login
                    'status' => 6,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Status updated successfully for no_fpb: ' . $no_fpb);
    }

    //2 save procurment
    public function updateConfirmByProcurment($id)
    {
        // Log id yang diterima
        \Log::info('Received id: ' . $id);

        // Cari data berdasarkan id
        $pengajuan = MstPoPengajuan::find($id);

        if (!$pengajuan) {
            \Log::error('No data found for id: ' . $id); // Log jika data tidak ditemukan
            return response()->json(['message' => 'Data not found'], 404);
        }

        // Jika file quotation_file diunggah, update data tanpa mengubah status
        if (request()->hasFile('quotation_file')) {
            $file = request()->file('quotation_file');
            $uuid = (string) Str::uuid(); // Generate UUID untuk nama file
            $extension = $file->getClientOriginalExtension(); // Dapatkan ekstensi file asli

            // Nama file menggunakan UUID dan ekstensi file asli
            $fileName = $uuid . '.' . $extension;

            // Simpan file di public/assets/pre_order
            $filePath = $file->move(public_path('assets/pre_order'), $fileName);

            // Simpan path file di database
            $pengajuan->update([
                'quotation_file' => 'assets/pre_order/' . $fileName, // Simpan path file yang diunggah
            ]);
        } else {
            // Jika no_po diisi, ubah status menjadi 7
            if (request()->filled('no_po')) {
                \Log::info('No PO filled for id: ' . $id);
                $pengajuan->update([
                    'no_po' => request('no_po'),
                    'status_1' => 7, // Ubah status menjadi 7
                    'status_2' => 7,
                ]);
            } else {
                // Jika no_po tidak diisi, tetap simpan keterangan dan status
                $pengajuan->update([
                    'status_1' => 6,
                    'status_2' => 6, // Ubah status menjadi 6
                ]);
            }

            // Save the keterangan in TrsPoPengajuan
            TrsPoPengajuan::create([
                'id_fpb' => $pengajuan->id,
                'no_po' => $pengajuan->no_po, // Tambahkan no_po di sini
                'nama_barang' => $pengajuan->nama_barang,
                'modified_at' => auth()->user()->name, // Menyimpan nama user yang login
                'keterangan' => request('keterangan'), // Get keterangan from request body
                'status' => $pengajuan->status_1, // Simpan status yang terbaru
            ]);
        }

        return response()->json(['message' => 'Status updated successfully for id: ' . $id]);
    }

    //cancel by procurment
    public function rejectItem($id)
    {
        // Validate the incoming request
        request()->validate([
            'keterangan' => 'required|string',
            'no_po' => 'nullable|string', // Tambahkan validasi opsional untuk no_po
        ]);

        // Log id yang diterima
        \Log::info('Received id: ' . $id);

        // Cari data berdasarkan id
        $pengajuan = MstPoPengajuan::find($id);

        if (!$pengajuan) {
            \Log::error('No data found for id: ' . $id); // Log jika data tidak ditemukan
            return response()->json(['message' => 'Data not found'], 404);
        }

        // Jika no_po diisi, ubah status menjadi 7
        if (request()->filled('no_po')) {
            \Log::info('No PO filled for id: ' . $id);
            $pengajuan->update([
                'no_po' => request('no_po'),
                'status_2' => 8,
            ]);
        } else {
            // Jika no_po tidak diisi, tetap simpan keterangan dan status
            $pengajuan->update([
                'status_2' => 8, // Ubah status menjadi 6
            ]);
        }

        // Save the keterangan in TrsPoPengajuan
        TrsPoPengajuan::create([
            'id_fpb' => $pengajuan->id,
            'nama_barang' => $pengajuan->nama_barang,
            'modified_at' => auth()->user()->name, // Menyimpan nama user yang login
            'keterangan' => request('keterangan'), // Get keterangan from request body
            'status' => $pengajuan->status_2, // Simpan status yang terbaru
        ]);

        return response()->json(['message' => 'Status updated successfully for id: ' . $id]);
    }

    //cancel by procurment
    public function updateCancelByProcurment($id)
    {
        // Validate the incoming request
        request()->validate([
            'keterangan' => 'required|string',
            'no_po' => 'nullable|string', // Tambahkan validasi opsional untuk no_po
        ]);

        // Log id yang diterima
        \Log::info('Received id: ' . $id);

        // Cari data berdasarkan id
        $pengajuan = MstPoPengajuan::find($id);

        if (!$pengajuan) {
            \Log::error('No data found for id: ' . $id); // Log jika data tidak ditemukan
            return response()->json(['message' => 'Data not found'], 404);
        }

        // Jika no_po diisi, ubah status menjadi 7
        if (request()->filled('no_po')) {
            \Log::info('No PO filled for id: ' . $id);
            $pengajuan->update([
                'no_po' => request('no_po'),
                'status_2' => 8,
            ]);
        } else {
            // Jika no_po tidak diisi, tetap simpan keterangan dan status
            $pengajuan->update([
                'status_2' => 8, // Ubah status menjadi 6
            ]);
        }

        // Save the keterangan in TrsPoPengajuan
        TrsPoPengajuan::create([
            'id_fpb' => $pengajuan->id,
            'nama_barang' => $pengajuan->nama_barang,
            'modified_at' => auth()->user()->name, // Menyimpan nama user yang login
            'keterangan' => request('keterangan'), // Get keterangan from request body
            'status' => $pengajuan->status_2, // Simpan status yang terbaru
        ]);

        return response()->json(['message' => 'Status updated successfully for id: ' . $id]);
    }
    //pengajuan Cancel
    public function updateCancelBySecHead($id)
    {
        // Validate the incoming request
        request()->validate([
            'keterangan' => 'required|string',
            'no_po' => 'nullable|string', // Tambahkan validasi opsional untuk no_po
        ]);

        // Log id yang diterima
        \Log::info('Received id: ' . $id);

        // Cari data berdasarkan id
        $pengajuan = MstPoPengajuan::find($id);

        if (!$pengajuan) {
            \Log::error('No data found for id: ' . $id); // Log jika data tidak ditemukan
            return response()->json(['message' => 'Data not found'], 404);
        }

        // Update status hanya untuk data dengan ID tertentu
        if (request()->filled('no_po')) {
            \Log::info('No PO filled for id: ' . $id);
            $pengajuan->update([
                'status_1' => 10, // Ubah status menjadi 11 (spesifik untuk ID ini saja)
                'status_2' => 10,
            ]);
        } else {
            $pengajuan->update([
                'status_1' => 10, // Ubah status menjadi 10 (spesifik untuk ID ini saja)
                'status_2' => 10,
            ]);
        }

        // Cari semua data yang memiliki no_fpb yang sama dan update catatan saja
        MstPoPengajuan::where('no_fpb', $pengajuan->no_fpb)
            ->update(['catatan' => 'Terdapat Reject Item']); // Update catatan untuk semua entri dengan no_fpb yang sama

        // Save the keterangan in TrsPoPengajuan untuk baris yang diperbarui
        TrsPoPengajuan::create([
            'id_fpb' => $pengajuan->id,
            'nama_barang' => $pengajuan->nama_barang,
            'modified_at' => auth()->user()->name, // Menyimpan nama user yang login
            'keterangan' => request('keterangan'), // Get keterangan from request body
            'status' => $pengajuan->status_1, // Simpan status yang terbaru
        ]);

        return response()->json(['message' => 'Status updated successfully for id: ' . $id . ' and catatan updated for no_fpb: ' . $pengajuan->no_fpb]);
    }

    public function updateFinishByProcurment($no_fpb)
    {
        // Log no_fpb yang diterima
        \Log::info('Received no_fpb: ' . $no_fpb);

        // Ubah kembali tanda minus (-) menjadi garis miring (/)
        $no_fpb = str_replace('-', '/', $no_fpb);
        \Log::info('Transformed no_fpb: ' . $no_fpb);

        // Cari data berdasarkan no_fpb yang telah diubah
        $pengajuanList = MstPoPengajuan::where('no_fpb', $no_fpb)->get();

        if ($pengajuanList->isEmpty()) {
            \Log::error('No data found for no_fpb: ' . $no_fpb); // Log jika data tidak ditemukan
            return redirect()->back()->with('error', 'Data not found');
        }

        // Update status data yang sesuai
        foreach ($pengajuanList as $pengajuan) {
            $pengajuan->update([
                'status_1' => 9,
                'catatan' => '', // Atau gunakan '' untuk string kosong
            ]);

            // Tambah data ke dalam model TrsPoPengajuan untuk setiap id yang ditemukan
            TrsPoPengajuan::create([
                'id_fpb' => $pengajuan->id,
                'nama_barang' => $pengajuan->nama_barang, // Menggunakan id dari setiap data MstPoPengajuan
                'modified_at' => auth()->user()->name, // Menyimpan nama user yang login
                'status' => 9,
            ]);
        }

        return redirect()->route('index.PO.procurement')->with('success', 'Status updated successfully for no_fpb: ' . $no_fpb);
    }

    public function downloadFile($id)
    {
        // Cari data berdasarkan ID
        $mstPoPengajuan = MstPoPengajuan::find($id);

        if (!$mstPoPengajuan) {
            return abort(404, 'File tidak ditemukan.');
        }

        // Dapatkan nama file dari model
        $fileName = $mstPoPengajuan->file;

        if (!$fileName) {
            return abort(404, 'Tidak ada file yang terlampir.');
        }

        // Tentukan path file di direktori public/assets/pre_order
        $filePath = public_path('assets/pre_order/' . $fileName);

        // Cek apakah file ada di server
        if (!file_exists($filePath)) {
            return abort(404, 'File tidak ditemukan di server.');
        }

        // Kembalikan file sebagai response download
        return response()->download($filePath, $fileName);
    }

    public function getPoHistory($id)
    {
        $history = DB::table('mst_po_pengajuans as mst')
            ->join('trs_po_pengajuans as trs', 'mst.id', '=', 'trs.id_fpb')
            ->select('mst.no_fpb', 'mst.kategori_po', 'trs.no_po', 'mst.nama_barang', 'trs.keterangan', 'trs.status', 'trs.modified_at', 'trs.created_at')
            ->where('mst.id', $id)
            ->orderBy('trs.created_at', 'desc') // Urutkan berdasarkan modified_at secara descending
            ->get();

        return response()->json(['data' => $history]);
    }

    public function getData(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (!$startDate || !$endDate) {
            return response()->json(['error' => 'Please provide both start and end dates.'], 400);
        }

        $data = MstPoPengajuan::select([
            'no_fpb',
            'no_po',
            'kategori_po',
            'nama_barang',
            'pcs',
            'price_list',
            'total_harga',
            'spesifikasi',
            'rekomendasi',
            'target_cost',
            'lead_time',
            'nama_customer',
            'nama_project',
            'no_so',
            'status_1',
            'status_2',
            'created_at',
            'modified_at'
        ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->map(function ($item) {
                // Convert status_1 to text
                switch ($item->status_1) {
                    case 1:
                        $item->status_1 = 'Draf';
                        break;
                    case 9:
                        $item->status_1 = 'Finish';
                        break;
                    default:
                        $item->status_1 = in_array($item->status_1, [2, 3, 4, 5, 6, 7, 8]) ? 'Open' : $item->status_1;
                        break;
                }

                // Convert status_2 to text
                switch ($item->status_2) {
                    case 1:
                        $item->status_2 = 'Draf';
                        break;
                    case 6:
                        $item->status_2 = 'PO Confirm';
                        break;
                    case 7:
                        $item->status_2 = 'PO Release';
                        break;
                    case 8:
                        $item->status_2 = 'Reject';
                        break;
                    case 10:
                        $item->status_2 = 'Pengajuan Reject';
                        break;
                    default:
                        $item->status_2 = in_array($item->status_2, [2, 3, 4, 5]) ? 'Open' : $item->status_2;
                        break;
                }

                return $item;
            });

        return response()->json($data);
    }
}
