<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\HR\JobPositionAccessService;
use App\Services\HR\TrainingParticipantService;
use Closure;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TrainingParticipantServiceTest extends TestCase
{
    private TrainingParticipantService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TrainingParticipantService(new JobPositionAccessService());
    }

    public function test_sharing_knowledge_requires_a_participant(): void
    {
        $this->assertValidationField(fn () => $this->service->prepareRows([[
            'is_sharing_knowledge' => true,
            'participant_user_ids' => [],
        ]], new User()), 'rows.0.participant_user_ids');
    }

    public function test_sharing_knowledge_rejects_duplicate_participant_ids_before_querying_database(): void
    {
        $this->assertValidationField(fn () => $this->service->prepareRows([[
            'is_sharing_knowledge' => true,
            'participant_user_ids' => [15, '15'],
        ]], new User()), 'rows.0.participant_user_ids');
    }

    public function test_regular_training_requires_one_employee(): void
    {
        $this->assertValidationField(fn () => $this->service->prepareRows([[
            'is_sharing_knowledge' => false,
            'id_user' => null,
        ]], new User()), 'rows.0.id_user');
    }

    private function assertValidationField(Closure $callback, string $field): void
    {
        try {
            $callback();
            $this->fail('ValidationException tidak dilempar.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }
}
