<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KmReadingSession extends Model
{
    protected $fillable = [
        'user_id', 'document_id', 'document_version_id', 'session_hash', 'device_hash',
        'client_active_seconds', 'credited_active_seconds', 'started_at', 'last_seen_at', 'ended_at',
    ];

    protected $casts = [
        'client_active_seconds' => 'integer',
        'credited_active_seconds' => 'integer',
        'started_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
}
