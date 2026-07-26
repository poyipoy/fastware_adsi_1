<?php

namespace App\Models;

use App\Enums\KnowledgeManagement\KmApprovalAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class KmApprovalEvent extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'km_pengajuan_id',
        'actor_id',
        'actor_name',
        'actor_role_snapshot',
        'action',
        'from_status',
        'to_status',
        'reason',
        'metadata',
        'acted_at',
    ];

    protected $casts = [
        'action' => KmApprovalAction::class,
        'from_status' => 'integer',
        'to_status' => 'integer',
        'metadata' => 'array',
        'acted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Event approval Knowledge Management bersifat append-only.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Event approval Knowledge Management tidak dapat dihapus.');
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(KmPengajuan::class, 'km_pengajuan_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
