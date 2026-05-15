<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrsItemCodeHistory extends Model
{
    use HasFactory;

    protected $table = 'trs_item_code_histories';

    protected $fillable = [
        'item_code_id',
        'action',
        'status_from',
        'status_to',
        'summary',
        'change_set',
        'actor_id',
        'actor_name',
    ];

    protected $casts = [
        'change_set' => 'array',
    ];

    public function itemCode(): BelongsTo
    {
        return $this->belongsTo(ItemCode::class, 'item_code_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
