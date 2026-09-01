<?php

namespace App\Models\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\User;
use App\Services\Warehouse\WarehouseQuantity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WarehouseStockTransaction extends Model
{
    use HasFactory;

    protected $table = 'trs_wh_stock_transactions';

    protected $fillable = [
        'transaction_number',
        'idempotency_key',
        'operation_key',
        'transaction_type',
        'item_condition',
        'from_location',
        'to_location',
        'consumable_id',
        'quantity',
        'stock_before',
        'stock_after',
        'verified_user_id',
        'verified_user_name',
        'verified_user_npk',
        'verified_user_section',
        'reference_number',
        'purpose',
        'usage_location',
        'notes',
        'reversal_of_id',
        'location_shipment_id',
        'stock_in_id',
        'machine_type_used',
        'transaction_at',
        'created_by',
    ];

    protected $casts = [
        'transaction_type' => WarehouseTransactionType::class,
        'item_condition' => WarehouseItemCondition::class,
        'quantity' => 'decimal:3',
        'stock_before' => 'decimal:3',
        'stock_after' => 'decimal:3',
        'transaction_at' => 'datetime',
    ];

    public function consumable(): BelongsTo
    {
        return $this->belongsTo(WarehouseConsumable::class, 'consumable_id');
    }

    public function verifiedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    public function locationShipment(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationShipment::class, 'location_shipment_id');
    }

    public function stockIn(): BelongsTo
    {
        return $this->belongsTo(WarehouseStockIn::class, 'stock_in_id');
    }

    public function getDisplayLocationAttribute(): ?string
    {
        $usage = trim((string) $this->usage_location);
        $usage = $usage === '' ? null : $usage;
        $from = $this->from_location ?: null;
        $to = $this->to_location ?: null;

        $type = $this->transaction_type instanceof WarehouseTransactionType
            ? $this->transaction_type
            : WarehouseTransactionType::tryFrom((string) $this->transaction_type);

        return match ($type) {
            WarehouseTransactionType::IN,
            WarehouseTransactionType::TRANSFER => $to ?: $usage ?: $from,
            WarehouseTransactionType::OUT => $from ?: $usage ?: $to,
            WarehouseTransactionType::ADJUSTMENT,
            WarehouseTransactionType::REVERSAL => $this->movementDelta() >= 0
                ? ($to ?: $usage ?: $from)
                : ($from ?: $usage ?: $to),
            default => $usage ?: $to ?: $from,
        };
    }

    private function movementDelta(): int
    {
        return WarehouseQuantity::toMilli((string) $this->stock_after)
            - WarehouseQuantity::toMilli((string) $this->stock_before);
    }
}
