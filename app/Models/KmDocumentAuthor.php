<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KmDocumentAuthor extends Model
{
    protected $table = 'km_document_authors';

    protected $fillable = [
        'km_pengajuan_id',
        'user_id',
    ];

    protected $casts = [
        'km_pengajuan_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Dokumen yang diatribusikan.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(KmPengajuan::class, 'km_pengajuan_id');
    }

    /**
     * User yang menjadi co-author (atribusi, bukan otorisasi).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
