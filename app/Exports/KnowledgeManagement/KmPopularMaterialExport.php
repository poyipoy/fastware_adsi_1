<?php

namespace App\Exports\KnowledgeManagement;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KmPopularMaterialExport implements FromArray, ShouldAutoSize, WithStyles
{
    /**
     * @param  array{
     *     rows: Collection<int, array<string, int|string>>,
     *     generated_at: \Illuminate\Support\Carbon,
     *     filters: array{category: int|null, tag_ids: list<int>},
     *     limit_reached: bool,
     *     truncated: bool
     * }  $report
     */
    public function __construct(
        private readonly array $report,
    ) {
    }

    public function array(): array
    {
        $rows = [
            ['Materi Populer — data operasional, bukan KPI'],
            ['Dibuat pada', $this->report['generated_at']->format('d-m-Y H:i:s').' WIB'],
            ['Catatan', 'Counter historis sebelum hardening mungkin memiliki keterbatasan.'],
            ['Filter', $this->filterSummary()],
        ];

        if ($this->report['limit_reached']) {
            $rows[] = [
                'Peringatan',
                $this->report['truncated']
                    ? 'Hasil melebihi 10.000 row; export dipotong pada batas 10.000.'
                    : 'Hasil mencapai batas export 10.000 row.',
            ];
        }

        $rows[] = [];
        $rows[] = ['ID', 'Judul', 'Kategori', 'Tag', 'Total Lihat', 'Pembaca Selesai', 'Jumlah Like'];

        foreach ($this->report['rows'] as $row) {
            $rows[] = [
                $row['id'],
                $row['judul'],
                $row['kategori'],
                $row['tags'],
                $row['total_views'],
                $row['completed_readers'],
                $row['likes_count'],
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $headingRow = $this->report['limit_reached'] ? 7 : 6;

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            $headingRow => ['font' => ['bold' => true]],
        ];
    }

    private function filterSummary(): string
    {
        $parts = [];
        if ($this->report['filters']['category'] !== null) {
            $parts[] = 'category='.$this->report['filters']['category'];
        }
        if ($this->report['filters']['tag_ids'] !== []) {
            $parts[] = 'tag_ids='.implode(',', $this->report['filters']['tag_ids']);
        }

        return $parts === [] ? 'Semua materi published' : implode('; ', $parts);
    }
}
