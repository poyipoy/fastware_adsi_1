<?php

namespace App\Exports;

use App\Models\SafetyPatrol;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;


class SafetyExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $patrols = SafetyPatrol::all();
        $patrols->transform(function ($patrol, $key) {
            return [
                'No' => $key + 1,
                'Tanggal Patrol' => $patrol->tanggal_patrol,
                'Area Patrol' => $patrol->area_patrol,
                'PIC Area' => $patrol->pic_area,
                'Kelengkapan Alat' => $patrol->kategori_1,
                'Kerapihan Area Kerja' => $patrol->kategori_2,
                'Kondisi Lingkungan Kerja' => $patrol->kategori_3,
                'Penempatan Alat/Barang' => $patrol->kategori_4,
                'Praktik 5S/5R' => $patrol->kategori_5,
                'Catatan Kategori 5S/5R' => $patrol->kategori_catatan,
                'Checksheet APAR' => $patrol->safety_1,
                'Penggunaan APD' => $patrol->safety_2,
                'Potensi Bahaya' => $patrol->safety_3,
                'Pemeliharaan APD' => $patrol->safety_4,
                'Kelengkapan APD' => $patrol->safety_5,
                'Catatan Safety' => $patrol->safety_catatan,
                'Pengelolaan Jenis & Kriteria Limbah' => $patrol->lingkungan_1,
                'Kebersihan Lingkungan' => $patrol->lingkungan_2,
                'Penyimpanan Limbah' => $patrol->lingkungan_3,
                'Tempat Sampah' => $patrol->lingkungan_4,
                'Catatan Lingkungan' => $patrol->lingkungan_catatan,
            ];
        });

        return $patrols;
    }

    public function headings(): array
    {
        return [
            "No",
            "Tanggal Patrol",
            "Area Patrol",
            "PIC Area",
            "Kelengkapan Alat",
            "Kerapihan Area Kerja",
            "Kondisi Lingkungan Kerja",
            "Penempatan Alat/Barang",
            "Praktik 5S/5R",
            "Catatan Kategori 5S/5R",
            "Checksheet APAR",
            "Penggunaan APD",
            "Potensi Bahaya",
            "Pemeliharaan APD",
            "Kelengkapan APD",
            "Catatan Safety",
            "Pengelolaan Jenis & Kriteria Limbah",
            "Kebersihan Lingkungan",
            "Penyimpanan Limbah",
            "Tempat Sampah",
            "Catatan Lingkungan"
        ];
    }
}
