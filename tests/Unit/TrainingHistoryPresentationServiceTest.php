<?php

namespace Tests\Unit;

use App\Models\MstDepartment;
use App\Models\MstSection;
use App\Models\TcPeopleDevelopment;
use App\Models\User;
use App\Services\HR\TrainingHistoryPresentationService;
use Tests\TestCase;

class TrainingHistoryPresentationServiceTest extends TestCase
{
    public function test_payload_exposes_explicit_history_fields_and_download_for_full_hr(): void
    {
        $actor = $this->user(91, 'SITI MARIA ULFA');
        $participant = $this->user(22, 'BUDI SETIAWAN', '00122');
        $training = $this->training();

        $payload = app(TrainingHistoryPresentationService::class)->payload($actor, [[
            'training' => $training,
            'participant' => $participant,
        ]]);

        $this->assertSame(1, $payload['meta']['total']);
        $this->assertSame([
            'id' => 77,
            'npk' => '00122',
            'employee_name' => 'BUDI SETIAWAN',
            'program' => 'Leadership Essentials',
            'category' => 'softskill',
            'competency' => 'Communication',
            'institution' => 'ADASI Learning Center',
            'period' => '2026-07-20',
            'year' => 2026,
            'department_id' => 4,
            'department_name' => 'Finance, Accounting & HRGA',
            'has_file' => true,
            'can_download' => true,
            'download_url' => route('download.pdf', ['id' => 77]),
        ], $payload['data'][0]);
    }

    public function test_download_url_is_hidden_when_actor_is_not_owner_or_full_hr(): void
    {
        $actor = $this->user(25, 'DEPARTMENT HEAD');
        $participant = $this->user(22, 'BUDI SETIAWAN', 0);
        $training = $this->training();

        $row = app(TrainingHistoryPresentationService::class)->row($actor, $training, $participant);

        $this->assertSame('-', $row['npk']);
        $this->assertTrue($row['has_file']);
        $this->assertFalse($row['can_download']);
        $this->assertNull($row['download_url']);
    }

    private function training(): TcPeopleDevelopment
    {
        $department = new MstDepartment(['name' => 'Finance, Accounting & HRGA']);
        $department->id = 4;

        $section = new MstSection(['name' => 'Accounting']);
        $section->id = 12;
        $section->setRelation('department', $department);

        $training = new TcPeopleDevelopment([
            'program_training' => 'Leadership',
            'program_training_plan' => 'Leadership Essentials',
            'kategori_competency' => 'softskill',
            'competency' => 'Communication',
            'lembaga' => 'Vendor Lama',
            'lembaga_plan' => 'ADASI Learning Center',
            'due_date' => '2026-07-10',
            'due_date_plan' => '2026-07-20',
            'tahun_aktual' => 2026,
            'file' => 'proof.pdf',
            'file_name' => 'Bukti Training.pdf',
            'modified_at' => 'TRAINING OWNER',
        ]);
        $training->id = 77;
        $training->setRelation('section', $section);

        return $training;
    }

    private function user(int $id, string $name, mixed $npk = null): User
    {
        $user = new User([
            'name' => $name,
            'npk' => $npk,
        ]);
        $user->id = $id;
        $user->exists = true;

        return $user;
    }
}
