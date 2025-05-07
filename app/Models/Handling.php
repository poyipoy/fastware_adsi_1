<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Handling extends Model
{
    use HasFactory;

    protected $table = 'handlings';

    protected $fillable = [
        'no_wo',
        'user_id',
        'customer_id',
        'name',
        'type_id',
        'nama_barang',
        'notes',
        'thickness',
        'weight',
        'outer_diameter',
        'inner_diameter',
        'length',
        'qty',
        'pcs',
        'category',
        'material_project',
        'jenis_test',
        'results',
        'process_type',
        'type_1',
        'type_2',
        'image',
        'status',
        'status_2',
        'modified_by',
    ];

    public static function countDataBetweenDates($start_date, $end_date)
    {
        return self::whereBetween('created_at', [$start_date, $end_date])
            ->where('status_2', 0)
            ->where('status', 3)
            ->count();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // Sesuaikan dengan nama kolom foreign key di tabel Handling
    }

    public function customers(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function type_materials(): BelongsTo
    {
        return $this->belongsTo(TypeMaterial::class, 'type_id');
    }

    public function schedule_viist(): HasMany
    {
        return $this->hasMany(ScheduleVisit::class, 'handling_id');
    }
}
