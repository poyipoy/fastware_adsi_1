<?php

namespace App\Models\Warehouse;

use App\Enums\Warehouse\WarehouseVerificationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseVerificationLog extends Model
{
    use HasFactory;

    protected $table = 'log_wh_verifications';

    protected $fillable = [
        'scanned_code_hash',
        'user_id',
        'transaction_id',
        'status',
        'failure_reason',
        'verified_at',
        'ip_address',
        'user_agent',
    ];

    protected $hidden = ['scanned_code_hash'];

    protected $casts = [
        'status' => WarehouseVerificationStatus::class,
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(WarehouseStockTransaction::class, 'transaction_id');
    }
}
