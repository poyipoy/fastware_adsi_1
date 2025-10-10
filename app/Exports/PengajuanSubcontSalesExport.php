<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PengajuanSubcontSalesExport implements FromCollection, WithHeadings
{
    /**
     * @var \Illuminate\Support\Collection<int, \App\Models\MstPengajuanSubcont>
     */
    protected Collection $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function collection(): Collection
    {
        return $this->rows->values()->map(function ($item, $index) {
            return [
                'No' => $index + 1,
                'PIC' => $item->modified_at,
                'Nama Customer' => $item->nama_customer,
                'QTY' => $item->qty,
                'Nama Project' => $item->nama_project,
                'Keterangan' => $item->keterangan,
                'Jenis Proses Subcont' => $item->jenis_proses_subcont,
                'Tgl Pengajuan' => $item->created_at,
                'Update Terakhir' => $item->latest_keterangan,
                'Status' => $this->mapStatus($item->status_1),
                'Quotation' => $item->quotation_file ? asset($item->quotation_file) : 'Quotation belum tersedia',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'PIC',
            'Nama Customer',
            'QTY',
            'Nama Project',
            'Keterangan',
            'Jenis Proses Subcont',
            'Tgl Pengajuan',
            'Update Terakhir',
            'Status',
            'Quotation',
        ];
    }

    protected function mapStatus(?int $status): string
    {
        return match ($status) {
            1 => 'Draf',
            2 => 'Open',
            3, 4 => 'On Progress',
            5 => 'Finish',
            default => 'Status Tidak Tersedia',
        };
    }
}

