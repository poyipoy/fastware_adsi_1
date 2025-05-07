<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TcJobPosition extends Model
{
    use HasFactory;

    protected $table = 'tc_job_positions';

    protected $fillable = ['id_user', 'id_role', 'job_position', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role');
    }

    public function mstTcs()
    {
        return $this->hasMany(MstTc::class, 'id_job_position');
    }

    public function mstSoftSkills()
    {
        return $this->hasMany(MstSoftSkill::class, 'id_job_position');
    }

    public function mstAdditional()
    {
        return $this->hasMany(MstAdditionals::class, 'id_job_position');
    }

    public function poinKategori()
    {
        return $this->hasMany(PoinKategori::class, 'id_job_position');
    }

    public function penilaianTcs()
    {
        return $this->hasMany(TrsPenilaianTc::class, 'id_job_position');
    }

    public function peopleDevelopments()
    {
        return $this->hasMany(TcPeopleDevelopment::class, 'id_job_position');
    }
}
