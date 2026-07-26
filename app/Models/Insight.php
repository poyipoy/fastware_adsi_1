<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insight extends Model
{
    use HasFactory;

    protected $table = 'km_insights';

    protected $fillable = [
        'id_user', 'id_km_pengajuan', 'content',
    ];

    protected $casts = [
        'id_user' => 'integer',
        'id_km_pengajuan' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function kmPengajuan()
    {
        return $this->belongsTo(KmPengajuan::class, 'id_km_pengajuan');
    }
}
