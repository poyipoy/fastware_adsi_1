<?php

namespace Tests\Feature\Warehouse;

use App\Enums\Warehouse\WarehouseTransactionType;
use App\Exports\WarehouseTransactionsExport;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;
use Illuminate\Support\Str;

class WarehouseExportTest extends WarehouseTestCase
{
    public function test_export_headings_and_rows_use_revision_three_display_fields(): void
    {
        $user = $this->createUser();
        $item = WarehouseConsumable::factory()->create(['item_code' => 'EXPORT-REV3']);
        $transaction = WarehouseStockTransaction::factory()->create([
            'transaction_type' => WarehouseTransactionType::OUT,
            'consumable_id' => $item->id,
            'verified_user_id' => $user->id,
            'created_by' => $user->id,
            'from_location' => 'DS8',
            'to_location' => null,
            'usage_location' => 'DS8',
        ])->load(['consumable', 'creator']);
        $export = new WarehouseTransactionsExport(collect([$transaction]));

        self::assertSame([
            'Transaction Time',
            'Transaction Number',
            'Type',
            'Condition',
            'Item Code',
            'Item',
            'Machine Type',
            'Unit',
            'Quantity',
            'Location',
            'Stock Before',
            'Stock After',
            'Employee',
            'Section',
            'Reference',
            'Purpose',
            'Created By',
            'Notes',
        ], $export->headings());
        self::assertCount(18, $export->map($transaction));
        self::assertSame('DS8', $export->map($transaction)[9]);
        self::assertSame($transaction->verified_user_name, $export->map($transaction)[12]);
        self::assertNotContains('Operation Key', $export->headings());
        self::assertNotContains('From Location', $export->headings());
        self::assertNotContains('To Location', $export->headings());
    }

    public function test_authorized_export_uses_snapshot_columns_and_xlsx_response(): void
    {
        $admin = $this->createUser(['role_id' => 1]);
        $item = WarehouseConsumable::factory()->create(['item_code' => 'EXPORT-1']);
        WarehouseStockTransaction::factory()->create(['transaction_type' => WarehouseTransactionType::IN, 'consumable_id' => $item->id, 'verified_user_id' => $admin->id, 'created_by' => $admin->id, 'transaction_number' => 'WH-EXPORT-'.Str::random(8)]);

        $response = $this->actingAs($admin)->get(route('warehouse.exports.transactions'));

        $response->assertOk();
        self::assertStringContainsString('xlsx', (string) $response->headers->get('content-disposition'));
    }

    public function test_authorized_department_employee_can_export(): void
    {
        $employee = $this->createUser();
        $this->actingAs($employee)->get(route('warehouse.exports.transactions'))->assertOk();
    }

    public function test_employee_outside_authorized_departments_cannot_export(): void
    {
        $employee = $this->createUser([], false);
        $this->createDepartmentPosition($employee, 'Human Resource', 'Export Outsider '.uniqid());

        $this->actingAs($employee)->get(route('warehouse.exports.transactions'))->assertForbidden();
    }
}
