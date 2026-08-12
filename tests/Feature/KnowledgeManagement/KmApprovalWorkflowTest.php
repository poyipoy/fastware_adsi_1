<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmApprovalAction;
use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Exceptions\KnowledgeManagement\InvalidKmTransitionException;
use App\Models\KmApprovalEvent;
use App\Models\KmKategori;
use App\Models\KmPengajuan;
use App\Models\Role;
use App\Models\User;
use App\Services\KnowledgeManagement\KmApprovalService;
use Illuminate\Database\QueryException;
use LogicException;
use Tests\Support\KnowledgeManagement\RunsKmWorkers;

final class KmApprovalWorkflowTest extends KmTestCase
{
    use RunsKmWorkers;

    public function test_all_valid_transitions_keep_legacy_status_in_sync_and_append_events(): void
    {
        $approver = $this->createApprover();
        $owner = $this->createUser('KM Document Owner');
        $category = KmKategori::factory()->create();
        $document = $this->draftDocument($owner, $category);
        $service = $this->approvalService();

        $document = $service->submit($document, $owner, ['request_id' => 'submit-1']);
        $this->assertStatus($document, KmDocumentStatus::PENDING_APPROVAL, 1);

        $document = $service->reject(
            $document,
            $approver,
            'Perlu sumber tambahan',
            $this->attributesFor($document, $category),
            ['request_id' => 'reject-1'],
        );
        $this->assertStatus($document, KmDocumentStatus::DRAFT, 1);

        $document = $service->submit($document, $owner, ['request_id' => 'submit-2']);
        $this->assertStatus($document, KmDocumentStatus::PENDING_APPROVAL, 1);

        $document = $service->approve(
            $document,
            $approver,
            $this->attributesFor($document, $category, ['judul' => 'Dokumen Disetujui']),
            ['request_id' => 'approve-1'],
        );
        $this->assertStatus($document, KmDocumentStatus::PUBLISHED, 2);

        $document = $service->deactivate($document, $approver, ['request_id' => 'deactivate-1']);
        $this->assertStatus($document, KmDocumentStatus::INACTIVE, 0);

        $events = KmApprovalEvent::query()->orderBy('id')->get();
        $this->assertSame([
            KmApprovalAction::SUBMITTED->value,
            KmApprovalAction::REJECTED->value,
            KmApprovalAction::SUBMITTED->value,
            KmApprovalAction::APPROVED->value,
            KmApprovalAction::DEACTIVATED->value,
        ], $events->map(fn (KmApprovalEvent $event): string => $event->action->value)->all());
        $this->assertSame([1, 2, 1, 2, 3], $events->pluck('from_status')->all());
        $this->assertSame([2, 1, 2, 3, 0], $events->pluck('to_status')->all());
        $this->assertSame('Perlu sumber tambahan', $events[1]->reason);
        $this->assertSame('approve-1', $events[3]->metadata['request_id']);
    }

    public function test_legacy_submit_and_approve_endpoints_allow_approve_without_reason_and_snapshot_actor(): void
    {
        $approver = $this->createApprover();
        $owner = $this->createUser('KM Document Owner');
        $category = KmKategori::factory()->create();
        $document = $this->draftDocument($owner, $category);

        $this->actingAs($owner)
            ->withHeader('X-Request-ID', 'http-submit')
            ->postJson(route('kirimKM', $document->getKey()))
            ->assertOk();

        $this->assertStatus($document->refresh(), KmDocumentStatus::PENDING_APPROVAL, 1);
        $this->assertDatabaseHas('km_notifications', [
            'user_id' => $approver->getKey(),
            'type' => 'document_submitted',
        ]);

        $response = $this->actingAs($approver)
            ->withHeader('X-Request-ID', 'http-approve')
            ->from('/persetujuanKM')
            ->put(route('approveKM'), $this->approvalPayload($document, $category, [
                'approve' => '1',
                'judul' => 'Judul yang telah ditinjau',
            ]));

        $response->assertRedirect('/persetujuanKM')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $this->assertStatus($document->refresh(), KmDocumentStatus::PUBLISHED, 2);
        $this->assertSame('Judul yang telah ditinjau', $document->judul);

        $approved = KmApprovalEvent::query()
            ->where('action', KmApprovalAction::APPROVED->value)
            ->sole();
        $this->assertSame($approver->getKey(), $approved->actor_id);
        $this->assertSame($approver->name, $approved->actor_name);
        $this->assertSame('KM APPROVER', $approved->actor_role_snapshot);
        $this->assertSame(KmDocumentStatus::PENDING_APPROVAL->value, $approved->from_status);
        $this->assertSame(KmDocumentStatus::PUBLISHED->value, $approved->to_status);
        $this->assertNull($approved->reason);
        $this->assertSame('http-approve', $approved->metadata['request_id']);
        $this->assertSame(25, (int) $owner->refresh()->km_total_poin);
        $this->assertDatabaseHas('km_point_ledger', [
            'user_id' => $owner->getKey(),
            'event_type' => 'published_document',
            'points' => 25,
        ]);
        $this->assertDatabaseHas('km_notifications', [
            'user_id' => $owner->getKey(),
            'type' => 'document_approved',
        ]);
    }

