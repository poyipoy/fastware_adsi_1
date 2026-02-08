<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MstEntertain extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'trs_ent_detail';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'mst_id_ent',
        'tgl',
        'tempat',
        'alamat',
        'jenis',
        'jumlah',
        'nama',
        'posisi',
        'nama_perusahaan',
        'jenis_usaha',
        'dokumen',
        'is_active',
        'status',
        'modified_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tgl' => 'date',
        'is_active' => 'integer',
        'status' => 'integer',
    ];
    
}
