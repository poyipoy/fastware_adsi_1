<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrpSystem extends Model
{
    use HasFactory;

    protected $table = 'crp_system'; // Nama tabel dalam database

    protected $fillable = [
        'user_id',            // Foreign Key ke tabel users
        'customer_id',        // Foreign Key ke tabel customers
        'mst_dbo_crp_id',    // Foreign Key ke tabel mst_dbo_crp
        'trs_dbo_crp_id',    // Foreign Key ke tabel trs_dbo_crp
        // Tambahkan kolom lainnya yang diperlukan
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mstDboCrp()
    {
        return $this->belongsTo(MstDboCrp::class);
    }

    public function trsDboCrp()
    {
        return $this->belongsTo(TrsDboCrp::class);
    }
}
