<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstTc extends Model
{
    use HasFactory;

    protected $table = 'mst_tcs'; // Define the table name if not standard
    protected $fillable = [
        'id_poin_kategori',
        'id_job_position',
        'keterangan_tc',
        'sub_kategori',
        'deskripsi_tc',
        'deskripsi_level_1',
        'deskripsi_level_2',
        'deskripsi_level_3',
        'deskripsi_level_4',
        'nilai',
    ];

    /**
     * Get the job position associated with the TC.
     */
    public function jobPosition()
    {
        return $this->belongsTo(MstJobPosition::class, 'id_job_position');
    }

    public function poinKategori()
    {
        return $this->belongsTo(PoinKategori::class, 'id_poin_kategori');
    }

    // Relasi ke model TrsPenilaianTcs
    public function penilaianTcs()
    {
        return $this->hasMany(TrsPenilaianTc::class, 'id_tc');
    }

    public function peopleDevelopments()
    {
        return $this->hasMany(TcPeopleDevelopment::class, 'id_tc');
    }
}
