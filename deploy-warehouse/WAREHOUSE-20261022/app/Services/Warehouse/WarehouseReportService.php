<?php

namespace App\Services\Warehouse;

use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class WarehouseReportService
{
    public function build(int $year, string $condition = 'NEW'): array
    {
        $condition = $this->condition($condition);
        $timezone = (string) config('app.timezone', 'Asia/Jakarta');
        $yearStart = CarbonImmutable::create($year, 1, 1, 0, 0, 0, $timezone)->startOfDay();
        $yearEnd = $yearStart->endOfYear();
        $latest = $this->movementQuery($condition)
            ->whereBetween('transaction_at', [$yearStart, $yearEnd])
            ->max('transaction_at');

        $cutoff = $latest ? CarbonImmutable::parse($latest, $timezone)->endOfMonth() : null;
        $monthCount = $cutoff?->month ?? 0;
        $months = collect($monthCount > 0 ? range(1, $monthCount) : [])->map(static function (int $month) use ($year, $timezone): array {
            $date = CarbonImmutable::create($year, $month, 1, 0, 0, 0, $timezone)->locale('id');

            return ['number' => $month, 'key' => $date->format('Y-m'), 'label' => $date->translatedFormat('M')];
        });

        $items = WarehouseConsumable::query()->orderBy('item_name')->get();
        if ($cutoff === null) {
            return [
                'year' => $year,
                'condition' => $condition,
                'cutoff' => null,
                'months' => $months,
                'items' => $this->emptyRows($items, $condition),
            ];
        }

        $monthly = $this->movementQuery($condition)
            ->whereBetween('transaction_at', [$yearStart, $cutoff])
            ->selectRaw("consumable_id, DATE_FORMAT(transaction_at, '%Y-%m') as month_key")
            ->selectRaw('SUM(GREATEST(stock_after - stock_before, 0)) as incoming')
            ->selectRaw('SUM(GREATEST(stock_before - stock_after, 0)) as outgoing')
            ->selectRaw('SUM(stock_after - stock_before) as net_delta')
            ->groupBy('consumable_id', 'month_key')
            ->get()
            ->groupBy('consumable_id');

        $futureDeltas = $this->movementQuery($condition)
            ->where('transaction_at', '>', $cutoff)
            ->selectRaw('consumable_id, SUM(stock_after - stock_before) as net_delta')
            ->groupBy('consumable_id')
            ->pluck('net_delta', 'consumable_id');

        $rows = $items->map(function (WarehouseConsumable $item) use ($condition, $monthly, $futureDeltas, $months): array {
            $movements = $monthly->get($item->getKey(), collect())->keyBy('month_key');
            $yearDelta = $movements->sum(static fn ($row): float => (float) $row->net_delta);
            $currentStock = $this->currentStockForCondition($item, $condition);
            $balanceAtCutoff = (float) $currentStock - (float) ($futureDeltas[$item->getKey()] ?? 0);
            $opening = $balanceAtCutoff - $yearDelta;
            $running = $opening;
            $monthRows = $months->map(function (array $month) use ($movements, &$running): array {
                $movement = $movements->get($month['key']);
                $incoming = (float) ($movement?->incoming ?? 0);
                $outgoing = (float) ($movement?->outgoing ?? 0);
                $monthOpening = $running;
                $running += $incoming - $outgoing;

                return $month + [
                    'opening' => $this->decimal($monthOpening),
                    'incoming' => $this->decimal($incoming),
                    'outgoing' => $this->decimal($outgoing),
                    'ending' => $this->decimal($running),
                ];
            });
            $total = $monthRows->sum(static fn (array $month): float => (float) $month['ending']);

            return $this->itemRow($item, $currentStock, $monthRows, $total, $monthRows->count());
        });

        return [
            'year' => $year,
            'condition' => $condition,
            'cutoff' => $cutoff,
            'months' => $months,
            'items' => $rows,
        ];
    }

    private function emptyRows(Collection $items, string $condition): Collection
    {
        return $items->map(fn (WarehouseConsumable $item): array => $this->itemRow(
            $item,
            $this->currentStockForCondition($item, $condition),
            collect(),
            0,
            0,
        ));
    }

    private function itemRow(
        WarehouseConsumable $item,
        string $currentStock,
        Collection $months,
        float $total,
        int $monthCount,
    ): array {
        return [
            'id' => $item->getKey(),
            'item_code' => $item->item_code,
            'item_name' => $item->item_name,
            'unit' => $item->unit,
            'is_active' => (bool) $item->is_active,
            'minimum_stock' => (string) $item->minimum_stock,
            'maximum_stock' => $item->maximum_stock === null ? null : (string) $item->maximum_stock,
            'current_stock' => $currentStock,
            'months' => $months,
            'total' => $this->decimal($total),
            'average' => $monthCount > 0 ? (int) ceil($total / $monthCount) : 0,
        ];
    }

    private function currentStockForCondition(WarehouseConsumable $item, string $condition): string
    {
        return match ($condition) {
            'ALL' => (string) $item->current_stock,
            'NEW' => $item->newStock(),
            'USED' => WarehouseQuantity::add(
                (string) $item->stock_used_ds8,
                (string) $item->stock_used_deltamas,
            ),
        };
    }

    private function movementQuery(string $condition): Builder
    {
        $query = WarehouseStockTransaction::query()
            ->whereIn('transaction_type', $this->reportableTypes());

        if ($condition !== 'ALL') {
            $query->where('item_condition', $condition);
        }

        return $query;
    }

    private function condition(string $condition): string
    {
        $condition = strtoupper(trim($condition));
        if (! in_array($condition, ['ALL', 'NEW', 'USED'], true)) {
            throw new \InvalidArgumentException('Condition reporting Warehouse tidak valid.');
        }

        return $condition;
    }

    private function decimal(float $value): string
    {
        return number_format(abs($value) < 0.0005 ? 0 : $value, 3, '.', '');
    }

    /** @return array<int, string> */
    private function reportableTypes(): array
    {
        return [
            WarehouseTransactionType::IN->value,
            WarehouseTransactionType::OUT->value,
            WarehouseTransactionType::ADJUSTMENT->value,
            WarehouseTransactionType::REVERSAL->value,
        ];
    }
}
