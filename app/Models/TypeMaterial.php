<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_name',
        'status',
    ];

    public function handings(): HasMany
    {
        return $this->hasMany(Handling::class);
    }

    public function detailInquiry(): HasMany
    {
        return $this->hasMany(DetailInquiry::class);
    }
}
