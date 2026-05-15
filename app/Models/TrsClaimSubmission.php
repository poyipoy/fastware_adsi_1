<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrsClaimSubmission extends Model
{
    use HasFactory;

    protected $table = 'trs_claim_submissions';

    protected $fillable = [
        'id_claim',
        'keterangan',
        'status',
        'modified_at',
    ];

    public function mstClaimSubmission()
    {
        return $this->belongsTo(MstClaimSubmission::class, 'id_claim');
    }
}
