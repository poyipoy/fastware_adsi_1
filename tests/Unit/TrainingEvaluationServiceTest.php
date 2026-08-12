<?php

namespace Tests\Unit;

use App\Models\TcPeopleDevelopment;
use App\Models\User;
use App\Services\HR\HRRoleAccessService;
use App\Services\HR\JobPositionAccessService;
use App\Services\HR\TrainingEvaluationService;
use App\Services\HR\TrainingParticipantService;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TrainingEvaluationServiceTest extends TestCase
{
    public function test_full_hr_can_edit_completed_sharing_activity_with_participants(): void
    {
        $training = $this->sharingTraining([$this->user(20, '1001', 'Participant Satu')]);

        $this->service()->assertCanEdit($this->user(1, '1', 'Administrator'), $training);

        $this->assertTrue(true);
    }

    public function test_full_hr_with_legacy_readonly_role_can_edit_sharing_evaluation(): void
    {
        $training = $this->sharingTraining([$this->user(20, '1001', 'Participant Satu')]);
        $actor = $this->user(91, '91', 'SITI MARIA ULFA');
        $actor->role_id = 15;

        $this->assertTrue($this->service()->canEdit($actor, $training));
        $this->service()->assertCanEdit($actor, $training);
    }

    public function test_non_full_hr_cannot_edit_sharing_evaluation(): void
    {
        $training = $this->sharingTraining([$this->user(20, '1001', 'Participant Satu')]);

        try {
            $this->service()->assertCanEdit($this->user(30, '2001', 'Department Head'), $training);
            $this->fail('Non full-HR seharusnya ditolak.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_sharing_evaluation_requires_done_status_and_participant(): void
    {
        $notDone = $this->sharingTraining([$this->user(20, '1001', 'Participant Satu')]);
        $notDone->status_2 = 'On Progress';

        try {
            $this->service()->assertCanEdit($this->user(91, '91', 'HR'), $notDone);
            $this->fail('Kegiatan yang belum Done seharusnya ditolak.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }

        $withoutParticipant = $this->sharingTraining([]);
        try {
            $this->service()->assertCanEdit($this->user(91, '91', 'HR'), $withoutParticipant);
            $this->fail('Kegiatan tanpa participant seharusnya ditolak.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_participant_can_only_view_completed_group_result(): void
    {
        $participant = $this->user(20, '1001', 'Participant Satu');
        $outsider = $this->user(30, '2001', 'User Lain');
        $training = $this->sharingTraining([$participant]);
        $service = $this->service();

        $this->assertFalse($service->canViewResult($participant, $training));

        $training->dievaluasi = 'HR';
        $training->tgl_pengajuan = '2026-07-30 10:00:00';

        $this->assertTrue($service->canViewResult($participant, $training));
        $this->assertFalse($service->canViewResult($outsider, $training));
        $this->assertTrue($service->canViewResult($this->user(1, '1', 'Administrator'), $training));
    }

    public function test_legacy_sharing_uses_id_user_as_participant_fallback(): void
    {
        $legacyParticipant = $this->user(27, '5678', 'Legacy Participant');
        $training = $this->sharingTraining([]);
        $training->id_user = 27;
        $training->setRelation('user', $legacyParticipant);
        $training->dievaluasi = 'HR';
        $training->tgl_pengajuan = '2026-07-30 10:00:00';
        $service = $this->service();

        $this->assertTrue($service->canViewResult($legacyParticipant, $training));
        $this->assertSame(
            ['5678 — Legacy Participant'],
            collect($service->payload($training)['participants'])->pluck('label')->all(),
        );
    }

    public function test_payload_is_explicit_and_reports_group_completion(): void
    {
        $training = $this->sharingTraining([
            $this->user(20, '0', 'Participant Satu'),
            $this->user(21, '1002', 'Participant Dua'),
        ]);
        $training->id = 952;
        $training->program_training = 'Sharing Internal';
        $training->dievaluasi = 'SITI MARIA ULFA';
        $training->tgl_pengajuan = '2026-07-30 10:00:00';

        $payload = $this->service()->payload($training);

        $this->assertTrue($payload['evaluation_completed']);
        $this->assertSame('Sharing Knowledge', $payload['activity_type']);
        $this->assertSame(2, $payload['participant_count']);
        $this->assertSame('- — Participant Satu', $payload['participants'][0]['label']);
        $this->assertArrayNotHasKey('modified_at', $payload);
    }

    public function test_regular_training_keeps_legacy_edit_behavior(): void
    {
        $training = new TcPeopleDevelopment([
            'is_sharing_knowledge' => false,
            'status_2' => 'Done',
        ]);

        $this->service()->assertCanEdit($this->user(30, '2001', 'Existing Actor'), $training);

        $this->assertTrue(true);
    }

    private function service(): TrainingEvaluationService
    {
        $participantService = new TrainingParticipantService(
            Mockery::mock(JobPositionAccessService::class),
        );

        return new TrainingEvaluationService(
            new HRRoleAccessService(),
            $participantService,
        );
    }

    /**
     * @param  array<int, User>  $participants
     */
    private function sharingTraining(array $participants): TcPeopleDevelopment
    {
        $training = new TcPeopleDevelopment([
            'is_sharing_knowledge' => true,
            'status_2' => 'Done',
        ]);
        $training->setRelation('participants', collect($participants));
        $training->setRelation('user', null);
        $training->setRelation('section', null);

        return $training;
    }

    private function user(int $id, string $npk, string $name): User
    {
        $user = new User([
            'npk' => $npk,
            'name' => $name,
        ]);
        $user->id = $id;

        return $user;
    }
}
