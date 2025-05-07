<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KmTransaksi extends Model
{
    use HasFactory;

    protected $table = 'km_transaksis';

    protected $fillable = [
        'id_km_pengajuan',
        'id_user',
        'poin',
        'level',
        'status',
        'modified_by',
    ];

    // Relasi dengan KmPengajuan
    public function kmPengajuan()
    {
        return $this->belongsTo(KmPengajuan::class, 'id_km_pengajuan');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function kmLihatBukus()
    {
        return $this->hasMany(KmLihatBuku::class, 'id_km_transaksi');
    }
}
