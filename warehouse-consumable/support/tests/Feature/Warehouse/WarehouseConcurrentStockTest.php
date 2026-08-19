<?php

namespace Tests\Feature\Warehouse;

use App\Data\Warehouse\WarehouseStockCommand;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Exceptions\WarehouseDomainException;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;
use App\Services\Warehouse\WarehouseAccessService;
use App\Services\Warehouse\WarehouseStockService;
use App\Services\Warehouse\WarehouseTransactionNumberGenerator;
use App\Services\Warehouse\WarehouseVerifierPolicy;

class WarehouseConcurrentStockTest extends WarehouseTestCase
{
    public function test_two_out_requests_cannot_overdraw_same_balance(): void
    {
        $actor = $this->createUser(['role_id' => 1]);
        $verified = $this->createUser();
        $item = WarehouseConsumable::query()->create([
            'item_code' => 'CNS-CONCURRENT',
            'barcode' => 'CONCURRENT-001',
            'item_name' => 'Concurrent Item',
            'unit' => 'pcs',
            'current_stock' => '5.000',
            'stock_ds8' => '5.000',
            'storage_location' => 'DS8',
            'minimum_stock' => '0.000',
            'is_active' => true,
        ]);
        $access = new WarehouseAccessService();
        $service = new WarehouseStockService($access, new WarehouseTransactionNumberGenerator(), new WarehouseVerifierPolicy($access));

        $service->execute(new WarehouseStockCommand(
            type: WarehouseTransactionType::OUT,
            consumableId: (int) $item->getKey(),
            quantity: '3',
            verifiedUserId: (int) $verified->getKey(),
            purpose: 'first',
            createdBy: (int) $actor->getKey(),
            idempotencyKey: '55555555-5555-4555-8555-555555555555',
            sourceLocation: 'DS8',
        ));

        try {
            $service->execute(new WarehouseStockCommand(
                type: WarehouseTransactionType::OUT,
                consumableId: (int) $item->getKey(),
                quantity: '3',
                verifiedUserId: (int) $verified->getKey(),
                purpose: 'second',
                createdBy: (int) $actor->getKey(),
                idempotencyKey: '66666666-6666-4666-8666-666666666666',
                sourceLocation: 'DS8',
            ));
            $this->fail('The second OUT should have been rejected.');
        } catch (WarehouseDomainException $exception) {
            $this->assertSame(422, $exception->status);
        }

        $this->assertSame('2.000', (string) $item->refresh()->current_stock);
        $this->assertSame(1, WarehouseStockTransaction::query()->count());
    }
}
