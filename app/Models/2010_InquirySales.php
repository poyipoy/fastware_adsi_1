<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquirySales extends Model
{
    use HasFactory;

    // Menentukan nama tabel jika berbeda dari konvensi penamaan
    protected $table = 'inquiry_sales';

    // Kolom yang dapat diisi secara massal
    protected $fillable = [
        'id_customer',
        'kode_inquiry',
        'jenis_inquiry',
        'loc_imp',
        'est_date',
        'supplier',
        'create_by',
        // 'to_approve',
        // 'to_validate',
        'progress',
        'refnopo',
        'attach_file',
        'status',
        'kasie_id',
        'kadept_id',
        'inventory_id',
        'purchasing_id',
        'is_active',
        'created_at',
        'updated_at',
        'modified_by',
    ];

    // Relasi ke DetailInquiry
    public function details()
    {
        return $this->hasMany(DetailInquiry::class, 'id_inquiry');
    }

    public  function detailinquiryimport()
    {
        return $this->hasMany(DetailInquiryImport::class, 'id_inquiry');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    public function kasie()
    {
        return $this->belongsTo(User::class, 'kasie_id');
    }

    public function kadept()
    {
        return $this->belongsTo(User::class, 'kadept_id');
    }

    public function inventory()
    {
        return $this->belongsTo(User::class, 'inventory_id');
    }

    public function purchasing()
    {
        return $this->belongsTo(User::class, 'purchasing_id');
    }
}
