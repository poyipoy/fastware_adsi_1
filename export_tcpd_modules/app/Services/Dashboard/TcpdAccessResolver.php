<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\MstJobPosition;
use Illuminate\Support\Facades\DB;

/**
 * Menentukan daftar job position yang boleh dilihat oleh seorang user
 * di dashboard TCPD, berdasarkan job_level dari job position user tersebut.
 *
 * Aturan scope:
 *   - div_head  → akses semua job aktif (level: all)
 *   - dept_head → akses semua job aktif di departemennya
 *   - sec_head  → akses semua job aktif di sectionnya
 *   - staff     → tidak ada akses TCPD ([] = sembunyikan dashboard)
 *   - (tidak ada job position) → [] = tidak ada akses
 *
 * User khusus (FULL_ACCESS_USER_IDS) selalu mendapat akses ke semua job,
 * terlepas dari job level mereka.
 */
class TcpdAccessResolver
{
    /**
     * User ID yang mendapat akses penuh ke seluruh data TCPD
     * terlepas dari job position yang dimiliki.
     * Gunakan ini hanya untuk kebutuhan khusus yang tidak bisa
     * direpresentasikan oleh job level di database.
     */
    private const FULL_ACCESS_USER_IDS = [
        91, // SITI MARIA ULFA – user dengan akses penuh khusus
    ];

    /**
     * Resolve daftar nama job position yang diizinkan untuk user ini.
     *
     * @return array|null  null = akses penuh (admin/superuser), [] = tidak ada akses
     */
    public static function resolve(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        // Role admin (role_id = 1) selalu dapat akses penuh
        if ((int) $user->role_id === 1) {
            return null;
        }

        // User dengan special full access
        if (in_array((int) $user->id, self::FULL_ACCESS_USER_IDS, true)) {
            return null; // null = akses penuh tanpa filter
        }

        // Ambil job position aktif dari user (termasuk job_level, dept, section)
        $userJobPositions = DB::table('user_job_positions')
            ->join('mst_job_positions', 'user_job_positions.mst_job_position_id', '=', 'mst_job_positions.id')
            ->leftJoin('mst_departments', 'mst_job_positions.department_id', '=', 'mst_departments.id')
            ->leftJoin('mst_sections', 'mst_job_positions.section_id', '=', 'mst_sections.id')
            ->where('user_job_positions.user_id', $user->id)
            ->where('mst_job_positions.is_active', true)
            ->select(
                'mst_job_positions.job_level',
                'mst_job_positions.department_id',
                'mst_job_positions.section_id',
                'mst_departments.name as department_name',
                'mst_sections.name as section_name',
            )
            ->get();

        if ($userJobPositions->isEmpty()) {
            return []; // Tidak ada job position → tidak ada akses
        }

        // Prioritas: div_head > dept_head > sec_head > staff
        // Cek dari level tertinggi ke terendah
        foreach (['div_head', 'dept_head', 'sec_head'] as $level) {
            $matched = $userJobPositions->where('job_level', $level);
            if ($matched->isNotEmpty()) {
                return match ($level) {
                    'div_head'  => self::allActiveJobs(),
                    'dept_head' => self::jobsByDepartmentIds(
                        $matched->pluck('department_id')->filter()->unique()->values()->all()
                    ),
                    'sec_head'  => self::jobsBySectionIds(
                        $matched->pluck('section_id')->filter()->unique()->values()->all()
                    ),
                };
            }
        }

        // Hanya punya job level 'staff' → tidak ada akses TCPD
        return [];
    }

    /** Ambil semua nama job aktif (kecuali level head). */
    private static function allActiveJobs(): array
    {
        return MstJobPosition::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(position_name) NOT LIKE ?', ['%head%'])
            ->orderBy('position_name')
            ->pluck('position_name')
            ->map(fn($n) => trim($n))
            ->unique()
            ->values()
            ->all();
    }

    /** Ambil nama job aktif berdasarkan department_id, kecuali level head. */
    private static function jobsByDepartmentIds(array $departmentIds): array
    {
        if (empty($departmentIds)) {
            return [];
        }

        return MstJobPosition::query()
            ->whereIn('department_id', $departmentIds)
            ->where('is_active', true)
            ->whereRaw('LOWER(position_name) NOT LIKE ?', ['%head%'])
            ->orderBy('position_name')
            ->pluck('position_name')
            ->map(fn($n) => trim($n))
            ->unique()
            ->values()
            ->all();
    }

    /** Ambil nama job aktif berdasarkan section_id, kecuali level head. */
    private static function jobsBySectionIds(array $sectionIds): array
    {
        if (empty($sectionIds)) {
            return [];
        }

        return MstJobPosition::query()
            ->whereIn('section_id', $sectionIds)
            ->where('is_active', true)
            ->whereRaw('LOWER(position_name) NOT LIKE ?', ['%head%'])
            ->orderBy('position_name')
            ->pluck('position_name')
            ->map(fn($n) => trim($n))
            ->unique()
            ->values()
            ->all();
    }
}
