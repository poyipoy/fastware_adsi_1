<?php

namespace App\Http\Controllers;

use App\Exports\SparepartExport;
use App\Models\Mesin;
use App\Models\Sparepart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SparepartController extends Controller
{
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls', // Validasi file hanya boleh berformat Excel
            'nomor_mesin' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $nomorMesin = $request->input('nomor_mesin');
        $file = $request->file('file');
        $filePath = $file->getRealPath();

        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip the first row (header)
            $header = null;

            foreach ($rows as $index => $row) {
                if ($index < 1) {
                    // Skip the first row (header)
                    $header = $row;
                    continue;
                }

                // Map header columns to row values
                $rowData = array_combine($header, $row);

                // Cek apakah data dengan nomor mesin yang sama sudah ada dalam database
                $existingSparepart = Sparepart::where('nomor_mesin', $nomorMesin)
                    ->where('nama_sparepart', $rowData['Nama Sparepart'])
                    ->where('deskripsi', $rowData['Deskripsi'])
                    ->first();

                // Jika ada data yang sudah ada dengan nomor mesin yang sama, perbarui datanya
                if ($existingSparepart) {
                    $existingSparepart->update([
                        'nama_sparepart' => $rowData['Nama Sparepart'],
                        'deskripsi' => $rowData['Deskripsi'],
                        'jumlah_stok' => $rowData['Jumlah Stok'],
                    ]);

                    $existingSparepart->touch();
                } else {
                    // Jika tidak ada data dengan nomor mesin yang sama, buat entri baru
                    Sparepart::create([
                        'nama_sparepart' => $rowData['Nama Sparepart'],
                        'deskripsi' => $rowData['Deskripsi'],
                        'jumlah_stok' => $rowData['Jumlah Stok'],
                        'nomor_mesin' => $nomorMesin,
                    ]);
                }
            }

            return response()->json(['success' => true, 'message' => 'Data berhasil diimpor.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: '.$e->getMessage()], 500);
        }
    }

    public function export($nomor_mesin)
    {
        $mesin = Mesin::where('no_mesin', $nomor_mesin)->firstOrFail(); // Mendapatkan data mesin berdasarkan nomor mesin yang dipilih
        $formatted_date = now()->format('d F Y'); // Format hari, tanggal, dan bulan
        $nama_file = 'Sparepart - Mesin '.$nomor_mesin.' - '.$formatted_date.'.xlsx'; // Nama file sesuai dengan nomor mesin dan tanggal yang rapi

        return Excel::download(new SparepartExport($nomor_mesin), $nama_file);
    }
}
