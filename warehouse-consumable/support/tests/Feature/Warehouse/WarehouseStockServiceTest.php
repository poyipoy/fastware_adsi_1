<?php

namespace Tests\Feature\Warehouse;

use App\Data\Warehouse\WarehouseStockCommand;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Exceptions\Warehouse\WarehouseDomainException;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseConsumableCategory;
use App\Models\Warehouse\WarehouseStockTransaction;
use App\Services\Warehouse\WarehouseAccessService;
use App\Services\Warehouse\WarehouseStockService;
use App\Services\Warehouse\WarehouseTransactionNumberGenerator;
use App\Services\Warehouse\WarehouseVerifierPolicy;

class WarehouseStockServiceTest extends WarehouseTestCase
{
    public function test_stock_out_locks_item_updates_snapshot_and_logs_hash(): void
    {
        $actor = $this->createUser(['role_id' => 1]);
        $verified = $this->createUser(['name' => 'Verified Employee']);
        $category = WarehouseConsumableCategory::query()->create(['code' => 'ELEC', 'name' => 'Electrical']);
        $item = WarehouseConsumable::query()->create([
            'category_id' => $category->getKey(),
            'item_code' => 'CNS-0001',
            'barcode' => '0000891234567890',
            'item_name' => 'Electrical Tape',
            'unit' => 'roll',
            'allow_fraction' => false,
            'current_stock' => '10.000',
            'minimum_stock' => '2.000',
            'is_active' => true,
        ]);

        $access = new WarehouseAccessService();
        $service = new WarehouseStockService($access, new WarehouseTransactionNumberGenerator(), new WarehouseVerifierPolicy($access));
        $result = $service->execute(new WarehouseStockCommand(
            type: WarehouseTransactionType::OUT,
            consumableId: (int) $item->getKey(),
            quantity: '3',
            verifiedUserId: (int) $verified->getKey(),
            purpose: 'Testing',
            createdBy: (int) $actor->getKey(),
            idempotencyKey: '11111111-1111-4111-8111-111111111111',
            verificationCodeHash: hash('sha256', 'CARD-TEST-1'),
        ));

        $this->assertFalse($result->idempotentReplay);
        $this->assertSame('7.000', (string) $result->transaction->stock_after);
        $this->assertSame('10.000', (string) $result->transaction->stock_before);
        $this->assertSame('7.000', (string) $item->refresh()->current_stock);
        $this->assertDatabaseHas('log_wh_verifications', [
            'transaction_id' => $result->transaction->getKey(),
            'status' => 'SUCCESS',
            'scanned_code_hash' => hash('sha256', 'CARD-TEST-1'),
        ]);
        $this->assertDatabaseMissing('log_wh_verifications', ['scanned_code_hash' => 'CARD-TEST-1']);
    }

    public function test_overdraw_is_rejected_without_creating_transaction(): void
    {
        $actor = $this->createUser(['role_id' => 1]);
        $verified = $this->createUser();
        $item = WarehouseConsumable::query()->create([
            'item_code' => 'CNS-OVERDRAW',
            'barcode' => 'OVERDRAW-001',
            'item_name' => 'Overdraw Item',
            'unit' => 'pcs',
            'current_stock' => '1.000',
            'minimum_stock' => '0.000',
            'is_active' => true,
        ]);

        $access = new WarehouseAccessService();
        $service = new WarehouseStockService($access, new WarehouseTransactionNumberGenerator(), new WarehouseVerifierPolicy($access));

        $this->expectException(WarehouseDomainException::class);
        $service->execute(new WarehouseStockCommand(
            type: WarehouseTransactionType::OUT,
            consumableId: (int) $item->getKey(),
            quantity: '2',
            verifiedUserId: (int) $verified->getKey(),
            purpose: 'Testing',
            createdBy: (int) $actor->getKey(),
            idempotencyKey: '22222222-2222-4222-8222-222222222222',
        ));

        $this->assertSame(0, WarehouseStockTransaction::query()->count());
    }

