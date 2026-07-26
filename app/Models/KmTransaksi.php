<?php

namespace App\Models;

use App\Enums\KnowledgeManagement\KmReadStatus;
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
        'completed_at',
        'points_awarded_at',
    ];

    protected $casts = [
        'id_km_pengajuan' => 'integer',
        'id_user' => 'integer',
        'poin' => 'integer',
        'level' => 'integer',
        'status' => 'integer',
        'modified_by' => 'integer',
        'completed_at' => 'datetime',
        'points_awarded_at' => 'datetime',
    ];

    public function readStatus(): ?KmReadStatus
    {
        return KmReadStatus::tryFrom((int) $this->status);
    }

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
