<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Console\Commands\MigrateAdasiCommand;
use App\Models\MstJobPosition;
use App\Models\User;
use App\Services\KnowledgeManagement\KmOrganizationAssignmentService;
use ReflectionMethod;

class KmOrganizationAssignmentPeriodTest extends KmTestCase
{
    public function test_assignment_writer_defaults_required_period_and_source(): void
    {
        $actor = User::factory()->create(['name' => 'KM Assignment Actor']);
        $user = User::factory()->create(['name' => 'KM Assignment Recipient']);
        $position = MstJobPosition::query()->create([
            'position_name' => 'KM Assignment Position',
            'is_active' => true,
        ]);

        $assignment = app(KmOrganizationAssignmentService::class)->create($actor, [
            'user_id' => $user->getKey(),
            'mst_job_position_id' => $position->getKey(),
            'is_active' => true,
        ], 'Test periode default.');

        $this->assertSame(today()->toDateString(), $assignment->effective_from?->toDateString());
        $this->assertSame('km_module', $assignment->assignment_source);
    }

    public function test_adasi_import_writer_supplies_required_period_and_source(): void
    {
        $method = new ReflectionMethod(MigrateAdasiCommand::class, 'activeJobPositionAssignmentValues');
        $method->setAccessible(true);

        $values = $method->invoke(app(MigrateAdasiCommand::class));

        $this->assertTrue($values['is_active']);
        $this->assertSame(today()->toDateString(), $values['effective_from']);
        $this->assertSame('adasi_import', $values['assignment_source']);
    }
}
