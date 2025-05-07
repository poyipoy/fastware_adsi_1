<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KmPengajuan extends Model
{
    use HasFactory;

    protected $table = 'km_pengajuans';

    protected $fillable = [
        'id_user',
        'id_km_kategori',
        'judul',
        'keterangan',
        'posisi',
        'sub_kategori',
        'image',
        'file',
        'file_name',
        'persetujuan',
        'status_baca',
        'status',
        'modified_by',
    ];

    // Relasi dengan KmTransaksi
    public function kmTransaksi()
    {
        return $this->hasMany(KmTransaksi::class, 'id_km_pengajuan');
    }

    // Relasi dengan User
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Relasi dengan User
    public function kmKategori()
    {
        return $this->belongsTo(KmKategori::class, 'id_km_kategori');
    }

    public function kmSukas()
    {
        return $this->hasMany(KmSuka::class, 'id_km_pengajuan');
    }

    public function insights()
    {
        return $this->hasMany(Insight::class, 'id_km_pengajuan');
    }

    public function kmLihatBukus()
    {
        return $this->hasMany(KmLihatBuku::class, 'id_km_pengajuan');
    }
}
