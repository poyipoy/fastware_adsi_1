<?php

namespace App\Models\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseConsumable extends Model
{
    use HasFactory;

    protected $table = 'mst_wh_consumables';

    protected $fillable = [
        'category_id',
        'item_code',
        'barcode',
        'item_name',
        'unit',
        'allow_fraction',
        'current_stock',
        'stock_deltamas',
        'stock_ds8',
        'stock_used_deltamas',
        'stock_used_ds8',
        'minimum_stock',
        'maximum_stock',
        'storage_location',
        'machine_type',
        'description',
        'photo_path',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'allow_fraction' => 'boolean',
        'is_active' => 'boolean',
        'current_stock' => 'decimal:3',
        'stock_deltamas' => 'decimal:3',
        'stock_ds8' => 'decimal:3',
        'stock_used_deltamas' => 'decimal:3',
        'stock_used_ds8' => 'decimal:3',
        'minimum_stock' => 'decimal:3',
        'maximum_stock' => 'decimal:3',
    ];

    protected $appends = ['stock_status'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(WarehouseConsumableCategory::class, 'category_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WarehouseStockTransaction::class, 'consumable_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getStockStatusAttribute(): string
    {
        $stock = (float) $this->current_stock;

        if ($stock <= 0) {
            return 'OUT';
        }

        if (config('warehouse.dashboard.low_stock_inclusive', true)
            ? $stock <= (float) $this->minimum_stock
            : $stock < (float) $this->minimum_stock) {
            return 'LOW';
        }

        return 'HEALTHY';
    }

    public function stockAt(string $location): string
    {
        return (string) $this->getAttribute($this->totalStockColumn($location));
    }

    public function usedStockAt(string $location): string
    {
        return (string) $this->getAttribute($this->usedStockColumn($location));
    }

    public function availableAt(string $location, WarehouseItemCondition $condition): string
    {
        $total = (float) $this->stockAt($location);
        $used = (float) $this->usedStockAt($location);

        return number_format($condition === WarehouseItemCondition::USED ? $used : $total - $used, 3, '.', '');
    }

    public function totalStockColumn(string $location): string
    {
        return match ($location) {
            'DS8' => 'stock_ds8',
            'Deltamas' => 'stock_deltamas',
            default => throw new \InvalidArgumentException('Lokasi Warehouse tidak valid.'),
        };
    }

    public function usedStockColumn(string $location): string
    {
        return match ($location) {
            'DS8' => 'stock_used_ds8',
            'Deltamas' => 'stock_used_deltamas',
            default => throw new \InvalidArgumentException('Lokasi Warehouse tidak valid.'),
        };
    }
}
