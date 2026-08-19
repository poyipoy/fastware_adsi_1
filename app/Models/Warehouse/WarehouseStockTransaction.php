<?php

namespace App\Models\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\User;
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
}
