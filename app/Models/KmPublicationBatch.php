<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KmPublicationBatch extends Model
{
    protected $fillable = [
        'document_version_id', 'status', 'recipient_count', 'processed_count',
        'last_error', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'recipient_count' => 'integer',
        'processed_count' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(KmDocumentVersion::class, 'document_version_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(KmPublicationRecipient::class, 'publication_batch_id');
    }
}
