<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WarehouseTransactionsExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $transactions)
    {
    }

    public function collection(): Collection
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return ['Transaction Time', 'Transaction Number', 'Type', 'Item Code', 'Item', 'Unit', 'Quantity', 'Stock Before', 'Stock After', 'Verified User', 'Section', 'Reference', 'Purpose', 'Location', 'Created By', 'Notes'];
    }

    public function map($transaction): array
    {
        return [
            optional($transaction->transaction_at)->format('Y-m-d H:i:s'),
            $transaction->transaction_number,
            $transaction->transaction_type?->value,
            $transaction->consumable?->item_code,
            $transaction->consumable?->item_name,
            $transaction->consumable?->unit,
            (string) $transaction->quantity,
            (string) $transaction->stock_before,
            (string) $transaction->stock_after,
            $transaction->verified_user_name,
            $transaction->verified_user_section,
            $transaction->reference_number,
            $transaction->purpose,
            $transaction->usage_location,
            $transaction->creator?->name,
            $transaction->notes,
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 20, 'B' => 24, 'C' => 14, 'D' => 16, 'E' => 28, 'F' => 12, 'G' => 12, 'H' => 14, 'I' => 14, 'J' => 24, 'K' => 20, 'L' => 20, 'M' => 28, 'N' => 20, 'O' => 24, 'P' => 36];
    }
}
