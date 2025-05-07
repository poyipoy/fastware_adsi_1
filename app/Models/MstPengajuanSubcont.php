<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstPengajuanSubcont extends Model
{
    use HasFactory;

    // Nama tabel (jika tidak sama dengan nama model dalam bentuk plural)
    protected $table = 'mst_pengajuan_subconts'; // Sesuaikan dengan nama tabel di database

    // Definisikan kolom yang bisa diisi (fillable)
    protected $fillable = [
        'nama_customer',
        'nama_project',
        'qty', 
        'keterangan', 
        'jenis_proses_subcont', 
        'file', 
        'file_name',
        'quotation_file',
        'status_1',
        'status_2',
        'modified_at'
    ];

     // Relasi ke model TrsPenilaianTcs
     public function trsPengajuanSubcont()
     {
         return $this->hasMany(TrsPengajuanSubcont::class, 'id_subcont');
     }
}
