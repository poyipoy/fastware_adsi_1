<?php

namespace App\Enums;

/**
 * Enum untuk status progres training (kolom status_2 di mst_pd_pengajuans).
 *
 * Menggunakan class konstanta (bukan PHP 8.1 enum) agar kompatibel dengan semua versi Laravel.
 */
class TrainingStatus
{
    // Status progres training
    public const MENCARI_VENDOR    = 'Mencari Vendor';
    public const PROSES_PENDAFTARAN = 'Proses Pendaftaran';
    public const ON_PROGRESS       = 'On Progress';
    public const DONE              = 'Done';
    public const PENDING           = 'Pending';
    public const DITOLAK           = 'Ditolak';

    // Status pengajuan (kolom status_1)
    public const STATUS_1_DRAFT    = 1; // Draft, belum dikirim
    public const STATUS_1_PENDING  = 2; // Menunggu persetujuan HRGA
    public const STATUS_1_APPROVED = 3; // Disetujui HRGA

    /**
     * Mendapatkan semua nilai status progres training yang valid.
     *
     * @return array<string>
     */
    public static function allProgress(): array
    {
        return [
            self::MENCARI_VENDOR,
            self::PROSES_PENDAFTARAN,
            self::ON_PROGRESS,
            self::DONE,
            self::PENDING,
            self::DITOLAK,
        ];
    }

    /**
     * Mendapatkan konfigurasi warna untuk tiap status progres.
     * Digunakan di view untuk konsistensi tampilan.
     *
     * @return array<string, array{bg: string, text: string, border: string, badge: string}>
     */
    public static function colorConfig(): array
    {
        return [
            self::MENCARI_VENDOR     => ['bg' => 'blue',   'text' => 'white', 'border' => 'border-info',      'badge' => 'bg-info'],
            self::PROSES_PENDAFTARAN => ['bg' => 'orange', 'text' => 'white', 'border' => 'border-primary',   'badge' => 'bg-primary'],
            self::ON_PROGRESS        => ['bg' => 'yellow', 'text' => 'black', 'border' => 'border-warning',   'badge' => 'bg-warning text-dark'],
            self::DONE               => ['bg' => 'green',  'text' => 'white', 'border' => 'border-success',   'badge' => 'bg-success'],
            self::PENDING            => ['bg' => 'gray',   'text' => 'white', 'border' => 'border-secondary', 'badge' => 'bg-secondary'],
            self::DITOLAK            => ['bg' => 'red',    'text' => 'white', 'border' => 'border-danger',    'badge' => 'bg-danger'],
        ];
    }

    /**
     * Memeriksa apakah nilai status valid.
     *
     * @param string|null $status
     * @return bool
     */
    public static function isValid(?string $status): bool
    {
        return in_array($status, self::allProgress(), true);
    }
}
