<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KmBookmark extends Model
{
    protected $table = 'km_bookmarks';

    protected $fillable = [
        'user_id',
        'km_pengajuan_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'km_pengajuan_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(KmPengajuan::class, 'km_pengajuan_id');
    }
}
