<?php

namespace App\Services\Warehouse;

use App\Models\User;
use App\Models\Warehouse\WarehouseStockTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class WarehouseTransactionQueryService
{
    public function build(array $filters): Builder
    {
        $timezone = (string) config('app.timezone', 'Asia/Jakarta');
        $from = ! empty($filters['date_from'])
            ? CarbonImmutable::parse($filters['date_from'], $timezone)->startOfDay()
            : CarbonImmutable::now($timezone)->subDays((int) config('warehouse.dashboard.default_period_days', 30) - 1)->startOfDay();
        $to = ! empty($filters['date_to'])
            ? CarbonImmutable::parse($filters['date_to'], $timezone)->endOfDay()
            : CarbonImmutable::now($timezone)->endOfDay();

        $query = WarehouseStockTransaction::query()
            ->with(['consumable:id,item_name,item_code,unit,category_id,machine_type', 'creator:id,name'])
            ->whereBetween('transaction_at', [$from, $to]);

        foreach (['transaction_type', 'item_condition', 'consumable_id', 'verified_user_section'] as $column) {
            $input = $column === 'verified_user_section' ? 'section' : $column;
            if (! empty($filters[$input])) {
                $query->where($column, $filters[$input]);
            }
        }
        if (! empty($filters['category_id'])) {
            $query->whereHas('consumable', fn (Builder $builder) => $builder->where('category_id', (int) $filters['category_id']));
        }
        foreach (['reference_number', 'transaction_number', 'operation_key'] as $column) {
            if (! empty($filters[$column])) {
                $query->where($column, 'like', '%'.trim((string) $filters[$column]).'%');
            }
        }

        $workspace = (string) ($filters['workspace'] ?? 'all');
        if ($workspace !== 'all') {
            $definition = config('warehouse.history_workspaces.'.$workspace);
            $userIds = $definition
                ? User::query()
                    ->where('npk', (int) $definition['npk'])
                    ->where('is_active', config('warehouse.identity.active_user_value', 0))
                    ->pluck('id')
                : collect();
            $userIds->count() === 1
                ? $query->where('verified_user_id', $userIds->first())
                : $query->whereRaw('1 = 0');
        } elseif (! empty($filters['verified_user_id'])) {
            $query->where('verified_user_id', (int) $filters['verified_user_id']);
        }

        return $query->orderByDesc('transaction_at')->orderByDesc('id');
    }
}
