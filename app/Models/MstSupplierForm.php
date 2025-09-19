<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstSupplierForm extends Model
{
    use HasFactory;

    // Nama tabel (opsional, Laravel otomatis pakai 'suppliers')
    protected $table = 'mst_supplier_form';

    // Kolom yang bisa diisi mass-assignment
    protected $fillable = [
        'supplier_kode',
        'supplier_name',
        'alamat',
        'npwp',
        'npwp_file',
        'telp',
        'director',
        'pic',
        'has_quality_standard',
        'quality_certificate',
        'sppkp_file',
        'quality_certificate_from',
        'nib_file',
        'has_quality_responsible',
        'quality_responsible_name',
        'has_material_safety',
        'has_safety',
        'employs_underage',
        'pays_min_wage',
        'kategori',
        'lampiran_compro',
        'type',
        'rek_bank',
    ];

    public function trsSupplierForms()
    {
        return $this->hasMany(TrsSupplierForm::class, 'mst_supplier');
    }
}
