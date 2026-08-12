<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstDepartment extends Model
{
    protected $table = 'mst_departments';

    protected $fillable = ['name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function sections()
    {
        return $this->hasMany(MstSection::class, 'department_id');
    }

    public function jobPositions()
    {
        return $this->hasMany(MstJobPosition::class, 'department_id');
    }
}
