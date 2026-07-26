<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmReadStatus;
use App\Exports\KnowledgeManagement\KmPopularMaterialExport;
use App\Models\KmKategori;
use App\Models\KmLihatBuku;
use App\Models\KmPengajuan;
use App\Models\KmSuka;
use App\Models\KmTag;
use App\Models\KmTransaksi;
use App\Models\Role;
use App\Models\User;
use App\Services\KnowledgeManagement\KmAccessService;
use App\Services\KnowledgeManagement\KmPopularMaterialReportService;
use Illuminate\Support\Facades\DB;

final class KmPopularMaterialAnalyticsTest extends KmTestCase
{
    private User $owner;

    private User $outsider;

    private User $approver;

    protected function setUp(): void
    {
        parent::setUp();

        $administrator = Role::query()->create(['role' => 'Administrator']);
        $employee = Role::query()->create(['role' => 'Employee']);
        $this->owner = User::factory()->create([
            'name' => 'Pemilik Analytics',
            'email' => 'pemilik-rahasia@example.test',
            'npk' => 'NPK-RAHASIA-001',
            'role_id' => $administrator->getKey(),
            'km_total_poin' => 0,
        ]);
        $this->outsider = User::factory()->create([
            'name' => 'Pegawai Biasa',
            'role_id' => $employee->getKey(),
            'km_total_poin' => 0,
        ]);
        $this->approver = User::factory()->create([
            'name' => 'MUGI PRAMONO',
            'role_id' => $employee->getKey(),
            'km_total_poin' => 0,
        ]);
    }

    public function test_only_existing_approval_access_group_can_open_html_and_exports(): void
    {
        foreach ([
            'km.analytics.popular',
            'km.analytics.popular.export.xlsx',
            'km.analytics.popular.export.pdf',
        ] as $routeName) {
            $this->actingAs($this->outsider)
                ->get(route($routeName))
                ->assertForbidden();
        }

        $this->actingAs($this->approver)
            ->get(route('km.analytics.popular'))
            ->assertOk()
            ->assertSee('Materi Populer')
            ->assertSee('bukan KPI');
    }

    public function test_report_uses_correlated_aggregates_distinct_completed_readers_and_deterministic_order(): void
    {
        DB::statement('CREATE INDEX km_test_transaksis_user_index ON km_transaksis (id_user)');
        DB::statement('ALTER TABLE km_transaksis DROP INDEX km_transaksis_user_document_unique');

        $first = $this->published(['judul' => 'Peringkat Pertama']);
        $second = $this->published(['judul' => 'Peringkat Kedua']);
        $third = $this->published(['judul' => 'Peringkat Ketiga']);
        $zero = $this->published(['judul' => 'Tanpa Interaksi']);
        $draft = KmPengajuan::factory()->draft()->create([
            'id_user' => $this->owner->getKey(),
            'judul' => 'Draft Tidak Masuk',
            'posisi' => 'All Employee',
        ]);

        $this->addViews($first, 20);
        $this->addViews($second, 20);
        $this->addViews($third, 20);
        $this->addViews($draft, 999);

        $readerA = User::factory()->create(['name' => 'Reader A', 'km_total_poin' => 0]);
        $readerB = User::factory()->create(['name' => 'Reader B', 'km_total_poin' => 0]);
        $readerC = User::factory()->create(['name' => 'Reader C', 'km_total_poin' => 0]);

        $this->addCompleted($first, $readerA);
        $this->addCompleted($first, $readerA);
        $this->addCompleted($first, $readerB);
        $this->addCompleted($second, $readerA);
        $this->addCompleted($second, $readerB);
        $this->addCompleted($third, $readerA);
        KmTransaksi::query()->create([
            'id_km_pengajuan' => $third->getKey(),
            'id_user' => $readerC->getKey(),
            'modified_by' => $readerC->getKey(),
            'status' => KmReadStatus::READING->value,
            'level' => 0,
        ]);

        $this->addLikes($first, 3);
        $this->addLikes($second, 2);
        $this->addLikes($third, 5);

        $materials = $this->actingAs($this->approver)
            ->get(route('km.analytics.popular'))
            ->assertOk()
            ->viewData('materials');

        $this->assertSame(
            [$first->getKey(), $second->getKey(), $third->getKey(), $zero->getKey()],
            $materials->pluck('id')->map(fn ($id): int => (int) $id)->all(),
        );
        $firstRow = $materials->firstWhere('id', $first->getKey());
        $this->assertSame(20, (int) $firstRow->total_views);
        $this->assertSame(2, (int) $firstRow->completed_readers);
        $this->assertSame(3, (int) $firstRow->likes_count);

        $zeroRow = $materials->firstWhere('id', $zero->getKey());
        $this->assertSame(0, (int) $zeroRow->total_views);
        $this->assertSame(0, (int) $zeroRow->completed_readers);
        $this->assertSame(0, (int) $zeroRow->likes_count);
        $this->assertNotContains($draft->getKey(), $materials->pluck('id')->all());
    }

