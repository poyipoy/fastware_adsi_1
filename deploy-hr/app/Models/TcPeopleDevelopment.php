<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk data pengajuan training / people development.
 *
 * @property int    $id
 * @property int    $section_id          FK ke mst_sections.id
 * @property int    $id_job_position     FK ke mst_job_positions.id
 * @property int    $id_user             FK ke users.id (karyawan yang diajukan)
 * @property int    $id_role
 * @property int    $id_tc
 * @property int    $id_sk
 * @property int    $id_ad
 * @property int    $id_trs
 * @property string $program_training    Nama program training yang diusulkan oleh atasan
 * @property string $program_training_plan Nama program training aktual (diisi HRGA)
 * @property string $kategori_competency Kategori kompetensi: technical|nontechnical|additional|Others
 * @property string $competency          Teks competency yang dipilih dari dropdown
 * @property string $due_date            Tanggal target usulan
 * @property string $due_date_plan       Tanggal aktual (diisi HRGA)
 * @property float  $biaya              Budget usulan (rupiah)
 * @property float  $biaya_plan         Budget aktual (diisi HRGA)
 * @property string $lembaga            Nama lembaga training usulan
 * @property string $lembaga_plan       Nama lembaga training aktual (diisi HRGA)
 * @property string $keterangan_tujuan  Keterangan tujuan training usulan
 * @property string $keterangan_plan    Keterangan tujuan training aktual
 * @property string $keterangan_tolak   Alasan penolakan jika ditolak
 * @property int    $status_1           Status pengajuan: 1=Draft, 2=Menunggu HRGA, 3=Disetujui
 * @property string $status_2           Status progres training (lihat TrainingStatus enum).
 *                                      Nilai yang valid: 'Mencari Vendor', 'Proses Pendaftaran',
 *                                      'On Progress', 'Done', 'Pending', 'Ditolak'
 * @property bool   $is_sharing_knowledge  1 = entry Sharing Knowledge (tanpa Section & Departemen)
 * @property string|null $objective_learning   Hasil yang diharapkan dari training
 * @property string|null $sharing_knowledge    Catatan hasil sharing knowledge pasca training (Modul 4.1)
 * @property string $modified_at        !! PERHATIAN: Kolom ini menyimpan NAMA USER (string),
 *                                         BUKAN timestamp. Ini digunakan sebagai identifier
 *                                         grup pengajuan dari atasan yang sama.
 *                                         Konvensi '_at' di sini adalah non-standar.
 * @property string $modified_updated   Timestamp terakhir diperbarui (format string)
 * @property int    $tahun_aktual       Tahun target training
 * @property int    $tahun_usulan       Tahun saat diusulkan (null = pengajuan dari atasan)
 * @property string $file               Nama file bukti training yang diupload
 * @property string $file_name          Nama asli file bukti training
 * @property string $diketahui          Nama trainee yang mengetahui evaluasi
 * @property string $dievaluasi         Nama user yang mengisi evaluasi
 * @property string $tgl_pengajuan      Tanggal pengajuan evaluasi
 * @property string $tgl_konfirm        Tanggal konfirmasi
 *
 * @see \App\Enums\TrainingStatus Untuk konstanta nilai status_2 yang valid
 */
class TcPeopleDevelopment extends Model
{
    use HasFactory;

    protected $table = 'mst_pd_pengajuans'; // Nama tabel di database

    protected $appends = [
        'evaluation_completed',
    ];

    protected $fillable = [
        'id_role',
        'id_job_position',  // integer FK ke mst_job_positions
        'id_user',
        'section_id',       // integer FK ke mst_sections
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
        'relevansi',
        'alasan_relevansi',
        'rekomendasi',
        'alasan_rekomendasi',
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
        'is_sharing_knowledge',
        'objective_learning',
        'objective_learning_aktual',
        'sharing_knowledge',
    ];

    // =============================================
    //  Relasi
    // =============================================

    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role');
    }

    /**
     * Relasi ke MstJobPosition (FK integer id_job_position).
     */
    public function jobPosition()
    {
        return $this->belongsTo(MstJobPosition::class, 'id_job_position');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function participants()
    {
        return $this->belongsToMany(
            User::class,
            'mst_pd_pengajuan_participants',
            'people_development_id',
            'user_id',
        )->withTimestamps();
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
        return $this->belongsTo(TrsPenilaianTc::class, 'id_trs');
    }

    /**
     * Relasi ke MstSection (FK integer section_id).
     */
    public function section()
    {
        return $this->belongsTo(MstSection::class, 'section_id');
    }

    public function getEvaluationCompletedAttribute(): bool
    {
        if ($this->is_sharing_knowledge) {
            return filled($this->dievaluasi) && filled($this->tgl_pengajuan);
        }

        return filled($this->diketahui);
    }
}
