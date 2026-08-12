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
        'document_version_id',
        'id_user',
        'poin',
        'level',
        'status',
        'modified_by',
        'completed_at',
        'points_awarded_at',
        'last_page',
        'pages_total',
        'unique_pages',
        'unique_pages_count',
        'active_seconds',
        'progress_percent',
        'last_progress_at',
    ];

    protected $casts = [
        'id_km_pengajuan' => 'integer',
        'document_version_id' => 'integer',
        'id_user' => 'integer',
        'poin' => 'integer',
        'level' => 'integer',
        'status' => 'integer',
        'modified_by' => 'integer',
        'completed_at' => 'datetime',
        'points_awarded_at' => 'datetime',
        'last_page' => 'integer',
        'pages_total' => 'integer',
        'unique_pages_count' => 'integer',
        'active_seconds' => 'integer',
        'progress_percent' => 'integer',
        'last_progress_at' => 'datetime',
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

    public function documentVersion()
    {
        return $this->belongsTo(KmDocumentVersion::class, 'document_version_id');
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
