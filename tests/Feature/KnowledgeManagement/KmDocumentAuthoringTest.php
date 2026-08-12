<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Models\KmPengajuan;
use App\Models\User;
use App\Services\KnowledgeManagement\KmDocumentAuthoringService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KmDocumentAuthoringTest extends KmTestCase
{
    private User $owner;

    private User $coAuthorCandidate;

    private KmPengajuan $draft;

    protected function setUp(): void
    {
        parent::setUp();

        $roleId = DB::table('roles')->insertGetId(['role' => 'Employee', 'created_at' => now(), 'updated_at' => now()]);

        $this->owner = User::factory()->create([
            'role_id' => $roleId,
            'is_active' => false,
            'km_total_poin' => 0,
        ]);

        $this->coAuthorCandidate = User::factory()->create([
            'role_id' => $roleId,
            'is_active' => false,
            'km_total_poin' => 0,
        ]);

        $this->draft = KmPengajuan::factory()->create([
            'id_user' => $this->owner->getKey(),
            'status' => KmDocumentStatus::DRAFT->value,
            'posisi' => 'All Employee',
            'draft_revision' => 0,
        ]);
    }

    /** @test */
    public function unauthenticated_cannot_autosave(): void
    {
        $response = $this->patchJson(route('km.documents.autosave', $this->draft), [
            'revision' => 0,
        ]);
        $response->assertUnauthorized();
    }

    /** @test */
    public function non_owner_cannot_autosave_draft(): void
    {
        $response = $this->actingAs($this->coAuthorCandidate)
            ->patchJson(route('km.documents.autosave', $this->draft), [
                'revision' => 0,
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function owner_can_autosave_draft_metadata(): void
    {
        $response = $this->actingAs($this->owner)
            ->patchJson(route('km.documents.autosave', $this->draft), [
                'judul' => 'Judul Baru Terautosave',
                'keterangan' => 'Keterangan baru',
                'reading_minutes' => 25,
                'revision' => 0,
            ]);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'draft_revision', 'autosaved_at']);

        $this->assertDatabaseHas('km_pengajuans', [
            'id' => $this->draft->getKey(),
            'judul' => 'Judul Baru Terautosave',
            'reading_minutes' => 25,
        ]);
    }

    /** @test */
    public function autosave_increments_draft_revision(): void
    {
        $response = $this->actingAs($this->owner)
            ->patchJson(route('km.documents.autosave', $this->draft), [
                'revision' => 0,
            ]);

        $response->assertOk();
        $this->assertEquals(1, $response->json('draft_revision'));

        // Save kedua dengan revision yang benar
        $response2 = $this->actingAs($this->owner)
            ->patchJson(route('km.documents.autosave', $this->draft), [
                'revision' => 1,
            ]);

        $response2->assertOk();
        $this->assertEquals(2, $response2->json('draft_revision'));
    }

    /** @test */
    public function stale_revision_returns_409_conflict(): void
    {
        // Simpan sekali dulu agar revision menjadi 1
        $this->actingAs($this->owner)
            ->patchJson(route('km.documents.autosave', $this->draft), ['revision' => 0]);

        // Kirim lagi dengan revision lama (0) → harus 409
        $response = $this->actingAs($this->owner)
            ->patchJson(route('km.documents.autosave', $this->draft), [
                'revision' => 0,
            ]);

        $response->assertStatus(409);
        $response->assertJsonStructure(['message', 'draft_revision']);
    }

    /** @test */
    public function autosave_syncs_tags(): void
    {
        $response = $this->actingAs($this->owner)
            ->patchJson(route('km.documents.autosave', $this->draft), [
                'tags' => ['laravel', 'php', 'programming'],
                'revision' => 0,
            ]);

        $response->assertOk();
        $this->assertDatabaseCount('km_tags', 3);
        $this->assertCount(3, $this->draft->fresh()->tags);
    }

    /** @test */
    public function autosave_syncs_co_authors(): void
    {
        $response = $this->actingAs($this->owner)
            ->patchJson(route('km.documents.autosave', $this->draft), [
                'co_author_ids' => [$this->coAuthorCandidate->getKey()],
                'revision' => 0,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('km_document_authors', [
            'km_pengajuan_id' => $this->draft->getKey(),
            'user_id' => $this->coAuthorCandidate->getKey(),
        ]);
    }

    /** @test */
    public function owner_cannot_add_themselves_as_co_author(): void
    {
        $response = $this->actingAs($this->owner)
            ->patchJson(route('km.documents.autosave', $this->draft), [
                'co_author_ids' => [$this->owner->getKey()],
                'revision' => 0,
            ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('co_author_ids.0');
        $this->assertDatabaseMissing('km_document_authors', [
            'km_pengajuan_id' => $this->draft->getKey(),
            'user_id' => $this->owner->getKey(),
        ]);
    }

    /** @test */
    public function autosave_cannot_change_status(): void
    {
        // Pastikan payload berbahaya tidak dapat mengubah status
        $response = $this->actingAs($this->owner)
            ->patchJson(route('km.documents.autosave', $this->draft), [
                'status' => KmDocumentStatus::PUBLISHED->value,
                'revision' => 0,
            ]);

        // Request masih berhasil (field status diabaikan)
        $response->assertOk();

        $this->assertDatabaseHas('km_pengajuans', [
            'id' => $this->draft->getKey(),
            'status' => KmDocumentStatus::DRAFT->value,
        ]);
    }

    /** @test */
    public function autosave_rejected_for_non_draft_document(): void
    {
        $publishedDoc = KmPengajuan::factory()->create([
            'id_user' => $this->owner->getKey(),
            'status' => KmDocumentStatus::PUBLISHED->value,
            'posisi' => 'All Employee',
            'draft_revision' => 0,
        ]);

        $response = $this->actingAs($this->owner)
            ->patchJson(route('km.documents.autosave', $publishedDoc), [
                'revision' => 0,
            ]);

        $response->assertUnprocessable();
    }

    /** @test */
    public function full_access_user_cannot_autosave_another_users_draft(): void
    {
        $admin = User::factory()->create([
            'id' => 91,
            'role_id' => $this->owner->role_id,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->patchJson(route('km.documents.autosave', $this->draft), ['revision' => 0])
            ->assertForbidden();
    }

    /** @test */
    public function inactive_and_duplicate_co_authors_are_rejected(): void
    {
        $inactive = User::factory()->create([
            'role_id' => $this->owner->role_id,
            'is_active' => true,
        ]);

        $this->actingAs($this->owner)
            ->patchJson(route('km.documents.autosave', $this->draft), [
                'co_author_ids' => [$inactive->getKey()],
                'revision' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('co_author_ids.0');

        $this->actingAs($this->owner)
            ->patchJson(route('km.documents.autosave', $this->draft), [
                'co_author_ids' => [
                    $this->coAuthorCandidate->getKey(),
                    $this->coAuthorCandidate->getKey(),
                ],
                'revision' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('co_author_ids.1');
    }

    /** @test */
    public function autosave_can_clear_all_tags_and_co_authors(): void
    {
        $this->actingAs($this->owner)
            ->patchJson(route('km.documents.autosave', $this->draft), [
                'tags' => [' Laravel  Dasar '],
                'co_author_ids' => [$this->coAuthorCandidate->getKey()],
                'revision' => 0,
            ])
            ->assertOk();

        $this->actingAs($this->owner)
            ->patchJson(route('km.documents.autosave', $this->draft), [
                'tags' => [],
                'co_author_ids' => [],
                'revision' => 1,
            ])
            ->assertOk();

        $this->assertDatabaseMissing('km_document_tag', ['km_pengajuan_id' => $this->draft->getKey()]);
        $this->assertDatabaseMissing('km_document_authors', ['km_pengajuan_id' => $this->draft->getKey()]);
        $this->assertDatabaseHas('km_tags', ['name' => 'Laravel Dasar', 'slug' => 'laravel-dasar']);
    }

    /** @test */
    public function autosave_status_race_is_rejected_without_overwrite(): void
    {
        DB::table('km_pengajuans')->where('id', $this->draft->getKey())->update([
            'status' => KmDocumentStatus::PENDING_APPROVAL->value,
        ]);

        try {
            app(KmDocumentAuthoringService::class)->autosave($this->draft, $this->owner, [
                'judul' => 'Tidak boleh tersimpan',
                'revision' => 0,
            ]);
            $this->fail('Autosave seharusnya ditolak setelah status berubah.');
        } catch (ValidationException) {
            $this->assertNotSame('Tidak boleh tersimpan', $this->draft->fresh()->judul);
            $this->assertSame(0, (int) $this->draft->fresh()->draft_revision);
        }
    }

    /** @test */
    public function metadata_transaction_rolls_back_when_co_author_changes_state(): void
    {
        $this->draft->forceFill(['reading_minutes' => 5])->save();

        try {
            app(KmDocumentAuthoringService::class)->synchronizeMetadata($this->draft, [
                'reading_minutes' => 40,
                'tags' => ['Harus Rollback'],
                'co_author_ids' => [999999],
            ]);
            $this->fail('Metadata tidak valid seharusnya melempar validation exception.');
        } catch (ValidationException) {
            $this->assertSame(5, (int) $this->draft->fresh()->reading_minutes);
            $this->assertDatabaseMissing('km_tags', ['slug' => 'harus-rollback']);
        }
    }

    /** @test */
    public function autosave_does_not_change_file_status_approval_or_points(): void
    {
        $this->draft->forceFill([
            'file_path' => 'documents/'.$this->draft->getKey().'/11111111-1111-1111-1111-111111111111.pdf',
            'file_checksum_sha256' => str_repeat('a', 64),
            'persetujuan' => '1',
        ])->save();
        $before = $this->draft->only(['file_path', 'file_checksum_sha256', 'status', 'persetujuan']);
        $points = (int) $this->owner->km_total_poin;

        $this->actingAs($this->owner)
            ->patchJson(route('km.documents.autosave', $this->draft), [
                'judul' => 'Metadata aman',
                'revision' => 0,
            ])
            ->assertOk();

        $this->assertSame($before, $this->draft->fresh()->only(array_keys($before)));
        $this->assertSame($points, (int) $this->owner->fresh()->km_total_poin);
    }

    /** @test */
    public function co_author_options_require_document_update_authorization(): void
    {
        $this->actingAs($this->coAuthorCandidate)
            ->getJson(route('km.co-authors.options', ['document_id' => $this->draft->getKey()]))
            ->assertForbidden();

        $additionalCandidates = User::factory()->count(24)->create([
            'role_id' => $this->owner->role_id,
            'is_active' => false,
            'km_total_poin' => 0,
        ]);
        $blockedCandidate = User::factory()->create([
            'role_id' => $this->owner->role_id,
            'is_active' => true,
            'km_total_poin' => 0,
        ]);

        $allOptions = $this->actingAs($this->owner)
            ->getJson(route('km.co-authors.options', [
                'document_id' => $this->draft->getKey(),
            ]));
        $expectedActiveCount = User::query()
            ->where('is_active', false)
            ->whereKeyNot($this->owner->getKey())
            ->count();
        $allOptions->assertOk()->assertJsonCount($expectedActiveCount, 'data');
        $allIds = collect($allOptions->json('data'))->pluck('id');
        $this->assertTrue($allIds->contains($this->coAuthorCandidate->getKey()));
        $this->assertTrue($allIds->contains($additionalCandidates->last()->getKey()));
        $this->assertFalse($allIds->contains($this->owner->getKey()));
        $this->assertFalse($allIds->contains($blockedCandidate->getKey()));

        $response = $this->actingAs($this->owner)
            ->getJson(route('km.co-authors.options', [
                'document_id' => $this->draft->getKey(),
                'q' => $this->coAuthorCandidate->name,
            ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($this->coAuthorCandidate->getKey()));
        $this->assertFalse($ids->contains($this->owner->getKey()));
    }
}
