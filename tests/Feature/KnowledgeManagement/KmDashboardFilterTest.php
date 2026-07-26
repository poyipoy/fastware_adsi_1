<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Models\KmKategori;
use App\Models\KmLihatBuku;
use App\Models\KmPengajuan;
use App\Models\KmTransaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;

class KmDashboardFilterTest extends KmTestCase
{
    use WithFaker;

    private User $regularUser;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = \Illuminate\Support\Facades\DB::table('roles')->insertGetId(['role' => 'Administrator', 'created_at' => now(), 'updated_at' => now()]);
        $userRole = \Illuminate\Support\Facades\DB::table('roles')->insertGetId(['role' => 'Employee', 'created_at' => now(), 'updated_at' => now()]);

        $this->adminUser = User::factory()->create([
            'role_id' => $adminRole,
            'km_total_poin' => 0,
            'is_active' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'role_id' => $userRole,
            'km_total_poin' => 0,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dsKnowlege'));
        $response->assertRedirect();
    }

    /** @test */
    public function authenticated_user_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege'));

        $response->assertOk();
        $response->assertViewIs('dashboard.dsKnowlege');
        $response->assertViewHas('pengajuans');
        $response->assertViewHas('leaderboard');
        $response->assertViewHas('kategoris');
        $response->assertViewHas('filters');
    }

    /** @test */
    public function dashboard_returns_empty_state_when_no_published_documents(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege'));

        $response->assertOk();
        $pengajuans = $response->viewData('pengajuans');
        $this->assertCount(0, $pengajuans);
    }

