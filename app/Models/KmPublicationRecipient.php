<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KmPublicationRecipient extends Model
{
    protected $fillable = [
        'publication_batch_id', 'user_id', 'department_snapshot',
        'job_position_snapshot', 'notified_at', 'notification_id', 'inaccessible_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'inaccessible_at' => 'datetime',
    ];
}
