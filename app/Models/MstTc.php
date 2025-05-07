<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstTc extends Model
{
    use HasFactory;

    protected $table = 'mst_tcs'; // Define the table name if not standard
    protected $fillable = ['id_job_position', 'id_poin_kategori','keterangan_tc', 'deskripsi_tc', 'nilai'];

    /**
     * Get the job position associated with the TC.
     */
    public function jobPosition()
    {
        return $this->belongsTo(TcJobPosition::class, 'id_job_position');
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
