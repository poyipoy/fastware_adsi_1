<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KmAssignmentUser extends Model
{
    protected $fillable = [
        'assignment_id', 'user_id', 'department_snapshot', 'job_position_snapshot', 'due_at',
        'completed_at', 'exempted_at', 'exempted_by', 'exemption_reason', 'completion_event_id',
        'reminded_h3_at', 'reminded_h1_at', 'overdue_notified_at',
    ];

    protected $casts = [
        'due_at' => 'datetime', 'completed_at' => 'datetime', 'exempted_at' => 'datetime',
        'reminded_h3_at' => 'datetime', 'reminded_h1_at' => 'datetime', 'overdue_notified_at' => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(KmAssignment::class, 'assignment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
