<?php

namespace App\Models\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseLocationShipmentStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseLocationShipment extends Model
{
    use HasFactory;

    protected $table = 'trs_wh_location_shipments';

    protected $fillable = [
        'shipment_number',
        'consumable_id',
        'item_condition',
        'quantity_sent',
        'from_location',
        'to_location',
        'status',
        'sent_by_user_id',
        'sender_npk_snapshot',
        'sender_name_snapshot',
        'sender_notes',
        'sent_at',
        'validation_actor_user_id',
        'validator_user_id',
        'validator_npk_snapshot',
        'validator_name_snapshot',
        'received_quantity',
        'received_condition',
        'validation_notes',
        'validated_at',
        'stock_transaction_id',
        'cancelled_by_user_id',
        'cancelled_at',
        'cancellation_reason',
        'creation_idempotency_key',
        'validation_idempotency_key',
        'cancellation_idempotency_key',
        'migrated_stock_in_id',
        'migration_original_status',
    ];

    protected $casts = [
        'status' => WarehouseLocationShipmentStatus::class,
        'item_condition' => WarehouseItemCondition::class,
        'received_condition' => WarehouseItemCondition::class,
        'quantity_sent' => 'decimal:3',
        'received_quantity' => 'decimal:3',
        'sent_at' => 'datetime',
        'validated_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function consumable(): BelongsTo
    {
        return $this->belongsTo(WarehouseConsumable::class, 'consumable_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function validationActor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validation_actor_user_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_user_id');
    }

    public function stockTransaction(): BelongsTo
    {
        return $this->belongsTo(WarehouseStockTransaction::class, 'stock_transaction_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function migratedStockIn(): BelongsTo
    {
        return $this->belongsTo(WarehouseStockIn::class, 'migrated_stock_in_id');
    }

    public function scopeWaitingValidation(Builder $query): Builder
    {
        return $query->where('status', WarehouseLocationShipmentStatus::WAITING_VALIDATION->value);
    }

    public function scopeReserving(Builder $query): Builder
    {
        return $query->whereIn('status', [
            WarehouseLocationShipmentStatus::WAITING_VALIDATION->value,
            WarehouseLocationShipmentStatus::DISCREPANCY->value,
        ]);
    }

    public function scopeForDestination(Builder $query, string $location): Builder
    {
        return $query->where('to_location', $location);
    }

    public function scopeForSource(Builder $query, string $location): Builder
    {
        return $query->where('from_location', $location);
    }

    public function isTerminal(): bool
    {
        return $this->status?->isTerminal() ?? false;
    }

    public function isReserving(): bool
    {
        return $this->status?->reservesStock() ?? false;
    }

    public function canValidate(): bool
    {
        return $this->status === WarehouseLocationShipmentStatus::WAITING_VALIDATION;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [
            WarehouseLocationShipmentStatus::WAITING_VALIDATION,
            WarehouseLocationShipmentStatus::DISCREPANCY,
        ], true);
    }
}