    public function test_idempotency_replay_returns_canonical_transaction(): void
    {
        $actor = $this->createUser(['role_id' => 1]);
        $verified = $this->createUser();
        $item = WarehouseConsumable::query()->create([
            'item_code' => 'CNS-IDEMP',
            'barcode' => 'IDEMP-001',
            'item_name' => 'Idempotent Item',
            'unit' => 'pcs',
            'current_stock' => '0.000',
            'minimum_stock' => '0.000',
            'is_active' => true,
        ]);
        $access = new WarehouseAccessService();
        $service = new WarehouseStockService($access, new WarehouseTransactionNumberGenerator(), new WarehouseVerifierPolicy($access));
        $command = new WarehouseStockCommand(
            type: WarehouseTransactionType::IN,
            consumableId: (int) $item->getKey(),
            quantity: '5',
            verifiedUserId: (int) $verified->getKey(),
            storageLocation: 'DS8',
            createdBy: (int) $actor->getKey(),
            idempotencyKey: '33333333-3333-4333-8333-333333333333',
        );

        $first = $service->execute($command);
        $second = $service->execute($command);

        $this->assertFalse($first->idempotentReplay);
        $this->assertTrue($second->idempotentReplay);
        $this->assertSame($first->transaction->getKey(), $second->transaction->getKey());
        $this->assertSame('5.000', (string) $item->refresh()->current_stock);
        $this->assertSame('DS8', (string) $item->storage_location);
        $this->assertSame('DS8', (string) $first->transaction->usage_location);
        $this->assertSame(1, WarehouseStockTransaction::query()->count());
    }

    public function test_inactive_actor_is_rejected(): void
    {
        $actor = $this->createUser(['role_id' => 1, 'is_active' => 1]);
        $verified = $this->createUser();
        $item = WarehouseConsumable::query()->create([
            'item_code' => 'CNS-INACTIVE',
            'barcode' => 'INACTIVE-001',
            'item_name' => 'Inactive Item',
            'unit' => 'pcs',
            'current_stock' => '0.000',
            'minimum_stock' => '0.000',
            'is_active' => true,
        ]);
        $access = new WarehouseAccessService();
        $service = new WarehouseStockService($access, new WarehouseTransactionNumberGenerator(), new WarehouseVerifierPolicy($access));

        $this->expectException(WarehouseDomainException::class);
        $service->execute(new WarehouseStockCommand(
            type: WarehouseTransactionType::IN,
            consumableId: (int) $item->getKey(),
            quantity: '1',
            verifiedUserId: (int) $verified->getKey(),
            createdBy: (int) $actor->getKey(),
            idempotencyKey: '44444444-4444-4444-8444-444444444444',
        ));
    }

    public function test_service_rechecks_verifier_access_inside_transaction(): void
    {
        $actor = $this->createUser(['role_id' => 1]);
        $verified = $this->createUser([], false);
        $this->createDepartmentPosition($verified, 'Human Resource', 'Service Outsider '.uniqid());
        $item = WarehouseConsumable::factory()->create(['current_stock' => '5.000']);
        $access = new WarehouseAccessService();
        $service = new WarehouseStockService($access, new WarehouseTransactionNumberGenerator(), new WarehouseVerifierPolicy($access));

        try {
            $service->execute(new WarehouseStockCommand(
                type: WarehouseTransactionType::OUT,
                consumableId: (int) $item->getKey(),
                quantity: '1',
                verifiedUserId: (int) $verified->getKey(),
                createdBy: (int) $actor->getKey(),
                idempotencyKey: '77777777-7777-4777-8777-777777777777',
            ));
            $this->fail('Stock Out must be rejected for a verifier without Warehouse access.');
        } catch (WarehouseDomainException $exception) {
            $this->assertSame('NPK karyawan tidak memiliki akses Warehouse untuk memverifikasi Stock Out.', $exception->getMessage());
        }

        $this->assertDatabaseHas('mst_wh_consumables', ['id' => $item->id, 'current_stock' => '5.000']);
        $this->assertDatabaseCount('trs_wh_stock_transactions', 0);
    }
}
