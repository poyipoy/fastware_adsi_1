<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SumbangSaran extends Model
{
    use HasFactory;
    protected $table = 'sumbang_sarans';
    protected $fillable = [
        'id_user',
        'tgl_pengajuan_ide',
        'lokasi_ide',
        'tgl_diterapkan',
        'plant',
        'judul',
        'keadaan_sebelumnya',
        'image',
        'file_name',
        'usulan_ide',
        'image_2',
        'file_name_2',
        'keuntungan_ide',
        'status',
        'tgl_pengajuan',
        'tgl_verifikasi',
        'suka',
        'modified_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function penilaians(): HasMany
    {
        return $this->hasMany(PenilaianSS::class);
    }
}
