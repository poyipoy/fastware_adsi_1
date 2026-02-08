<?php

namespace Tests\Unit;

use App\Enums\HRMenuAccessGroup;
use PHPUnit\Framework\TestCase;

class HRMenuAccessGroupTest extends TestCase
{
    /**
     * Test enum cases exist
     */
    public function test_enum_cases_exist(): void
    {
        $this->assertNotEmpty(HRMenuAccessGroup::cases());
        $this->assertContainsOnlyInstancesOf(HRMenuAccessGroup::class, HRMenuAccessGroup::cases());
    }

    /**
     * Test HR_MAIN has correct users
     */
    public function test_hr_main_has_correct_users(): void
    {
        $users = HRMenuAccessGroup::HR_MAIN->getAllowedUsers();

        $this->assertIsArray($users);
        $this->assertContains('ADMINSTRATOR', $users);
        $this->assertContains('JESSICA PAUNE', $users);
        $this->assertContains('MUGI PRAMONO', $users);
    }

    /**
     * Test hasAccess method for authorized user
     */
    public function test_has_access_returns_true_for_authorized_user(): void
    {
        $this->assertTrue(HRMenuAccessGroup::HR_MAIN->hasAccess('ADMINSTRATOR'));
        $this->assertTrue(HRMenuAccessGroup::HR_MAIN->hasAccess('JESSICA PAUNE'));
    }

    /**
     * Test hasAccess method for unauthorized user
     */
    public function test_has_access_returns_false_for_unauthorized_user(): void
    {
        $this->assertFalse(HRMenuAccessGroup::HR_MAIN->hasAccess('RANDOM USER'));
        $this->assertFalse(HRMenuAccessGroup::HR_MAIN->hasAccess('UNKNOWN'));
    }

    /**
     * Test Knowledge Management access
     */
    public function test_knowledge_management_access(): void
    {
        $users = HRMenuAccessGroup::KNOWLEDGE_MANAGEMENT->getAllowedUsers();

        $this->assertIsArray($users);
        $this->assertContains('ADMINSTRATOR', $users);
        $this->assertContains('MUGI PRAMONO', $users);
        $this->assertContains('JESSICA PAUNE', $users);
    }

    /**
     * Test Job Position access (limited users)
     */
    public function test_job_position_has_limited_access(): void
    {
        $users = HRMenuAccessGroup::JOB_POSITION->getAllowedUsers();

        $this->assertIsArray($users);
        $this->assertContains('ADMINSTRATOR', $users);
        $this->assertContains('JESSICA PAUNE', $users);
        $this->assertContains('SITI MARIA ULFA', $users);
        
        // Should be limited to these 3 users only
        $this->assertCount(3, $users);
    }

    /**
     * Test Training Development access
     */
    public function test_training_development_access(): void
    {
        $users = HRMenuAccessGroup::TRAINING_DEVELOPMENT->getAllowedUsers();

        $this->assertIsArray($users);
        $this->assertContains('ADMINSTRATOR', $users);
        $this->assertContains('JESSICA PAUNE', $users);
        $this->assertContains('ARY RODJO PRASETYO', $users);
    }

    /**
     * Test all enum cases have getAllowedUsers method
     */
    public function test_all_enum_cases_return_users_array(): void
    {
        foreach (HRMenuAccessGroup::cases() as $case) {
            $users = $case->getAllowedUsers();
            
            $this->assertIsArray($users);
            $this->assertNotEmpty($users, "Enum case {$case->value} should have users");
        }
    }

    /**
     * Test enum value format
     */
    public function test_enum_values_are_lowercase_with_underscores(): void
    {
        foreach (HRMenuAccessGroup::cases() as $case) {
            $this->assertMatchesRegularExpression(
                '/^[a-z_]+$/',
                $case->value,
                "Enum value {$case->value} should be lowercase with underscores"
            );
        }
    }

    /**
     * Test admin has access to all menus
     */
    public function test_admin_has_access_to_all_menus(): void
    {
        foreach (HRMenuAccessGroup::cases() as $case) {
            $this->assertTrue(
                $case->hasAccess('ADMINSTRATOR'),
                "ADMINSTRATOR should have access to {$case->value}"
            );
        }
    }

    /**
     * Test case sensitivity of user names
     */
    public function test_user_access_is_case_sensitive(): void
    {
        // Correct case
        $this->assertTrue(HRMenuAccessGroup::HR_MAIN->hasAccess('ADMINSTRATOR'));
        
        // Wrong case - should fail
        $this->assertFalse(HRMenuAccessGroup::HR_MAIN->hasAccess('adminstrator'));
        $this->assertFalse(HRMenuAccessGroup::HR_MAIN->hasAccess('Administrator'));
    }
}

