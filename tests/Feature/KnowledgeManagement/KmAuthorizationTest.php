<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Models\KmPengajuan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

final class KmAuthorizationTest extends KmTestCase
{
    public function test_legacy_and_private_document_route_contracts_remain_available_and_authenticated(): void
    {
        $contracts = [
            'pengajuanKM' => ['GET', 'km'],
            'dsKnowlege' => ['GET', 'dsKnowlege'],
            'persetujuanKM' => ['GET', 'persetujuanKM'],
            'km.documents.preview' => ['GET', 'km/documents/{kmPengajuan}/preview'],
            'km.documents.download' => ['GET', 'km/documents/{kmPengajuan}/download'],
            'kmTransaksi.markAsRead' => ['POST', 'kmTransaksi/markAsRead'],
            'kmTransaksi.saveTransaction' => ['POST', 'kmTransaksi/saveTransaction'],
            'storeKM' => ['POST', 'km'],
            'updateKM' => ['PUT', 'knowledge-management/update'],
            'editKM' => ['GET', 'km/{id}/edit'],
            'showPersetujuan' => ['GET', 'km/{id}/showPersetujuan'],
            'approveKM' => ['PUT', 'knowledge-management/approveKM'],
            'updateStatusKM' => ['PATCH', 'km/{id}/update-status'],
            'kirimKM' => ['POST', 'kirimKM/{id}'],
            'kmSuka.like' => ['POST', 'like'],
            'kmSuka.unlike' => ['POST', 'unlike'],
            'insights.add' => ['POST', 'insights/add'],
        ];

        foreach ($contracts as $name => [$method, $uri]) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Route {$name} must remain available.");
            $this->assertSame($uri, $route->uri(), "Route {$name} changed URI.");
            $this->assertContains($method, $route->methods(), "Route {$name} changed HTTP method.");
            $this->assertContains('auth', $route->gatherMiddleware(), "Route {$name} must use auth middleware.");
        }
    }

    public function test_guest_is_redirected_for_web_page_and_receives_401_for_json_document_access(): void
    {
        $this->get(route('pengajuanKM'))->assertRedirect(route('login'));
        $this->getJson(route('km.documents.preview', 999999))->assertUnauthorized();
        $this->postJson(route('kmSuka.like'), ['id_km_pengajuan' => 999999])->assertUnauthorized();
    }

    public function test_owner_can_edit_and_inspect_own_draft_without_private_metadata_in_json(): void
    {
        $owner = $this->user(1101, 'Document Owner');
        $document = KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'file' => 'legacy-safe-name.pdf',
            'file_name' => 'Pedoman.pdf',
            'file_disk' => 'km_private',
            'file_path' => 'documents/1/11111111-1111-1111-1111-111111111111.pdf',
            'file_original_name' => 'Pedoman.pdf',
            'file_mime_type' => 'application/pdf',
            'file_size_bytes' => 123,
            'file_checksum_sha256' => str_repeat('a', 64),
            'file_migrated_at' => now(),
        ]);

        $edit = $this->actingAs($owner)->getJson(route('editKM', $document));
        $edit->assertOk()
            ->assertJsonPath('id', $document->getKey())
            ->assertJsonPath('has_file', true)
            ->assertJsonPath('preview_url', route('km.documents.preview', $document))
            ->assertJsonPath('download_url', route('km.documents.download', $document));
        $this->assertSafeDocumentPayload($edit->json());

        $show = $this->actingAs($owner)->getJson(route('showPersetujuan', $document));
        $show->assertOk()
            ->assertJsonPath('km.id', $document->getKey())
            ->assertJsonPath('km.can_approve', false)
            ->assertJsonPath('km.can_reject', false);
        $this->assertSafeDocumentPayload($show->json('km'));
    }

    public function test_owner_cannot_edit_a_non_draft_document(): void
    {
        $owner = $this->user(1102, 'Published Owner');
        $document = KmPengajuan::factory()->published()->for($owner, 'user')->create();

        $this->actingAs($owner)
            ->getJson(route('editKM', $document))
            ->assertForbidden();

        $this->actingAs($owner)
            ->putJson(route('updateKM'), [
                'id' => $document->getKey(),
                'judul' => 'Perubahan terlarang',
                'keterangan' => 'Dokumen sudah diterbitkan.',
            ])
            ->assertForbidden();
    }

    public function test_other_employee_cannot_access_or_mutate_an_owners_draft_by_changing_object_id(): void
    {
        $owner = $this->user(1103, 'Draft Owner');
        $intruder = $this->user(1104, 'Other Employee');
        $document = KmPengajuan::factory()->draft()->for($owner, 'user')->create();
        $categoryId = $document->id_km_kategori;

        $requests = [
            'editKM' => fn () => $this->getJson(route('editKM', $document)),
            'updateKM' => fn () => $this->putJson(route('updateKM'), [
                'id' => $document->getKey(),
                'judul' => 'Mengganti judul',
                'keterangan' => 'Tidak diizinkan.',
            ]),
            'showPersetujuan' => fn () => $this->getJson(route('showPersetujuan', $document)),
            'approveKM' => fn () => $this->putJson(route('approveKM'), [
                'id' => $document->getKey(),
                'action' => 'approved',
                'posisi' => 'All Employee',
                'id_km_kategori' => $categoryId,
                'judul' => $document->judul,
                'keterangan' => $document->keterangan,
            ]),
            'updateStatusKM' => fn () => $this->patchJson(route('updateStatusKM', $document)),
            'kirimKM' => fn () => $this->postJson(route('kirimKM', $document)),
            'markAsRead' => fn () => $this->postJson(route('kmTransaksi.markAsRead'), [
                'id_km_pengajuan' => $document->getKey(),
            ]),
            'saveTransaction' => fn () => $this->postJson(route('kmTransaksi.saveTransaction'), [
                'id_km_pengajuan' => $document->getKey(),
            ]),
            'like' => fn () => $this->postJson(route('kmSuka.like'), [
                'id_km_pengajuan' => $document->getKey(),
            ]),
            'unlike' => fn () => $this->postJson(route('kmSuka.unlike'), [
                'id_km_pengajuan' => $document->getKey(),
            ]),
            'insight' => fn () => $this->postJson(route('insights.add'), [
                'id_km_pengajuan' => $document->getKey(),
                'content' => 'Tidak boleh tersimpan.',
            ]),
            'preview' => fn () => $this->getJson(route('km.documents.preview', $document)),
            'download' => fn () => $this->getJson(route('km.documents.download', $document)),
        ];

        $this->actingAs($intruder);
        foreach ($requests as $endpoint => $request) {
            $response = $request();
            $this->assertSame(403, $response->getStatusCode(), "{$endpoint} did not enforce object access.");
        }

        $this->assertDatabaseMissing('km_insights', ['content' => 'Tidak boleh tersimpan.']);
        $this->assertDatabaseCount('km_sukas', 0);
        $this->assertDatabaseCount('km_transaksis', 0);
        $this->assertSame('Draft Owner', DB::table('users')->where('id', $owner->id)->value('name'));
    }

    public function test_approver_can_open_pending_document_but_regular_employee_cannot(): void
    {
        $owner = $this->user(1105, 'Pending Owner');
        $approver = $this->user(1106, 'MUGI PRAMONO');
        $employee = $this->user(1107, 'Regular Employee');
        $document = KmPengajuan::factory()->pending()->for($owner, 'user')->create();

        $this->actingAs($approver)->get(route('persetujuanKM'))->assertOk();
        $this->actingAs($approver)
            ->getJson(route('showPersetujuan', $document))
            ->assertOk()
            ->assertJsonPath('km.can_approve', true)
            ->assertJsonPath('km.can_reject', true);

        $this->actingAs($employee)->get(route('persetujuanKM'))->assertForbidden();
        $this->actingAs($employee)
            ->getJson(route('showPersetujuan', $document))
            ->assertForbidden();
    }

    public function test_employee_can_view_only_published_documents_matching_legacy_position_rules(): void
    {
        $owner = $this->user(1108, 'Published Document Owner');
        $employee = $this->user(1109, 'Employee Viewer', 4);
        $eligible = KmPengajuan::factory()->published()->for($owner, 'user')->create([
            'posisi' => 'All Employee',
        ]);
        $ineligible = KmPengajuan::factory()->published()->for($owner, 'user')->create([
            'posisi' => 'Dept. Head',
        ]);

        $this->actingAs($employee)
            ->getJson(route('showPersetujuan', $eligible))
            ->assertOk()
            ->assertJsonPath('km.can_approve', false);

        $this->actingAs($employee)
            ->getJson(route('showPersetujuan', $ineligible))
            ->assertForbidden();
    }

    public function test_published_visibility_role_does_not_gain_workflow_access_to_other_users_draft(): void
    {
        $owner = $this->user(1110, 'Workflow Owner');
        $publishedViewer = $this->user(1111, 'Published Visibility User', 15);
        $draft = KmPengajuan::factory()->draft()->for($owner, 'user')->create();
        $restrictedPublished = KmPengajuan::factory()->published()->for($owner, 'user')->create([
            'posisi' => 'Dept. Head',
        ]);

        $this->actingAs($publishedViewer)
            ->getJson(route('showPersetujuan', $restrictedPublished))
            ->assertOk();

        $this->actingAs($publishedViewer)
            ->getJson(route('showPersetujuan', $draft))
            ->assertForbidden();
        $this->actingAs($publishedViewer)
            ->getJson(route('editKM', $draft))
            ->assertForbidden();
        $this->actingAs($publishedViewer)
            ->postJson(route('kirimKM', $draft))
            ->assertForbidden();
        $this->actingAs($publishedViewer)
            ->patchJson(route('updateStatusKM', $draft))
            ->assertForbidden();
    }

    private function user(int $id, string $name, ?int $roleId = 4): User
    {
        return User::factory()->create([
            'id' => $id,
            'name' => $name,
            'role_id' => $roleId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertSafeDocumentPayload(array $payload): void
    {
        foreach ([
            'file_disk',
            'file_path',
            'file_mime_type',
            'file_size_bytes',
            'file_checksum_sha256',
            'file_migrated_at',
        ] as $privateKey) {
            $this->assertArrayNotHasKey($privateKey, $payload, "Private metadata {$privateKey} leaked in JSON.");
        }

        $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('11111111-1111-1111-1111-111111111111', $encoded);
        $this->assertStringNotContainsString(str_repeat('a', 64), $encoded);
    }
}
