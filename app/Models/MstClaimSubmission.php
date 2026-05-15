<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstClaimSubmission extends Model
{
    use HasFactory;

    protected $table = 'mst_claim_submissions';

    protected $fillable = [
        'no_pr',
        'nama_produk',
        'submission_date',
        'category',
        'description_of_issue',
        'proposed_solution',
        'status',
        'file',
        'file_name',
        'catatan_procurement',
        'modified_at',
        'supplier',
    ];

    protected $casts = [
        'submission_date' => 'date',
    ];

    public function trsClaimSubmission()
    {
        return $this->hasMany(TrsClaimSubmission::class, 'id_claim');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open' => 'Open',
            'on_progress' => 'On Progress',
            'finished' => 'Finished',
            default => 'Unknown',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'open' => 'bg-primary',
            'on_progress' => 'bg-warning',
            'finished' => 'bg-success',
            default => 'bg-secondary',
        };
    }
}
