<?php

namespace Tests\Unit;

use App\Http\Requests\UpdateTrainingFollowUpRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateTrainingFollowUpRequestTest extends TestCase
{
    public function test_sharing_knowledge_accepts_multiple_participant_ids(): void
    {
        $request = $this->requestFor([
            'id' => 'new_1',
            'id_user' => null,
            'is_sharing_knowledge' => true,
            'participant_user_ids' => [12, 34],
            'program_training' => 'Sharing Internal',
        ]);

        $request->validateResolved();

        $this->assertSame(
            [12, 34],
            $request->validated('rows.0.participant_user_ids'),
        );
    }

    public function test_sharing_knowledge_rejects_duplicate_participant_ids(): void
    {
        $request = $this->requestFor([
            'id' => 'new_2',
            'id_user' => null,
            'is_sharing_knowledge' => true,
            'participant_user_ids' => [12, '12'],
            'program_training' => 'Sharing Internal',
        ]);

        try {
            $request->validateResolved();
            $this->fail('Duplicate participant seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'rows.0.participant_user_ids',
                $exception->errors(),
            );
        }
    }

    public function test_same_participant_is_allowed_in_different_sharing_activities(): void
    {
        $request = $this->requestForRows([
            [
                'id' => 953,
                'id_user' => null,
                'is_sharing_knowledge' => true,
                'participant_user_ids' => [12, 34],
                'program_training' => 'Sharing Pertama',
            ],
            [
                'id' => 'new_4',
                'id_user' => null,
                'is_sharing_knowledge' => true,
                'participant_user_ids' => [12, 56],
                'program_training' => 'Sharing Kedua',
            ],
        ]);

        $request->validateResolved();

        $this->assertSame([12, 34], $request->validated('rows.0.participant_user_ids'));
        $this->assertSame([12, 56], $request->validated('rows.1.participant_user_ids'));
    }

    public function test_sharing_knowledge_requires_at_least_one_participant(): void
    {
        $request = $this->requestFor([
            'id' => 'new_3',
            'id_user' => null,
            'is_sharing_knowledge' => true,
            'participant_user_ids' => [],
            'program_training' => 'Sharing Internal',
        ]);

        try {
            $request->validateResolved();
            $this->fail('Sharing Knowledge tanpa participant seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['Sharing Knowledge wajib memiliki participant.'],
                $exception->errors()['rows.0.participant_user_ids'] ?? [],
            );
        }
    }

    public function test_inert_legacy_sharing_row_is_ignored_without_being_deleted(): void
    {
        $request = $this->requestFor([
            'id' => 949,
            'id_user' => null,
            'is_sharing_knowledge' => true,
            'participant_user_ids' => [],
            'program_training' => '',
            'program_training_plan' => null,
            'biaya' => '0',
            'biaya_plan' => '0',
        ]);

        $request->validateResolved();

        $this->assertSame([], $request->validated('rows'));
    }

    public function test_meaningful_legacy_sharing_row_still_requires_a_participant(): void
    {
        $request = $this->requestFor([
            'id' => 952,
            'id_user' => null,
            'is_sharing_knowledge' => true,
            'participant_user_ids' => [],
            'program_training' => 'Sharing Internal',
            'biaya' => '0',
            'biaya_plan' => '0',
        ]);

        try {
            $request->validateResolved();
            $this->fail('Sharing Knowledge yang memiliki isi tetap wajib memiliki participant.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['Sharing Knowledge wajib memiliki participant.'],
                $exception->errors()['rows.0.participant_user_ids'] ?? [],
            );
        }
    }

    private function requestFor(array $row): UpdateTrainingFollowUpRequest
    {
        return $this->requestForRows([$row]);
    }

    private function requestForRows(array $rows): UpdateTrainingFollowUpRequest
    {
        $request = UpdateTrainingFollowUpRequest::create('/update-data', 'POST', [
            'data' => json_encode($rows, JSON_THROW_ON_ERROR),
        ]);

        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));

        return $request;
    }
}
