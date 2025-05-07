<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstSoftSkill extends Model
{
    use HasFactory;

    protected $table = 'mst_soft_skills';

    protected $fillable = ['id_job_position','id_poin_kategori','keterangan_sk', 'deskripsi_sk','nilai']; // Allow mass assignment on these fields

    /**
     * Get the job position associated with the soft skill.
     */
    public function jobPosition()
    {
        return $this->belongsTo(TcJobPosition::class, 'id_job_position');
    }

    public function poinKategori()
    {
        return $this->belongsTo(PoinKategori::class, 'id_poin_kategori');
    }

    public function penilaianTcs()
    {
        return $this->hasMany(TrsPenilaianTc::class, 'id_sk');
    }

    public function peopleDevelopments()
    {
        return $this->hasMany(TcPeopleDevelopment::class, 'id_sk');
    }
}
