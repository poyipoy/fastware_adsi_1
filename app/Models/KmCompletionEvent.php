<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KmCompletionEvent extends Model
{
    protected $fillable = [
        'event_key', 'user_id', 'document_id', 'document_version_id', 'transaction_id',
        'assignment_user_id', 'completion_type', 'acknowledged_at', 'actor_id', 'reason',
        'evidence_snapshot', 'completed_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime', 'completed_at' => 'datetime', 'evidence_snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(KmPengajuan::class, 'document_id');
    }

    public function hrisOutboundEvent(): HasOne
    {
        return $this->hasOne(KmHrisOutboundEvent::class, 'completion_event_id');
    }
}
