<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailInquiry extends Model
{
    use HasFactory;

    protected $table = 'detail_inquiry'; // Pastikan nama tabel benar
    protected $fillable = [
        'id_inquiry',
        'id_type',
        'nama_material',
        'jenis',
        'thickness',
        'inner_diameter',
        'outer_diameter',
        'weight',
        'length',
        'qty',
        'm1',
        'm2',
        'm3',
        'so',
        'konfirmasi',
        'nopo',
        'ship',
        'note',
        'file',
    ];

    public function inquirySales()
    {
        return $this->belongsTo(InquirySales::class);
    }

    public function type_materials(): BelongsTo
    {
        return $this->belongsTo(TypeMaterial::class, 'id_type');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'create_by');
    }
}
