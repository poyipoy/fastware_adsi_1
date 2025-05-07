<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mesin extends Model
{
    use HasFactory;
    protected $table = 'mesin';
    protected $fillable = [
        'section',
        'no_mesin', 'tipe', 'tanggal_dibuat',
        'umur', 'spesifikasi', 'lokasi', 'tanggal_preventif',
        'foto', 'sparepart', 'status'
    ];

    protected $dates = ['created_at', 'updated_at'];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d | H:i:s');
    }

    public function getUmurMesinAttribute()
    {
        // Menghitung umur mesin berdasarkan tanggal_dibuat
        if ($this->tanggal_dibuat) {
            $tanggalDibuat = Carbon::parse($this->tanggal_dibuat);
            $umur = $tanggalDibuat->diffInYears(Carbon::now()); // Menghitung selisih tahun
            return $umur;
        }

        return null; // Jika tanggal_dibuat kosong, kembalikan null
    }

    public function spareparts()
    {
        return $this->hasMany(Sparepart::class);
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d | H:i:s');
    }

    public function formFPP()
    {
        return $this->belongsTo(FormFPP::class, 'id_fpp', 'id_fpp');
    }

    public function time_line(): HasMany
    {
        return $this->hasMany(TimeLine::class);
    }

    public function preventives()
    {
        return $this->hasMany(Preventive::class);
    }

    public function detailPreventives()
    {
        return $this->hasMany(DetailPreventive::class, 'id_mesin', 'id');
    }

    public function preventifs()
    {
        return $this->hasMany(JadwalPreventif::class, 'nomor_mesin', 'no_mesin');
    }

    public function getStatusBackgroundColorAttribute()
    {
        $status = strtolower($this->attributes['status']);

        switch ($status) {
            case '0':
                return 'green'; // Mengubah warna menjadi 'green' untuk status 0 (Open)
            case '1':
                return 'blue'; // Mengubah warna menjadi 'orange' untuk status 1 (On Progress)
            default:
                return 'transparent'; // Mengembalikan 'transparent' untuk nilai lain
        }
    }
    public function ubahText()
    {
        $status = $this->attributes['status'];

        switch ($status) {
            case '0':
                return 'Open';
            case '1':
                return 'Finish';
            default:
                return 'Unknown';
        }
    }
}
