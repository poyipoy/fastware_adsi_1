<?php

namespace App\Exports;

use App\Models\MstPengajuanSubcont;
use App\Models\TrsPengajuanSubcont;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomRequestExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @var \Illuminate\Support\Collection<int, \App\Models\MstPengajuanSubcont>
     */
    protected Collection $materials;

    /**
     * @var array<int, int|null>
     */
    protected array $indicatorDays = [];

    /**
     * @var array<int, int|null>
     */
    protected array $leadTimes = [];

    /**
     * @var array<int>
     */
    protected array $selectedIds = [];

    protected int $rowCounter = 0;

    public function __construct(array $selectedIds = [])
    {
        $this->selectedIds = $selectedIds;
        $this->prepareData();
    }

    public function collection(): Collection
    {
        return $this->materials;
    }

    public function headings(): array
    {
        return [
            'Status Indicator',
            'No',
            'No Ref',
            'PIC',
            'Nama Customer',
            'Nama Project',
            'No SO',
            'Keterangan',
            'Part Name',
            'Note Sales',
            'Jenis Proses',
            'Tgl Pengajuan',
            'LeadTime',
            'Status',
            'Cost Production',
            'Selling Price',
            'Profit (%)',
            'Custom',
            'Custom Approval',
            'Marketing Dept Head',
            'Marketing Approval',
            'Finance Dept Head',
            'Finance Approval',
            'Quotation Subcont',
        ];
    }

    /**
     * @param \App\Models\MstPengajuanSubcont $pengajuan
     */
    public function map($pengajuan): array
    {
        $this->rowCounter++;

        $indicatorDays = $this->indicatorDays[$pengajuan->id] ?? null;
        $indicatorLabel = '';

        if (is_numeric($indicatorDays)) {
            if ($indicatorDays > 3) {
                $indicatorLabel = 'Outstanding';
            } elseif ($indicatorDays >= 0 && $indicatorDays <= 3) {
                $indicatorLabel = 'On Track';
            }
        }

        $leadTimeValue = $this->leadTimes[$pengajuan->id] ?? null;
        $leadTimeLabel = is_numeric($leadTimeValue) ? $leadTimeValue . ' Hari' : '-';

        $statusLabel = $this->resolveStatusLabel($pengajuan);

        $profitLabel = $this->formatProfit($pengajuan->harga_awal, $pengajuan->harga_akhir);

        return [
            $indicatorLabel,
            $this->rowCounter,
            $pengajuan->no_ref,
            $pengajuan->modified_at ?? '',
            $pengajuan->nama_customer ?? '',
            $pengajuan->nama_project ?? '',
            $pengajuan->so ?? '',
            $pengajuan->keterangan ?? '',
            $pengajuan->part_name ?? '',
            $pengajuan->note_sales ?? '',
            $pengajuan->jenis_proses_subcont !== 'Null' ? $pengajuan->jenis_proses_subcont : '',
            $pengajuan->created_at ? Carbon::parse($pengajuan->created_at)->toDateTimeString() : '',
            $leadTimeLabel,
            $statusLabel,
            $this->formatCurrency($pengajuan->harga_awal),
            $this->formatCurrency($pengajuan->harga_akhir),
            $profitLabel,
            optional($pengajuan->production)->name ?? '',
            $pengajuan->date_confirm_prod ?? '',
            optional($pengajuan->marketing)->name ?? '',
            $pengajuan->date_app_1 ?? '',
            optional($pengajuan->finance)->name ?? '',
            $pengajuan->date_app_2 ?? '',
            $pengajuan->quotation_file ? asset($pengajuan->quotation_file) : '',
        ];
    }

    protected function resolveStatusLabel($pengajuan): string
    {
        $statusLabels = [
            1 => 'Draft',
            2 => 'Open',
            3 => 'On Progress',
            4 => 'On Progress',
            5 => 'Finish',
        ];

        if ($pengajuan->sec_line == 1) {
            return $statusLabels[$pengajuan->status_1] ?? 'Status Tidak Tersedia';
        }

        if (in_array($pengajuan->status_1, [1, 2], true)) {
            return $statusLabels[2];
        }

        return $statusLabels[$pengajuan->status_1] ?? 'Status Tidak Tersedia';
    }

    protected function formatCurrency($value): string
    {
        return $value !== null ? 'Rp' . number_format($value, 0, ',', '.') : '';
    }

    protected function formatProfit($hargaAwal, $hargaAkhir): string
    {
        if ($hargaAkhir > 0) {
            $profit = (($hargaAkhir - $hargaAwal) / $hargaAkhir) * 100;
            return number_format($profit, 2) . '%';
        }

        return '';
    }

    protected function prepareData(): void
    {
        $query = MstPengajuanSubcont::with(['sales', 'marketing', 'production', 'finance'])
            ->whereIn('sec_line', [1, 2])
            ->orderBy('created_at', 'desc');

        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
        }

        $this->materials = $query->get();

        $today = Carbon::now()->startOfDay();

        $activityLogs = $this->materials->isNotEmpty()
            ? TrsPengajuanSubcont::whereIn('id_subcont', $this->materials->pluck('id'))
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('id_subcont')
            : collect();

        foreach ($this->materials as $item) {
            if ($item->confirm_prod && $item->harga_akhir && is_null($item->approval_1)) {
                $this->indicatorDays[$item->id] = Carbon::parse($item->updated_at)->startOfDay()->diffInDays($today);
            } elseif ($item->approval_1 && $item->date_app_1) {
                $this->indicatorDays[$item->id] = Carbon::parse($item->date_app_1)->startOfDay()->diffInDays($today);
            } else {
                $this->indicatorDays[$item->id] = null;
            }

            $createdAt = Carbon::parse($item->created_at)->startOfDay();

            if ($item->status_1 == 5) {
                $logsForItem = $activityLogs->get($item->id, collect());
                $latestLog = $logsForItem->first();

                if ($latestLog) {
                    $logUpdatedAt = Carbon::parse($latestLog->updated_at)->startOfDay();
                    $this->leadTimes[$item->id] = $createdAt->diffInDays($logUpdatedAt);
                } else {
                    $this->leadTimes[$item->id] = $createdAt->diffInDays($today);
                }
            } else {
                $this->leadTimes[$item->id] = $createdAt->diffInDays($today);
            }
        }
    }
}
