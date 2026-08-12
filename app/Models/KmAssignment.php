<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KmAssignment extends Model
{
    protected $fillable = ['document_version_id', 'title', 'status', 'due_at', 'target_snapshot', 'created_by', 'reason'];

    protected $casts = ['due_at' => 'datetime', 'target_snapshot' => 'array'];

    public function version(): BelongsTo
    {
        return $this->belongsTo(KmDocumentVersion::class, 'document_version_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(KmAssignmentUser::class, 'assignment_id');
    }
}
