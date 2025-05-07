<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrxDboProgPurchase extends Model
{
    use HasFactory;

    protected $table = 'trx_dbo_progpurchase'; // Pastikan nama tabel sesuai

    protected $fillable = [
        'inquiry_id',
        'user_id',
        'description',
        'created_at',
        'updated_at',
    ];

    // Definisikan relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // Sesuaikan dengan nama field di tabel
    }
}
