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
        return ['Transaction Time', 'Transaction Number', 'Type', 'Condition', 'Item Code', 'Item', 'Machine Type', 'Unit', 'Quantity', 'Location', 'Stock Before', 'Stock After', 'Employee', 'Section', 'Reference', 'Purpose', 'Created By', 'Notes'];
    }

    public function map($transaction): array
    {
        return [
            optional($transaction->transaction_at)->format('Y-m-d H:i:s'),
            $transaction->transaction_number,
            $transaction->transaction_type?->value,
            $transaction->item_condition?->value,
            $transaction->consumable?->item_code,
            $transaction->consumable?->item_name,
            $transaction->consumable?->machine_type,
            $transaction->consumable?->unit,
            (string) $transaction->quantity,
            $transaction->display_location,
            (string) $transaction->stock_before,
            (string) $transaction->stock_after,
            $transaction->verified_user_name,
            $transaction->verified_user_section,
            $transaction->reference_number,
            $transaction->purpose,
            $transaction->creator?->name,
            $transaction->notes,
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 20, 'B' => 24, 'C' => 14, 'D' => 12, 'E' => 16, 'F' => 28, 'G' => 20, 'H' => 12, 'I' => 12, 'J' => 18, 'K' => 14, 'L' => 14, 'M' => 24, 'N' => 20, 'O' => 20, 'P' => 28, 'Q' => 24, 'R' => 36];
    }
}
