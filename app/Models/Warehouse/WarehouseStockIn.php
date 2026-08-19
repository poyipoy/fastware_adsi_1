<?php

namespace App\Models\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseStockInStatus;
use App\Enums\Warehouse\WarehouseStockInValidationResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseStockIn extends Model
{
    use HasFactory;

    protected $table = 'trs_wh_stock_ins';

    protected $fillable = [
        'stock_in_number',
        'creation_idempotency_key',
        'validation_idempotency_key',
        'cancellation_idempotency_key',
        'status',
        'validation_result',
        'consumable_id',
        'item_condition',
        'quantity_expected',
        'quantity_received',
        'received_consumable_id',
        'received_condition',
        'destination_location',
        'source_location',
        'notes',
        'validation_notes',
        'cancellation_reason',
        'created_by',
        'creator_npk_snapshot',
        'creator_name_snapshot',
        'validated_at',
        'validator_user_id',
        'validator_npk_snapshot',
        'validator_name_snapshot',
        'cancelled_by_user_id',
        'cancelled_at',
        'stock_transaction_id',
    ];

    protected $casts = [
        'status' => WarehouseStockInStatus::class,
        'validation_result' => WarehouseStockInValidationResult::class,
        'item_condition' => WarehouseItemCondition::class,
        'received_condition' => WarehouseItemCondition::class,
        'quantity_expected' => 'decimal:3',
        'quantity_received' => 'decimal:3',
        'validated_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function consumable(): BelongsTo
    {
        return $this->belongsTo(WarehouseConsumable::class, 'consumable_id');
    }

    public function receivedConsumable(): BelongsTo
    {
        return $this->belongsTo(WarehouseConsumable::class, 'received_consumable_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_user_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function stockTransaction(): BelongsTo
    {
        return $this->belongsTo(WarehouseStockTransaction::class, 'stock_transaction_id');
    }

    public function scopeWaitingValidation(Builder $query): Builder
    {
        return $query->where('status', WarehouseStockInStatus::WAITING_VALIDATION->value);
    }

    public function scopeReserving(Builder $query): Builder
    {
        return $query->waitingValidation()->whereNotNull('source_location');
    }

    public function scopeForSource(Builder $query, string $location): Builder
    {
        return $query->where('source_location', $location);
    }

    public function canValidate(): bool
    {
        return $this->status === WarehouseStockInStatus::WAITING_VALIDATION;
    }

    public function canCancel(): bool
    {
        return $this->status === WarehouseStockInStatus::WAITING_VALIDATION;
    }
}
