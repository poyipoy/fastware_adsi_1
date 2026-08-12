<?php

namespace Tests\Feature;

use App\Models\TcPeopleDevelopment;
use App\Models\User;
use App\Services\HR\TrainingHistoryQueryService;
use Illuminate\Support\LazyCollection;
use Tests\TestCase;

class TrainingHistoryEndpointTest extends TestCase
{
    public function test_filter_endpoint_returns_the_explicit_history_contract(): void
    {
        $actor = new User(['name' => 'SITI MARIA ULFA']);
        $actor->id = 91;
        $actor->exists = true;

        $participant = new User([
            'name' => 'MEDI KRISNANTO',
            'npk' => '5661',
        ]);
        $participant->id = 44;

        $training = new TcPeopleDevelopment([
            'program_training_plan' => 'C++',
            'kategori_competency' => 'technical',
            'competency' => 'TEST 1',
            'lembaga_plan' => 'TES 2',
            'due_date_plan' => '2026-07-29',
            'tahun_aktual' => 2026,
            'file' => null,
            'file_name' => null,
            'modified_at' => 'SITI MARIA ULFA',
        ]);
        $training->id = 949;
        $training->setRelation('section', null);
        $training->setRelation('jobPosition', null);

        $this->mock(TrainingHistoryQueryService::class, function ($mock) use ($training, $participant): void {
            $mock->shouldReceive('flattened')
                ->once()
                ->withArgs(function (User $user, array $filters): bool {
                    return (int) $user->id === 91
                        && $filters === [
                            'department_id' => 4,
                            'year' => 2026,
                            'search' => 'MEDI',
                        ];
                })
                ->andReturn(LazyCollection::make([[
                    'training' => $training,
                    'participant' => $participant,
                ]]));
        });

        $response = $this->actingAs($actor)->getJson(route('people_development.filter', [
            'department_id' => 4,
            'year' => 2026,
            'search' => 'MEDI',
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', 949)
            ->assertJsonPath('data.0.npk', '5661')
            ->assertJsonPath('data.0.employee_name', 'MEDI KRISNANTO')
            ->assertJsonPath('data.0.program', 'C++')
            ->assertJsonPath('data.0.has_file', false)
            ->assertJsonPath('data.0.can_download', false)
            ->assertJsonPath('data.0.download_url', null)
            ->assertJsonMissingPath('data.0.modified_at')
            ->assertJsonMissingPath('data.0.biaya');
    }
}
