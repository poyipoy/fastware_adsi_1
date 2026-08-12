<?php

namespace Tests\Feature\Warehouse;

use Illuminate\Support\Facades\DB;

class WarehouseUserVerificationTest extends WarehouseTestCase
{
    public function test_npk_scan_accepts_leading_zero_and_enter_or_tab_without_returning_input_code(): void
    {
        $npk = $this->freshNpk();
        $employee = $this->createUser(['name' => 'Verified Employee', 'npk' => $npk, 'section' => 'Assembly']);
        $operator = $this->createUser();
        $paddedNpk = str_pad((string) $npk, 10, '0', STR_PAD_LEFT);

        foreach (["{$paddedNpk}\t", " {$paddedNpk}\r\n"] as $scan) {
            $response = $this->actingAs($operator)->postJson(route('warehouse.scans.user'), [
                'code' => $scan,
                'type' => 'OUT',
            ]);

            $response->assertOk()
                ->assertJsonPath('data.id', $employee->id)
                ->assertJsonPath('data.name', 'Verified Employee')
                ->assertJsonPath('data.section', 'Assembly')
                ->assertJsonMissing(['code' => $scan])
                ->assertJsonMissing(['card_code' => $scan]);
            self::assertSame((string) $npk, (string) $response->json('data.npk'));
        }
    }

    public function test_eligible_employee_can_verify_both_stock_directions(): void
    {
        $npk = $this->freshNpk();
        $employee = $this->createUser(['name' => 'Warehouse Verifier', 'npk' => $npk]);
        $operator = $this->createUser();

        foreach (['IN', 'OUT'] as $type) {
            $this->actingAs($operator)->postJson(route('warehouse.scans.user'), [
                'code' => $npk."\n",
                'type' => $type,
            ])->assertOk()->assertJsonPath('data.id', $employee->id);
        }
    }

    public function test_unknown_numeric_npk_is_rejected_and_only_a_hash_is_logged(): void
    {
        $operator = $this->createUser();
        $raw = (string) $this->freshNpk();

        $response = $this->actingAs($operator)->postJson(route('warehouse.scans.user'), [
            'code' => $raw,
            'type' => 'OUT',
        ]);

        $response->assertNotFound()
            ->assertJsonPath('message', 'NPK karyawan tidak ditemukan atau tidak aktif.')
            ->assertJsonMissing(['code' => $raw]);
        $this->assertDatabaseHas('log_wh_verifications', [
            'status' => 'FAILED',
            'failure_reason' => 'Unknown or inactive employee NPK',
            'scanned_code_hash' => hash('sha256', $raw),
        ]);
    }

    public function test_non_numeric_and_zero_npk_are_rejected(): void
    {
        $operator = $this->createUser();

        $this->actingAs($operator)->postJson(route('warehouse.scans.user'), [
            'code' => 'NPK-5539',
            'type' => 'OUT',
        ])->assertUnprocessable()->assertJsonPath('message', 'Barcode NPK harus berisi angka.');

        $this->actingAs($operator)->postJson(route('warehouse.scans.user'), [
            'code' => '0000',
            'type' => 'OUT',
        ])->assertUnprocessable()->assertJsonPath('message', 'Barcode NPK tidak valid.');
    }

    public function test_inactive_or_unauthorized_employee_cannot_be_a_verifier(): void
    {
        $operator = $this->createUser();
        $inactive = $this->createUser(['npk' => $this->freshNpk(), 'is_active' => 1]);
        $outsider = $this->createUser(['npk' => $this->freshNpk()], false);
        $this->createDepartmentPosition($outsider, 'Human Resource', 'Verifier Outsider '.uniqid());

        $this->actingAs($operator)->postJson(route('warehouse.scans.user'), [
            'code' => (string) $inactive->npk,
            'type' => 'IN',
        ])->assertNotFound();

        $this->actingAs($operator)->postJson(route('warehouse.scans.user'), [
            'code' => (string) $outsider->npk,
            'type' => 'OUT',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'NPK karyawan tidak memiliki akses Warehouse untuk memverifikasi Stock Out.');
    }

    public function test_duplicate_npk_selects_the_only_active_administrator(): void
    {
        $npk = $this->freshNpk();
        $administrator = $this->createUser(['name' => 'Administrator Winner', 'npk' => $npk, 'role_id' => 1], false);
        $itStaff = $this->createUser(['name' => 'Duplicate IT Staff', 'npk' => $npk], false);
        $this->createDepartmentPosition($itStaff, 'PDCA, Inventory, Procurement & IT', 'IT Staff Duplicate '.uniqid());
        $operator = $this->createUser();

        $this->actingAs($operator)->postJson(route('warehouse.scans.user'), [
            'code' => str_pad((string) $npk, 10, '0', STR_PAD_LEFT)."\r\n",
            'type' => 'IN',
        ])->assertOk()
            ->assertJsonPath('data.id', $administrator->id)
            ->assertJsonPath('data.name', 'Administrator Winner');
    }

    public function test_duplicate_npk_without_one_unique_administrator_is_ambiguous(): void
    {
        $npk = $this->freshNpk();
        $first = $this->createUser(['npk' => $npk]);
        $this->createUser(['npk' => $npk]);
        $operator = $this->createUser();

        $response = $this->actingAs($operator)->postJson(route('warehouse.scans.user'), [
            'code' => (string) $first->npk,
            'type' => 'OUT',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'NPK terdaftar pada lebih dari satu user.')
            ->assertJsonMissing(['code' => (string) $first->npk]);
    }

    public function test_duplicate_npk_with_multiple_administrators_is_ambiguous(): void
    {
        $npk = $this->freshNpk();
        $this->createUser(['npk' => $npk, 'role_id' => 1], false);
        $this->createUser(['npk' => $npk, 'role_id' => 1], false);
        $operator = $this->createUser();

        $this->actingAs($operator)->postJson(route('warehouse.scans.user'), [
            'code' => (string) $npk,
            'type' => 'IN',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'NPK terdaftar pada lebih dari satu user.');
    }

    public function test_actor_without_warehouse_access_cannot_use_user_scan_endpoint(): void
    {
        $operator = $this->createUser([], false);
        $employee = $this->createUser();

        $this->actingAs($operator)->postJson(route('warehouse.scans.user'), [
            'code' => (string) $employee->npk,
            'type' => 'IN',
        ])->assertForbidden();
    }
}
