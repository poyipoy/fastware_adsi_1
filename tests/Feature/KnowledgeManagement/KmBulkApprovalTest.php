<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmApprovalAction;
use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Models\KmApprovalEvent;
use App\Models\KmKategori;
use App\Models\KmPengajuan;
use App\Models\Role;
use App\Models\User;
use App\Services\KnowledgeManagement\KmApprovalService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\KnowledgeManagement\RunsKmWorkers;

final class KmBulkApprovalTest extends KmTestCase
{
    use RunsKmWorkers;

    public function test_bulk_approve_is_atomic_and_assigns_each_item_category_with_one_event_each(): void
    {
        $approver = $this->createApprover();
        $owner = $this->createUser('Pemilik Batch');
        $firstCategory = KmKategori::factory()->create(['nama_kategori' => 'Kategori Pertama']);
        $secondCategory = KmKategori::factory()->create(['nama_kategori' => 'Kategori Kedua']);
        [$first, $second] = $this->pendingDocuments(2, $owner)->all();

        $response = $this->actingAs($approver)
            ->withHeader('X-Request-ID', 'bulk-approve-001')
            ->postJson(route('km.approvals.bulk'), [
                'action' => 'approve',
                'items' => [
                    ['document_id' => $second->getKey(), 'id_km_kategori' => $secondCategory->getKey()],
                    ['document_id' => $first->getKey(), 'id_km_kategori' => $firstCategory->getKey()],
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'processed_count' => 2,
                'action' => KmApprovalAction::APPROVED->value,
            ]);
        $this->assertSame(KmDocumentStatus::PUBLISHED, $first->refresh()->documentStatus());
        $this->assertSame($firstCategory->getKey(), $first->id_km_kategori);
        $this->assertSame(KmDocumentStatus::PUBLISHED, $second->refresh()->documentStatus());
        $this->assertSame($secondCategory->getKey(), $second->id_km_kategori);

        $events = KmApprovalEvent::query()->orderBy('km_pengajuan_id')->get();
        $this->assertCount(2, $events);
        $this->assertSame(
            [$first->getKey(), $second->getKey()],
            $events->pluck('km_pengajuan_id')->all(),
        );
        $this->assertSame(
            [KmApprovalAction::APPROVED->value, KmApprovalAction::APPROVED->value],
            $events->map(fn (KmApprovalEvent $event): string => $event->action->value)->all(),
        );
        $this->assertSame(
            ['bulk-approve-001'],
            $events->pluck('metadata.request_id')->unique()->values()->all(),
        );
        $this->assertSame(
            [KmDocumentStatus::PENDING_APPROVAL->value],
            $events->pluck('from_status')->unique()->values()->all(),
        );
        $this->assertSame(
            [KmDocumentStatus::PUBLISHED->value],
            $events->pluck('to_status')->unique()->values()->all(),
        );
    }

    public function test_bulk_reject_ignores_categories_and_requires_trimmed_reason(): void
    {
        $approver = $this->createApprover();
        $owner = $this->createUser('Pemilik Reject');
        $originalCategory = KmKategori::factory()->create();
        $documents = $this->pendingDocuments(2, $owner, $originalCategory);

        $this->actingAs($approver)
            ->withHeader('X-Request-ID', 'bulk-reject-001')
            ->postJson(route('km.approvals.bulk'), [
                'action' => 'reject',
                'reason' => '  Referensi belum lengkap.  ',
                'items' => $documents->map(fn (KmPengajuan $document): array => [
                    'document_id' => $document->getKey(),
                    'id_km_kategori' => 999999,
                ])->all(),
            ])
            ->assertOk()
            ->assertJson([
                'processed_count' => 2,
                'action' => KmApprovalAction::REJECTED->value,
            ]);

        foreach ($documents as $document) {
            $document->refresh();
            $this->assertSame(KmDocumentStatus::DRAFT, $document->documentStatus());
            $this->assertSame($originalCategory->getKey(), $document->id_km_kategori);
        }
        $this->assertSame(
            ['Referensi belum lengkap.'],
            KmApprovalEvent::query()->pluck('reason')->unique()->values()->all(),
        );
    }

    public function test_reject_rejects_whitespace_or_overlong_reason_without_mutation(): void
    {
        $approver = $this->createApprover();
        $document = $this->pendingDocuments(1, $this->createUser('Pemilik Reason'))->sole();

        foreach ([" \t\n ", str_repeat('x', 2001)] as $reason) {
            $this->actingAs($approver)
                ->postJson(route('km.approvals.bulk'), [
                    'action' => 'reject',
                    'reason' => $reason,
                    'items' => [['document_id' => $document->getKey()]],
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('reason');
        }

        $this->assertSame(KmDocumentStatus::PENDING_APPROVAL, $document->refresh()->documentStatus());
        $this->assertSame(0, KmApprovalEvent::query()->count());
    }

    public function test_duplicate_and_more_than_one_hundred_items_are_rejected(): void
    {
        $approver = $this->createApprover();
        $category = KmKategori::factory()->create();
        $document = $this->pendingDocuments(1, $this->createUser('Pemilik Limit'))->sole();
        $item = [
            'document_id' => $document->getKey(),
            'id_km_kategori' => $category->getKey(),
        ];

        $this->actingAs($approver)
            ->postJson(route('km.approvals.bulk'), [
                'action' => 'approve',
                'items' => [$item, $item],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.1.document_id');

        $this->actingAs($approver)
            ->postJson(route('km.approvals.bulk'), [
                'action' => 'approve',
                'items' => array_fill(0, 101, $item),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertSame(KmDocumentStatus::PENDING_APPROVAL, $document->refresh()->documentStatus());
        $this->assertSame(0, KmApprovalEvent::query()->count());
    }

    public function test_unauthorized_user_receives_forbidden_and_batch_is_unchanged(): void
    {
        $owner = $this->createUser('Pemilik Forbidden');
        $outsider = $this->createUser('Bukan Approver');
        $category = KmKategori::factory()->create();
        $document = $this->pendingDocuments(1, $owner)->sole();

        $this->actingAs($outsider)
            ->postJson(route('km.approvals.bulk'), [
                'action' => 'approve',
                'items' => [[
                    'document_id' => $document->getKey(),
                    'id_km_kategori' => $category->getKey(),
                ]],
            ])
            ->assertForbidden();

        $this->assertSame(KmDocumentStatus::PENDING_APPROVAL, $document->refresh()->documentStatus());
        $this->assertSame(0, KmApprovalEvent::query()->count());
    }

    public function test_missing_document_id_returns_validation_error_without_partial_update(): void
    {
        $approver = $this->createApprover();
        $category = KmKategori::factory()->create();
        $document = $this->pendingDocuments(1, $this->createUser('Pemilik Missing'))->sole();

        $this->actingAs($approver)
            ->postJson(route('km.approvals.bulk'), [
                'action' => 'approved',
                'items' => [
                    [
                        'document_id' => $document->getKey(),
                        'id_km_kategori' => $category->getKey(),
                    ],
                    [
                        'document_id' => 999999,
                        'id_km_kategori' => $category->getKey(),
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.1.document_id');

        $this->assertSame(KmDocumentStatus::PENDING_APPROVAL, $document->refresh()->documentStatus());
        $this->assertSame(0, KmApprovalEvent::query()->count());
    }

    public function test_mixed_or_stale_status_rolls_back_every_row_and_event(): void
    {
        $approver = $this->createApprover();
        $owner = $this->createUser('Pemilik Mixed');
        $category = KmKategori::factory()->create();
        $pending = $this->pendingDocuments(1, $owner)->sole();
        $published = KmPengajuan::factory()->published()->create([
            'id_user' => $owner->getKey(),
            'id_km_kategori' => $category->getKey(),
        ]);

        $this->actingAs($approver)
            ->postJson(route('km.approvals.bulk'), [
                'action' => 'approve',
                'items' => [
                    ['document_id' => $pending->getKey(), 'id_km_kategori' => $category->getKey()],
                    ['document_id' => $published->getKey(), 'id_km_kategori' => $category->getKey()],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertSame(KmDocumentStatus::PENDING_APPROVAL, $pending->refresh()->documentStatus());
        $this->assertSame(KmDocumentStatus::PUBLISHED, $published->refresh()->documentStatus());
        $this->assertSame(0, KmApprovalEvent::query()->count());
    }

    public function test_failure_while_writing_second_event_rolls_back_entire_batch(): void
    {
        $approver = $this->createApprover();
        $owner = $this->createUser('Pemilik Event Failure');
        $category = KmKategori::factory()->create();
        $documents = $this->pendingDocuments(2, $owner)->sortBy('id')->values();
        $secondId = $documents[1]->getKey();

        DB::unprepared(
            'CREATE TRIGGER km_test_fail_second_bulk_event BEFORE INSERT ON km_approval_events '
            .'FOR EACH ROW BEGIN '
            ."IF NEW.km_pengajuan_id = {$secondId} THEN "
            ."SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'forced second event failure'; "
            .'END IF; END'
        );

        try {
            app(KmApprovalService::class)->bulkAct(
                $approver,
                $documents->map(fn (KmPengajuan $document): array => [
                    'document_id' => $document->getKey(),
                    'id_km_kategori' => $category->getKey(),
                ])->all(),
                KmApprovalAction::APPROVED,
                null,
                ['request_id' => 'event-failure'],
            );
            $this->fail('Event kedua seharusnya menggagalkan seluruh transaksi.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('forced second event failure', $exception->getMessage());
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS km_test_fail_second_bulk_event');
        }

        foreach ($documents as $document) {
            $this->assertSame(KmDocumentStatus::PENDING_APPROVAL, $document->refresh()->documentStatus());
            $this->assertNull($document->id_km_kategori);
        }
        $this->assertSame(0, KmApprovalEvent::query()->count());
    }

    public function test_overlapping_parallel_batches_commit_only_one_complete_batch(): void
    {
        $firstApprover = $this->createApprover();
        $secondApprover = $this->createUser('YULMAI RIDO WINANDA', $firstApprover->role_id);
        $owner = $this->createUser('Pemilik Paralel');
        $category = KmKategori::factory()->create();
        $documents = $this->pendingDocuments(3, $owner)->sortBy('id')->values();

        $results = $this->runKmWorkers([
            [
                'bulk_approve',
                $documents[0]->getKey().','.$documents[1]->getKey(),
                (string) $firstApprover->getKey(),
                (string) $category->getKey(),
                'parallel-batch-a',
            ],
            [
                'bulk_approve',
                $documents[1]->getKey().','.$documents[2]->getKey(),
                (string) $secondApprover->getKey(),
                (string) $category->getKey(),
                'parallel-batch-b',
            ],
        ]);

        $this->assertEqualsCanonicalizing(
            ['approved', 'rejected'],
            array_column($results, 'outcome'),
        );
        $this->assertSame(KmDocumentStatus::PUBLISHED, $documents[1]->refresh()->documentStatus());
        $this->assertSame(
            2,
            KmPengajuan::query()->where('status', KmDocumentStatus::PUBLISHED->value)->count(),
        );
        $this->assertSame(
            1,
            KmPengajuan::query()->where('status', KmDocumentStatus::PENDING_APPROVAL->value)->count(),
        );

        $events = KmApprovalEvent::query()->get();
        $this->assertCount(2, $events);
        $this->assertCount(1, $events->pluck('metadata.request_id')->unique());
    }

    public function test_successful_web_request_redirects_with_processed_count_and_alias_is_supported(): void
    {
        $approver = $this->createApprover();
        $category = KmKategori::factory()->create();
        $document = $this->pendingDocuments(1, $this->createUser('Pemilik Web'))->sole();

        $this->actingAs($approver)
            ->from('/persetujuanKM')
            ->post(route('km.approvals.bulk'), [
                'action' => 'approved',
                'items' => [[
                    'document_id' => $document->getKey(),
                    'id_km_kategori' => $category->getKey(),
                ]],
            ])
            ->assertRedirect('/persetujuanKM')
            ->assertSessionHas('success', '1 dokumen berhasil diproses secara atomik.');
    }

    private function createApprover(): User
    {
        $role = Role::query()->create(['role' => 'KM APPROVER']);

        return $this->createUser('MUGI PRAMONO', $role->getKey());
    }

    private function createUser(string $name, ?int $roleId = null): User
    {
        return User::factory()->create([
            'name' => $name,
            'role_id' => $roleId,
            'km_total_poin' => 0,
            'is_active' => true,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, KmPengajuan>
     */
    private function pendingDocuments(
        int $count,
        User $owner,
        ?KmKategori $category = null,
    ): \Illuminate\Database\Eloquent\Collection {
        return KmPengajuan::factory()
            ->count($count)
            ->pending()
            ->create([
                'id_user' => $owner->getKey(),
                'id_km_kategori' => $category?->getKey(),
                'posisi' => 'All Employee',
            ]);
    }
}
