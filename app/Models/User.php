<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int|null $role_id
 */
class User extends Authenticatable
{
    use HasFactory;

    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'name',
        'section',
        'npk',
        'username',
        'password',
        'pass',
        'email',
        'telp',
        'km_total_poin',
        'file',
        'file_name',
        'is_active',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'pass',
    ];

    public function roles(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function handings(): HasMany
    {
        return $this->hasMany(Handling::class);
    }

    public function schedule_visits(): HasMany
    {
        return $this->hasMany(ScheduleVisit::class);
    }

    public function sumbang_saran()
    {
        return $this->hasMany(SumbangSaran::class, 'id_user');
    }

    public function penilaians(): HasMany
    {
        return $this->hasMany(PenilaianSS::class);
    }

    // Relasi dengan KmPengajuan
    public function kmPengajuan()
    {
        return $this->hasMany(KmPengajuan::class, 'id_user');
    }

    // Relasi dengan KmBookmark
    public function bookmarks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KmBookmark::class, 'user_id');
    }

    // Relasi dengan KmTransaksi
    public function kmTransaksi()
    {
        return $this->hasMany(KmTransaksi::class, 'id_user');
    }

    public function kmSukas()
    {
        return $this->hasMany(KmSuka::class, 'id_user');
    }

    public function insights()
    {
        return $this->hasMany(Insight::class, 'id_user');
    }

    public function userJobPositions()
    {
        return $this->hasMany(UserJobPosition::class, 'user_id');
    }

    // Relasi ke model TrsPenilaianTcs
    public function penilaianTcs()
    {
        return $this->hasMany(TrsPenilaianTc::class, 'id_user', 'modified_at');
    }

    // Relasi ke TcPeopleDevelopment
    public function peopleDevelopments()
    {
        return $this->hasMany(TcPeopleDevelopment::class, 'id_user');
    }
    public function details()
    {
        return $this->hasMany(DetailTcPenilaian::class, 'id_user', 'id');
    }

    public function trsdbocrp()
    {
        return $this->hasMany(TrsDboCrp::class, 'partner', 'id');
    }

    //relasi ke model MstPengajuanSubcont
    public function mstPengajuanSubcont()
    {
        return $this->hasMany(MstPengajuanSubcont::class, 'modified_at', 'id');
    }
    public function mstPengajuanSubconts()
    {
        return $this->hasMany(MstPengajuanSubcont::class, 'confirm_prod', 'id');
    }
    public function mstPengajuanSubconts1()
    {
        return $this->hasMany(MstPengajuanSubcont::class, 'approval_1', 'id');
    }
    public function mstPengajuanSubconts2()
    {
        return $this->hasMany(MstPengajuanSubcont::class, 'approval_2', 'id');
    }
    public function mstPengajuanSubconts3()
    {
        return $this->hasMany(MstPengajuanSubcont::class, 'modifiet_at', 'id');
    }

    //relasi ke sales logbook
    public function logbookVisits(): HasMany
    {
        return $this->hasMany(LogbookVisits::class, 'id_user');
    }
    public function trsLogbookVisits(): HasMany
    {
        return $this->hasMany(TrsLogbookVisits::class, 'id_user');
    }

    public function itemCodesCreated(): HasMany
    {
        return $this->hasMany(ItemCode::class, 'created_by');
    }

    public function itemCodesApproved(): HasMany
    {
        return $this->hasMany(ItemCode::class, 'approved_by');
    }

    public function itemCodesFinished(): HasMany
    {
        return $this->hasMany(ItemCode::class, 'finished_by');
    }
}
