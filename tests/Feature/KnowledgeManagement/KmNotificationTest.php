<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Models\KmNotification;
use App\Models\KmPengajuan;
use App\Models\User;
use App\Services\KnowledgeManagement\KmNotificationService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class KmNotificationTest extends KmTestCase
{
    public function test_notification_endpoints_are_private_and_user_scoped(): void
    {
        $this->getJson(route('km.notifications.index'))->assertUnauthorized();

        $administrator = $this->administrator();
        $other = $this->regularUser('Other Reader');
        $service = app(KmNotificationService::class);
        $service->record($administrator, 'document_approved', 'approved:1', [
            'document_id' => 1,
            'title' => 'Materi Administrator',
        ]);
        $service->record($other, 'document_rejected', 'rejected:2', [
            'document_id' => 2,
            'title' => 'Materi Pengguna Lain',
        ]);

        $response = $this->actingAs($administrator)->getJson(route('km.notifications.index'));
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.data.title', 'Materi Administrator')
            ->assertJsonPath('unread_count', 1);

        $this->getJson(route('km.notifications.index', ['per_page' => 51]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
        $this->getJson(route('km.notifications.index', ['status' => 'unread']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('query');

        $otherNotification = KmNotification::query()->where('user_id', $other->getKey())->sole();
        $this->postJson(route('km.notifications.read', $otherNotification))->assertNotFound();
        $this->assertNull($otherNotification->refresh()->read_at);

        $this->postJson(route('km.notifications.read-all'))->assertOk();
        $this->assertNull($otherNotification->refresh()->read_at);
    }

    public function test_notification_event_key_and_read_mutations_are_idempotent(): void
    {
        $user = $this->administrator();
        $service = app(KmNotificationService::class);

        foreach ([1, 2] as $attempt) {
            $service->record($user, 'approval_reminder', 'reminder:document:10', [
                'document_id' => 10,
                'title' => 'Materi Idempoten '.$attempt,
            ]);
        }

        $this->assertSame(1, KmNotification::query()->count());
        $notification = KmNotification::query()->sole();

        foreach ([1, 2] as $attempt) {
            $this->actingAs($user)
                ->postJson(route('km.notifications.read', $notification))
                ->assertOk()
                ->assertJsonPath('read', true);
        }
        $this->assertNotNull($notification->refresh()->read_at);

        $this->postJson(route('km.notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }

    public function test_submitting_document_notifies_only_the_eligible_approver_set(): void
    {
        $approver = $this->grantKmApprovalAccess(
            $this->regularUser('HRGA Legal Notification Approver', false),
        );
        $owner = $this->regularUser('Document Owner');
        $unrelatedHead = $this->regularUser('Unrelated Head');
        $document = KmPengajuan::factory()->draft()->create([
            'id_user' => $owner->getKey(),
            'posisi' => 'All Employee',
        ]);
        $this->attachReadyDraftVersion($document, $owner);

        $this->actingAs($owner)
            ->postJson(route('kirimKM', $document))
            ->assertOk();

        $this->assertDatabaseHas('km_notifications', [
            'user_id' => $approver->getKey(),
            'type' => 'document_submitted',
        ]);
        $this->assertDatabaseMissing('km_notifications', [
            'user_id' => $unrelatedHead->getKey(),
            'type' => 'document_submitted',
        ]);
    }

    public function test_notification_is_persisted_only_after_commit_and_payload_is_allowlisted(): void
    {
        $user = $this->administrator();
        $service = app(KmNotificationService::class);

        try {
            DB::transaction(function () use ($service, $user): void {
                $service->record($user, 'document_approved', 'rolled-back-notification', [
                    'document_id' => 11,
                    'title' => 'Tidak boleh tersimpan',
                ]);
                throw new RuntimeException('Paksa rollback.');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Paksa rollback.', $exception->getMessage());
        }
        $this->assertDatabaseMissing('km_notifications', [
            'event_key' => 'rolled-back-notification',
        ]);

        DB::transaction(function () use ($service, $user): void {
            $service->record($user, 'document_approved', 'committed-notification', [
                'document_id' => 12,
                'title' => 'Materi aman',
                'file_path' => 'documents/12/private.pdf',
                'active_seconds' => 999,
            ]);
        });

        $notification = KmNotification::query()->where('event_key', 'committed-notification')->sole();
        $this->assertEquals([
            'document_id' => 12,
            'title' => 'Materi aman',
        ], $notification->data);
    }

    private function administrator(): User
    {
        return User::factory()->create([
            'name' => 'ADMINSTRATOR',
            'role_id' => null,
            'is_active' => true,
        ]);
    }

    private function regularUser(string $name, bool $isActive = true): User
    {
        return User::factory()->create([
            'name' => $name,
            'role_id' => 99,
            'is_active' => $isActive,
        ]);
    }
}
