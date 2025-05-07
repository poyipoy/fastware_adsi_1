<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'users_id',
        'handling_id',
        'schedule',
        'results',
        'due_date',
        'notes',
        'pic',
        'file',
        'file_name',
        'history_type',
        'status',
    ];

    public function handlings(): BelongsTo
    {
        return $this->belongsTo(Handling::class, 'handling_id');
    }

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }
}
