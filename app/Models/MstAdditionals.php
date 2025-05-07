<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstAdditionals extends Model
{
    use HasFactory;
    protected $table = 'mst_additionals'; // Specify the table name if it does not follow Laravel's naming convention

    protected $fillable = ['id_job_position', 'id_poin_kategori', 'keterangan_ad', 'deskripsi_ad','nilai']; // Allow mass assignment on these fields

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
        return $this->hasMany(TrsPenilaianTc::class, 'id_ad');
    }

    public function peopleDevelopments()
    {
        return $this->hasMany(TcPeopleDevelopment::class, 'id_ad');
    }
}
