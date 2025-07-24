<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
        'part_name',
        'harga_awal',
        'harga_akhir',
        'approval_1',
        'date_app_1',
        'approval_2',
        'date_app_2',
        'confirm_prod',
         'date_confirm_prod',
        'sec_line',
        'is_active',
        'so',
        'note_sales',
        'no_ref',
        'modified_at'
    ];

     // Relasi ke model TrsPenilaianTcs
     public function trsPengajuanSubcont()
     {
         return $this->hasMany(TrsPengajuanSubcont::class, 'id_subcont');
     }
     public function sales()
     {
        return $this->belongsTo(User::class, 'modified_at');
     }
     public function marketing()
     {
        return $this->belongsTo(User::class, 'approval_1');
     }
     public function finance()
     {
        return $this->belongsTo(User::class, 'approval_2');
     }
     public function production()
     {
        return $this->belongsTo(User::class, 'confirm_prod');
     }

     public function getFormattedDateApp1Attribute()
     {
         return $this->date_app_1 ? Carbon::parse($this->date_app_1)->format('d-m-Y') : '';
     }
      public function getFormattedDateApp2Attribute()
     {
      return $this->date_app_2 ? Carbon::parse($this->date_app_2)->format('d-m-Y') : '';
      }
     
}
