<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstAdditionals extends Model
{
    use HasFactory;
    protected $table = 'mst_additionals'; // Specify the table name if it does not follow Laravel's naming convention

    protected $fillable = [
        'id_poin_kategori',
        'id_job_position',
        'keterangan_ad',
        'deskripsi_ad',
        'deskripsi_level_1',
        'deskripsi_level_2',
        'deskripsi_level_3',
        'deskripsi_level_4',
        'nilai',
    ]; // Allow mass assignment on these fields

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
        return $this->hasMany(TrsPenilaianTc::class, 'id_ad');
    }

    public function peopleDevelopments()
    {
        return $this->hasMany(TcPeopleDevelopment::class, 'id_ad');
    }
}
