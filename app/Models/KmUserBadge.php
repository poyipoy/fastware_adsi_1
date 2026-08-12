<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KmUserBadge extends Model
{
    protected $fillable = ['user_id', 'badge_id', 'event_key', 'evidence', 'awarded_at'];
    protected $casts = ['evidence' => 'array', 'awarded_at' => 'datetime'];

    public function badge(): BelongsTo
    {
        return $this->belongsTo(KmBadge::class, 'badge_id');
    }
}
