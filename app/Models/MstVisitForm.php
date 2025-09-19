<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstVisitForm extends Model
{
    use HasFactory;

   protected $table = 'mst_visit';

    protected $fillable = [
        'supplier_id',
        'type',
        'tanggal_visit',
        'lokasi',
        'kelengkapan_apd',
        'fasilitas',
        'alat_ukur',
        'lisensi',
        'lima_r',
        'catatan',
        'lampiran_foto',
        'lampiran_compro',
        'kualitas_baja',
        'stok',
        'waktu_kirim',
        'responsif',
        'office_wh',
        'mesin',
        'safety',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    // Ditambahkan: Casting untuk memastikan kolom tanggal diperlakukan sebagai objek Carbon.
    protected $casts = [
        'tanggal_visit' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(MstSupplierForm::class, 'supplier_id');
    }

    public function trsSupplierForms()
    {
        return $this->hasMany(TrsSupplierForm::class, 'mst_visit');
    }

    /**
     * Getter untuk lampiran_foto dalam bentuk array.
     */
    public function getLampiranFotoArrayAttribute()
    {
        return $this->lampiran_foto 
            ? explode(',', $this->lampiran_foto) 
            : [];
    }

    /**
     * Getter untuk lampiran_compro dalam bentuk array.
     */
    public function getLampiranComproArrayAttribute()
    {
        return $this->lampiran_compro 
            ? explode(',', $this->lampiran_compro) 
            : [];
    }
}
