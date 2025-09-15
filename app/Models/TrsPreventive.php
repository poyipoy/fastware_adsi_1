<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrsPreventive extends Model
{
    use HasFactory;

    protected $table = 'trs_preventives';

    protected $fillable = [
        'prev_id',
        'keterangan',
        'modified_at'
    ];

    public function jadwalPreventif()
    {
        return $this->belongsTo(JadwalPreventif::class, 'prev_id');
    }

    public function userprev()
    {
        return $this->belongsTo(User::class, 'modified_at');
    }
}
