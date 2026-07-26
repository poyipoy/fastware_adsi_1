<?php

namespace Database\Factories;

use App\Enums\KnowledgeManagement\KmApprovalAction;
use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Models\KmApprovalEvent;
use App\Models\KmPengajuan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KmApprovalEvent>
 */
class KmApprovalEventFactory extends Factory
{
    protected $model = KmApprovalEvent::class;

    public function definition(): array
    {
        return [
            'km_pengajuan_id' => KmPengajuan::factory()->pending(),
            'actor_id' => User::factory(),
            'actor_name' => fake()->name(),
            'actor_role_snapshot' => 'HR',
            'action' => KmApprovalAction::SUBMITTED,
            'from_status' => KmDocumentStatus::DRAFT->value,
            'to_status' => KmDocumentStatus::PENDING_APPROVAL->value,
            'reason' => null,
            'metadata' => ['request_id' => fake()->uuid()],
            'acted_at' => now(),
        ];
    }
}
