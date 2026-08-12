<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstSoftSkill extends Model
{
    use HasFactory;

    protected $table = 'mst_soft_skills';

    protected $fillable = [
        'id_poin_kategori',
        'id_job_position',
        'keterangan_sk',
        'deskripsi_sk',
        'deskripsi_level_1',
        'deskripsi_level_2',
        'deskripsi_level_3',
        'deskripsi_level_4',
        'nilai',
    ]; // Allow mass assignment on these fields

    /**
     * Get the job position associated with the soft skill.
     */
    public function jobPosition()
    {
        return $this->belongsTo(MstJobPosition::class, 'id_job_position');
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
