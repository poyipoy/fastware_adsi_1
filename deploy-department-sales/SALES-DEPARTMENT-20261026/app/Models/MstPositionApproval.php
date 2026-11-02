<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstPositionApproval extends Model
{
    use HasFactory;

    protected $table = 'mst_position_approvals';

    protected $fillable = [
        'position_id',
        'approval_level',
        'approver_position_id',
    ];

    protected $casts = [
        'approval_level' => 'integer',
    ];

    // --- Relationships ---

    /**
     * Posisi yang mengajukan (pihak bawah dalam hierarki).
     */
    public function position()
    {
        return $this->belongsTo(MstJobPosition::class, 'position_id');
    }

    /**
     * Posisi approver (pihak atasan dalam hierarki).
     */
    public function approverPosition()
    {
        return $this->belongsTo(MstJobPosition::class, 'approver_position_id');
    }
}
