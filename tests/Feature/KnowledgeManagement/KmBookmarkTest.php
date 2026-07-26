<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Models\KmBookmark;
use App\Models\KmPengajuan;
use App\Models\User;

class KmBookmarkTest extends KmTestCase
{
    private User $owner;

    private User $viewer;

    private KmPengajuan $publishedDoc;

    protected function setUp(): void
    {
        parent::setUp();

        $roleId = \Illuminate\Support\Facades\DB::table('roles')->insertGetId(['role' => 'Employee', 'created_at' => now(), 'updated_at' => now()]);

        $this->owner = User::factory()->create(['role_id' => $roleId, 'is_active' => true, 'km_total_poin' => 0]);
        $this->viewer = User::factory()->create(['role_id' => $roleId, 'is_active' => true, 'km_total_poin' => 0]);

        $this->publishedDoc = KmPengajuan::factory()->create([
            'id_user' => $this->owner->getKey(),
            'status' => KmDocumentStatus::PUBLISHED->value,
            'posisi' => 'All Employee',
        ]);
    }

    /** @test */
    public function unauthenticated_cannot_bookmark(): void
    {
        $response = $this->postJson(route('km.bookmarks.store', $this->publishedDoc));
        $response->assertUnauthorized();
    }

    /** @test */
    public function user_can_bookmark_published_document(): void
    {
        $response = $this->actingAs($this->viewer)
            ->postJson(route('km.bookmarks.store', $this->publishedDoc));

        $response->assertStatus(201);
        $response->assertJson(['bookmarked' => true]);

        $this->assertDatabaseHas('km_bookmarks', [
            'user_id' => $this->viewer->getKey(),
            'km_pengajuan_id' => $this->publishedDoc->getKey(),
        ]);
    }

    /** @test */
    public function bookmarking_same_document_twice_is_idempotent(): void
    {
        $this->actingAs($this->viewer)
            ->postJson(route('km.bookmarks.store', $this->publishedDoc))
            ->assertStatus(201);

        // Kedua kali harus 200 (sudah ada), bukan 201
        $this->actingAs($this->viewer)
            ->postJson(route('km.bookmarks.store', $this->publishedDoc))
            ->assertStatus(200)
            ->assertJson(['bookmarked' => true]);

        // Hanya 1 row di database
        $this->assertDatabaseCount('km_bookmarks', 1);
    }

    /** @test */
    public function user_can_remove_bookmark(): void
    {
        KmBookmark::create([
            'user_id' => $this->viewer->getKey(),
            'km_pengajuan_id' => $this->publishedDoc->getKey(),
        ]);

        $response = $this->actingAs($this->viewer)
            ->deleteJson(route('km.bookmarks.destroy', $this->publishedDoc));

        $response->assertStatus(204);

        $this->assertDatabaseMissing('km_bookmarks', [
            'user_id' => $this->viewer->getKey(),
            'km_pengajuan_id' => $this->publishedDoc->getKey(),
        ]);
    }

    /** @test */
    public function removing_nonexistent_bookmark_returns_204(): void
    {
        // Tidak ada bookmark — DELETE tetap harus 204 (idempotent)
        $response = $this->actingAs($this->viewer)
            ->deleteJson(route('km.bookmarks.destroy', $this->publishedDoc));

        $response->assertStatus(204);
    }

    /** @test */
    public function user_cannot_bookmark_draft_document_they_do_not_own(): void
    {
        $draftDoc = KmPengajuan::factory()->create([
            'id_user' => $this->owner->getKey(),
            'status' => KmDocumentStatus::DRAFT->value,
            'posisi' => 'All Employee',
        ]);

        $response = $this->actingAs($this->viewer)
            ->postJson(route('km.bookmarks.store', $draftDoc));

        $response->assertForbidden();
    }

    /** @test */
    public function bookmark_is_deleted_when_document_is_deleted(): void
    {
        KmBookmark::create([
            'user_id' => $this->viewer->getKey(),
            'km_pengajuan_id' => $this->publishedDoc->getKey(),
        ]);

        $docId = $this->publishedDoc->getKey();
        $this->publishedDoc->delete();

        $this->assertDatabaseMissing('km_bookmarks', [
            'km_pengajuan_id' => $docId,
        ]);
    }
}
