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
        return ['Transaction Time', 'Transaction Number', 'Operation Key', 'Type', 'Condition', 'Item Code', 'Item', 'Machine Type', 'Unit', 'Quantity', 'Stock Before', 'Stock After', 'From Location', 'To Location', 'Verified User', 'Section', 'Reference', 'Purpose', 'Created By', 'Notes'];
    }

    public function map($transaction): array
    {
        return [
            optional($transaction->transaction_at)->format('Y-m-d H:i:s'),
            $transaction->transaction_number,
            $transaction->operation_key,
            $transaction->transaction_type?->value,
            $transaction->item_condition?->value,
            $transaction->consumable?->item_code,
            $transaction->consumable?->item_name,
            $transaction->consumable?->machine_type,
            $transaction->consumable?->unit,
            (string) $transaction->quantity,
            (string) $transaction->stock_before,
            (string) $transaction->stock_after,
            $transaction->from_location,
            $transaction->to_location,
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
        return ['A' => 20, 'B' => 24, 'C' => 38, 'D' => 14, 'E' => 12, 'F' => 16, 'G' => 28, 'H' => 20, 'I' => 12, 'J' => 12, 'K' => 14, 'L' => 14, 'M' => 16, 'N' => 16, 'O' => 24, 'P' => 20, 'Q' => 20, 'R' => 28, 'S' => 24, 'T' => 36];
    }
}
