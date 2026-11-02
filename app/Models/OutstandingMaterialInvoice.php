<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutstandingMaterialInvoice extends Model
{
    use HasFactory;

    protected $table = 'outstanding_material_invoices';

    protected $fillable = [
        'supplier',
        'number_invoice',
        'invoice_identity_key',
        'packing_list_path',
        'mtc_path',
        'document_review_required',
        'archived_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'document_review_required' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(OutstandingMaterial::class, 'invoice_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
