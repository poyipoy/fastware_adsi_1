<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Preventive extends Model
{
    use HasFactory;
    protected $table = 'preventives';
    protected $fillable = [
        'nama_mesin',
        'type', 'no_mesin', 'mulai_ops', 'issue',
        'rencana_perbaikan', 'status'
    ];

    protected $dates = ['created_at', 'updated_at'];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d | H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d | H:i:s');
    }

    public function getStatusBackgroundColorAttribute()
    {
        $status = strtolower($this->attributes['status']);

        switch ($status) {
            case '0':
                return 'green'; // Mengubah warna menjadi 'green' untuk status 0 (Open)
            case '1':
                return 'orange'; // Mengubah warna menjadi 'orange' untuk status 1 (On Progress)
            case '2':
                return 'blue'; // Mengubah warna menjadi 'blue' untuk status 2 (Finish)
            case '3':
                return 'black'; // Mengubah warna menjadi 'black' untuk status 3 (Closed)
            default:
                return 'transparent'; // Mengembalikan 'transparent' untuk nilai lain
        }
    }

    public function mesin()
    {
        return $this->belongsTo(Mesin::class);
    }
}