    public function test_reject_requires_non_whitespace_reason_and_stores_trimmed_reason(): void
    {
        $approver = $this->createApprover();
        $owner = $this->createUser('KM Document Owner');
        $category = KmKategori::factory()->create();
        $document = KmPengajuan::factory()->pending()->create([
            'id_user' => $owner->getKey(),
            'id_km_kategori' => $category->getKey(),
        ]);

        $invalid = $this->actingAs($approver)
            ->from('/persetujuanKM')
            ->put(route('approveKM'), $this->approvalPayload($document, $category, [
                'reject' => '1',
                'reason' => " \t\n ",
            ]));

        $invalid->assertRedirect('/persetujuanKM')->assertSessionHasErrors('reason');
        $this->assertStatus($document->refresh(), KmDocumentStatus::PENDING_APPROVAL, 1);
        $this->assertSame(0, KmApprovalEvent::query()->count());

        $valid = $this->actingAs($approver)
            ->withHeader('X-Request-ID', 'http-reject')
            ->from('/persetujuanKM')
            ->put(route('approveKM'), $this->approvalPayload($document, $category, [
                'reject' => '1',
                'reason' => '  Mohon lengkapi referensi.  ',
            ]));

        $valid->assertRedirect('/persetujuanKM')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $this->assertStatus($document->refresh(), KmDocumentStatus::DRAFT, 1);

        $event = KmApprovalEvent::query()->sole();
        $this->assertSame(KmApprovalAction::REJECTED, $event->action);
        $this->assertSame('Mohon lengkapi referensi.', $event->reason);
        $this->assertSame(KmDocumentStatus::PENDING_APPROVAL->value, $event->from_status);
        $this->assertSame(KmDocumentStatus::DRAFT->value, $event->to_status);
        $this->assertSame('http-reject', $event->metadata['request_id']);
        $this->assertDatabaseHas('km_notifications', [
            'user_id' => $owner->getKey(),
            'type' => 'document_rejected',
        ]);
    }

    public function test_unauthorized_approver_cannot_change_document_or_create_event(): void
    {
        $this->createApprover();
        $owner = $this->createUser('KM Document Owner');
        $outsider = $this->createUser('Ordinary Employee');
        $category = KmKategori::factory()->create();
        $document = KmPengajuan::factory()->pending()->create([
            'id_user' => $owner->getKey(),
            'id_km_kategori' => $category->getKey(),
        ]);

        $this->actingAs($outsider)->put(
            route('approveKM'),
            $this->approvalPayload($document, $category, ['approve' => '1']),
        )->assertForbidden();

        $this->assertStatus($document->refresh(), KmDocumentStatus::PENDING_APPROVAL, 1);
        $this->assertSame(0, KmApprovalEvent::query()->count());
    }

