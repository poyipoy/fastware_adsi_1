<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany; 
use Illuminate\Database\Eloquent\SoftDeletes;

class Insight extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'km_insights';

    protected $fillable = [
        'id_user',
        'id_km_pengajuan',
        'document_version_id',
        'parent_id',
        'content',
        'edited_at',
        'deleted_by',
        'delete_reason',
        'featured_at',
        'featured_by',
    ];

    protected $casts = [
        'id_user' => 'integer',
        'id_km_pengajuan' => 'integer',
        'document_version_id' => 'integer',
        'parent_id' => 'integer',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
        'deleted_by' => 'integer',
        'featured_at' => 'datetime',
        'featured_by' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function kmPengajuan(): BelongsTo
    {
        return $this->belongsTo(KmPengajuan::class, 'id_km_pengajuan');
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(KmDocumentVersion::class, 'document_version_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')->withTrashed();
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(KmInsightReaction::class, 'insight_id');
    }

    public function mentionedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'km_insight_mentions',
            'insight_id',
            'mentioned_user_id'
        )->withPivot('created_at');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function featuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'featured_by');
    }

    public function isEditableBy(User $user): bool
    {
        return $this->deleted_at === null
            && (int) $this->id_user === (int) $user->getKey()
            && $this->created_at !== null
            && $this->created_at->gte(
                now()->subMinutes((int) config('knowledge_management.insights.edit_window_minutes', 30))
            );
    }
}
