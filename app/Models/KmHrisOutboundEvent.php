<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KmHrisOutboundEvent extends Model
{
    protected $fillable = [
        'event_key', 'completion_event_id', 'employee_hris_id', 'payload', 'status',
        'attempts', 'next_attempt_at', 'last_error', 'response_checksum_sha256', 'sent_at',
    ];

    protected $casts = [
        'payload' => 'array', 'attempts' => 'integer', 'next_attempt_at' => 'datetime', 'sent_at' => 'datetime',
    ];
}
