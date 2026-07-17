<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class WorkingExperienceTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function array(): array
    {
        return [
            // Baris Instruksi
            ['Instruksi:', 'Isi tahun mulai (contoh: 2020)', 'Isi tahun selesai (contoh: 2023)', 'Nama Jabatan', 'Harus sesuai nama Section di sistem (perhatikan huruf besar/kecil)', 'Harus sesuai nama Departemen di sistem', 'Keterangan tambahan (Opsional)'],
            
            // Baris Contoh Data
            ['Budi Santoso', '2020', '2023', 'Staff Administrasi', 'Finance', 'Keuangan', 'Contoh keterangan jabatan']
        ];
    }

    public function headings(): array
    {
        return [
            'nama_karyawan',
            'tahun_mulai',
            'tahun_selesai',
            'jabatan',
            'section',
            'departemen',
            'keterangan'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Styling untuk Header (Baris 1)
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['argb' => Color::COLOR_WHITE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FF0F172A', // Slate 900 (Dark)
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Styling untuk Baris Instruksi (Baris 2)
        $sheet->getStyle('A2:G2')->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['argb' => 'FF475569'], // Slate 600
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFF8FAFC', // Slate 50 (Sangat terang)
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'wrapText' => true,
            ],
        ]);

        // Berikan border untuk seluruh tabel (Baris 1 s/d 3)
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'], // Slate 300
                ],
            ],
        ];

        $sheet->getStyle('A1:G3')->applyFromArray($styleArray);

        // Atur tinggi baris instruksi agar text-wrap terlihat
        $sheet->getRowDimension(2)->setRowHeight(40);

        return [];
    }
}
