<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KmKategori extends Model
{
    use HasFactory;
    protected $table = 'km_kategoris';

    protected $fillable = [
        'nama_kategori',
        'poin_kategori',
    ];

    // Relasi dengan KmTransaksi
    public function kmPengajuan()
    {
        return $this->hasMany(KmPengajuan::class, 'id_km_kategori');
    }
}
