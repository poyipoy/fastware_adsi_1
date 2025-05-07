<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// relasi
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;
    protected $fillable = [
        'role',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function jobPositions()
    {
        return $this->hasMany(TcJobPosition::class, 'id_role');
    }

    // Relasi ke TcPeopleDevelopment
    public function peopleDevelopments()
    {
        return $this->hasMany(TcPeopleDevelopment::class, 'id_role');
    }
}
