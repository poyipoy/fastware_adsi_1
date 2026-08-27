<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstJobPosition extends Model
{
    use HasFactory;

    protected $table = 'mst_job_positions';

    protected $fillable = [
        'position_name',
        'department_id',
        'section_id',
        'job_level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // --- Relationships ---

    public function department()
    {
        return $this->belongsTo(MstDepartment::class, 'department_id');
    }

    public function section()
    {
        return $this->belongsTo(MstSection::class, 'section_id');
    }

    /**
     * Daftar rute approval untuk posisi ini (sebagai pihak pengaju).
     */
    public function approvalRoutes()
    {
        return $this->hasMany(MstPositionApproval::class, 'position_id')
                    ->orderBy('approval_level');
    }

    /**
     * Rute approval di mana posisi ini bertindak sebagai approver.
     */
    public function approverForRoutes()
    {
        return $this->hasMany(MstPositionApproval::class, 'approver_position_id');
    }

    /**
     * User-user yang menjabat posisi ini.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_job_positions', 'mst_job_position_id', 'user_id')
                    ->withPivot('is_active')
                    ->withTimestamps();
    }

    /**
     * Hanya user aktif yang menjabat posisi ini.
     */
    public function activeUsers()
    {
        return $this->users()->wherePivot('is_active', true);
    }

    // --- Helpers ---

    /**
     * Ambil approver posisi (MstJobPosition) untuk level tertentu.
     */
    public function getApproverPosition(int $level): ?self
    {
        return $this->approvalRoutes
                    ->firstWhere('approval_level', $level)
                    ?->approverPosition;
    }

    /**
     * Scope: posisi yang aktif saja.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
