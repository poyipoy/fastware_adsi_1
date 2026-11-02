<?php

namespace App\Models\Warehouse;

use App\Enums\Warehouse\WarehouseVerificationScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseRestrictedVerifier extends Model
{
    protected $table = 'mst_wh_restricted_verifiers';

    protected $fillable = ['user_id', 'scope', 'is_active'];

    protected $casts = [
        'scope' => WarehouseVerificationScope::class,
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
