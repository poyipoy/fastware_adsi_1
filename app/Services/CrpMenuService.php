<?php

namespace App\Services;

use App\Enums\CrpMenuAccessGroup;
use Illuminate\Support\Collection;

class CrpMenuService
{
    /**
     * Get CRP System menu structure with access control
     * 
     * @param string $userName
     * @return Collection
     */
    public function getMenuStructure(string $userName): Collection
    {
        $hasAccess = CrpMenuAccessGroup::CRP_MAIN->hasAccess($userName);

        return collect([
            'visible' => $hasAccess,
            'items' => [
                [
                    'label' => 'Form CRP',
                    'route' => 'crp',
                    'visible' => $hasAccess,
                ],
            ],
        ]);
    }
}

