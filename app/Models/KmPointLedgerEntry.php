<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class KmPointLedgerEntry extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'km_point_ledger';

    protected $fillable = [
        'user_id',
        'event_type',
        'event_key',
        'points',
        'department_snapshot',
        'km_pengajuan_id',
        'document_version_id',
        'km_insight_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'points' => 'integer',
        'km_pengajuan_id' => 'integer',
        'document_version_id' => 'integer',
        'km_insight_id' => 'integer',
        'notes' => 'array',
        'created_by' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Ledger poin Knowledge Management bersifat append-only.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Entry ledger poin Knowledge Management tidak dapat dihapus.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(KmPengajuan::class, 'km_pengajuan_id');
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(KmDocumentVersion::class, 'document_version_id');
    }

    public function insight(): BelongsTo
    {
        return $this->belongsTo(Insight::class, 'km_insight_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
