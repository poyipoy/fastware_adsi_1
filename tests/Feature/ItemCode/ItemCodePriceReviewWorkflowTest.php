<?php

namespace Tests\Feature\ItemCode;

use App\Exports\ItemCodeExport;
use App\Models\ItemCode;
use App\Models\TrsItemCodeHistory;
use App\Models\User;
use App\Services\ItemCodeHistoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class ItemCodePriceReviewWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $database = (string) DB::connection()->getDatabaseName();

        if (! app()->environment('testing')
            || DB::connection()->getDriverName() !== 'mysql'
            || ! str_ends_with($database, '_testing')) {
            $this->markTestSkipped(
                'Item Code tests require MySQL, APP_ENV=testing, and DB_DATABASE ending with _testing.'
            );
        }

        foreach (['item_codes', 'trs_item_code_histories'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped(
                    sprintf('Tabel %s belum tersedia pada database testing.', $table)
                );
            }
        }
    }

    public function test_mamik_can_create_without_price_and_spoofed_price_is_ignored(): void
    {
        $mamik = $this->user('MAMIK ABIDIN');

        $withoutPrice = $this->payload([
            'product_code' => 'MAMIK-NO-PRICE',
        ]);
        unset($withoutPrice['price_per_pcs']);

        $this->actingAs($mamik)
            ->post(route('item-code.store'), $withoutPrice)
            ->assertRedirect();

        $this->assertDatabaseHas('item_codes', [
            'created_by' => $mamik->id,
            'product_code' => 'MAMIK-NO-PRICE',
            'price_per_pcs' => null,
            'status' => ItemCode::STATUS_DRAFT,
        ]);

        $this->actingAs($mamik)
            ->post(route('item-code.store'), $this->payload([
                'product_code' => 'MAMIK-SPOOFED-PRICE',
                'price_per_pcs' => '999999.99',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('item_codes', [
            'created_by' => $mamik->id,
            'product_code' => 'MAMIK-SPOOFED-PRICE',
            'price_per_pcs' => null,
        ]);

        $missingCurrency = $this->payload(['product_code' => 'MAMIK-CURRENCY-REQUIRED']);
        unset($missingCurrency['currency']);

        $this->actingAs($mamik)
            ->post(route('item-code.store'), $missingCurrency)
            ->assertSessionHasErrors('currency');

        $vivian = $this->user('VIVIAN ANGELIKA');
        $normalPayload = $this->payload(['product_code' => 'NORMAL-PRICE-REQUIRED']);
        unset($normalPayload['price_per_pcs']);

        $this->actingAs($vivian)
            ->post(route('item-code.store'), $normalPayload)
            ->assertSessionHasErrors('price_per_pcs');

        $this->actingAs($mamik)
            ->post(route('item-code.store'), $this->updatePricePayload())
            ->assertForbidden();

        $this->actingAs($mamik)
            ->post(route('item-code.import'))
            ->assertForbidden();

        $this->actingAs($mamik)
            ->get(route('item-code.importTemplate', ['type' => ItemCode::TYPE_NEW_PRODUCT]))
            ->assertForbidden();
    }

    public function test_mamik_mutations_are_limited_and_special_price_is_immutable_on_draft_updates(): void
    {
        $mamik = $this->user('MAMIK ABIDIN');
        $vivian = $this->user('VIVIAN ANGELIKA');

        $special = $this->item($mamik, [
            'product_code' => 'SPECIAL-EDIT',
            'price_per_pcs' => '150.00',
        ]);

        $this->actingAs($mamik)
            ->put(route('item-code.update', $special), $this->payload([
                'product_code' => 'SPECIAL-EDIT',
                'description' => 'Deskripsi diperbarui',
                'price_per_pcs' => '999.99',
            ]))
            ->assertRedirect();

        $special->refresh();
        $this->assertSame('150.00', $special->price_per_pcs);
        $this->assertSame('Deskripsi diperbarui', $special->description);

        $this->actingAs($vivian)
            ->put(route('item-code.update', $special), $this->payload([
                'product_code' => 'SPECIAL-EDIT',
                'price_per_pcs' => '777.77',
            ]))
            ->assertRedirect();

        $this->assertSame('150.00', $special->refresh()->price_per_pcs);

        $otherItem = $this->item($vivian, ['product_code' => 'OTHER-OWNER']);

        $this->actingAs($mamik)
            ->put(route('item-code.update', $otherItem), $this->payload([
                'product_code' => 'OTHER-OWNER',
            ]))
            ->assertForbidden();

        $this->actingAs($mamik)
            ->delete(route('item-code.destroy', $otherItem))
            ->assertForbidden();

        $this->actingAs($mamik)
            ->post(route('item-code.submit', $otherItem))
            ->assertForbidden();

        $specialUpdatePrice = $this->item($mamik, [
            'type' => ItemCode::TYPE_UPDATE_PRICE,
            'tanggal_lama' => now()->subDay()->toDateString(),
            'harga_baru' => '175.00',
            'tanggal_harga_baru' => now()->toDateString(),
            'reason_new_price' => 'Penyesuaian',
        ]);

        $this->actingAs($mamik)
            ->post(route('item-code.submit', $specialUpdatePrice))
            ->assertForbidden();
    }

    public function test_submission_target_is_resolved_per_creator_and_type_for_single_and_bulk_actions(): void
    {
        $mamik = $this->user('MAMIK ABIDIN');
        $vivian = $this->user('VIVIAN ANGELIKA');
        $ilyas = $this->user('ILYAS NOOR FIRDAUS');

        $mamikSingle = $this->item($mamik, ['product_code' => 'MAMIK-SINGLE', 'price_per_pcs' => null]);
        $this->actingAs($mamik)
            ->post(route('item-code.submit', $mamikSingle))
            ->assertRedirect();
        $this->assertSame(ItemCode::STATUS_PENDING_PRICE_REVIEW, $mamikSingle->refresh()->status);

        $requestedHistory = TrsItemCodeHistory::query()
            ->where('item_code_id', $mamikSingle->id)
            ->where('action', ItemCodeHistoryService::ACTION_PRICE_REVIEW_REQUESTED)
            ->first();
        $this->assertNotNull($requestedHistory);

        $normal = $this->item($vivian, ['product_code' => 'NORMAL-SINGLE']);
        $this->actingAs($vivian)
            ->post(route('item-code.submit', $normal))
            ->assertRedirect();
        $this->assertSame(ItemCode::STATUS_SUBMITTED, $normal->refresh()->status);

        $ilyasOwn = $this->item($ilyas, ['product_code' => 'ILYAS-OWN']);
        $this->actingAs($ilyas)
            ->post(route('item-code.submit', $ilyasOwn))
            ->assertRedirect();
        $this->assertSame(ItemCode::STATUS_SUBMITTED, $ilyasOwn->refresh()->status);

        $mamikByOtherActor = $this->item($mamik, ['product_code' => 'MAMIK-BY-VIVIAN']);
        $this->actingAs($vivian)
            ->post(route('item-code.submit', $mamikByOtherActor))
            ->assertRedirect();
        $this->assertSame(
            ItemCode::STATUS_PENDING_PRICE_REVIEW,
            $mamikByOtherActor->refresh()->status
        );

        $mamikUpdatePrice = $this->item($mamik, [
            'type' => ItemCode::TYPE_UPDATE_PRICE,
            'tanggal_lama' => now()->subDay()->toDateString(),
            'harga_baru' => '110.00',
            'tanggal_harga_baru' => now()->toDateString(),
            'reason_new_price' => 'Penyesuaian',
        ]);
        $this->actingAs($vivian)
            ->post(route('item-code.submit', $mamikUpdatePrice))
            ->assertRedirect();
        $this->assertSame(ItemCode::STATUS_SUBMITTED, $mamikUpdatePrice->refresh()->status);

        $bulkMamik = $this->item($mamik, ['product_code' => 'MAMIK-BULK']);
        $bulkNormal = $this->item($vivian, ['product_code' => 'NORMAL-BULK']);
        $this->actingAs($vivian)
            ->post(route('item-code.submitAll'), [
                'tab' => ItemCode::TYPE_NEW_PRODUCT,
                'selected_ids' => [$bulkMamik->id, $bulkNormal->id],
            ])
            ->assertRedirect();

        $this->assertSame(ItemCode::STATUS_PENDING_PRICE_REVIEW, $bulkMamik->refresh()->status);
        $this->assertSame(ItemCode::STATUS_SUBMITTED, $bulkNormal->refresh()->status);

        $mamikOwnBulk = $this->item($mamik, ['product_code' => 'MAMIK-OWN-BULK']);
        $otherDraft = $this->item($vivian, ['product_code' => 'OTHER-DRAFT']);
        $this->actingAs($mamik)
            ->post(route('item-code.submitAll'), [
                'tab' => ItemCode::TYPE_NEW_PRODUCT,
                'selected_ids' => [$mamikOwnBulk->id, $otherDraft->id],
            ])
            ->assertRedirect();

        $this->assertSame(ItemCode::STATUS_PENDING_PRICE_REVIEW, $mamikOwnBulk->refresh()->status);
        $this->assertSame(ItemCode::STATUS_DRAFT, $otherDraft->refresh()->status);
    }

    public function test_only_ilyas_sees_the_isolated_queue_and_pending_items_cannot_be_approved(): void
    {
        $mamik = $this->user('MAMIK ABIDIN');
        $ilyas = $this->user('ILYAS NOOR FIRDAUS');
        $vivian = $this->user('VIVIAN ANGELIKA');
        $jessica = $this->user('JESSICA PAUNE');
        $administrator = $this->user('ADMINISTRATOR');

        $eligible = $this->item($mamik, [
            'product_code' => 'QUEUE-ELIGIBLE',
            'status' => ItemCode::STATUS_PENDING_PRICE_REVIEW,
            'price_per_pcs' => null,
        ]);
        $this->item($vivian, [
            'product_code' => 'QUEUE-WRONG-CREATOR',
            'status' => ItemCode::STATUS_PENDING_PRICE_REVIEW,
        ]);
        $this->item($mamik, [
            'product_code' => 'QUEUE-ALREADY-SUBMITTED',
            'status' => ItemCode::STATUS_SUBMITTED,
        ]);

        $this->actingAs($ilyas)
            ->get(route('item-code.price-review.index'))
            ->assertOk()
            ->assertSee('QUEUE-ELIGIBLE')
            ->assertDontSee('QUEUE-WRONG-CREATOR')
            ->assertDontSee('QUEUE-ALREADY-SUBMITTED')
            ->assertSee('data-price-review-confirmation', false)
            ->assertSee('data-review-action="confirm"', false)
            ->assertSee('data-review-action="return"', false)
            ->assertSee('Swal.fire({', false)
            ->assertDontSee('onsubmit="return window.confirm', false);

        foreach ([$vivian, $mamik, $administrator] as $unauthorized) {
            $this->actingAs($unauthorized)
                ->get(route('item-code.price-review.index'))
                ->assertForbidden();
        }

        $this->actingAs($jessica)
            ->get(route('item-code.approval', ['tab' => ItemCode::TYPE_NEW_PRODUCT]))
            ->assertOk()
            ->assertDontSee('QUEUE-ELIGIBLE');

        $this->actingAs($jessica)
            ->post(route('item-code.approve', $eligible))
            ->assertForbidden();
    }

    public function test_ilyas_can_confirm_valid_price_and_only_for_eligible_mamik_items(): void
    {
        $mamik = $this->user('MAMIK ABIDIN');
        $ilyas = $this->user('ILYAS NOOR FIRDAUS');
        $vivian = $this->user('VIVIAN ANGELIKA');
        $jessica = $this->user('JESSICA PAUNE');
        $martinus = $this->user('MARTINUS CAHYO RAHASTO');
        $adhi = $this->user('ADHI PRASETIYO');

        $item = $this->item($mamik, [
            'product_code' => 'CONFIRM-PRICE',
            'status' => ItemCode::STATUS_PENDING_PRICE_REVIEW,
            'price_per_pcs' => null,
            'approved_by' => $jessica->id,
            'approved2_by' => $martinus->id,
            'finished_by' => $adhi->id,
        ]);

        $this->actingAs($ilyas)
            ->post(route('item-code.price-review.confirm', $item), [
                'price_per_pcs' => '10.123',
            ])
            ->assertSessionHasErrors('price_per_pcs');

        $this->actingAs($ilyas)
            ->post(route('item-code.price-review.confirm', $item), [
                'price_per_pcs' => '-0.01',
            ])
            ->assertSessionHasErrors('price_per_pcs');

        $this->assertSame(ItemCode::STATUS_PENDING_PRICE_REVIEW, $item->refresh()->status);

        $this->actingAs($vivian)
            ->post(route('item-code.price-review.confirm', $item), [
                'price_per_pcs' => '125.50',
            ])
            ->assertForbidden();

        $this->actingAs($ilyas)
            ->post(route('item-code.price-review.confirm', $item), [
                'price_per_pcs' => '125.50',
            ])
            ->assertRedirect(route('item-code.price-review.index'));

        $item->refresh();
        $this->assertSame(ItemCode::STATUS_SUBMITTED, $item->status);
        $this->assertSame('125.50', $item->price_per_pcs);
        $this->assertSame($ilyas->id, $item->price_reviewed_by);
        $this->assertNotNull($item->price_reviewed_at);
        $this->assertNull($item->approved_by);
        $this->assertNull($item->approved2_by);
        $this->assertNull($item->finished_by);

        $history = TrsItemCodeHistory::query()
            ->where('item_code_id', $item->id)
            ->where('action', ItemCodeHistoryService::ACTION_PRICE_REVIEW_COMPLETED)
            ->sole();
        $this->assertSame(ItemCode::STATUS_PENDING_PRICE_REVIEW, $history->status_from);
        $this->assertSame(ItemCode::STATUS_SUBMITTED, $history->status_to);
        $this->assertSame($ilyas->id, $history->actor_id);
        $this->assertSame(
            ['price_per_pcs', 'price_reviewed_by', 'price_reviewed_at', 'status'],
            collect($history->change_set)->pluck('field')->all()
        );

        $this->actingAs($jessica)
            ->get(route('item-code.approval', ['tab' => ItemCode::TYPE_NEW_PRODUCT]))
            ->assertOk()
            ->assertSee('CONFIRM-PRICE');

        $wrongCreator = $this->item($vivian, [
            'status' => ItemCode::STATUS_PENDING_PRICE_REVIEW,
        ]);
        $this->actingAs($ilyas)
            ->post(route('item-code.price-review.confirm', $wrongCreator), [
                'price_per_pcs' => '10.00',
            ])
            ->assertForbidden();
    }

    public function test_ilyas_can_return_an_item_without_losing_price_attachment_or_audit(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('item-code-attachments/review.pdf', 'pdf');

        $mamik = $this->user('MAMIK ABIDIN');
        $ilyas = $this->user('ILYAS NOOR FIRDAUS');
        $item = $this->item($mamik, [
            'product_code' => 'RETURN-TO-MAMIK',
            'status' => ItemCode::STATUS_PENDING_PRICE_REVIEW,
            'price_per_pcs' => '200.00',
            'price_reviewed_by' => $ilyas->id,
            'price_reviewed_at' => now()->subHour(),
            'attachment' => 'item-code-attachments/review.pdf',
        ]);
        $reviewedAt = $item->price_reviewed_at?->format('Y-m-d H:i:s');

        $this->actingAs($ilyas)
            ->post(route('item-code.price-review.return', $item), [
                'return_reason' => 'x',
            ])
            ->assertSessionHasErrors('return_reason');

        $this->actingAs($ilyas)
            ->post(route('item-code.price-review.return', $item), [
                'return_reason' => Str::repeat('x', 501),
            ])
            ->assertSessionHasErrors('return_reason');

        $this->actingAs($ilyas)
            ->post(route('item-code.price-review.return', $item), [
                'return_reason' => 'Deskripsi produk perlu diperbaiki.',
            ])
            ->assertRedirect(route('item-code.price-review.index'));

        $item->refresh();
        $this->assertSame(ItemCode::STATUS_DRAFT, $item->status);
        $this->assertSame('200.00', $item->price_per_pcs);
        $this->assertSame($ilyas->id, $item->price_reviewed_by);
        $this->assertSame($reviewedAt, $item->price_reviewed_at?->format('Y-m-d H:i:s'));
        $this->assertSame('item-code-attachments/review.pdf', $item->attachment);
        Storage::disk('local')->assertExists($item->attachment);

        $history = TrsItemCodeHistory::query()
            ->where('item_code_id', $item->id)
            ->where('action', ItemCodeHistoryService::ACTION_PRICE_REVIEW_RETURNED)
            ->sole();
        $this->assertStringContainsString(
            'Deskripsi produk perlu diperbaiki.',
            json_encode($history->change_set)
        );

        $this->actingAs($mamik)
            ->post(route('item-code.submit', $item))
            ->assertRedirect();

        $this->assertSame(ItemCode::STATUS_PENDING_PRICE_REVIEW, $item->refresh()->status);
        $this->assertSame('200.00', $item->price_per_pcs);
    }

    public function test_rejected_approved_items_always_return_to_price_review_on_resubmit(): void
    {
        $mamik = $this->user('MAMIK ABIDIN');
        $jessica = $this->user('JESSICA PAUNE');
        $martinus = $this->user('MARTINUS CAHYO RAHASTO');

        foreach ([ItemCode::STATUS_APPROVED_1, ItemCode::STATUS_APPROVED_2] as $status) {
            $item = $this->item($mamik, [
                'status' => $status,
                'price_per_pcs' => '300.00',
                'price_reviewed_by' => $this->user('ILYAS NOOR FIRDAUS')->id,
                'price_reviewed_at' => now()->subDay(),
                'approved_by' => $jessica->id,
                'approved2_by' => $status === ItemCode::STATUS_APPROVED_2 ? $martinus->id : null,
            ]);

            $this->actingAs($jessica)
                ->post(route('item-code.reject', $item), [
                    'reject_reason' => 'Perlu diperiksa kembali.',
                ])
                ->assertRedirect();

            $this->assertSame(ItemCode::STATUS_DRAFT, $item->refresh()->status);
            $this->assertSame('300.00', $item->price_per_pcs);

            $this->actingAs($mamik)
                ->post(route('item-code.submit', $item))
                ->assertRedirect();

            $this->assertSame(ItemCode::STATUS_PENDING_PRICE_REVIEW, $item->refresh()->status);
            $this->assertSame('300.00', $item->price_per_pcs);
        }
    }

    public function test_null_price_ui_export_state_machine_and_schema_contract_are_safe(): void
    {
        $mamik = $this->user('MAMIK ABIDIN');
        $item = $this->item($mamik, [
            'product_code' => 'NULL-PRICE-UI',
            'status' => ItemCode::STATUS_PENDING_PRICE_REVIEW,
            'price_per_pcs' => null,
        ]);

        $this->actingAs($mamik)
            ->get(route('item-code.form', [
                'tab' => ItemCode::TYPE_NEW_PRODUCT,
                'status' => ItemCode::STATUS_PENDING_PRICE_REVIEW,
            ]))
            ->assertOk()
            ->assertSee('NULL-PRICE-UI')
            ->assertSee('Menunggu Input Harga')
            ->assertSee('Harga akan diperiksa dan diisi oleh ILYAS NOOR FIRDAUS')
            ->assertSee('"price_per_pcs":null', false);

        $mapped = (new ItemCodeExport(collect([$item->load('creator')])))->map($item);
        $this->assertNull($mapped[2]);
        $this->assertSame('Menunggu Input Harga', $mapped[15]);

        $item->status = ItemCode::STATUS_APPROVED_1;
        $this->assertTrue($item->canTransitionTo(ItemCode::STATUS_CANCELLED));
        $item->status = ItemCode::STATUS_PENDING_PRICE_REVIEW;
        $this->assertFalse($item->canTransitionTo(ItemCode::STATUS_APPROVED_1));

        $cancelled = $this->item($mamik, [
            'status' => ItemCode::STATUS_CANCELLED,
            'cancelled_by' => $this->user('ILYAS NOOR FIRDAUS')->id,
            'cancelled_at' => now(),
        ]);
        $this->actingAs($mamik)
            ->post(route('item-code.submit', $cancelled))
            ->assertForbidden();
        $this->assertSame(ItemCode::STATUS_CANCELLED, $cancelled->refresh()->status);

        $statusColumn = DB::selectOne("SHOW COLUMNS FROM item_codes WHERE Field = 'status'");
        $priceColumn = DB::selectOne("SHOW COLUMNS FROM item_codes WHERE Field = 'price_per_pcs'");
        $this->assertStringContainsString('pending_price_review', (string) $statusColumn->Type);
        $this->assertStringContainsString('cancelled', (string) $statusColumn->Type);
        $this->assertSame('YES', (string) $priceColumn->Null);
        $this->assertTrue(Schema::hasColumn('item_codes', 'price_reviewed_by'));
        $this->assertTrue(Schema::hasColumn('item_codes', 'price_reviewed_at'));

        /** @var \Illuminate\Database\Migrations\Migration $migration */
        $migration = require database_path(
            'migrations/2026_07_30_000001_add_price_review_workflow_to_item_codes_table.php'
        );

        try {
            $migration->down();
            $this->fail('Rollback harus ditolak saat status pending_price_review masih ada.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('pending_price_review', $exception->getMessage());
        }

        $item->forceFill(['status' => ItemCode::STATUS_DRAFT])->save();

        try {
            $migration->down();
            $this->fail('Rollback harus ditolak saat price_per_pcs NULL masih ada.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('price_per_pcs NULL', $exception->getMessage());
        }
    }

    private function payload(array $overrides = []): array
    {
        $this->sequence++;

        return array_merge([
            'nomor_pengajuan' => null,
            'type' => ItemCode::TYPE_NEW_PRODUCT,
            'category' => 'Material',
            'supplier' => 'Supplier Test',
            'product_code' => 'ITEM-' . $this->sequence . '-' . Str::upper(Str::random(5)),
            'description' => 'Produk pengujian',
            'qty' => 1,
            'unit' => 'PCS',
            'price_per_pcs' => '100.00',
            'currency' => 'IDR',
            'tanggal' => now()->toDateString(),
            'reason_new_price' => 'Kebutuhan pengujian',
        ], $overrides);
    }

    private function updatePricePayload(array $overrides = []): array
    {
        return array_merge($this->payload([
            'type' => ItemCode::TYPE_UPDATE_PRICE,
            'tanggal_lama' => now()->subDay()->toDateString(),
            'harga_baru' => '110.00',
            'tanggal_harga_baru' => now()->toDateString(),
            'reason_new_price' => 'Penyesuaian harga',
        ]), $overrides);
    }

    private function item(User $creator, array $overrides = []): ItemCode
    {
        $this->sequence++;

        return ItemCode::query()->create(array_merge([
            'nomor_pengajuan' => sprintf(
                '%04d/IC/PROC/%s/%s',
                $this->sequence,
                now()->format('m'),
                now()->format('y')
            ),
            'type' => ItemCode::TYPE_NEW_PRODUCT,
            'category' => 'Material',
            'supplier' => 'Supplier Test',
            'product_code' => 'ITEM-' . $this->sequence . '-' . Str::upper(Str::random(5)),
            'description' => 'Produk pengujian',
            'qty' => 1,
            'unit' => 'PCS',
            'price_per_pcs' => '100.00',
            'currency' => 'IDR',
            'tanggal' => now()->toDateString(),
            'tanggal_lama' => null,
            'harga_baru' => null,
            'reason_new_price' => 'Kebutuhan pengujian',
            'attachment' => null,
            'selisih' => null,
            'tanggal_harga_baru' => null,
            'status' => ItemCode::STATUS_DRAFT,
            'created_by' => $creator->id,
            'price_reviewed_by' => null,
            'price_reviewed_at' => null,
            'approved_by' => null,
            'approved2_by' => null,
            'finished_by' => null,
            'cancelled_by' => null,
            'cancelled_at' => null,
        ], $overrides));
    }

    private function user(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => Str::uuid() . '@example.test',
        ]);
    }
}
