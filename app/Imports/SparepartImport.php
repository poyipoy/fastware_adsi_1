<?php

namespace App\Imports;

use App\Models\Sparepart;
use App\Models\Mesin;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;

class SparepartImport implements ToModel
{
    protected $nomorMesin;

    public function __construct($nomorMesin)
    {
        $this->nomorMesin = $nomorMesin;
    }

    public function model(array $row)
    {
        static $header = null;
        static $existingNomorMesin = [];

        // Jika header belum diambil, ambil dari baris kedua
        if ($header === null) {
            // Memulai dari baris kedua
            $header = $row;
            return null; // Tidak perlu membuat instance model untuk baris header
        }

        // Cek apakah data dengan nomor mesin yang sama sudah ada dalam database
        $existingSparepart = Sparepart::where('nomor_mesin', $this->nomorMesin)
            ->where('nama_sparepart', $row[array_search('Nama Sparepart', $header)])
            ->where('deskripsi', $row[array_search('Deskripsi', $header)])
            ->first();

        // Jika ada data yang sudah ada dengan nomor mesin yang sama, perbarui datanya
        if ($existingSparepart) {
            $existingSparepart->update([
                'nama_sparepart' => $row[array_search('Nama Sparepart', $header)],
                'deskripsi' => $row[array_search('Deskripsi', $header)],
                'jumlah_stok' => $row[array_search('Jumlah Stok', $header)],
            ]);

            $existingSparepart->touch();
        } else {
            // Jika tidak ada data dengan nomor mesin yang sama, buat entri baru
            Sparepart::create([
                'nama_sparepart' => $row[array_search('Nama Sparepart', $header)],
                'deskripsi' => $row[array_search('Deskripsi', $header)],
                'jumlah_stok' => $row[array_search('Jumlah Stok', $header)],
                'nomor_mesin' => $this->nomorMesin,
                // Anda mungkin perlu menambahkan logika pembuatan entri baru lainnya di sini
            ]);
        }

        return null; // Tidak perlu mengembalikan instance model
    }
}
