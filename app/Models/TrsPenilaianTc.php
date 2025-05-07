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
        'modified_at',
        'modified_updated',
    ];

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
        return $this->belongsTo(TcJobPosition::class, 'id_job_position');
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
}
