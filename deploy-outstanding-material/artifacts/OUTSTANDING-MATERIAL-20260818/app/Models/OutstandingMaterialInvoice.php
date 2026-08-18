<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'document_review_required' => 'boolean',
    ];

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
