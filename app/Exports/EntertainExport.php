<?php

namespace App\Exports;

use App\Models\MstEntertain;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EntertainExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected $userId;
    protected $namaPerusahaan;

    public function __construct($startDate = null, $endDate = null, $userId = null, $namaPerusahaan = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->userId = $userId;
        $this->namaPerusahaan = $namaPerusahaan;
    }

    public function query()
    {
        $query = MstEntertain::with('user');

        // Apply filters
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('tgl', [$this->startDate, $this->endDate]);
        }

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        if ($this->namaPerusahaan) {
            $query->where('nama_perusahaan', 'like', '%' . $this->namaPerusahaan . '%');
        }

        return $query->orderBy('tgl', 'desc');
    }

    public function headings(): array
    {
        return [
            'No',
            'User',
            'Tanggal',
            'Tempat',
            'Alamat',
            'Jenis',
            'Jumlah',
            'Nama',
            'Posisi',
            'Nama Perusahaan',
            'Jenis Usaha',
            'Status Active',
            'Status',
        ];
    }

    public function map($entertain): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $entertain->user ? $entertain->user->name : '-',
            $entertain->tgl ? date('d-m-Y', strtotime($entertain->tgl)) : '-',
            $entertain->tempat ?? '-',
            $entertain->alamat ?? '-',
            $entertain->jenis ?? '-',
            $entertain->jumlah ?? '-',
            $entertain->nama ?? '-',
            $entertain->posisi ?? '-',
            $entertain->nama_perusahaan ?? '-',
            $entertain->jenis_usaha ?? '-',
            $entertain->is_active == 1 ? 'Active' : 'Inactive',
            $entertain->status == 1 ? 'Approved' : 'Pending',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ]
            ],
        ];
    }
}
