<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrsPengajuanSubcont extends Model
{
    use HasFactory;

    protected $table = 'trs_pengajuan_subconts';

    protected $fillable = [
        'id_subcont',
        'keterangan',
        'status',
        'modified_at',
    ];

    // Relasi ke model Tc (id_tc)
    public function mstPengajuanSubcont()
    {
        return $this->belongsTo(MstPoPengajuan::class, 'id_subcont');
    }
}
