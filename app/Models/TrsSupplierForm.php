<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrsSupplierForm extends Model
{
    use HasFactory;

    protected $table = 'trs_supplier_form';

    protected $fillable = [
        'mst_supplier',
        'mst_visit',
        'visit',
        'visit_file',
        'visit_schedule',
        'visit_approval',
        'visit_ket',
        'trial',
        'trial_file',
        'trial_schedule',
        'trial_actual',
        'trial_approval',
        'trial_ket',
        'status',
        'approve',
        'supplier_kode',
        'is_active',
    ];

    /**
     * Relasi ke Supplier (jika ada tabel mst_supplier)
     */
    public function supplier()
    {
        return $this->belongsTo(MstSupplierForm::class, 'mst_supplier');
    }

    /**
     * Relasi ke Visit (jika ada tabel mst_visit)
     */
    public function visitDetail()
    {
        return $this->belongsTo(MstVisitForm::class, 'mst_visit');
    }

    public function logs()
    {
        return $this->hasMany(TrxFormSupplier::class, 'trs_id');
    }
}
