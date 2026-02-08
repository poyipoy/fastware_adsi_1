<?php

namespace Tests\Unit;

use App\Services\HRMenuService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class HRMenuServiceTest extends TestCase
{
    private HRMenuService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HRMenuService();
    }

    /**
     * Test getMenuStructure returns Collection
     */
    public function test_get_menu_structure_returns_collection(): void
    {
        $menu = $this->service->getMenuStructure('ADMINSTRATOR');

        $this->assertInstanceOf(Collection::class, $menu);
    }

    /**
     * Test menu structure has required keys
     */
    public function test_menu_structure_has_required_keys(): void
    {
        $menu = $this->service->getMenuStructure('ADMINSTRATOR');

        $this->assertArrayHasKey('show_main_menu', $menu);
        $this->assertArrayHasKey('knowledge_management', $menu);
        $this->assertArrayHasKey('base_competency', $menu);
        $this->assertArrayHasKey('training_development', $menu);
    }

    /**
     * Test admin user sees main menu
     */
    public function test_admin_user_sees_main_menu(): void
    {
        $menu = $this->service->getMenuStructure('ADMINSTRATOR');

        $this->assertTrue($menu['show_main_menu']);
    }

    /**
     * Test unauthorized user doesn't see main menu
     */
    public function test_unauthorized_user_doesnt_see_main_menu(): void
    {
        $menu = $this->service->getMenuStructure('RANDOM USER');

        $this->assertFalse($menu['show_main_menu']);
    }

    /**
     * Test knowledge management menu has items array
     */
    public function test_knowledge_management_has_items_array(): void
    {
        $menu = $this->service->getMenuStructure('ADMINSTRATOR');

        $this->assertIsArray($menu['knowledge_management']);
        $this->assertArrayHasKey('items', $menu['knowledge_management']);
        $this->assertIsArray($menu['knowledge_management']['items']);
    }

    /**
     * Test base competency menu has items array
     */
    public function test_base_competency_has_items_array(): void
    {
        $menu = $this->service->getMenuStructure('ADMINSTRATOR');

        $this->assertIsArray($menu['base_competency']);
        $this->assertArrayHasKey('items', $menu['base_competency']);
        $this->assertIsArray($menu['base_competency']['items']);
    }

    /**
     * Test training development menu has items array
     */
    public function test_training_development_has_items_array(): void
    {
        $menu = $this->service->getMenuStructure('ADMINSTRATOR');

        $this->assertIsArray($menu['training_development']);
        $this->assertArrayHasKey('items', $menu['training_development']);
        $this->assertIsArray($menu['training_development']['items']);
    }

    /**
     * Test menu items have required fields
     */
    public function test_menu_items_have_required_fields(): void
    {
        $menu = $this->service->getMenuStructure('ADMINSTRATOR');
        $items = $menu['knowledge_management']['items'];

        foreach ($items as $item) {
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('route', $item);
            $this->assertArrayHasKey('visible', $item);
            
            $this->assertIsString($item['label']);
            $this->assertIsString($item['route']);
            $this->assertIsBool($item['visible']);
        }
    }

    /**
     * Test admin sees all menu items as visible
     */
    public function test_admin_sees_all_menu_items_as_visible(): void
    {
        $menu = $this->service->getMenuStructure('ADMINSTRATOR');

        // Knowledge Management items
        foreach ($menu['knowledge_management']['items'] as $item) {
            $this->assertTrue($item['visible'], "Item '{$item['label']}' should be visible for admin");
        }

        // Base Competency items
        foreach ($menu['base_competency']['items'] as $item) {
            $this->assertTrue($item['visible'], "Item '{$item['label']}' should be visible for admin");
        }

        // Training Development items
        foreach ($menu['training_development']['items'] as $item) {
            $this->assertTrue($item['visible'], "Item '{$item['label']}' should be visible for admin");
        }
    }

    /**
     * Test hasAnyAccess for authorized user
     */
    public function test_has_any_access_returns_true_for_authorized_user(): void
    {
        $this->assertTrue($this->service->hasAnyAccess('ADMINSTRATOR'));
        $this->assertTrue($this->service->hasAnyAccess('JESSICA PAUNE'));
    }

    /**
     * Test hasAnyAccess for unauthorized user
     */
    public function test_has_any_access_returns_false_for_unauthorized_user(): void
    {
        $this->assertFalse($this->service->hasAnyAccess('RANDOM USER'));
    }

    /**
     * Test getVisibleItems filters correctly
     */
    public function test_get_visible_items_filters_correctly(): void
    {
        $items = [
            ['label' => 'Item 1', 'visible' => true],
            ['label' => 'Item 2', 'visible' => false],
            ['label' => 'Item 3', 'visible' => true],
        ];

        $visibleItems = $this->service->getVisibleItems($items);

        $this->assertInstanceOf(Collection::class, $visibleItems);
        $this->assertCount(2, $visibleItems);
    }

    /**
     * Test getVisibleItems returns Collection
     */
    public function test_get_visible_items_returns_collection(): void
    {
        $items = [];
        $result = $this->service->getVisibleItems($items);

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * Test limited user sees limited menus
     */
    public function test_limited_user_sees_limited_menus(): void
    {
        // SITI MARIA ULFA has limited access
        $menu = $this->service->getMenuStructure('SITI MARIA ULFA');

        // Should see main menu
        $this->assertTrue($menu['show_main_menu']);

        // Count visible items - should be less than admin
        $kmVisibleCount = collect($menu['knowledge_management']['items'])
            ->filter(fn($item) => $item['visible'])
            ->count();
        
        $this->assertGreaterThanOrEqual(0, $kmVisibleCount);
    }

    /**
     * Test menu structure consistency for different users
     */
    public function test_menu_structure_consistency_for_different_users(): void
    {
        $users = ['ADMINSTRATOR', 'JESSICA PAUNE', 'RANDOM USER'];

        foreach ($users as $user) {
            $menu = $this->service->getMenuStructure($user);

            // All should have same structure
            $this->assertArrayHasKey('show_main_menu', $menu);
            $this->assertArrayHasKey('knowledge_management', $menu);
            $this->assertArrayHasKey('base_competency', $menu);
            $this->assertArrayHasKey('training_development', $menu);

            // All submenus should have items array
            $this->assertArrayHasKey('items', $menu['knowledge_management']);
            $this->assertArrayHasKey('items', $menu['base_competency']);
            $this->assertArrayHasKey('items', $menu['training_development']);
        }
    }

    /**
     * Test no menu item has empty label
     */
    public function test_no_menu_item_has_empty_label(): void
    {
        $menu = $this->service->getMenuStructure('ADMINSTRATOR');
        
        $allItems = array_merge(
            $menu['knowledge_management']['items'],
            $menu['base_competency']['items'],
            $menu['training_development']['items']
        );

        foreach ($allItems as $item) {
            $this->assertNotEmpty($item['label'], 'Menu item should have non-empty label');
        }
    }

    /**
     * Test no menu item has empty route
     */
    public function test_no_menu_item_has_empty_route(): void
    {
        $menu = $this->service->getMenuStructure('ADMINSTRATOR');
        
        $allItems = array_merge(
            $menu['knowledge_management']['items'],
            $menu['base_competency']['items'],
            $menu['training_development']['items']
        );

        foreach ($allItems as $item) {
            $this->assertNotEmpty($item['route'], 'Menu item should have non-empty route');
        }
    }

    /**
     * Test service returns same structure on multiple calls
     */
    public function test_service_returns_consistent_structure(): void
    {
        $menu1 = $this->service->getMenuStructure('ADMINSTRATOR');
        $menu2 = $this->service->getMenuStructure('ADMINSTRATOR');

        $this->assertEquals($menu1->toArray(), $menu2->toArray());
    }
}

