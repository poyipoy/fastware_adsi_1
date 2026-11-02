<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrsPenilaianTc extends Model
{
    use HasFactory;

    protected $table = 'trs_penilaian_tcs';

    protected $fillable = [
        'id_tc',
        'id_sk',
        'id_ad',
        'id_job_position',
        'id_user',
        'nilai_tc',
        'nilai_sk',
        'nilai_ad',
        'total_nilai',
        'status',
        'tahun_penilaian',
        'is_locked',
        'modified_at',
        'modified_updated',
    ];

    protected $casts = [
        'nilai_tc' => 'integer',
        'nilai_sk' => 'integer',
        'nilai_ad' => 'integer',
        'is_locked' => 'boolean',
        'tahun_penilaian' => 'integer',
    ];

    /**
     * Scope: filter by tahun penilaian.
     */
    public function scopeForYear($query, int $year)
    {
        return $query->where('tahun_penilaian', $year);
    }

    /**
     * Scope: hanya data yang belum terkunci.
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    /**
     * Scope: data tahun berjalan (unlocked).
     */
    public function scopeForCurrentYear($query)
    {
        return $query->where('tahun_penilaian', now()->year)->where('is_locked', false);
    }

    /**
     * Lock semua data tahun lama (sebelum tahun berjalan).
     * Dipanggil otomatis saat akses halaman penilaian.
     */
    public static function lockPreviousYears(): int
    {
        return static::where('tahun_penilaian', '<', now()->year)
            ->where('is_locked', false)
            ->update(['is_locked' => true]);
    }

    /**
     * Get distinct tahun penilaian yang tersedia.
     */
    public static function getAvailableYears(): array
    {
        return static::select('tahun_penilaian')
            ->distinct()
            ->orderByDesc('tahun_penilaian')
            ->pluck('tahun_penilaian')
            ->toArray();
    }

    // Relasi ke model Tc (id_tc)
    public function tc()
    {
        return $this->belongsTo(MstTc::class, 'id_tc');
    }

    // Relasi ke model Sk (id_sk)
    public function sk()
    {
        return $this->belongsTo(MstSoftSkill::class, 'id_sk');
    }

    // Relasi ke model Ad (id_ad)
    public function ad()
    {
        return $this->belongsTo(MstAdditionals::class, 'id_ad');
    }

    // Relasi ke model PoinKategori (id_poin_kategori)
    public function poinKategori()
    {
        return $this->belongsTo(PoinKategori::class, 'id_poin_kategori');
    }

    // Relasi ke model JobPosition (id_job_position)
    public function jobPosition()
    {
        return $this->belongsTo(MstJobPosition::class, 'id_job_position');
    }

    // Relasi ke model User (id_user)
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Relasi ke User berdasarkan modified_at
    public function userModifier()
    {
        return $this->belongsTo(User::class, 'modified_at', 'id');
    }

    public function detailPenilaian()
    {
        return $this->hasMany(DetailTcPenilaian::class,'id_job_position');
    }

    public function peopleDevelopments()
    {
        return $this->hasMany(TcPeopleDevelopment::class, 'id_trs');
    }

    public function getDeptHeadNameAttribute()
    {
        $jobPosition = $this->jobPosition;
        if (!$jobPosition) {
            return 'N/A';
        }

        $deptHeadJob = \App\Models\MstJobPosition::where('department_id', $jobPosition->department_id)
            ->where('position_name', 'like', '%Dept%Head%')
            ->first();

        if ($deptHeadJob) {
            $user = $deptHeadJob->activeUsers()->first();
            if ($user) {
                return $user->name;
            }
        }

        return 'N/A';
    }
}
