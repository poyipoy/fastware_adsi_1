<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseStockTransactionMachineUsage extends Model
{
    use HasFactory;

    protected $table = 'trs_wh_stock_transaction_machine_usages';

    protected $fillable = [
        'transaction_id',
        'machine_code',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(WarehouseStockTransaction::class, 'transaction_id');
    }
}
