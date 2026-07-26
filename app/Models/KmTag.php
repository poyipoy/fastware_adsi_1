<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KmTag extends Model
{
    use HasFactory;

    protected $table = 'km_tags';

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Dokumen-dokumen yang memiliki tag ini.
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            KmPengajuan::class,
            'km_document_tag',
            'km_tag_id',
            'km_pengajuan_id'
        )->withTimestamps();
    }
}
