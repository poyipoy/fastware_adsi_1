<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Models\Insight;
use App\Models\KmPengajuan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class KmInsightSocialTest extends KmTestCase
{
    public function test_threads_mentions_reactions_and_author_edit_window_use_server_rules(): void
    {
        [$owner, $author, $mentioned] = $this->users();
        $document = $this->document($owner);

        $rootResponse = $this->actingAs($author)->postJson(
            route('km.insights.store', $document),
            [
                'content' => 'Insight utama yang dapat ditindaklanjuti.',
                'mention_ids' => [$mentioned->getKey()],
            ],
        );
        $rootResponse->assertCreated();
        $rootId = (int) $rootResponse->json('id');
        $this->assertDatabaseHas('km_insight_mentions', [
            'insight_id' => $rootId,
            'mentioned_user_id' => $mentioned->getKey(),
        ]);
        $this->assertDatabaseHas('km_notifications', [
            'user_id' => $mentioned->getKey(),
            'type' => 'insight_mention',
        ]);

        $replyResponse = $this->actingAs($mentioned)->postJson(
            route('km.insights.store', $document),
            ['content' => 'Balasan pertama.', 'parent_id' => $rootId],
        );
        $replyResponse->assertCreated();
        $replyId = (int) $replyResponse->json('id');

        $nested = $this->actingAs($author)->postJson(
            route('km.insights.store', $document),
            ['content' => 'Balasan tingkat tiga direanchor.', 'parent_id' => $replyId],
        );
        $nested->assertCreated();
        $this->assertSame(
            $rootId,
            (int) Insight::query()->findOrFail((int) $nested->json('id'))->parent_id,
        );

        $list = $this->getJson(route('km.insights.index', $document));
        $list->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonCount(2, 'data.0.replies');

        $this->actingAs($mentioned)->putJson(route('km.insights.reaction.store', $rootId), [
            'reaction' => 'helpful',
        ])->assertOk()->assertJsonPath('reaction', 'helpful');
        $this->assertSame(1, DB::table('km_notifications')
            ->where('user_id', $author->getKey())
            ->where('type', 'insight_reaction')
            ->count());
        $this->putJson(route('km.insights.reaction.store', $rootId), [
            'reaction' => 'agree',
        ])->assertOk()->assertJsonPath('reaction', 'agree');
        $this->assertSame(1, DB::table('km_insight_reactions')->count());
        $this->assertSame('agree', DB::table('km_insight_reactions')->value('reaction'));
        $this->assertSame(1, DB::table('km_notifications')
            ->where('user_id', $author->getKey())
            ->where('type', 'insight_reaction')
            ->count(), 'Perubahan jenis reaction tidak boleh membuat notifikasi baru.');

        $this->deleteJson(route('km.insights.reaction.destroy', $rootId))->assertOk();
        $this->assertSame(1, DB::table('km_notifications')
            ->where('user_id', $author->getKey())
            ->where('type', 'insight_reaction')
            ->count(), 'Penghapusan reaction tidak boleh membuat notifikasi.');
        $this->putJson(route('km.insights.reaction.store', $rootId), [
            'reaction' => 'helpful',
        ])->assertOk();
        $this->assertSame(2, DB::table('km_notifications')
            ->where('user_id', $author->getKey())
            ->where('type', 'insight_reaction')
            ->count(), 'Reaction baru setelah penghapusan adalah business event baru.');

        $this->actingAs($author)->putJson(route('km.insights.reaction.store', $rootId), [
            'reaction' => 'agree',
        ])->assertOk();
        $this->assertSame(2, DB::table('km_notifications')
            ->where('user_id', $author->getKey())
            ->where('type', 'insight_reaction')
            ->count(), 'Reaction diri sendiri tidak boleh membuat notifikasi.');

        $this->actingAs($mentioned)->putJson(route('km.insights.reaction.store', $rootId), [
            'reaction' => 'invalid',
        ])->assertUnprocessable()->assertJsonValidationErrors('reaction');

        $this->actingAs($author)->patchJson(route('km.insights.update', $rootId), [
            'content' => 'Insight utama yang sudah diperbarui.',
        ])->assertOk();
        $this->assertNotNull(Insight::query()->findOrFail($rootId)->edited_at);
        $this->assertDatabaseHas('km_insight_mentions', [
            'insight_id' => $rootId,
            'mentioned_user_id' => $mentioned->getKey(),
        ]);

        $this->actingAs($author)->patchJson(route('km.insights.update', $rootId), [
            'content' => 'Mention lama tetap tercatat.',
            'mention_ids' => [],
        ])->assertOk();
        $this->assertDatabaseHas('km_insight_mentions', [
            'insight_id' => $rootId,
            'mentioned_user_id' => $mentioned->getKey(),
        ]);

        $this->actingAs($author)->patchJson(route('km.insights.update', $rootId), [
            'content' => 'Mention lama tetap tercatat.',
            'mention_ids' => [],
        ])->assertOk();
        $this->assertDatabaseHas('km_insight_mentions', [
            'insight_id' => $rootId,
            'mentioned_user_id' => $mentioned->getKey(),
        ]);

        $this->actingAs($mentioned)->patchJson(route('km.insights.update', $rootId), [
            'content' => 'Edit pengguna lain tidak diizinkan.',
        ])->assertForbidden();

        $laterRoots = collect(range(1, 11))->map(fn (int $number): Insight => Insight::query()->create([
            'id_user' => $author->getKey(),
            'id_km_pengajuan' => $document->getKey(),
            'content' => "Insight tambahan {$number}.",
        ]));
        $focused = $this->actingAs($author)->getJson(route('km.insights.index', [
            'kmPengajuan' => $document,
            'focus_id' => $laterRoots->last()->getKey(),
        ]));
        $focused->assertOk()
            ->assertJsonPath('data.0.id', $laterRoots->last()->getKey());
    }

    public function test_feature_limit_awards_once_and_unfeature_does_not_reverse_points(): void
    {
        [$owner, $author] = $this->users();
        $document = $this->document($owner);
        $insights = collect(range(1, 4))->map(fn (int $number): Insight => Insight::query()->create([
            'id_user' => $author->getKey(),
            'id_km_pengajuan' => $document->getKey(),
            'content' => "Insight {$number}",
        ]));

        foreach ($insights->take(3) as $insight) {
            $this->actingAs($owner)
                ->postJson(route('km.insights.feature', $insight))
                ->assertOk();
        }
        $this->postJson(route('km.insights.feature', $insights->last()))
            ->assertUnprocessable();

        $this->assertSame(3, Insight::query()->whereNotNull('featured_at')->count());
        $this->assertSame(3, DB::table('km_point_ledger')->count());
        $this->assertSame(30, (int) $author->refresh()->km_total_poin);

        $first = $insights->first();
        $this->deleteJson(route('km.insights.unfeature', $first))->assertOk();
        $this->postJson(route('km.insights.feature', $first))->assertOk();
        $this->assertSame(3, DB::table('km_point_ledger')->count());
        $this->assertSame(30, (int) $author->refresh()->km_total_poin);
        $this->assertSame(
            3,
            DB::table('km_notifications')->where('type', 'insight_featured')->count(),
        );
    }

    public function test_insight_queries_reject_invalid_and_unknown_parameters(): void
    {
        [$owner, $reader] = $this->users();
        $document = $this->document($owner);

        $this->actingAs($reader)
            ->getJson(route('km.insights.index', [
                'kmPengajuan' => $document,
                'per_page' => 26,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');

        $this->getJson(route('km.insights.index', [
            'kmPengajuan' => $document,
            'sort' => 'latest',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('query');

        $this->getJson(route('km.insights.mention-options', [
            'kmPengajuan' => $document,
            'role' => 'admin',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('query');
    }

    public function test_mention_picker_keeps_role_context_but_exposes_only_safe_fields(): void
    {
        $owner = User::factory()->create([
            'name' => 'Restricted Document Owner',
            'role_id' => 99,
            'is_active' => false,
        ]);
        $head = User::factory()->create([
            'name' => 'Eligible Department Head',
            'email' => 'head@example.test',
            'role_id' => 2,
            'is_active' => false,
        ]);
        $regularUser = User::factory()->create([
            'name' => 'Regular Restricted User',
            'role_id' => 99,
            'is_active' => false,
        ]);
        $blockedHead = User::factory()->create([
            'name' => 'Blocked Department Head',
            'role_id' => 2,
            'is_active' => true,
        ]);
        $document = $this->document($owner, 'Dept. Head');

        $response = $this->actingAs($head)->getJson(route('km.insights.mention-options', $document));
        $response->assertOk()
            ->assertJsonFragment([
                'id' => $head->getKey(),
                'name' => $head->name,
                'email' => $head->email,
            ])
            ->assertJsonMissing(['id' => $regularUser->getKey()])
            ->assertJsonMissing(['id' => $blockedHead->getKey()])
            ->assertJsonMissingPath('data.0.role_id')
            ->assertJsonMissingPath('data.0.password');
    }

    public function test_mention_options_return_all_login_enabled_accounts_and_support_server_search(): void
    {
        [$owner, $reader] = $this->users();
        $document = $this->document($owner);

        foreach (range(1, 24) as $number) {
            User::factory()->create([
                'name' => sprintf('Alpha Mention Candidate %02d', $number),
                'email' => sprintf('alpha.mention.%02d@example.test', $number),
                'npk' => sprintf('MENTION-%02d', $number),
                'role_id' => 99,
                'is_active' => false,
            ]);
        }

        $target = User::factory()->create([
            'name' => 'Zulu Searchable Employee',
            'email' => 'zulu.searchable@example.test',
            'npk' => 'NPK-ZULU-991',
            'role_id' => 99,
            'is_active' => false,
        ]);
        $blocked = User::factory()->create([
            'name' => 'Blocked Mention Employee',
            'email' => 'blocked.mention@example.test',
            'npk' => 'NPK-BLOCKED-991',
            'role_id' => 99,
            'is_active' => true,
        ]);
        $expectedActiveCount = User::query()->where('is_active', false)->count();

        $this->actingAs($reader)
            ->getJson(route('km.insights.mention-options', $document))
            ->assertOk()
            ->assertJsonCount($expectedActiveCount, 'data')
            ->assertJsonFragment([
                'id' => $target->getKey(),
                'name' => $target->name,
                'email' => $target->email,
            ])
            ->assertJsonMissing(['id' => $blocked->getKey()]);

        foreach (['Zulu Searchable', 'zulu.searchable@example.test', 'NPK-ZULU-991'] as $query) {
            $this->getJson(route('km.insights.mention-options', [
                'kmPengajuan' => $document,
                'q' => $query,
            ]))
                ->assertOk()
                ->assertJsonFragment([
                    'id' => $target->getKey(),
                    'name' => $target->name,
                    'email' => $target->email,
                ]);
        }

        $this->postJson(route('km.insights.store', $document), [
            'content' => 'Akun yang tidak dapat login tidak boleh di-mention.',
            'mention_ids' => [$blocked->getKey()],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mention_ids.0');
    }

    public function test_moderation_requires_reason_and_deleted_content_is_masked_for_readers(): void
    {
        [$owner, $author, $reader] = $this->users();
        $document = $this->document($owner);
        $insight = Insight::query()->create([
            'id_user' => $author->getKey(),
            'id_km_pengajuan' => $document->getKey(),
            'content' => 'Konten yang dimoderasi.',
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $this->actingAs($owner)
            ->deleteJson(route('km.insights.destroy', $insight))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->deleteJson(route('km.insights.destroy', $insight), [
            'reason' => 'Tidak sesuai pedoman diskusi.',
        ])->assertOk()->assertJsonPath('deleted', true);

        $this->actingAs($reader)
            ->getJson(route('km.insights.index', $document))
            ->assertOk()
            ->assertJsonPath('data.0.content', 'Insight telah dihapus.')
            ->assertJsonPath('data.0.delete_reason', null);

        $this->actingAs($owner)
            ->getJson(route('km.insights.index', $document))
            ->assertOk()
            ->assertJsonPath('data.0.content', 'Konten yang dimoderasi.')
            ->assertJsonPath('data.0.delete_reason', 'Tidak sesuai pedoman diskusi.');
    }

    /** @return array<int, User> */
    private function users(): array
    {
        return [
            User::factory()->create(['name' => 'ADMINSTRATOR', 'role_id' => null, 'km_total_poin' => 0, 'is_active' => false]),
            User::factory()->create(['name' => 'Insight Author', 'role_id' => 99, 'km_total_poin' => 0, 'is_active' => false]),
            User::factory()->create(['name' => 'Insight Reader', 'role_id' => 99, 'km_total_poin' => 0, 'is_active' => false]),
        ];
    }

    private function document(User $owner, string $position = 'All Employee'): KmPengajuan
    {
        return KmPengajuan::factory()->published()->create([
            'id_user' => $owner->getKey(),
            'posisi' => $position,
        ]);
    }
}
