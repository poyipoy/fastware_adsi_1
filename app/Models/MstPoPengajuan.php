<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstPoPengajuan extends Model
{
    use HasFactory;

    // Nama tabel (jika tidak sama dengan nama model dalam bentuk plural)
    protected $table = 'mst_po_pengajuans'; // Sesuaikan dengan nama tabel di database

    // Definisikan kolom yang bisa diisi (fillable)
    protected $fillable = [
        'no_fpb',
        'no_po', 
        'kategori_po', 
        'nama_barang', 
        'qty', 
        'pcs',
        'price_list',
        'total_harga',
        'spesifikasi', 
        'file', 
        'file_name', 
        'quotation_file',
        'amount', 
        'rekomendasi', 
        'due_date',
        'target_cost',
        'lead_time',
        'nama_customer',
        'nama_project', 
        'no_so',
        'konfirmasi_quotation',
        'catatan',
        'status_1', 
        'status_2', 
        'created_at', 
        'updated_at', 
        'modified_at'
    ];

     // Relasi ke model TrsPenilaianTcs
     public function trsPoPengajuan()
     {
         return $this->hasMany(TrsPoPengajuan::class, 'id_fpb');
     }
}
