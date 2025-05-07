<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrsPoPengajuan extends Model
{
    use HasFactory;

    protected $table = 'trs_po_pengajuans';

    protected $fillable = [
        'id_fpb',
        'no_po',
        'keterangan',
        'status',
        'modified_at',
    ];

    // Relasi ke model Tc (id_tc)
    public function mst_po()
    {
        return $this->belongsTo(MstPoPengajuan::class, 'id_fpb');
    }
}
