<?php

namespace App\Services;

use App\Enums\HRMenuAccessGroup;
use Illuminate\Support\Collection;

class HRMenuService
{
    /**
     * Get HR menu structure with access control
     * 
     * @param string $userName
     * @return Collection
     */
    public function getMenuStructure(string $userName): Collection
    {
        return collect([
            'show_main_menu' => HRMenuAccessGroup::HR_MAIN->hasAccess($userName),
            'knowledge_management' => $this->getKnowledgeManagementMenu($userName),
            'base_competency' => $this->getBaseCompetencyMenu($userName),
            'training_development' => $this->getTrainingDevelopmentMenu($userName),
        ]);
    }

    /**
     * Get Knowledge Management submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getKnowledgeManagementMenu(string $userName): array
    {
        return [
            'show_form' => HRMenuAccessGroup::KNOWLEDGE_MANAGEMENT->hasAccess($userName),
            'show_approval' => HRMenuAccessGroup::KNOWLEDGE_APPROVAL->hasAccess($userName),
            'items' => [
                [
                    'label' => 'Form Knowledge Management',
                    'route' => 'pengajuanKM',
                    'visible' => HRMenuAccessGroup::KNOWLEDGE_MANAGEMENT->hasAccess($userName),
                ],
                [
                    'label' => 'Persetujuan Knowledge Management',
                    'route' => 'persetujuanKM',
                    'visible' => HRMenuAccessGroup::KNOWLEDGE_APPROVAL->hasAccess($userName),
                ],
            ],
        ];
    }

    /**
     * Get Base Competency submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getBaseCompetencyMenu(string $userName): array
    {
        return [
            'items' => [
                [
                    'label' => 'Form Job Position',
                    'route' => 'jobShow',
                    'visible' => HRMenuAccessGroup::JOB_POSITION->hasAccess($userName),
                ],
                [
                    'label' => 'Form Pengajuan Competency',
                    'route' => 'tcShow',
                    'visible' => HRMenuAccessGroup::TECHNICAL_COMPETENCY->hasAccess($userName),
                ],
                [
                    'label' => 'Penilaian Technical Competency Ka. Sie',
                    'route' => 'penilaian.index',
                    'visible' => HRMenuAccessGroup::COMPETENCY_KASIE->hasAccess($userName),
                ],
                [
                    'label' => 'Penilaian Technical Competency Ka. Dept',
                    'route' => 'penilaian.index',
                    'visible' => HRMenuAccessGroup::COMPETENCY_KADEPT->hasAccess($userName),
                ],
                [
                    'label' => 'Penilaian Technical Competency HR',
                    'route' => 'penilaian.index',
                    'visible' => HRMenuAccessGroup::COMPETENCY_HR->hasAccess($userName),
                ],
                [
                    'label' => 'Summary Competency',
                    'route' => 'job.positions.index',
                    'visible' => HRMenuAccessGroup::SUMMARY_COMPETENCY->hasAccess($userName),
                ],
            ],
        ];
    }

    /**
     * Get Training Development submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getTrainingDevelopmentMenu(string $userName): array
    {
        return [
            'items' => [
                [
                    'label' => 'Form Pengajuan Training',
                    'route' => 'indexPD',
                    'visible' => HRMenuAccessGroup::TRAINING_DEVELOPMENT->hasAccess($userName),
                ],
                [
                    'label' => 'Persetujuan Development',
                    'route' => 'indexPD2',
                    'visible' => HRMenuAccessGroup::TRAINING_APPROVAL->hasAccess($userName),
                ],
                [
                    'label' => 'History Development',
                    'route' => 'penilaian.index2',
                    'visible' => HRMenuAccessGroup::TRAINING_HISTORY->hasAccess($userName),
                ],
            ],
        ];
    }

    /**
     * Check if user has access to any HR menu
     * 
     * @param string $userName
     * @return bool
     */
    public function hasAnyAccess(string $userName): bool
    {
        return HRMenuAccessGroup::HR_MAIN->hasAccess($userName);
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
}