    /** @test */
    public function dashboard_only_shows_published_documents(): void
    {
        // Draft — tidak boleh muncul
        KmPengajuan::factory()->create([
            'id_user' => $this->regularUser->getKey(),
            'status' => KmDocumentStatus::DRAFT->value,
            'posisi' => 'All Employee',
        ]);

        // Published — boleh muncul
        KmPengajuan::factory()->create([
            'id_user' => $this->adminUser->getKey(),
            'status' => KmDocumentStatus::PUBLISHED->value,
            'posisi' => 'All Employee',
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege'));

        $response->assertOk();
        $pengajuans = $response->viewData('pengajuans');
        $this->assertCount(1, $pengajuans);
    }

    /** @test */
    public function search_filter_finds_matching_title(): void
    {
        KmPengajuan::factory()->create([
            'id_user' => $this->adminUser->getKey(),
            'judul' => 'Panduan Laravel Terbaru',
            'status' => KmDocumentStatus::PUBLISHED->value,
            'posisi' => 'All Employee',
        ]);

        KmPengajuan::factory()->create([
            'id_user' => $this->adminUser->getKey(),
            'judul' => 'Dokumen Lain',
            'status' => KmDocumentStatus::PUBLISHED->value,
            'posisi' => 'All Employee',
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege', ['q' => 'Laravel']));

        $response->assertOk();
        $pengajuans = $response->viewData('pengajuans');
        $this->assertCount(1, $pengajuans);
        $this->assertStringContainsString('Laravel', $pengajuans->first()->judul);
    }

    /** @test */
    public function invalid_sort_parameter_is_rejected(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege', ['sort' => 'invalid_sort_value']));

        // Validasi gagal → redirect back dengan error
        $response->assertRedirect();
    }

    /** @test */
    public function invalid_read_status_parameter_is_rejected(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege', ['read_status' => 'not_a_valid_status']));

        $response->assertRedirect();
    }

    /** @test */
    public function bookmarked_filter_shows_only_bookmarked_documents(): void
    {
        $docA = KmPengajuan::factory()->create([
            'id_user' => $this->adminUser->getKey(),
            'judul' => 'Dokumen A (Bookmarked)',
            'status' => KmDocumentStatus::PUBLISHED->value,
            'posisi' => 'All Employee',
        ]);

        KmPengajuan::factory()->create([
            'id_user' => $this->adminUser->getKey(),
            'judul' => 'Dokumen B (Not Bookmarked)',
            'status' => KmDocumentStatus::PUBLISHED->value,
            'posisi' => 'All Employee',
        ]);

        // Bookmark dokumen A sebagai regularUser
        \App\Models\KmBookmark::create([
            'user_id' => $this->regularUser->getKey(),
            'km_pengajuan_id' => $docA->getKey(),
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege', ['bookmarked' => '1']));

        $response->assertOk();
        $pengajuans = $response->viewData('pengajuans');
        $this->assertCount(1, $pengajuans);
        $this->assertEquals($docA->getKey(), $pengajuans->first()->getKey());
    }

    /** @test */
    public function per_page_parameter_with_invalid_value_is_rejected(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege', ['per_page' => '999']));

        $response->assertRedirect();
    }

    /** @test */
    public function xss_in_search_query_does_not_execute(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege', ['q' => '<script>alert(1)</script>']));

        // Harus OK (tidak crash), dan tidak ada XSS di response
        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    /** @test */
    public function search_uses_fulltext_metadata_instead_of_like_wildcards(): void
    {
        $matching = KmPengajuan::factory()->published()->create([
            'id_user' => $this->adminUser->getKey(),
            'judul' => 'Panduan Rekonsiliasi Metadata',
            'keterangan' => 'Rekonsiliasi membantu menjaga integritas pengetahuan.',
            'posisi' => 'All Employee',
        ]);
        KmPengajuan::factory()->published()->create([
            'id_user' => $this->adminUser->getKey(),
            'judul' => 'Panduan Keselamatan',
            'keterangan' => 'Materi prosedur kerja aman.',
            'posisi' => 'All Employee',
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege', ['q' => 'Rekonsiliasi']));

        $response->assertOk();
        $this->assertSame([$matching->getKey()], $response->viewData('pengajuans')->pluck('id')->all());
    }

    /** @test */
    public function dashboard_combines_category_date_and_read_status_filters(): void
    {
        $category = KmKategori::factory()->create();
        $matching = KmPengajuan::factory()->published()->create([
            'id_user' => $this->adminUser->getKey(),
            'id_km_kategori' => $category->getKey(),
            'posisi' => 'All Employee',
            'created_at' => '2026-07-10 12:00:00',
        ]);
        KmPengajuan::factory()->published()->create([
            'id_user' => $this->adminUser->getKey(),
            'id_km_kategori' => $category->getKey(),
            'posisi' => 'All Employee',
            'created_at' => '2026-06-10 12:00:00',
        ]);
        KmTransaksi::factory()->create([
            'id_km_pengajuan' => $matching->getKey(),
            'id_user' => $this->regularUser->getKey(),
            'status' => \App\Enums\KnowledgeManagement\KmReadStatus::READING->value,
        ]);

        $response = $this->actingAs($this->regularUser)->get(route('dsKnowlege', [
            'category' => $category->getKey(),
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
            'read_status' => 'reading',
        ]));

        $response->assertOk();
        $this->assertSame([$matching->getKey()], $response->viewData('pengajuans')->pluck('id')->all());
    }

    /** @test */
    public function dashboard_supports_deterministic_title_and_popular_sorting(): void
    {
        $zulu = KmPengajuan::factory()->published()->create([
            'id_user' => $this->adminUser->getKey(),
            'judul' => 'Zulu',
            'posisi' => 'All Employee',
        ]);
        $alpha = KmPengajuan::factory()->published()->create([
            'id_user' => $this->adminUser->getKey(),
            'judul' => 'Alpha',
            'posisi' => 'All Employee',
        ]);
        KmLihatBuku::query()->create([
            'id_km_pengajuan' => $zulu->getKey(),
            'jumlah_lihat' => 25,
        ]);

        $titleResponse = $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege', ['sort' => 'title_asc']));
        $this->assertSame(
            [$alpha->getKey(), $zulu->getKey()],
            $titleResponse->viewData('pengajuans')->pluck('id')->all(),
        );

        $popularResponse = $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege', ['sort' => 'popular']));
        $this->assertSame($zulu->getKey(), $popularResponse->viewData('pengajuans')->first()->getKey());
    }

    /** @test */
    public function pagination_is_capped_and_preserves_query_string(): void
    {
        KmPengajuan::factory()->count(50)->published()->create([
            'id_user' => $this->adminUser->getKey(),
            'posisi' => 'All Employee',
        ]);

        $response = $this->actingAs($this->regularUser)->get(route('dsKnowlege', [
            'per_page' => 48,
            'sort' => 'oldest',
        ]));

        $response->assertOk()->assertSee('per_page=48', false)->assertSee('sort=oldest', false);
        $paginator = $response->viewData('pengajuans');
        $this->assertCount(48, $paginator);
        $this->assertSame(50, $paginator->total());
    }

    /** @test */
    public function visibility_is_applied_before_counts_and_filters(): void
    {
        KmPengajuan::factory()->published()->create([
            'id_user' => $this->adminUser->getKey(),
            'judul' => 'Rahasia HR',
            'posisi' => 'HR',
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege', ['q' => 'Rahasia']));

        $response->assertOk();
        $this->assertSame(0, $response->viewData('pengajuans')->total());
    }

    /** @test */
    public function empty_state_distinguishes_no_materials_from_no_filter_results(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege'))
            ->assertSee('Belum ada materi');

        $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege', ['q' => 'tidak-ada']))
            ->assertSee('Filter tidak menemukan hasil');
    }

    /** @test */
    public function unknown_query_parameter_is_rejected(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('dsKnowlege', ['unsupported_filter' => 'value']))
            ->assertRedirect()
            ->assertSessionHasErrors('query');
    }
}
