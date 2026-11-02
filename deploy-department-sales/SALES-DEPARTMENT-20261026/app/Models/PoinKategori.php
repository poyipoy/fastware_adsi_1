<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoinKategori extends Model
{
    use HasFactory;
    protected $table = 'tc_poin_kategoris'; // Ensuring the model uses the correct table

    protected $fillable = ['judul_keterangan', 'deskripsi_1', 'deskripsi_2', 'deskripsi_3', 'deskripsi_4'];

    public function tc()
    {
        return $this->hasMany(MstTc::class, 'id_poin_kategori');
    }

    public function sk()
    {
        return $this->hasMany(MstSoftSkill::class, 'id_poin_kategori');
    }

    public function ad()
    {
        return $this->hasMany(MstAdditionals::class, 'id_poin_kategori');
    }

    public function penilaianTcs()
    {
        return $this->hasMany(TrsPenilaianTcs::class, 'id_poin_kategori');
    }
}
