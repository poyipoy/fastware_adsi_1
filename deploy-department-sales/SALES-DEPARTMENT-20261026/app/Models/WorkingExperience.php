<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk tabel working_experiences.
 * Source of truth untuk riwayat jabatan karyawan (dikelola di Mapping Karyawan).
 * Ditampilkan read-only di dsDetailCompetency.
 *
 * @property int         $id
 * @property int         $user_id
 * @property int         $year_start        Tahun mulai menjabat
 * @property int|null    $year_end          NULL = masih menjabat ("Present")
 * @property string      $job_position      Nama jabatan
 * @property string|null $section
 * @property string|null $departemen
 * @property string|null $keterangan
 */
class WorkingExperience extends Model
{
    use HasFactory;

    protected $table = 'working_experiences';

    protected $fillable = [
        'user_id',
        'year_start',
        'year_end',
        'job_position',
        'section',
        'departemen',
        'keterangan',
    ];

    protected $casts = [
        'year_start' => 'integer',
        'year_end'   => 'integer',
    ];

    // ========================
    //  Relationships
    // ========================

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ========================
    //  Helpers
    // ========================

    /**
     * Tahun selesai untuk ditampilkan di UI.
     * Jika null → "Present".
     */
    public function getYearEndLabelAttribute(): string
    {
        return $this->year_end ? (string) $this->year_end : 'Present';
    }

    // ========================
    //  Scopes
    // ========================

    /**
     * Urutkan kronologis dari terlama ke terbaru.
     */
    public function scopeChronological($query)
    {
        return $query->orderBy('year_start', 'asc')
                     ->orderByRaw('year_end IS NULL DESC') // NULL (Present) di atas
                     ->orderBy('year_end', 'asc');
    }
}
