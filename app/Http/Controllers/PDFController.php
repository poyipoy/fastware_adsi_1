<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Handling;
use App\Models\FormFPP;
use App\Models\Mesin;
use App\Models\Customer;
use App\Models\TypeMaterial;
use App\Models\ScheduleVisit;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
// use Barryvdh\DomPDF\Facade as PDF;
use PDF;
// use Barryvdh\DomPDF\Facade as PDF;

//import Facade "Storage"
use Illuminate\Support\Facades\Storage;

class PDFController extends Controller
{
    //GA JADI PKE GINIAAN PAKE JSAJA
    public function showDetail($no_wo)
    {
        // Mengambil data detail berdasarkan nomor WO
        $record = Handling::where('no_wo', $no_wo)->first();

        // Memastikan data ditemukan
        if ($record) {
            dd($record);
            // Jika data ditemukan, tampilkan halaman detail
            return view('template.pdf', compact('record'));
        } else {
            // Jika data tidak ditemukan, redirect atau tampilkan pesan error
            return redirect()->back()->with('error', 'Record not found.');
        }
    }

    public function downloadPdf($id)
    {
        // Dapatkan data berdasarkan id
        $data = Handling::find($id);

        // Buat PDF menggunakan data tersebut
        $pdf = PDF::loadView('pdf_view', compact('data'));

        // Unduh PDF
        return $pdf->download('file.pdf');
    }

    public function generatePDF(Mesin $mesin)
    {
        // Mengambil formperbaikans berdasarkan status 3 dan nomor_mesin dari mesin yang sama dengan mesin di formperbaikan
        $formperbaikans = FormFPP::where('status', '3')
            ->where('mesin', $mesin->no_mesin)
            ->orderBy('created_at', 'DESC')
            ->get();

        // Mengubah format tanggal
        $formatted_date = date('l, j F Y');

        // Mengubah nilai status menjadi "Closed"
        $formperbaikans->map(function ($formperbaikan) {
            $formperbaikan->status = "Closed";
            return $formperbaikan;
        });

        $data = [
            'title' => 'History Kerusakan Mesin ' . $mesin->no_mesin, // Menambahkan nomor mesin ke judul
            'date' => $formatted_date,
            'formperbaikans' => $formperbaikans
        ];

        $pdf = PDF::loadView('pdf.mesin', $data);

        $filename = 'History Kerusakan Mesin - ' . $mesin->no_mesin . '.pdf'; // Menambahkan nomor mesin ke nama file

        return $pdf->download($filename);
    }
}
