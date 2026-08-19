<?php

namespace App\Services\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseLocationShipmentStatus;
use App\Exceptions\WarehouseDomainException;
use App\Models\User;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseLocationShipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

final class WarehouseLocationShipmentService
{
    public function __construct(
        private readonly WarehouseAccessService $access,
        private readonly WarehouseStockReservationService $reservations,
        private readonly WarehouseStockService $stockService,
    ) {
    }

    public function createShipment(
        User $sender,
        int $consumableId,
        WarehouseItemCondition|string $itemCondition,
        string $quantity,
        string $fromLocation,
        string $toLocation,
        ?string $notes,
        string $idempotencyKey,
    ): WarehouseLocationShipment {
        $this->assertUuid($idempotencyKey);
        $condition = $this->condition($itemCondition);
        $fromLocation = $this->location($fromLocation);
        $toLocation = $this->location($toLocation);
        if ($fromLocation === $toLocation) {
            throw new WarehouseDomainException('Lokasi asal dan tujuan harus berbeda.', 422);
        }
        if (! $this->access->can($sender, 'warehouse.location-shipment.create')) {
            throw new WarehouseDomainException('Actor tidak memiliki hak membuat Pengiriman Antar Lokasi.', 403);
        }

        return DB::transaction(function () use (
            $sender,
            $consumableId,
            $condition,
            $quantity,
            $fromLocation,
            $toLocation,
            $notes,
            $idempotencyKey,
        ): WarehouseLocationShipment {
            $existing = WarehouseLocationShipment::query()
                ->where('creation_idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();
            if ($existing !== null) {
                if (! $this->matchesCreationReplay($existing, $consumableId, $condition, $quantity, $fromLocation, $toLocation, $notes)) {
                    throw new WarehouseDomainException('Idempotency key Pengiriman sudah digunakan untuk payload berbeda.', 409);
                }

                return $existing->load(['consumable', 'sender']);
            }

            $item = WarehouseConsumable::query()->lockForUpdate()->find($consumableId);
            if ($item === null || ! $item->is_active) {
                throw new WarehouseDomainException('Consumable tidak aktif atau tidak ditemukan.', 422);
            }

            $normalizedQuantity = WarehouseQuantity::normalize($quantity, (bool) $item->allow_fraction);
            $this->reservations->assertAvailable($item, $fromLocation, $condition, $normalizedQuantity);

            $shipment = WarehouseLocationShipment::query()->create([
                'shipment_number' => $this->shipmentNumber(),
                'consumable_id' => $item->getKey(),
                'item_condition' => $condition->value,
                'quantity_sent' => $normalizedQuantity,
                'from_location' => $fromLocation,
                'to_location' => $toLocation,
                'status' => WarehouseLocationShipmentStatus::WAITING_VALIDATION->value,
                'sent_by_user_id' => $sender->getKey(),
                'sender_npk_snapshot' => $sender->npk === null ? null : (string) $sender->npk,
                'sender_name_snapshot' => mb_substr((string) $sender->name, 0, 180),
                'sender_notes' => $notes === null || trim($notes) === '' ? null : mb_substr(trim($notes), 0, 2000),
                'sent_at' => now(),
                'creation_idempotency_key' => $idempotencyKey,
            ]);

            return $shipment->load(['consumable', 'sender']);
        }, 3);
    }

    public function validateShipment(
        User $actor,
        WarehouseLocationShipment $shipment,
        User $validator,
        string $receivedQuantity,
        WarehouseItemCondition|string $receivedCondition,
        ?string $validationNotes,
        string $idempotencyKey,
        ?int $scannedConsumableId = null,
    ): WarehouseLocationShipment {
        $this->assertUuid($idempotencyKey);
        $condition = $this->condition($receivedCondition);

        if (! $this->access->can($actor, 'warehouse.location-shipment.validate')) {
            throw new WarehouseDomainException('Actor tidak memiliki hak menjalankan Validasi.', 403);
        }
        if (! $this->access->can($validator, 'warehouse.location-shipment.validate')) {
            throw new WarehouseDomainException('Validator tidak memiliki akses Warehouse.', 422);
        }

        return DB::transaction(function () use (
            $actor,
            $shipment,
            $validator,
            $receivedQuantity,
            $condition,
            $validationNotes,
            $idempotencyKey,
            $scannedConsumableId,
        ): WarehouseLocationShipment {
            $locked = WarehouseLocationShipment::query()->lockForUpdate()->find($shipment->getKey());
            if ($locked === null) {
                throw new WarehouseDomainException('Pengiriman tidak ditemukan.', 404);
            }

            if ((int) $locked->sent_by_user_id === (int) $validator->getKey()) {
                throw new WarehouseDomainException('Pengirim tidak boleh menjadi Validator pengiriman miliknya sendiri.', 422);
            }

            if ($locked->validation_idempotency_key === $idempotencyKey) {
                $replayItem = WarehouseConsumable::query()->lockForUpdate()->find($locked->consumable_id);
                if ($replayItem === null) {
                    throw new WarehouseDomainException('Consumable pengiriman tidak aktif atau tidak ditemukan.', 409);
                }
                $normalizedReplayQuantity = WarehouseQuantity::normalize($receivedQuantity, (bool) $replayItem->allow_fraction);
                $replayNotes = is_string($validationNotes) ? trim($validationNotes) : '';
                if ((int) $locked->validator_user_id !== (int) $validator->getKey()
                    || WarehouseQuantity::compare((string) $locked->received_quantity, $normalizedReplayQuantity) !== 0
                    || $locked->received_condition !== $condition
                    || (string) ($locked->validation_notes ?? '') !== $replayNotes) {
                    throw new WarehouseDomainException('Idempotency key Validasi sudah digunakan untuk payload berbeda.', 409);
                }

                return $locked->load(['consumable', 'sender', 'validator', 'stockTransaction']);
            }
            if (! $locked->canValidate()) {
                throw new WarehouseDomainException('Pengiriman sudah tidak menunggu Validasi.', 409);
            }

            $item = WarehouseConsumable::query()->lockForUpdate()->find($locked->consumable_id);
            if ($item === null || ! $item->is_active) {
                throw new WarehouseDomainException('Consumable pengiriman tidak aktif atau tidak ditemukan.', 409);
            }

            $normalizedReceived = WarehouseQuantity::normalize($receivedQuantity, (bool) $item->allow_fraction);
            $notes = is_string($validationNotes) ? trim($validationNotes) : '';
            $matches = $scannedConsumableId === null || $scannedConsumableId === (int) $locked->consumable_id;
            $matches = $matches
                && WarehouseQuantity::compare((string) $locked->quantity_sent, $normalizedReceived) === 0
                && $locked->item_condition === $condition;

            $common = [
                'validation_actor_user_id' => $actor->getKey(),
                'validator_user_id' => $validator->getKey(),
                'validator_npk_snapshot' => $validator->npk === null ? null : (string) $validator->npk,
                'validator_name_snapshot' => mb_substr((string) $validator->name, 0, 180),
                'received_quantity' => $normalizedReceived,
                'received_condition' => $condition->value,
                'validation_notes' => $notes === '' ? null : mb_substr($notes, 0, 2000),
                'validated_at' => now(),
                'validation_idempotency_key' => $idempotencyKey,
            ];

            if (! $matches) {
                if ($notes === '') {
                    throw new WarehouseDomainException('Catatan Validasi wajib diisi untuk hasil Tidak Sesuai.', 422);
                }

                $locked->forceFill($common + [
                    'status' => WarehouseLocationShipmentStatus::DISCREPANCY->value,
                ])->save();

                return $locked->fresh(['consumable', 'sender', 'validator']);
            }

            $this->reservations->assertAvailable(
                $item,
                (string) $locked->from_location,
                $locked->item_condition,
                (string) $locked->quantity_sent,
                (int) $locked->getKey(),
            );

            $transferKey = Uuid::uuid5($idempotencyKey, 'warehouse-location-shipment-transfer')->toString();
            $result = $this->stockService->transfer(
                actorId: (int) $actor->getKey(),
                verifiedUserId: (int) $validator->getKey(),
                consumableId: (int) $locked->consumable_id,
                quantity: (string) $locked->quantity_sent,
                itemCondition: $locked->item_condition,
                fromLocation: (string) $locked->from_location,
                toLocation: (string) $locked->to_location,
                notes: $notes === '' ? null : $notes,
                idempotencyKey: $transferKey,
                locationShipmentId: (int) $locked->getKey(),
            );

            $locked->forceFill($common + [
                'status' => WarehouseLocationShipmentStatus::VALIDATED->value,
                'stock_transaction_id' => $result->transaction->getKey(),
            ])->save();

            return $locked->fresh(['consumable', 'sender', 'validator', 'stockTransaction']);
        }, 3);
    }

    public function cancelShipment(
        User $actor,
        WarehouseLocationShipment $shipment,
        string $reason,
        string $idempotencyKey,
    ): WarehouseLocationShipment {
        $this->assertUuid($idempotencyKey);
        $reason = trim($reason);
        if ($reason === '') {
            throw new WarehouseDomainException('Alasan pembatalan wajib diisi.', 422);
        }
        if (! $this->access->can($actor, 'warehouse.location-shipment.cancel')) {
            throw new WarehouseDomainException('Actor tidak memiliki hak membatalkan Pengiriman.', 403);
        }

        return DB::transaction(function () use ($actor, $shipment, $reason, $idempotencyKey): WarehouseLocationShipment {
            $locked = WarehouseLocationShipment::query()->lockForUpdate()->find($shipment->getKey());
            if ($locked === null) {
                throw new WarehouseDomainException('Pengiriman tidak ditemukan.', 404);
            }
            if ($locked->cancellation_idempotency_key === $idempotencyKey) {
                if ((int) $locked->cancelled_by_user_id !== (int) $actor->getKey()
                    || (string) $locked->cancellation_reason !== $reason) {
                    throw new WarehouseDomainException('Idempotency key pembatalan sudah digunakan untuk payload berbeda.', 409);
                }

                return $locked->load(['consumable', 'sender', 'cancelledBy']);
            }
            if (! $locked->canCancel()) {
                throw new WarehouseDomainException('Pengiriman yang sudah selesai tidak dapat dibatalkan.', 409);
            }
            if ((int) $locked->sent_by_user_id !== (int) $actor->getKey()
                && ! $this->access->can($actor, 'warehouse.location-shipment.cancel')) {
                throw new WarehouseDomainException('Hanya pengirim atau petugas berwenang yang dapat membatalkan Pengiriman.', 403);
            }

            $locked->forceFill([
                'status' => WarehouseLocationShipmentStatus::CANCELLED->value,
                'cancelled_by_user_id' => $actor->getKey(),
                'cancelled_at' => now(),
                'cancellation_reason' => mb_substr($reason, 0, 2000),
                'cancellation_idempotency_key' => $idempotencyKey,
            ])->save();

            return $locked->fresh(['consumable', 'sender', 'cancelledBy']);
        }, 3);
    }

    private function matchesCreationReplay(
        WarehouseLocationShipment $existing,
        int $consumableId,
        WarehouseItemCondition $condition,
        string $quantity,
        string $fromLocation,
        string $toLocation,
        ?string $notes,
    ): bool {
        return (int) $existing->consumable_id === $consumableId
            && $existing->item_condition === $condition
            && WarehouseQuantity::compare((string) $existing->quantity_sent, $quantity) === 0
            && (string) $existing->from_location === $fromLocation
            && (string) $existing->to_location === $toLocation
            && (string) ($existing->sender_notes ?? '') === trim((string) ($notes ?? ''));
    }

    private function condition(WarehouseItemCondition|string $condition): WarehouseItemCondition
    {
        if ($condition instanceof WarehouseItemCondition) {
            return $condition;
        }

        $resolved = WarehouseItemCondition::tryFrom(strtoupper(trim($condition)));
        if ($resolved === null) {
            throw new WarehouseDomainException('Kondisi barang Pengiriman tidak valid.', 422);
        }

        return $resolved;
    }

    private function location(string $location): string
    {
        $location = trim($location);
        if (! in_array($location, (array) config('warehouse.storage_locations', ['DS8', 'Deltamas']), true)) {
            throw new WarehouseDomainException('Lokasi hanya boleh DS8 atau Deltamas.', 422);
        }

        return $location;
    }

    private function assertUuid(string $key): void
    {
        if (! Str::isUuid($key)) {
            throw new WarehouseDomainException('Idempotency key harus berupa UUID.', 422);
        }
    }

    private function shipmentNumber(): string
    {
        return 'SHP-'.now(config('app.timezone', 'Asia/Jakarta'))->format('Ymd-His').'-'.strtoupper(Str::random(6));
    }
}
