<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KmLihatBuku extends Model
{
    use HasFactory;

    protected $table = 'km_lihat_bukus';

    // The attributes that are mass assignable
    protected $fillable = [
        'id_km_transaksi',
        'id_km_pengajuan',
        'jumlah_lihat',
    ];

    public function kmTransaksi()
    {
        return $this->belongsTo(KmTransaksi::class, 'id_km_transaksi');
    }

    // If KmLihatBuku belongs to a KmPengajuan
    public function kmPengajuan()
    {
        return $this->belongsTo(KmPengajuan::class, 'id_km_pengajuan');
    }
}