    public function test_category_and_multiple_tag_filters_use_any_of_semantics(): void
    {
        $category = KmKategori::factory()->create();
        $otherCategory = KmKategori::factory()->create();
        $tagA = KmTag::factory()->create(['name' => 'Tag A', 'slug' => 'tag-a']);
        $tagB = KmTag::factory()->create(['name' => 'Tag B', 'slug' => 'tag-b']);
        $tagC = KmTag::factory()->create(['name' => 'Tag C', 'slug' => 'tag-c']);

        $first = $this->published([
            'judul' => 'Filter Pertama',
            'id_km_kategori' => $category->getKey(),
        ]);
        $first->tags()->attach($tagA);
        $second = $this->published([
            'judul' => 'Filter Kedua',
            'id_km_kategori' => $category->getKey(),
        ]);
        $second->tags()->attach($tagB);
        $excluded = $this->published([
            'judul' => 'Filter Dikecualikan',
            'id_km_kategori' => $otherCategory->getKey(),
        ]);
        $excluded->tags()->attach($tagC);

        $materials = $this->actingAs($this->approver)
            ->get(route('km.analytics.popular', [
                'category' => $category->getKey(),
                'tag_ids' => [$tagA->getKey(), $tagB->getKey()],
            ]))
            ->assertOk()
            ->viewData('materials');

        $this->assertEqualsCanonicalizing(
            [$first->getKey(), $second->getKey()],
            $materials->pluck('id')->all(),
        );
        $this->assertNotContains($excluded->getKey(), $materials->pluck('id')->all());
    }

    public function test_export_cap_reads_one_extra_row_and_html_displays_warning(): void
    {
        $this->published(['judul' => 'Cap Satu']);
        $this->published(['judul' => 'Cap Dua']);
        $this->published(['judul' => 'Cap Tiga']);

        $limitedService = new class(app(KmAccessService::class)) extends KmPopularMaterialReportService
        {
            public function exportLimit(): int
            {
                return 2;
            }
        };
        $this->app->instance(KmPopularMaterialReportService::class, $limitedService);

        $report = $limitedService->exportReport($this->approver, [
            'category' => null,
            'tag_ids' => [],
        ]);

        $this->assertCount(2, $report['rows']);
        $this->assertTrue($report['limit_reached']);
        $this->assertTrue($report['truncated']);
        $this->assertSame('Asia/Jakarta', $report['generated_at']->getTimezone()->getName());

        $this->actingAs($this->approver)
            ->get(route('km.analytics.popular'))
            ->assertOk()
            ->assertSee('export dibatasi pada 10.000 materi pertama');
    }

