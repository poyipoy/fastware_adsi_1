<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserJobAccess extends Model
{
    protected $table = 'tc_user_job_accesses';

    protected $fillable = [
        'user_id',
        'role_id',
        'job_position',
    ];

    /**
     * Relasi ke user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ambil daftar job_position yang bisa diakses oleh user tertentu.
     * Return array of job_position strings, atau array kosong jika tidak ada.
     */
    public static function getPositionsForUser(int $userId): array
    {
        return static::where('user_id', $userId)
            ->pluck('job_position')
            ->toArray();
    }

    /**
     * Ambil daftar job_position yang bisa diakses berdasarkan role_id.
     * Return array of job_position strings, atau array kosong jika tidak ada.
     */
    public static function getPositionsForRole(int $roleId): array
    {
        return static::where('role_id', $roleId)
            ->pluck('job_position')
            ->toArray();
    }
}
