<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Models\KmKategori;
use App\Models\KmPengajuan;
use App\Models\KmTag;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class KmMetadataSearchTest extends KmTestCase
{
    private User $owner;

    private User $reader;

    protected function setUp(): void
    {
        parent::setUp();

        $administrator = Role::query()->create(['role' => 'Administrator']);
        $employee = Role::query()->create(['role' => 'Employee']);
        $this->owner = User::factory()->create([
            'name' => 'ADMINSTRATOR',
            'role_id' => $administrator->getKey(),
            'km_total_poin' => 0,
        ]);
        $this->reader = User::factory()->create([
            'name' => 'Pembaca Metadata',
            'role_id' => $employee->getKey(),
            'km_total_poin' => 0,
        ]);
    }

    public function test_fulltext_matches_title_or_description_and_excludes_non_matches(): void
    {
        $titleMatch = $this->published([
            'judul' => 'Panduan Kubernetes Perusahaan',
            'keterangan' => 'Materi orkestrasi container.',
        ]);
        $descriptionMatch = $this->published([
            'judul' => 'Panduan Monitoring',
            'keterangan' => 'Observabilitas membantu diagnosis layanan.',
        ]);
        $this->published([
            'judul' => 'Pedoman Keselamatan',
            'keterangan' => 'Prosedur penggunaan alat pelindung.',
        ]);

        $this->assertSame(
            [$titleMatch->getKey()],
            $this->searchIds(['q' => 'Kubernetes']),
        );
        $this->assertSame(
            [$descriptionMatch->getKey()],
            $this->searchIds(['q' => 'Observabilitas']),
        );
        $this->assertSame([], $this->searchIds(['q' => 'TidakDitemukanMetadata']));
    }

    public function test_search_does_not_match_filename_file_path_or_binary_only_token(): void
    {
        $this->published([
            'judul' => 'Materi Umum',
            'keterangan' => 'Tidak memiliki kata pencarian.',
            'file' => 'RahasiaBiner.pdf',
            'file_name' => 'RahasiaBiner.pdf',
            'file_path' => 'documents/1/rahasia-biner.pdf',
            'file_original_name' => 'RahasiaBiner.pdf',
        ]);

        $this->assertSame([], $this->searchIds(['q' => 'RahasiaBiner']));
    }

    public function test_multiple_tags_use_any_of_semantics_and_combine_with_search_and_category(): void
    {
        $category = KmKategori::factory()->create();
        $otherCategory = KmKategori::factory()->create();
        $safety = KmTag::factory()->create(['name' => 'Safety', 'slug' => 'safety']);
        $quality = KmTag::factory()->create(['name' => 'Quality', 'slug' => 'quality']);
        $finance = KmTag::factory()->create(['name' => 'Finance', 'slug' => 'finance']);

        $first = $this->published([
            'judul' => 'Kalibrasi Peralatan Safety',
            'keterangan' => 'Prosedur kalibrasi berkala.',
            'id_km_kategori' => $category->getKey(),
        ]);
        $first->tags()->attach($safety);
        $second = $this->published([
            'judul' => 'Audit Kalibrasi Quality',
            'keterangan' => 'Checklist kalibrasi mutu.',
            'id_km_kategori' => $category->getKey(),
        ]);
        $second->tags()->attach($quality);
        $excluded = $this->published([
            'judul' => 'Kalibrasi Anggaran Finance',
            'keterangan' => 'Kebutuhan biaya kalibrasi.',
            'id_km_kategori' => $otherCategory->getKey(),
        ]);
        $excluded->tags()->attach($finance);

        $ids = $this->searchIds([
            'q' => 'Kalibrasi',
            'category' => $category->getKey(),
            'tag_ids' => [$safety->getKey(), $quality->getKey()],
        ]);

        $this->assertEqualsCanonicalizing(
            [$first->getKey(), $second->getKey()],
            $ids,
        );
        $this->assertNotContains($excluded->getKey(), $ids);
    }

    public function test_published_visibility_is_applied_to_fulltext_and_tag_filters(): void
    {
        $tag = KmTag::factory()->create();
        $visible = $this->published([
            'judul' => 'Visibilitas Operasional',
            'keterangan' => 'Materi untuk seluruh pegawai.',
            'posisi' => 'All Employee',
        ]);
        $visible->tags()->attach($tag);
        $hidden = $this->published([
            'judul' => 'Visibilitas Operasional HR',
            'keterangan' => 'Materi terbatas HR.',
            'posisi' => 'HR',
        ]);
        $hidden->tags()->attach($tag);

        $ids = $this->searchIds([
            'q' => 'Visibilitas',
            'tag_ids' => [$tag->getKey()],
        ]);

        $this->assertSame([$visible->getKey()], $ids);
        $this->assertNotContains($hidden->getKey(), $ids);
    }

    public function test_relevance_sort_descends_by_score_then_document_id_asc(): void
    {
        $strong = $this->published([
            'judul' => 'Optimisasi Optimisasi Sistem',
            'keterangan' => 'Optimisasi layanan melalui optimisasi proses.',
        ]);
        $weak = $this->published([
            'judul' => 'Pengantar Optimisasi',
            'keterangan' => 'Dasar pengelolaan layanan.',
        ]);

        $ids = $this->searchIds(['q' => 'Optimisasi', 'sort' => 'relevance']);
        $this->assertSame([$strong->getKey(), $weak->getKey()], $ids);

        $firstTie = $this->published([
            'judul' => 'Deterministik SearchToken',
            'keterangan' => 'Materi yang sama.',
        ]);
        $secondTie = $this->published([
            'judul' => 'Deterministik SearchToken',
            'keterangan' => 'Materi yang sama.',
        ]);

        $this->assertSame(
            [$firstTie->getKey(), $secondTie->getKey()],
            $this->searchIds(['q' => 'SearchToken', 'sort' => 'relevance']),
        );
    }

    public function test_default_sort_remains_created_at_desc_then_id_desc(): void
    {
        $old = $this->published(['created_at' => '2026-07-01 08:00:00']);
        $firstNew = $this->published(['created_at' => '2026-07-02 08:00:00']);
        $secondNew = $this->published(['created_at' => '2026-07-02 08:00:00']);

        $this->assertSame(
            [$secondNew->getKey(), $firstNew->getKey(), $old->getKey()],
            $this->searchIds(),
        );
    }

    public function test_relevance_without_query_and_invalid_tag_values_are_rejected(): void
    {
        $tag = KmTag::factory()->create();

        $this->actingAs($this->reader)
            ->get(route('dsKnowlege', ['sort' => 'relevance']))
            ->assertRedirect()
            ->assertSessionHasErrors('sort');

        $this->actingAs($this->reader)
            ->get(route('dsKnowlege', ['tag_ids' => [$tag->getKey(), $tag->getKey()]]))
            ->assertRedirect()
            ->assertSessionHasErrors('tag_ids.1');

        $this->actingAs($this->reader)
            ->get(route('dsKnowlege', ['tag_ids' => [999999]]))
            ->assertRedirect()
            ->assertSessionHasErrors('tag_ids.0');
    }

    public function test_pagination_preserves_search_tag_sort_and_page_size_query_string(): void
    {
        $tag = KmTag::factory()->create();
        $documents = collect();
        for ($index = 0; $index < 13; $index++) {
            $document = $this->published([
                'judul' => "PaginasiUnik Materi {$index}",
                'keterangan' => 'PaginasiUnik untuk pengujian query string.',
            ]);
            $document->tags()->attach($tag);
            $documents->push($document);
        }

        $response = $this->actingAs($this->reader)->get(route('dsKnowlege', [
            'q' => 'PaginasiUnik',
            'tag_ids' => [$tag->getKey()],
            'sort' => 'relevance',
            'per_page' => 12,
        ]));

        $response->assertOk();
        $paginator = $response->viewData('pengajuans');
        $this->assertCount(12, $paginator);
        $this->assertSame(13, $paginator->total());

        parse_str((string) parse_url($paginator->url(2), PHP_URL_QUERY), $query);
        $this->assertSame('PaginasiUnik', $query['q']);
        $this->assertSame([(string) $tag->getKey()], array_map('strval', $query['tag_ids']));
        $this->assertSame('relevance', $query['sort']);
        $this->assertSame('12', (string) $query['per_page']);
        $this->assertSame('2', (string) $query['page']);
    }

    public function test_fulltext_migration_detects_drift_and_supports_testing_down_up_cycle(): void
    {
        $migrationPath = database_path(
            'migrations/2026_07_18_120001_add_km_metadata_fulltext_index_to_km_pengajuans.php'
        );

        try {
            (require $migrationPath)->up();
            $this->fail('Index existing tanpa migration row harus dilaporkan sebagai schema drift.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Schema drift FULLTEXT KM', $exception->getMessage());
        }

        (require $migrationPath)->down();
        $this->assertSame([], $this->fulltextColumns());

        (require $migrationPath)->up();
        $this->assertSame(['judul', 'keterangan'], $this->fulltextColumns());
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

    /**
     * @param  array<string, mixed>  $filters
     * @return list<int>
     */
    private function searchIds(array $filters = []): array
    {
        $response = $this->actingAs($this->reader)->get(route('dsKnowlege', $filters));
        $response->assertOk();

        return $response->viewData('pengajuans')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<string>
     */
    private function fulltextColumns(): array
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', 'km_pengajuans')
            ->where('INDEX_NAME', 'km_pengajuans_judul_keterangan_fulltext')
            ->where('INDEX_TYPE', 'FULLTEXT')
            ->orderBy('SEQ_IN_INDEX')
            ->pluck('COLUMN_NAME')
            ->all();
    }
}
