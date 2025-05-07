<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize; // Import ShouldAutoSize
use App\Models\Sparepart;

class SparepartExport implements FromCollection, WithHeadings, ShouldAutoSize // Implement ShouldAutoSize
{
    protected $nomor_mesin;

    public function __construct($nomor_mesin)
    {
        $this->nomor_mesin = $nomor_mesin;
    }

    public function collection()
    {
        $spareparts = Sparepart::select("nama_sparepart", "deskripsi", "jumlah_stok")
            ->where('nomor_mesin', $this->nomor_mesin)
            ->get();

        // Menambahkan nomor pada setiap baris data
        $numberedSpareparts = $spareparts->map(function ($sparepart, $index) {
            $sparepartData = [
                'No' => $index + 1,
                'Nama Sparepart' => $sparepart->nama_sparepart,
                'Deskripsi' => $sparepart->deskripsi,
                'Jumlah Stok' => $sparepart->jumlah_stok
            ];

            return $sparepartData;
        });

        return $numberedSpareparts;
    }

    public function headings(): array
    {
        return ["No", "Nama Sparepart", "Deskripsi", "Jumlah Stok"];
    }
}
