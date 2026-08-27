<?php

namespace App\Services\HR;

use App\Models\User;

class HRRoleAccessService
{
    /**
     * User ID yang mendapatkan full access ke semua level penilaian.
     * Hanya ID 1 (ADMINSTRATOR) dan ID 91 (SITI MARIA ULFA) yang memiliki akses penuh.
     */
    private const FULL_ACCESS_USER_IDS = [1, 91];

    public function hasFullAccess(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // Strictly check against allowed User IDs
        if (in_array((int) $user->id, self::FULL_ACCESS_USER_IDS, true)) {
            return true;
        }

        return false;
    }

    public function isKaSie(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->hasFullAccess($user)) {
            return true;
        }

        return \App\Models\UserJobPosition::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('jobPosition', function ($query) {
                $query->where('job_level', 'sec_head');
            })
            ->exists();
    }

    public function isKaDept(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->hasFullAccess($user)) {
            return true;
        }

        return \App\Models\UserJobPosition::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('jobPosition', function ($query) {
                $query->where('job_level', 'dept_head');
            })
            ->exists();
    }

    public function isDivHead(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->hasFullAccess($user)) {
            return true;
        }

        return \App\Models\UserJobPosition::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('jobPosition', function ($query) {
                $query->where('job_level', 'div_head');
            })
            ->exists();
    }

    public function canAccessCompetencyLevel(?User $user, string $level): bool
    {
        // Admin selalu punya akses ke semua level
        if ($this->hasFullAccess($user)) {
            return true;
        }

        return match ($level) {
            'kasie' => $this->isKaSie($user) || $this->isKaDept($user),
            'kadept' => $this->isKaDept($user),
            'divhead' => $this->isDivHead($user),
            'hr' => false, // Sudah di-handle oleh hasFullAccess di atas
            default => false,
        };
    }

    public function canAccessTrainingDevelopment(?User $user): bool
    {
        return $this->isKaDept($user);
    }

    public function canApproveTrainingDevelopment(?User $user): bool
    {
        return $this->hasFullAccess($user);
    }

    public function canManageTrainingConfig(?User $user): bool
    {
        // Gantikan logic @if (in_array($user->role_id, [1, 3, 15]))
        // Role ID 1 = Administrator
        // Role ID 3 = HR/Manager (assuming)
        // Role ID 15 = ?
        if (!$user) {
            return false;
        }

        // Siti Maria Ulfa (ID 91) dan Admin (Super Admin / hasFullAccess)
        if ($this->hasFullAccess($user) || (int) $user->role_id === 1) {
            return true;
        }


        return false;
    }

    public function canAccessTrainingHistory(?User $user): bool
    {
        return $this->hasFullAccess($user) || $this->roleStartsWith($user, 'SC') || $this->roleStartsWith($user, 'DH');
    }

    public function roleStartsWith(?User $user, string $prefix): bool
    {
        if (!$user) {
            return false;
        }

        $roleName = $this->normalize($this->roleName($user));
        $prefix = $this->normalize($prefix);

        return $roleName === $prefix || str_starts_with($roleName, $prefix . ' ');
    }

    public function roleName(?User $user): string
    {
        if (!$user) {
            return '';
        }

        return (string) optional($user->roles)->role;
    }

    public function fullAccessUserIds(): array
    {
        return self::FULL_ACCESS_USER_IDS;
    }

    public function normalize(?string $value): string
    {
        return mb_strtoupper(trim((string) $value));
    }
}