    public function test_invalid_transitions_do_not_change_fields_or_create_events(): void
    {
        $approver = $this->createApprover();
        $owner = $this->createUser('KM Document Owner');
        $category = KmKategori::factory()->create();
        $published = KmPengajuan::factory()->published()->create([
            'id_user' => $owner->getKey(),
            'id_km_kategori' => $category->getKey(),
            'judul' => 'Judul Asli',
        ]);

        try {
            $this->approvalService()->approve(
                $published,
                $approver,
                $this->attributesFor($published, $category, ['judul' => 'Tidak Boleh Tersimpan']),
            );
            $this->fail('Approving an already-published document should be rejected.');
        } catch (InvalidKmTransitionException $exception) {
            $this->assertSame(KmDocumentStatus::PUBLISHED, $exception->from);
            $this->assertSame(KmDocumentStatus::PUBLISHED, $exception->to);
        }

        $published->refresh();
        $this->assertStatus($published, KmDocumentStatus::PUBLISHED, 2);
        $this->assertSame('Judul Asli', $published->judul);

        $pending = KmPengajuan::factory()->pending()->create([
            'id_user' => $owner->getKey(),
            'id_km_kategori' => $category->getKey(),
        ]);
        try {
            $this->approvalService()->deactivate($pending, $approver);
            $this->fail('Deactivating a pending document should be rejected.');
        } catch (InvalidKmTransitionException $exception) {
            $this->assertSame(KmDocumentStatus::PENDING_APPROVAL, $exception->from);
            $this->assertSame(KmDocumentStatus::INACTIVE, $exception->to);
        }

        $this->assertStatus($pending->refresh(), KmDocumentStatus::PENDING_APPROVAL, 1);
        $this->assertSame(0, KmApprovalEvent::query()->count());
    }

    public function test_approval_events_cannot_be_updated_or_deleted_through_the_model(): void
    {
        $approver = $this->createApprover();
        $owner = $this->createUser('KM Document Owner');
        $category = KmKategori::factory()->create();
        $document = KmPengajuan::factory()->pending()->create([
            'id_user' => $owner->getKey(),
            'id_km_kategori' => $category->getKey(),
        ]);
        $event = KmApprovalEvent::factory()->create([
            'km_pengajuan_id' => $document->getKey(),
            'actor_id' => $approver->getKey(),
            'actor_name' => $approver->name,
            'reason' => 'Nilai awal',
        ]);

        $updateBlocked = false;
        try {
            $event->update(['reason' => 'Diubah secara ilegal']);
        } catch (LogicException $exception) {
            $updateBlocked = true;
            $this->assertStringContainsString('append-only', $exception->getMessage());
        }
        $this->assertTrue($updateBlocked);
        $this->assertSame('Nilai awal', KmApprovalEvent::query()->findOrFail($event->getKey())->reason);

        $deleteBlocked = false;
        try {
            KmApprovalEvent::query()->findOrFail($event->getKey())->delete();
        } catch (LogicException $exception) {
            $deleteBlocked = true;
            $this->assertStringContainsString('tidak dapat dihapus', $exception->getMessage());
        }
        $this->assertTrue($deleteBlocked);
        $this->assertSame(1, KmApprovalEvent::query()->count());
    }

    public function test_event_insert_failure_rolls_back_document_attributes_and_status(): void
    {
        $this->createApprover();
        $owner = $this->createUser('KM Document Owner');
        $category = KmKategori::factory()->create();
        $document = KmPengajuan::factory()->pending()->create([
            'id_user' => $owner->getKey(),
            'id_km_kategori' => $category->getKey(),
            'judul' => 'Judul Sebelum Transaksi',
        ]);
        $missingActor = new User();
        $missingActor->setAttribute('id', 999999);
        $missingActor->setAttribute('name', 'Missing Actor');
        $missingActor->setAttribute('role_id', null);
        $missingActor->exists = true;

        try {
            $this->approvalService()->approve(
                $document,
                $missingActor,
                $this->attributesFor($document, $category, [
                    'judul' => 'Judul yang Harus Dibatalkan',
                ]),
            );
            $this->fail('The event actor foreign key should reject a missing actor.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString(
                'km_approval_events_actor_foreign',
                strtolower($exception->getMessage()),
            );
        }

        $document->refresh();
        $this->assertStatus($document, KmDocumentStatus::PENDING_APPROVAL, 1);
        $this->assertSame('Judul Sebelum Transaksi', $document->judul);
        $this->assertSame(0, KmApprovalEvent::query()->count());
    }