    public function test_html_xlsx_and_pdf_use_same_order_filters_metrics_and_exclude_pii(): void
    {
        $category = KmKategori::factory()->create(['nama_kategori' => 'Operasional']);
        $tag = KmTag::factory()->create(['name' => 'Prioritas', 'slug' => 'prioritas']);
        $first = $this->published([
            'judul' => 'Materi Parity Utama',
            'id_km_kategori' => $category->getKey(),
        ]);
        $first->tags()->attach($tag);
        $second = $this->published([
            'judul' => 'Materi Parity Kedua',
            'id_km_kategori' => $category->getKey(),
        ]);
        $second->tags()->attach($tag);
        $this->addViews($first, 9);
        $this->addViews($second, 3);
        $this->addLikes($first, 1);

        $filters = [
            'category' => $category->getKey(),
            'tag_ids' => [$tag->getKey()],
        ];
        $service = app(KmPopularMaterialReportService::class);
        $report = $service->exportReport($this->approver, $filters);
        $expectedIds = [$first->getKey(), $second->getKey()];

        $htmlResponse = $this->actingAs($this->approver)
            ->get(route('km.analytics.popular', $filters))
            ->assertOk()
            ->assertDontSee($this->owner->email)
            ->assertDontSee($this->owner->npk);
        $this->assertSame(
            $expectedIds,
            $htmlResponse->viewData('materials')->pluck('id')->all(),
        );

        $xlsxRows = (new KmPopularMaterialExport($report))->array();
        $headingIndex = collect($xlsxRows)->search(
            fn (array $row): bool => ($row[0] ?? null) === 'ID'
        );
        $xlsxData = array_slice($xlsxRows, $headingIndex + 1);
        $this->assertSame($expectedIds, array_map(fn (array $row): int => (int) $row[0], $xlsxData));
        $this->assertSame(9, (int) $xlsxData[0][4]);

        $pdfHtml = view('knowlege_management.analytics.popular-pdf', $report)->render();
        $this->assertLessThan(
            strpos($pdfHtml, (string) $second->getKey()),
            strpos($pdfHtml, (string) $first->getKey()),
        );
        $this->assertStringNotContainsString($this->owner->email, $pdfHtml);
        $this->assertStringNotContainsString($this->owner->npk, $pdfHtml);

        $this->actingAs($this->approver)
            ->get(route('km.analytics.popular.export.xlsx', $filters))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            );
        $this->actingAs($this->approver)
            ->get(route('km.analytics.popular.export.pdf', $filters))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertSame(
            ['id', 'judul', 'kategori', 'tags', 'total_views', 'completed_readers', 'likes_count'],
            array_keys($report['rows']->first()),
        );
        $serialized = json_encode($report['rows'], JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($this->owner->name, $serialized);
        $this->assertStringNotContainsString($this->owner->email, $serialized);
        $this->assertStringNotContainsString($this->owner->npk, $serialized);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function published(array $attributes = []): KmPengajuan
    {
        return KmPengajuan::factory()->published()->create([
            'id_user' => $this->owner->getKey(),
            'posisi' => 'All Employee',
            ...$attributes,
        ]);
    }

    private function addViews(KmPengajuan $document, int $views): void
    {
        KmLihatBuku::query()->create([
            'id_km_pengajuan' => $document->getKey(),
            'jumlah_lihat' => $views,
        ]);
    }

    private function addCompleted(KmPengajuan $document, User $reader): void
    {
        KmTransaksi::query()->create([
            'id_km_pengajuan' => $document->getKey(),
            'id_user' => $reader->getKey(),
            'modified_by' => $reader->getKey(),
            'status' => KmReadStatus::COMPLETED->value,
            'level' => 0,
            'completed_at' => now(),
            'points_awarded_at' => now(),
        ]);
    }

    private function addLikes(KmPengajuan $document, int $count): void
    {
        for ($index = 0; $index < $count; $index++) {
            $user = User::factory()->create([
                'name' => "Like User {$document->getKey()} {$index}",
                'km_total_poin' => 0,
            ]);
            KmSuka::query()->create([
                'id_user' => $user->getKey(),
                'id_km_pengajuan' => $document->getKey(),
                'jumlah_like' => 1,
            ]);
        }
    }
}
