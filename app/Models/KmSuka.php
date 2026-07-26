<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KmSuka extends Model
{
    use HasFactory;

    protected $table = 'km_sukas'; // Specify the table name

    // Define the fillable fields
    protected $fillable = [
        'id_user',
        'id_km_pengajuan',
        'jumlah_like',
    ];

    protected $casts = [
        'id_user' => 'integer',
        'id_km_pengajuan' => 'integer',
        'jumlah_like' => 'integer',
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
