<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class UserJobPosition extends Model
{
    use HasFactory;

    protected $table = 'user_job_positions';

    protected $fillable = [
        'user_id',
        'mst_job_position_id',
        'is_active',
        'effective_from',
        'effective_until',
        'assignment_source',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function scopeActiveAt(
        Builder $query,
        CarbonInterface|string|null $date = null,
    ): Builder {
        $effectiveDate = $date instanceof CarbonInterface
            ? $date->toDateString()
            : ($date ?: today()->toDateString());

        $query->where($this->qualifyColumn('is_active'), true);

        if (! Schema::hasColumn($this->getTable(), 'effective_from')) {
            return $query;
        }

        return $query
            ->whereNotNull($this->qualifyColumn('effective_from'))
            ->whereDate($this->qualifyColumn('effective_from'), '<=', $effectiveDate)
            ->where(function (Builder $dates) use ($effectiveDate): void {
                $dates->whereNull($this->qualifyColumn('effective_until'))
                    ->orWhereDate($this->qualifyColumn('effective_until'), '>=', $effectiveDate);
            });
    }

    // --- Relationships ---

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jobPosition()
    {
        return $this->belongsTo(MstJobPosition::class, 'mst_job_position_id');
    }
}
