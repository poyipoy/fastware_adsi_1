<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TcPeopleDevelopment extends Model
{
    use HasFactory;

    protected $table = 'mst_pd_pengajuans'; // Nama tabel di database

    protected $fillable = [
        'id_role',
        'id_job_position',
        'id_user',
        'section',
        'id_tc',
        'id_sk',
        'id_ad',
        'id_trs',
        'program_training',
        'program_training_plan',
        'kategori_competency',
        'competency',
        'due_date',
        'due_date_plan',
        'lembaga',
        'lembaga_plan',
        'keterangan_tujuan',
        'keterangan_plan',
        'keterangan_tolak',
        'biaya',
        'biaya_plan',
        'tahun_aktual',
        'tahun_usulan',
        'file',
        'file_name',
        'status_1',
        'status_2',
        'modified_at',
        'modified_updated',
        // Tambahkan kolom baru di sini
        'relevansi',
        'rekomendasi',
        'kelengkapan_materi',
        'lokasi',
        'metode_pengajaran',
        'fasilitas',
        'lainnya_1',
        'metode_evaluasi',
        'minat',
        'daya_serap',
        'penerapan',
        'lainnya_2',
        'diketahui',
        'dievaluasi',
        'tgl_pengajuan',
        'tgl_konfirm',
        'efektif',
        'catatan_tambahan',
    ];


    // Definisi relasi
    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role');
    }

    public function jobPosition()
    {
        return $this->belongsTo(TcJobPosition::class, 'id_job_position');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function tc()
    {
        return $this->belongsTo(MstTc::class, 'id_tc');
    }

    public function softSkill()
    {
        return $this->belongsTo(MstSoftSkill::class, 'id_sk');
    }

    public function additional()
    {
        return $this->belongsTo(MstAdditionals::class, 'id_ad');
    }

    public function penilaian()
    {
        return $this->belongsTo(TrsPenilaian::class, 'id_trs');
    }
}
