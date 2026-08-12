<?php

namespace App\Services;

use App\Enums\HRMenuAccessGroup;
use App\Models\User;
use App\Services\HR\HRRoleAccessService;
use App\Services\KnowledgeManagement\KmAccessService;
use Illuminate\Support\Collection;

class HRMenuService
{
    private HRRoleAccessService $roleAccess;

    private KmAccessService $kmAccess;

    public function __construct(
        ?HRRoleAccessService $roleAccess = null,
        ?KmAccessService $kmAccess = null,
    ) {
        $this->roleAccess = $roleAccess ?? new HRRoleAccessService();
        $this->kmAccess = $kmAccess ?? new KmAccessService($this->roleAccess);
    }

    /**
     * Normalize the input to a User instance
     * 
     * @param User|string $user
     * @return User|null
     */
    private function resolveUser(User|string $user): ?User
    {
        if ($user instanceof User) {
            return $user;
        }
        return User::where('name', $user)->first();
    }

    /**
     * Get HR menu structure with access control
     * 
     * @param User|string $user
     * @return Collection
     */
    public function getMenuStructure(User|string $user): Collection
    {
        $userModel = $this->resolveUser($user);
        if (!$userModel) {
            return collect([
                'show_main_menu' => false,
                'knowledge_management' => ['show_form' => false, 'show_approval' => false, 'items' => []],
                'base_competency' => ['items' => []],
                'training_development' => ['items' => []],
            ]);
        }

        return collect([
            'show_main_menu' => $this->hasAnyAccess($userModel),
            'knowledge_management' => $this->getKnowledgeManagementMenu($userModel),
            'base_competency' => $this->getBaseCompetencyMenu($userModel),
            'training_development' => $this->getTrainingDevelopmentMenu($userModel),
        ]);
    }

    /**
     * Get Knowledge Management submenu
     * 
     * @param User $user
     * @return array
     */
    private function getKnowledgeManagementMenu(User $user): array
    {
        $showForm = $this->kmAccess->canCreate($user);
        $showApproval = $this->kmAccess->canApprove($user);

        return [
            'show_form' => $showForm,
            'show_approval' => $showApproval,
            'items' => [
                [
                    'label' => 'Form Knowledge Management',
                    'route' => 'pengajuanKM',
                    'visible' => $showForm,
                ],
                [
                    'label' => 'Persetujuan Knowledge Management',
                    'route' => 'persetujuanKM',
                    'visible' => $showApproval,
                ],
            ],
        ];
    }

    /**
     * Get Base Competency submenu
     * 
     * @param User $user
     * @return array
     */
    private function getBaseCompetencyMenu(User $user): array
    {
        return [
            'items' => [
                [
                    'label' => 'Form Job Position',
                    'route' => 'jobShow',
                    'visible' => HRMenuAccessGroup::JOB_POSITION->hasAccessForUser($user),
                ],
                [
                    'label' => 'Form Pengajuan Competency',
                    'route' => 'tcShow',
                    'visible' => HRMenuAccessGroup::TECHNICAL_COMPETENCY->hasAccessForUser($user),
                ],
                [
                    'label' => 'Penilaian Technical Competency Ka. Sie',
                    'route' => 'penilaian.index',
                    'visible' => $this->canAccessCompetencyLevel($user, 'kasie'),
                ],
                [
                    'label' => 'Penilaian Technical Competency Ka. Dept',
                    'route' => 'penilaian.index',
                    'params' => ['level' => 'kadept'],
                    'visible' => $this->canAccessCompetencyLevel($user, 'kadept'),
                ],
                [
                    'label' => 'Penilaian Technical Competency HR',
                    'route' => 'penilaian.index',
                    'params' => ['level' => 'hr'],
                    'visible' => $this->canAccessCompetencyLevel($user, 'hr'),
                ],
                [
                    'label' => 'Summary Competency',
                    'route' => 'job.positions.index',
                    'visible' => HRMenuAccessGroup::SUMMARY_COMPETENCY->hasAccessForUser($user),
                ],
            ],
        ];
    }

    /**
     * Get Training Development submenu
     * 
     * @param User $user
     * @return array
     */
    private function getTrainingDevelopmentMenu(User $user): array
    {
        return [
            'items' => [
                [
                    'label' => 'Form Pengajuan Training',
                    'route' => 'indexPD',
                    'visible' => $this->canAccessTrainingDevelopment($user),
                ],
                [
                    'label' => 'Persetujuan Development',
                    'route' => 'indexPD2',
                    'visible' => $this->canApproveTrainingDevelopment($user),
                ],
                [
                    'label' => 'History Development',
                    'route' => 'historiDept',
                    'visible' => $this->canAccessTrainingHistory($user),
                ],
            ],
        ];
    }

    /**
     * Check if user has access to any HR menu
     * 
     * @param User $user
     * @return bool
     */
    public function hasAnyAccess(User $user): bool
    {
        return $this->kmAccess->canCreate($user)
            || HRMenuAccessGroup::HR_MAIN->hasAccessForUser($user)
            || $this->canAccessCompetencyLevel($user, 'kasie')
            || $this->canAccessCompetencyLevel($user, 'kadept')
            || $this->canAccessCompetencyLevel($user, 'hr')
            || $this->canAccessTrainingDevelopment($user)
            || $this->canApproveTrainingDevelopment($user)
            || $this->canAccessTrainingHistory($user);
    }

    /**
     * Get visible menu items from a menu structure
     * 
     * @param array $menuItems
     * @return Collection
     */
    public function getVisibleItems(array $menuItems): Collection
    {
        return collect($menuItems)
            ->filter(fn($item) => $item['visible'] ?? false);
    }

    private function canAccessCompetencyLevel(User $user, string $level): bool
    {
        return $this->roleAccess->canAccessCompetencyLevel($user, $level);
    }

    private function canAccessTrainingDevelopment(User $user): bool
    {
        return $this->roleAccess->canAccessTrainingDevelopment($user);
    }

    private function canApproveTrainingDevelopment(User $user): bool
    {
        return $this->roleAccess->canApproveTrainingDevelopment($user);
    }

    private function canAccessTrainingHistory(User $user): bool
    {
        return $this->roleAccess->canAccessTrainingHistory($user);
    }
}