    public function test_serialized_stale_approver_attempts_commit_only_one_transition_and_event(): void
    {
        $firstApprover = $this->createApprover();
        $secondApprover = $this->grantKmApprovalAccess(
            $this->createUser('Second KM Approver', $firstApprover->role_id),
        );
        $owner = $this->createUser('KM Document Owner');
        $category = KmKategori::factory()->create();
        $document = KmPengajuan::factory()->pending()->create([
            'id_user' => $owner->getKey(),
            'id_km_kategori' => $category->getKey(),
            'judul' => 'Judul Awal',
        ]);
        $firstAttempt = KmPengajuan::query()->findOrFail($document->getKey());
        $staleSecondAttempt = KmPengajuan::query()->findOrFail($document->getKey());

        $this->approvalService()->approve(
            $firstAttempt,
            $firstApprover,
            $this->attributesFor($firstAttempt, $category, ['judul' => 'Persetujuan Pertama']),
            ['request_id' => 'approver-a'],
        );

        try {
            $this->approvalService()->approve(
                $staleSecondAttempt,
                $secondApprover,
                $this->attributesFor($staleSecondAttempt, $category, ['judul' => 'Persetujuan Kedua']),
                ['request_id' => 'approver-b'],
            );
            $this->fail('A stale second approval should fail after the first approval commits.');
        } catch (InvalidKmTransitionException $exception) {
            $this->assertSame(KmDocumentStatus::PUBLISHED, $exception->from);
        }

        $document->refresh();
        $this->assertStatus($document, KmDocumentStatus::PUBLISHED, 2);
        $this->assertSame('Persetujuan Pertama', $document->judul);
        $this->assertSame(1, KmApprovalEvent::query()->count());
        $event = KmApprovalEvent::query()->sole();
        $this->assertSame($firstApprover->getKey(), $event->actor_id);
        $this->assertSame('approver-a', $event->metadata['request_id']);
    }

    public function test_two_parallel_approvers_commit_only_one_transition_and_event(): void
    {
        $firstApprover = $this->createApprover();
        $secondApprover = $this->grantKmApprovalAccess(
            $this->createUser('Second KM Approver', $firstApprover->role_id),
        );
        $owner = $this->createUser('KM Parallel Owner');
        $category = KmKategori::factory()->create();
        $document = KmPengajuan::factory()->pending()->create([
            'id_user' => $owner->getKey(),
            'id_km_kategori' => $category->getKey(),
            'judul' => 'Parallel Approval',
        ]);

        $results = $this->runKmWorkers([
            [
                'approve',
                (string) $document->getKey(),
                (string) $firstApprover->getKey(),
                (string) $category->getKey(),
                'parallel-a',
            ],
            [
                'approve',
                (string) $document->getKey(),
                (string) $secondApprover->getKey(),
                (string) $category->getKey(),
                'parallel-b',
            ],
        ]);

        $this->assertEqualsCanonicalizing(
            ['approved', 'invalid_transition'],
            array_column($results, 'outcome'),
        );
        $document->refresh();
        $this->assertStatus($document, KmDocumentStatus::PUBLISHED, 2);
        $this->assertSame(1, KmApprovalEvent::query()->count());
        $event = KmApprovalEvent::query()->sole();
        $this->assertContains($event->metadata['request_id'], ['parallel-a', 'parallel-b']);
        $this->assertSame('Approved by '.$event->metadata['request_id'], $document->judul);
    }

    private function createApprover(): User
    {
        $role = Role::query()->create(['role' => 'KM APPROVER']);
        $approver = $this->createUser('KM Test Approver', $role->getKey());
        $approver->update(['is_active' => false]);

        return $this->grantKmApprovalAccess(
            $approver,
        );
    }

    private function createUser(string $name, ?int $roleId = null): User
    {
        return User::factory()->create([
            'name' => $name,
            'role_id' => $roleId,
            'km_total_poin' => 0,
        ]);
    }

    private function draftDocument(User $owner, KmKategori $category): KmPengajuan
    {
        $document = KmPengajuan::factory()->draft()->create([
            'id_user' => $owner->getKey(),
            'id_km_kategori' => $category->getKey(),
        ]);
        $this->attachReadyDraftVersion($document, $owner);

        return $document->refresh();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function attributesFor(
        KmPengajuan $document,
        KmKategori $category,
        array $overrides = [],
    ): array {
        return [
            'posisi' => $document->posisi ?? 'All Employee',
            'id_km_kategori' => $category->getKey(),
            'judul' => $document->judul ?? 'Judul KM',
            'keterangan' => $document->keterangan,
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function approvalPayload(
        KmPengajuan $document,
        KmKategori $category,
        array $overrides = [],
    ): array {
        return [
            'id' => $document->getKey(),
            ...$this->attributesFor($document, $category),
            ...$overrides,
        ];
    }

    private function approvalService(): KmApprovalService
    {
        return app(KmApprovalService::class);
    }

    private function assertStatus(
        KmPengajuan $document,
        KmDocumentStatus $expected,
        int $legacyApproval,
    ): void {
        $this->assertSame($expected, $document->documentStatus());
        $this->assertSame($legacyApproval, (int) $document->persetujuan);
    }
}
