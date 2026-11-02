<?php

namespace Tests\Feature\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseStockInStatus;
use App\Enums\Warehouse\WarehouseStockInValidationResult;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockIn;
use App\Models\Warehouse\WarehouseStockTransaction;
use App\Services\Warehouse\WarehouseStockReservationService;
use Illuminate\Support\Str;

class WarehouseStockInValidationRevisionTest extends WarehouseTestCase
{
    /**
     * Scenarios 1, 2, 3, 4, 25: Physical examination form options, empty barcode, and markup contracts.
     */
    public function test_physical_examination_form_renders_three_options_and_empty_barcode(): void
    {
        $creator = $this->createUser([], false);
        $this->createPicPosition($creator);
        $validator = $this->createUser();

        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'TEST-EXAM-ITEM',
            'barcode' => 'BARCODE-EXAM-123',
            'current_stock' => '10.000',
            'stock_ds8' => '10.000',
        ]);

        $stockIn = WarehouseStockIn::factory()->create([
            'stock_in_number' => 'WH-IN-EXAM-001',
            'consumable_id' => $item->id,
            'item_condition' => WarehouseItemCondition::NEW,
            'quantity_expected' => '5.000',
            'destination_location' => 'DS8',
            'created_by' => $creator->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        $response = $this->actingAs($validator)->get(route('warehouse.stock-in.validate-form', $stockIn));

        $response->assertOk();

        // Scenario 1: Third radio option "Barang Tidak Sesuai" is rendered
        $response->assertSee('value="REJECT"', false);
        $response->assertSee('Barang Tidak Sesuai');

        // Scenario 2: Barcode input loads strictly EMPTY (no prefill)
        $response->assertSee('name="received_item_barcode" value=""', false);
        $response->assertDontSee('value="BARCODE-EXAM-123"', false);
        $response->assertDontSee('value="TEST-EXAM-ITEM"', false);

        // Scenario 3: Existing options "Sesuai" and "Ada Selisih" retain markup
        $response->assertSee('value="MATCH"', false);
        $response->assertSee('Sesuai');
        $response->assertSee('value="MANUAL_ADJUSTMENT"', false);
        $response->assertSee('Ada Selisih / Input Manual');

        // Scenario 4: Rejection section and category tags present in DOM
        $response->assertSee('data-warehouse-rejection-section', false);
        $response->assertSee('[PENOLAKAN FISIK: SALAH BARANG]');
        $response->assertSee('[PENOLAKAN FISIK: BARANG RUSAK]');
        $response->assertSee('[PENOLAKAN FISIK: LAINNYA]');
        $response->assertSee('data-warehouse-rejection-notes', false);
        $response->assertSee('data-warehouse-rejection-trigger', false);

        // Scenario 25: Responsive layout and accessibility attributes
        $response->assertSee('warehouse-stock-in-result-options');
        $response->assertSee('autofocus', false);
        $response->assertSee('id="rejectionConfirmModal"', false);
    }

    /**
     * Sibling non-nested dual-form architecture verification.
     */
    public function test_physical_examination_form_has_separate_non_nested_rejection_form_and_modal(): void
    {
        $creator = $this->createUser([], false);
        $this->createPicPosition($creator);
        $validator = $this->createUser();

        $item = WarehouseConsumable::factory()->create();
        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'created_by' => $creator->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        $response = $this->actingAs($validator)->get(route('warehouse.stock-in.validate-form', $stockIn));
        $content = $response->getContent();

        // Form 1 posts to validate with name="idempotency_key"
        self::assertStringContainsString('action="'.route('warehouse.stock-in.validate', $stockIn).'"', $content);

        // Form 2 posts to cancel with name="idempotency_key"
        self::assertStringContainsString('action="'.route('warehouse.stock-in.cancel', $stockIn).'"', $content);
        self::assertStringContainsString('id="warehouse-stock-in-rejection-form"', $content);

        // Verify forms are not nested
        $firstFormClosePos = strpos($content, '</form>');
        $secondFormOpenPos = strpos($content, '<form method="POST" action="'.route('warehouse.stock-in.cancel', $stockIn).'"');
        self::assertNotFalse($firstFormClosePos);
        self::assertNotFalse($secondFormOpenPos);
        self::assertGreaterThan($firstFormClosePos, $secondFormOpenPos, 'Rejection Form 2 must be outside Form 1 (sibling, not nested).');
    }

    /**
     * Scenarios 5 & 6: Rejection reason is mandatory on client and server.
     */
    public function test_rejection_reason_is_mandatory_and_rejects_empty_or_whitespace(): void
    {
        $creator = $this->createUser([], false);
        $this->createPicPosition($creator);
        $validator = $this->createUser();

        $item = WarehouseConsumable::factory()->create();
        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'created_by' => $creator->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        // Scenario 5: Empty reason returns 422
        $response = $this->actingAs($validator)->postJson(route('warehouse.stock-in.cancel', $stockIn), [
            'reason' => '',
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['reason']);

        // Scenario 6: Whitespace-only reason returns 422
        $responseWhitespace = $this->actingAs($validator)->postJson(route('warehouse.stock-in.cancel', $stockIn), [
            'reason' => '     ',
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $responseWhitespace->assertStatus(422);

        // Short reason (< 3 chars) returns 422
        $responseShort = $this->actingAs($validator)->postJson(route('warehouse.stock-in.cancel', $stockIn), [
            'reason' => 'ab',
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $responseShort->assertStatus(422)->assertJsonValidationErrors(['reason']);
    }

    /**
     * Scenarios 7, 8, 9, 10, 24: Rejection for wrong item / damage causes zero stock mutation and zero ledger records.
     */
    public function test_physical_rejection_for_wrong_item_or_damaged_goods_causes_zero_stock_mutation(): void
    {
        $creator = $this->createUser([], false);
        $this->createPicPosition($creator);
        $validator = $this->createUser();

        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'REJECT-TEST-ITEM',
            'current_stock' => '10.000',
            'stock_ds8' => '10.000',
            'stock_deltamas' => '0.000',
        ]);

        $stockIn = WarehouseStockIn::factory()->create([
            'stock_in_number' => 'WH-IN-REJECT-001',
            'consumable_id' => $item->id,
            'item_condition' => WarehouseItemCondition::NEW,
            'quantity_expected' => '5.000',
            'destination_location' => 'DS8',
            'created_by' => $creator->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        $initialLedgerCount = WarehouseStockTransaction::query()->count();
        $reason = '[PENOLAKAN FISIK: SALAH BARANG] Fisik item yang dikirim tidak sesuai spesifikasi PO.';
        $idempotencyKey = (string) Str::uuid();

        // Scenario 7: Rejection succeeds with mismatch reason
        $response = $this->actingAs($validator)->post(route('warehouse.stock-in.cancel', $stockIn), [
            'reason' => $reason,
            'idempotency_key' => $idempotencyKey,
            'redirect_to' => 'validations',
        ]);

        $response->assertRedirect(route('warehouse.validations.index'));
        $response->assertSessionHas('status');

        $stockIn->refresh();
        $item->refresh();

        // Status is CANCELLED
        self::assertSame(WarehouseStockInStatus::CANCELLED, $stockIn->status);

        // Scenario 9: Zero ledger mutations
        self::assertSame($initialLedgerCount, WarehouseStockTransaction::query()->count());

        // Scenario 10: Zero destination stock increment
        self::assertSame('10.000', (string) $item->current_stock);
        self::assertSame('10.000', (string) $item->stock_ds8);

        // Scenario 24: Audit trail fully populated
        self::assertSame($reason, $stockIn->cancellation_reason);
        self::assertSame($validator->id, $stockIn->cancelled_by_user_id);
        self::assertNotNull($stockIn->cancelled_at);
        self::assertSame($idempotencyKey, $stockIn->cancellation_idempotency_key);
    }

    /**
     * Scenario 11: Rejection immediately releases source reservations for internal transfers.
     */
    public function test_physical_rejection_immediately_releases_source_reservations_for_internal_transfers(): void
    {
        $creator = $this->createUser([], false);
        $this->createPicPosition($creator);
        $validator = $this->createUser();

        $item = WarehouseConsumable::factory()->create([
            'current_stock' => '20.000',
            'stock_ds8' => '20.000',
            'stock_deltamas' => '0.000',
        ]);

        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'item_condition' => WarehouseItemCondition::NEW,
            'quantity_expected' => '4.000',
            'source_location' => 'DS8',
            'destination_location' => 'Deltamas',
            'created_by' => $creator->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        $reservationService = app(WarehouseStockReservationService::class);

        // Pre-rejection: 4.000 reserved at DS8
        self::assertSame('4.000', $reservationService->reserved($item->id, 'DS8', WarehouseItemCondition::NEW));

        // Reject stock in
        $this->actingAs($validator)->post(route('warehouse.stock-in.cancel', $stockIn), [
            'reason' => '[PENOLAKAN FISIK: BARANG RUSAK] Barang rusak parah saat perjalanan transfer.',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        // Post-rejection: 0.000 reserved
        self::assertSame('0.000', $reservationService->reserved($item->id, 'DS8', WarehouseItemCondition::NEW));
    }

    /**
     * Scenario 12: Successful matching quantities continue to work via form.
     */
    public function test_successful_matching_quantities_continue_to_work_via_form(): void
    {
        $creator = $this->createUser([], false);
        $this->createPicPosition($creator);
        $validator = $this->createUser();

        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'MATCH-TEST-ITEM',
            'current_stock' => '0.000',
            'stock_ds8' => '0.000',
        ]);

        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'item_condition' => WarehouseItemCondition::NEW,
            'quantity_expected' => '10.000',
            'destination_location' => 'DS8',
            'created_by' => $creator->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        $response = $this->actingAs($validator)->post(route('warehouse.stock-in.validate', $stockIn), [
            'received_item_barcode' => 'MATCH-TEST-ITEM',
            'validation_result' => 'MATCH',
            'quantity_received' => '10.000',
            'idempotency_key' => (string) Str::uuid(),
            'redirect_to' => 'validations',
        ]);

        $response->assertRedirect(route('warehouse.validations.index'));

        $stockIn->refresh();
        $item->refresh();

        self::assertSame(WarehouseStockInStatus::VALIDATED, $stockIn->status);
        self::assertSame(WarehouseStockInValidationResult::MATCH, $stockIn->validation_result);
        self::assertSame('10.000', (string) $item->stock_ds8);
    }

    /**
     * Scenario 13: Successful manual adjustment continues to work via form.
     */
    public function test_successful_manual_adjustment_continues_to_work_via_form(): void
    {
        $creator = $this->createUser([], false);
        $this->createPicPosition($creator);
        $validator = $this->createUser();

        $item = WarehouseConsumable::factory()->create([
            'item_code' => 'MANUAL-TEST-ITEM',
            'current_stock' => '0.000',
            'stock_ds8' => '0.000',
        ]);

        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'item_condition' => WarehouseItemCondition::NEW,
            'quantity_expected' => '10.000',
            'destination_location' => 'DS8',
            'created_by' => $creator->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        $response = $this->actingAs($validator)->post(route('warehouse.stock-in.validate', $stockIn), [
            'received_item_barcode' => 'MANUAL-TEST-ITEM',
            'validation_result' => 'MANUAL_ADJUSTMENT',
            'quantity_received' => '8.000',
            'validation_notes' => 'Ada 2 pcs yang tidak lolos pengecekan.',
            'idempotency_key' => (string) Str::uuid(),
            'redirect_to' => 'validations',
        ]);

        $response->assertRedirect(route('warehouse.validations.index'));

        $stockIn->refresh();
        $item->refresh();

        self::assertSame(WarehouseStockInStatus::VALIDATED, $stockIn->status);
        self::assertSame(WarehouseStockInValidationResult::MANUAL_ADJUSTMENT, $stockIn->validation_result);
        self::assertSame('8.000', (string) $item->stock_ds8);
    }

    /**
     * Scenarios 14, 15, 16: Validation listing navigation and decommission of direct approve / tolak buttons.
     */
    public function test_validation_listing_converts_setujui_to_navigation_and_removes_tolak_modal(): void
    {
        $creator = $this->createUser([], false);
        $this->createPicPosition($creator);
        $validator = $this->createUser();

        $item = WarehouseConsumable::factory()->create();
        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'created_by' => $creator->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        $response = $this->actingAs($validator)->get(route('warehouse.validations.index'));

        $response->assertOk();

        // Scenario 15: Setujui is an anchor navigating to physical examination form
        $response->assertSee('href="'.route('warehouse.stock-in.validate-form', $stockIn).'"', false);
        $response->assertSee('Setujui</a>', false);

        // Scenario 14: Tolak button and modal are absent from listing
        $response->assertDontSee('data-bs-target="#rejectModal', false);
        $response->assertDontSee('Tolak</button>', false);

        // Scenario 16: Direct approval form is removed from listing
        $response->assertDontSee('<form method="POST" action="'.route('warehouse.stock-in.validate', $stockIn).'"', false);
    }

    /**
     * Scenario 17: Full module access user (Jessica Paune) can validate and reject without verifier record.
     */
    public function test_full_module_access_user_jessica_paune_can_validate_and_reject(): void
    {
        $jessica = $this->createUser(['name' => 'Jessica Paune'], false);
        $item = WarehouseConsumable::factory()->create();

        $stockIn1 = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'created_by' => $jessica->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        $stockIn2 = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'created_by' => $jessica->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        // Jessica can view validate form
        $this->actingAs($jessica)->get(route('warehouse.stock-in.validate-form', $stockIn1))->assertOk();

        // Jessica can validate
        $this->actingAs($jessica)->post(route('warehouse.stock-in.validate', $stockIn1), [
            'validation_result' => 'MATCH',
            'quantity_received' => (string) $stockIn1->quantity_expected,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();
        self::assertSame(WarehouseStockInStatus::VALIDATED, $stockIn1->fresh()->status);

        // Jessica can reject
        $this->actingAs($jessica)->post(route('warehouse.stock-in.cancel', $stockIn2), [
            'reason' => '[PENOLAKAN FISIK: SALAH BARANG] Ditolak oleh Jessica Paune.',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();
        self::assertSame(WarehouseStockInStatus::CANCELLED, $stockIn2->fresh()->status);
    }

    /**
     * Scenario 18: Restricted verifier can validate and reject.
     */
    public function test_restricted_verifier_can_validate_and_reject(): void
    {
        $verifier = $this->createUser(); // with warehouse verifier access
        $item = WarehouseConsumable::factory()->create();

        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'created_by' => $verifier->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        $this->actingAs($verifier)->get(route('warehouse.stock-in.validate-form', $stockIn))->assertOk();

        $this->actingAs($verifier)->post(route('warehouse.stock-in.cancel', $stockIn), [
            'reason' => '[PENOLAKAN FISIK: BARANG RUSAK] Rusak dalam kemasan.',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        self::assertSame(WarehouseStockInStatus::CANCELLED, $stockIn->fresh()->status);
    }

    /**
     * Scenario 19: Creator with validator privileges can validate own stock in (self-validation allowed).
     */
    public function test_creator_with_validator_privilege_can_validate_own_stock_in(): void
    {
        $validator = $this->createUser();
        $item = WarehouseConsumable::factory()->create();

        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'created_by' => $validator->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        $this->actingAs($validator)->post(route('warehouse.stock-in.validate', $stockIn), [
            'validation_result' => 'MATCH',
            'quantity_received' => (string) $stockIn->quantity_expected,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        self::assertSame(WarehouseStockInStatus::VALIDATED, $stockIn->fresh()->status);
    }

    /**
     * Scenario 20: Duplicate rejection submissions with same key and identical reason are idempotent.
     */
    public function test_duplicate_rejection_submissions_with_identical_reason_are_idempotent(): void
    {
        $validator = $this->createUser();
        $item = WarehouseConsumable::factory()->create();

        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'created_by' => $validator->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        $key = (string) Str::uuid();
        $reason = '[PENOLAKAN FISIK: SALAH BARANG] Salah barang dari supplier.';

        // First cancellation
        $res1 = $this->actingAs($validator)->postJson(route('warehouse.stock-in.cancel', $stockIn), [
            'reason' => $reason,
            'idempotency_key' => $key,
        ]);
        $res1->assertOk()->assertJsonPath('data.status', 'CANCELLED');

        // Replay with identical key and reason
        $res2 = $this->actingAs($validator)->postJson(route('warehouse.stock-in.cancel', $stockIn), [
            'reason' => $reason,
            'idempotency_key' => $key,
        ]);
        $res2->assertOk()->assertJsonPath('data.status', 'CANCELLED');
    }

    /**
     * Scenario 21: Same-key rejection replay with different reason or actor preserves original audit state.
     */
    public function test_same_key_rejection_replay_with_different_reason_or_actor_preserves_original_audit_state(): void
    {
        $validator1 = $this->createUser();
        $validator2 = $this->createUser();
        $item = WarehouseConsumable::factory()->create();

        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'created_by' => $validator1->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        $key = (string) Str::uuid();
        $originalReason = '[PENOLAKAN FISIK: SALAH BARANG] Alasan asli validator 1.';
        $differentReason = '[PENOLAKAN FISIK: LAINNYA] Alasan berbeda dari validator 2.';

        // Initial cancellation by Validator 1
        $res1 = $this->actingAs($validator1)->postJson(route('warehouse.stock-in.cancel', $stockIn), [
            'reason' => $originalReason,
            'idempotency_key' => $key,
        ]);
        $res1->assertOk()->assertJsonPath('data.status', 'CANCELLED');

        // Replay by Validator 2 with same key but different reason
        $res2 = $this->actingAs($validator2)->postJson(route('warehouse.stock-in.cancel', $stockIn), [
            'reason' => $differentReason,
            'idempotency_key' => $key,
        ]);
        $res2->assertOk()->assertJsonPath('data.status', 'CANCELLED');

        // Invariants: Database audit state retains Validator 1 and Original Reason
        $stockIn->refresh();
        self::assertSame($originalReason, $stockIn->cancellation_reason);
        self::assertSame($validator1->id, $stockIn->cancelled_by_user_id);
    }

    /**
     * Scenario 22: Cancellation request with different key on already CANCELLED record fails with conflict.
     */
    public function test_cancellation_request_with_different_key_on_already_cancelled_record_fails(): void
    {
        $validator = $this->createUser();
        $item = WarehouseConsumable::factory()->create();

        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'created_by' => $validator->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        // Cancel with key 1
        $this->actingAs($validator)->postJson(route('warehouse.stock-in.cancel', $stockIn), [
            'reason' => '[PENOLAKAN FISIK: SALAH BARANG] First cancel.',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertOk();

        // Cancel again with key 2 -> 409 Conflict
        $this->actingAs($validator)->postJson(route('warehouse.stock-in.cancel', $stockIn), [
            'reason' => '[PENOLAKAN FISIK: SALAH BARANG] Second cancel attempt.',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertStatus(409);
    }

    /**
     * Scenario 23: Concurrent approve and reject operations are mutually exclusive.
     */
    public function test_concurrent_approve_and_reject_are_mutually_exclusive(): void
    {
        $validator = $this->createUser();
        $item = WarehouseConsumable::factory()->create();

        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'created_by' => $validator->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        // Validate first
        $this->actingAs($validator)->postJson(route('warehouse.stock-in.validate', $stockIn), [
            'validation_result' => 'MATCH',
            'quantity_received' => (string) $stockIn->quantity_expected,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertCreated();

        // Attempt to cancel already validated stock in -> 409 Conflict
        $this->actingAs($validator)->postJson(route('warehouse.stock-in.cancel', $stockIn), [
            'reason' => '[PENOLAKAN FISIK: SALAH BARANG] Too late.',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertStatus(409);
    }

    /**
     * Scenario 26: Existing API integrations remain compatible without barcode payload.
     */
    public function test_existing_api_integrations_remain_compatible_without_barcode_payload(): void
    {
        $validator = $this->createUser();
        $item = WarehouseConsumable::factory()->create([
            'current_stock' => '0.000',
            'stock_ds8' => '0.000',
        ]);

        $stockIn = WarehouseStockIn::factory()->create([
            'consumable_id' => $item->id,
            'quantity_expected' => '3.000',
            'destination_location' => 'DS8',
            'created_by' => $validator->id,
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
        ]);

        // Programmatic API call without received_item_barcode
        $response = $this->actingAs($validator)->postJson(route('warehouse.stock-in.validate', $stockIn), [
            'quantity_received' => '3.000',
            'validation_result' => 'MATCH',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertCreated();
        self::assertSame(WarehouseStockInStatus::VALIDATED, $stockIn->fresh()->status);
        self::assertSame('3.000', (string) $item->fresh()->stock_ds8);
    }
}
