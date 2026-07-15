<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk pengaturan Tahun Aktif Pengajuan Training.
 * Hanya role HR dan Administrator yang boleh mengubah.
 *
 * @property int      $id
 * @property int      $year       Tahun aktif
 * @property bool     $is_active
 * @property int|null $updated_by FK ke users.id
 */
class MstPdActiveYear extends Model
{
    use HasFactory;

    protected $table = 'mst_pd_active_years';

    protected $fillable = [
        'year',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'year'      => 'integer',
        'is_active' => 'boolean',
    ];

    // ========================
    //  Relationships
    // ========================

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ========================
    //  Static Helpers
    // ========================

    /**
     * Ambil tahun aktif yang sedang berlaku.
     * Jika belum pernah di-set → default ke tahun berjalan.
     */
    public static function getActiveYear(): int
    {
        $record = static::where('is_active', true)
                        ->orderBy('year', 'desc')
                        ->first();

        return $record ? $record->year : (int) date('Y');
    }

    /**
     * Set tahun aktif baru, non-aktifkan semua yang lama.
     */
    public static function setActiveYear(int $year, int $updatedBy): self
    {
        // Non-aktifkan semua yang lama
        static::where('is_active', true)->update(['is_active' => false]);

        // Buat atau perbarui record untuk tahun yang dipilih
        return static::updateOrCreate(
            ['year' => $year],
            [
                'is_active'  => true,
                'updated_by' => $updatedBy,
            ]
        );
    }
}
