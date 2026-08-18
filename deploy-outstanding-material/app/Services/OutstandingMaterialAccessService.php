<?php

namespace App\Services;

use App\Enums\ProcurementMenuAccessGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class OutstandingMaterialAccessService
{
    /** @var array<string, bool> */
    private array $salesCache = [];

    public function canView(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return ProcurementMenuAccessGroup::OUTSTANDING_MATERIAL->hasAccess(
            (string) $user->getAttribute('name'),
        ) || $this->isSales($user);
    }

    /**
     * Export follows module view access, including active Sales users.
     */
    public function canExport(?User $user): bool
    {
        return $this->canView($user);
    }

    public function canManage(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ((int) $user->getAttribute('role_id') === 1) {
            return true;
        }

        return in_array($this->normalizedName($user), [
            'ADMINISTRATOR',
            'ADMINSTRATOR',
            'ILYAS NOOR FIRDAUS',
        ], true);
    }

    /**
     * The business rule intentionally grants document upload only to Ilyas;
     * manager or administrator access must not widen this capability.
     */
    public function canUploadInvoiceDocuments(?User $user): bool
    {
        return $user !== null && $this->normalizedName($user) === 'ILYAS NOOR FIRDAUS';
    }

    public function canDownloadInvoiceDocuments(?User $user): bool
    {
        return $this->isSales($user);
    }

    public function isSales(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $cacheKey = (string) ($user->getKey() ?: spl_object_id($user));
        if (array_key_exists($cacheKey, $this->salesCache)) {
            return $this->salesCache[$cacheKey];
        }

        $this->salesCache[$cacheKey] = $user->userJobPositions()
            ->activeAt()
            ->whereHas('jobPosition', function (Builder $position): void {
                $position
                    ->where('is_active', true)
                    ->where(function (Builder $scope): void {
                        $scope
                            ->whereRaw('LOWER(position_name) LIKE ?', ['%sales%'])
                            ->orWhereHas('department', function (Builder $department): void {
                                $department
                                    ->where('is_active', true)
                                    ->whereRaw('LOWER(name) LIKE ?', ['%sales%']);
                            });
                    });
            })
            ->exists();

        return $this->salesCache[$cacheKey];
    }

    private function normalizedName(User $user): string
    {
        return strtoupper(trim((string) $user->getAttribute('name')));
    }
}
