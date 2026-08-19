<?php

namespace App\Services\Warehouse;

use App\Models\User;
use App\Models\UserJobPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class WarehouseAccessService
{
    private const ABILITIES = [
        'warehouse.dashboard.view',
        'warehouse.stock-in.create',
        'warehouse.stock-in.validate',
        'warehouse.stock-out.create',
        'warehouse.master.manage',
        'warehouse.transaction.view',
        'warehouse.transaction.reverse',
        'warehouse.location-shipment.view',
        'warehouse.location-shipment.create',
        'warehouse.location-shipment.validate',
        'warehouse.location-shipment.cancel',
        'warehouse.report.view',
        'warehouse.report.export',
    ];

    public function isLoginEnabled(?User $user): bool
    {
        return $user !== null
            && (int) $user->getAttribute('is_active') === (int) config('warehouse.identity.active_user_value', 0);
    }

    public function isAdministrator(?User $user): bool
    {
        return $this->isLoginEnabled($user)
            && in_array((int) $user->getAttribute('role_id'), array_map('intval', (array) config('warehouse.authorization.administrator_role_ids', [1])), true);
    }

    public function hasDepartmentAccess(?User $user): bool
    {
        if (! $this->isLoginEnabled($user) || ! $this->hasOrganizationTables()) {
            return false;
        }

        $departmentNames = array_values(array_filter(array_map(
            static fn ($name): string => trim((string) $name),
            (array) config('warehouse.authorization.authorized_department_names', []),
        )));

        if ($departmentNames === []) {
            return false;
        }

        return UserJobPosition::query()
            ->where('user_id', $user->getKey())
            ->activeAt()
            ->whereHas('jobPosition', static function (Builder $position) use ($departmentNames): void {
                $position
                    ->where('is_active', true)
                    ->whereHas('department', static function (Builder $department) use ($departmentNames): void {
                        $department->where('is_active', true)->whereIn('name', $departmentNames);
                    });
            })
            ->exists();
    }

    public function hasModuleAccess(?User $user): bool
    {
        return $this->isAdministrator($user) || $this->hasDepartmentAccess($user);
    }

    public function can(?User $user, string $ability): bool
    {
        return in_array($ability, self::ABILITIES, true) && $this->hasModuleAccess($user);
    }

    public function canAdjust(?User $user): bool
    {
        return $this->hasModuleAccess($user);
    }

    public function canViewTransaction(?User $actor, $transaction): bool
    {
        return $this->can($actor, 'warehouse.transaction.view');
    }

    private function hasOrganizationTables(): bool
    {
        return Schema::hasTable('user_job_positions')
            && Schema::hasTable('mst_job_positions')
            && Schema::hasTable('mst_departments');
    }
}
