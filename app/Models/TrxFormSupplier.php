<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrxFormSupplier extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     *
     * @var string
     */
    protected $table = 'trx_form_supplier';

    /**
     * Kolom-kolom yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trs_id',
        'keterangan',
        'status',
        'created_by',
    ];

    /**
     * Mendefinisikan relasi "belongsTo" ke model TrsSupplierForm.
     * Setiap log aktivitas dimiliki oleh satu form supplier.
     */
    public function form()
    {
        return $this->belongsTo(TrsSupplierForm::class, 'trs_id');
    }
}
