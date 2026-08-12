<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KmInsightReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'insight_id',
        'user_id',
        'reaction',
    ];

    protected $casts = [
        'insight_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function insight(): BelongsTo
    {
        return $this->belongsTo(Insight::class, 'insight_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

