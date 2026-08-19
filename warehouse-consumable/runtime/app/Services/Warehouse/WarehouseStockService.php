<?php

namespace App\Services\Warehouse;

use App\Data\Warehouse\WarehouseStockCommand;
use App\Data\Warehouse\WarehouseStockResult;
use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Exceptions\WarehouseDomainException;
use App\Models\User;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockTransaction;
use App\Models\Warehouse\WarehouseVerificationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class WarehouseStockService
{
    public function __construct(
        private readonly WarehouseAccessService $access,
        private readonly WarehouseTransactionNumberGenerator $numberGenerator,
        private readonly WarehouseVerifierPolicy $verifierPolicy,
    ) {
    }

    public function execute(WarehouseStockCommand $command): WarehouseStockResult
    {
        $idempotencyKey = $this->idempotencyKey($command->idempotencyKey);

        return DB::transaction(
            fn (): WarehouseStockResult => $this->executeLocked($command, $idempotencyKey),
            3,
        );
    }

    public function executeWithUsedReturn(
        WarehouseStockCommand $primary,
        WarehouseStockCommand $usedReturn,
    ): WarehouseStockResult {
        if ($primary->type !== WarehouseTransactionType::OUT
            || ($primary->itemCondition ?? WarehouseItemCondition::NEW) !== WarehouseItemCondition::NEW
            || $usedReturn->type !== WarehouseTransactionType::IN
            || ($usedReturn->itemCondition ?? WarehouseItemCondition::NEW) !== WarehouseItemCondition::USED) {
            throw new WarehouseDomainException('Pasangan pengembalian barang bekas tidak valid.');
        }

        $primaryKey = $this->idempotencyKey($primary->idempotencyKey);
        $returnKey = $this->idempotencyKey($usedReturn->idempotencyKey);

        return DB::transaction(function () use ($primary, $usedReturn, $primaryKey, $returnKey): WarehouseStockResult {
            $userIds = collect([
                $primary->createdBy ?? $primary->verifiedUserId,
                $primary->verifiedUserId,
                $usedReturn->createdBy ?? $usedReturn->verifiedUserId,
                $usedReturn->verifiedUserId,
            ])->filter()->unique()->sort()->values();
            User::query()->whereIn('id', $userIds)->orderBy('id')->lockForUpdate()->get();

            WarehouseConsumable::query()
                ->whereIn('id', [$primary->consumableId, $usedReturn->consumableId])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $primaryResult = $this->executeLocked($primary, $primaryKey);
            $returnResult = $this->executeLocked($usedReturn, $returnKey);

            return new WarehouseStockResult(
                transaction: $primaryResult->transaction,
                idempotentReplay: $primaryResult->idempotentReplay && $returnResult->idempotentReplay,
                relatedTransactions: [$returnResult->transaction],
            );
        }, 3);
    }

    public function reverse(
        WarehouseStockTransaction $original,
        int $actorId,
        int $verifiedUserId,
        string $reason,
        ?string $idempotencyKey = null,
        ?string $legacyLocation = null,
    ): WarehouseStockResult {
        $reason = trim($reason);

        if ($reason === '') {
            throw new WarehouseDomainException('Alasan reversal wajib diisi.');
        }

        if ($original->transaction_type === WarehouseTransactionType::TRANSFER) {
            throw new WarehouseDomainException('Pengiriman Antar Lokasi tidak dapat dibatalkan. Buat pengiriman balik sebagai koreksi.', 422);
        }

        return $this->execute(new WarehouseStockCommand(
            type: WarehouseTransactionType::REVERSAL,
            consumableId: (int) $original->consumable_id,
            quantity: (string) $original->quantity,
            verifiedUserId: $verifiedUserId,
            notes: $reason,
            idempotencyKey: $idempotencyKey,
            createdBy: $actorId,
            reversalOfId: (int) $original->getKey(),
            legacyLocation: $legacyLocation,
        ));
    }

    public function adjust(
        int $actorId,
        int $verifiedUserId,
        int $consumableId,
        string $quantity,
        string $direction,
        string $reasonCategory,
        string $reason,
        ?string $idempotencyKey = null,
        WarehouseItemCondition|string $itemCondition = WarehouseItemCondition::NEW,
        ?string $storageLocation = null,
    ): WarehouseStockResult {
        $direction = strtoupper(trim($direction));

        if (! in_array($direction, ['IN', 'OUT'], true)) {
            throw new WarehouseDomainException('Direction adjustment tidak valid.');
        }

        if (trim($reasonCategory) === '' || trim($reason) === '') {
            throw new WarehouseDomainException('Kategori dan alasan adjustment wajib diisi.');
        }

        $condition = is_string($itemCondition)
            ? WarehouseItemCondition::tryFrom(strtoupper($itemCondition))
            : $itemCondition;
        if ($condition === null) {
            throw new WarehouseDomainException('Kondisi barang adjustment tidak valid.');
        }

        return $this->execute(new WarehouseStockCommand(
            type: WarehouseTransactionType::ADJUSTMENT,
            consumableId: $consumableId,
            quantity: $quantity,
            verifiedUserId: $verifiedUserId,
            notes: $reason,
            idempotencyKey: $idempotencyKey,
            createdBy: $actorId,
            adjustmentReasonCategory: $reasonCategory,
            adjustmentReason: $reason,
            adjustmentDirection: $direction,
            storageLocation: $direction === 'IN' ? $storageLocation : null,
            itemCondition: $condition,
            sourceLocation: $direction === 'OUT' ? $storageLocation : null,
        ));
    }

    public function transfer(
        int $actorId,
        int $verifiedUserId,
        int $consumableId,
        string $quantity,
        WarehouseItemCondition|string $itemCondition,
        string $fromLocation,
        string $toLocation,
        ?string $notes = null,
        ?string $idempotencyKey = null,
        ?int $locationShipmentId = null,
    ): WarehouseStockResult {
        $condition = is_string($itemCondition)
            ? WarehouseItemCondition::tryFrom(strtoupper($itemCondition))
            : $itemCondition;
        if ($condition === null) {
            throw new WarehouseDomainException('Kondisi barang Pengiriman Antar Lokasi tidak valid.');
        }

        return $this->execute(new WarehouseStockCommand(
            type: WarehouseTransactionType::TRANSFER,
            consumableId: $consumableId,
            quantity: $quantity,
            verifiedUserId: $verifiedUserId,
            notes: $notes,
            idempotencyKey: $idempotencyKey,
            createdBy: $actorId,
            itemCondition: $condition,
            sourceLocation: $fromLocation,
            toLocation: $toLocation,
            locationShipmentId: $locationShipmentId,
        ));
    }

    private function executeLocked(WarehouseStockCommand $command, string $idempotencyKey): WarehouseStockResult
    {
        $actorId = $command->createdBy ?? $command->verifiedUserId;
        $actor = User::query()->lockForUpdate()->find($actorId);

        if (! $this->access->isLoginEnabled($actor)) {
            throw new WarehouseDomainException('Actor transaksi tidak aktif atau tidak ditemukan.', 403);
        }

        $this->authorizeType($actor, $command->type);
        $this->validateIdentifiers($command);

        $existing = WarehouseStockTransaction::query()
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();

        if ($existing !== null) {
            if (! $this->matchesReplay($existing, $command)) {
                throw new WarehouseDomainException('Idempotency key sudah digunakan untuk payload berbeda.', 409);
            }

            return new WarehouseStockResult($existing->loadMissing('consumable'), true);
        }

        $verifiedUser = User::query()->lockForUpdate()->find($command->verifiedUserId);
        if (! $this->access->isLoginEnabled($verifiedUser)) {
            throw new WarehouseDomainException('User terverifikasi tidak aktif atau tidak ditemukan.', 422);
        }

        $original = $this->lockOriginal($command);
        $verificationDirection = $this->verifierPolicy->directionForCommand($command, $original);
        $this->verifierPolicy->assertUserCanVerify(
            $verifiedUser,
            $verificationDirection,
            $this->verifierPolicy->commandRequiresRestrictedVerifier($command, $original),
        );

        $consumableId = $original?->consumable_id ?? $command->consumableId;
        $item = WarehouseConsumable::query()->lockForUpdate()->find($consumableId);
        if ($item === null || ! $item->is_active) {
            throw new WarehouseDomainException('Consumable tidak aktif atau tidak ditemukan.', 422);
        }

        $condition = $original?->item_condition ?? $command->itemCondition ?? WarehouseItemCondition::NEW;
        $quantity = $original
            ? WarehouseQuantity::normalize((string) $original->quantity, (bool) $item->allow_fraction)
            : WarehouseQuantity::normalize($command->quantity, (bool) $item->allow_fraction);
        [$fromLocation, $toLocation] = $this->locations($command, $item, $original);

        $stockBefore = WarehouseQuantity::fromMilli(WarehouseQuantity::toMilli((string) $item->current_stock));
        $globalDelta = $this->deltaMilli($command, $quantity, $original);

        if ($command->type === WarehouseTransactionType::TRANSFER) {
            $this->applyLocationDelta(
                $item,
                (string) $fromLocation,
                $condition,
                -WarehouseQuantity::toMilli($quantity),
                $command->locationShipmentId,
            );
            $this->applyLocationDelta($item, (string) $toLocation, $condition, WarehouseQuantity::toMilli($quantity));
        } else {
            $movementLocation = $globalDelta >= 0 ? $toLocation : $fromLocation;
            $this->applyLocationDelta($item, (string) $movementLocation, $condition, $globalDelta);
        }

        $stockAfter = $this->syncCurrentStock($item);
        $expectedAfter = WarehouseQuantity::toMilli($stockBefore) + $globalDelta;
        if (WarehouseQuantity::toMilli($stockAfter) !== $expectedAfter) {
            throw new WarehouseDomainException('Integritas saldo lokasi tidak sesuai dengan stok total.', 409);
        }

        $transaction = WarehouseStockTransaction::query()->create([
            'transaction_number' => $this->numberGenerator->generate(),
            'idempotency_key' => $idempotencyKey,
            'operation_key' => $command->operationKey,
            'transaction_type' => $command->type->value,
            'item_condition' => $condition->value,
            'from_location' => $fromLocation,
            'to_location' => $toLocation,
            'consumable_id' => $item->getKey(),
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'verified_user_id' => $verifiedUser->getKey(),
            'verified_user_name' => mb_substr((string) $verifiedUser->name, 0, 180),
            'verified_user_npk' => $verifiedUser->npk === null ? null : (string) $verifiedUser->npk,
            'verified_user_section' => $verifiedUser->section === null ? null : mb_substr((string) $verifiedUser->section, 0, 120),
            'reference_number' => $command->referenceNumber,
            'purpose' => $command->purpose,
            'usage_location' => $toLocation ?? $fromLocation,
            'notes' => $this->notes($command, $original),
            'reversal_of_id' => $original?->getKey(),
            'location_shipment_id' => $command->locationShipmentId,
            'transaction_at' => now(),
            'created_by' => $actor->getKey(),
        ]);

        $updates = [
            'current_stock' => $stockAfter,
            'stock_deltamas' => $item->stock_deltamas,
            'stock_ds8' => $item->stock_ds8,
            'stock_used_deltamas' => $item->stock_used_deltamas,
            'stock_used_ds8' => $item->stock_used_ds8,
            'updated_by' => $actor->getKey(),
        ];
        $item->forceFill($updates)->save();

        if ($command->verificationCodeHash !== null) {
            WarehouseVerificationLog::query()->create([
                'scanned_code_hash' => $command->verificationCodeHash,
                'user_id' => $verifiedUser->getKey(),
                'transaction_id' => $transaction->getKey(),
                'status' => 'SUCCESS',
                'verified_at' => now(),
            ]);
        }

        return new WarehouseStockResult($transaction->load('consumable'));
    }

    private function lockOriginal(WarehouseStockCommand $command): ?WarehouseStockTransaction
    {
        if ($command->type !== WarehouseTransactionType::REVERSAL) {
            return null;
        }
        if ($command->reversalOfId === null) {
            throw new WarehouseDomainException('Transaksi reversal harus memiliki transaksi asal.');
        }

        $original = WarehouseStockTransaction::query()->lockForUpdate()->find($command->reversalOfId);
        if ($original === null) {
            throw new WarehouseDomainException('Transaksi asal tidak ditemukan.', 404);
        }
        if ($original->transaction_type === WarehouseTransactionType::TRANSFER) {
            throw new WarehouseDomainException('Pengiriman Antar Lokasi tidak dapat dibatalkan. Buat pengiriman balik sebagai koreksi.', 422);
        }
        if (WarehouseStockTransaction::query()->where('reversal_of_id', $original->getKey())->exists()) {
            throw new WarehouseDomainException('Transaksi tersebut sudah pernah direverse.', 409);
        }

        return $original;
    }

    /** @return array{0: ?string, 1: ?string} */
    private function locations(
        WarehouseStockCommand $command,
        WarehouseConsumable $item,
        ?WarehouseStockTransaction $original,
    ): array {
        if ($original !== null) {
            $originalDelta = WarehouseQuantity::toMilli((string) $original->stock_after)
                - WarehouseQuantity::toMilli((string) $original->stock_before);
            $location = $originalDelta > 0 ? $original->to_location : $original->from_location;
            $location = $this->normalizedLocation($location ?? $command->legacyLocation);
            if ($location === null) {
                throw new WarehouseDomainException('Lokasi transaksi lama wajib dipilih sebelum reversal.', 422);
            }

            return $originalDelta > 0 ? [$location, null] : [null, $location];
        }

        if ($command->type === WarehouseTransactionType::IN) {
            return [null, $this->requiredLocation($command->toLocation ?? $this->commandStorageLocation($command), 'Lokasi tujuan Stock In wajib diisi.')];
        }
        if ($command->type === WarehouseTransactionType::OUT) {
            return [$this->requiredLocation($command->sourceLocation, 'Lokasi asal Stock Out wajib diisi.'), null];
        }
        if ($command->type === WarehouseTransactionType::ADJUSTMENT) {
            $direction = $this->verifierPolicy->normalizeDirection((string) $command->adjustmentDirection);
            $location = $this->requiredLocation(
                $direction === 'IN'
                    ? ($command->toLocation ?? $this->commandStorageLocation($command))
                    : ($command->sourceLocation ?? $this->commandStorageLocation($command)),
                'Lokasi Adjustment wajib diisi.',
            );

            return $direction === 'IN' ? [null, $location] : [$location, null];
        }
        if ($command->type === WarehouseTransactionType::TRANSFER) {
            $from = $this->requiredLocation($command->sourceLocation, 'Lokasi asal Pengiriman Antar Lokasi wajib diisi.');
            $to = $this->requiredLocation($command->toLocation ?? $this->commandStorageLocation($command), 'Lokasi tujuan Pengiriman Antar Lokasi wajib diisi.');
            if ($from === $to) {
                throw new WarehouseDomainException('Lokasi asal dan tujuan Pengiriman Antar Lokasi harus berbeda.', 422);
            }

            return [$from, $to];
        }

        throw new WarehouseDomainException('Lokasi transaksi tidak dapat ditentukan.');
    }

    private function applyLocationDelta(
        WarehouseConsumable $item,
        string $location,
        WarehouseItemCondition $condition,
        int $deltaMilli,
        ?int $excludeShipmentId = null,
    ): void {
        if ($deltaMilli < 0) {
            app(WarehouseStockReservationService::class)->assertAvailable(
                $item,
                $location,
                $condition,
                WarehouseQuantity::fromMilli(abs($deltaMilli)),
                $excludeShipmentId,
            );
        }

        $totalColumn = $item->totalStockColumn($location);
        $usedColumn = $item->usedStockColumn($location);
        $totalAfter = WarehouseQuantity::toMilli((string) $item->getAttribute($totalColumn)) + $deltaMilli;
        $usedAfter = WarehouseQuantity::toMilli((string) $item->getAttribute($usedColumn))
            + ($condition === WarehouseItemCondition::USED ? $deltaMilli : 0);

        if ($totalAfter < 0 || $usedAfter < 0 || $usedAfter > $totalAfter) {
            $label = $condition === WarehouseItemCondition::USED ? 'bekas' : 'baru';
            throw new WarehouseDomainException("Stok {$label} di lokasi {$location} tidak mencukupi.", 422);
        }

        $item->setAttribute($totalColumn, WarehouseQuantity::fromMilli($totalAfter));
        $item->setAttribute($usedColumn, WarehouseQuantity::fromMilli($usedAfter));
    }

    private function syncCurrentStock(WarehouseConsumable $item): string
    {
        $totalMilli = WarehouseQuantity::toMilli((string) $item->stock_deltamas)
            + WarehouseQuantity::toMilli((string) $item->stock_ds8);
        if ($totalMilli < 0) {
            throw new WarehouseDomainException('Stok tidak mencukupi.', 422);
        }

        return WarehouseQuantity::fromMilli($totalMilli);
    }

    private function authorizeType(User $actor, WarehouseTransactionType $type): void
    {
        $ability = match ($type) {
            WarehouseTransactionType::IN => 'warehouse.stock-in.create',
            WarehouseTransactionType::OUT => 'warehouse.stock-out.create',
            WarehouseTransactionType::TRANSFER => 'warehouse.location-shipment.validate',
            WarehouseTransactionType::ADJUSTMENT => null,
            WarehouseTransactionType::REVERSAL => 'warehouse.transaction.reverse',
        };

        if ($type === WarehouseTransactionType::ADJUSTMENT) {
            if (! $this->access->canAdjust($actor)) {
                throw new WarehouseDomainException('Actor tidak memiliki hak adjustment.', 403);
            }

            return;
        }
        if (! $this->access->can($actor, (string) $ability)) {
            throw new WarehouseDomainException('Actor tidak memiliki hak transaksi ini.', 403);
        }
    }

    private function validateIdentifiers(WarehouseStockCommand $command): void
    {
        if ($command->operationKey !== null && ! Str::isUuid($command->operationKey)) {
            throw new WarehouseDomainException('Operation key harus berupa UUID.');
        }
    }

    private function commandStorageLocation(WarehouseStockCommand $command): ?string
    {
        return $command->storageLocation ?? $command->usageLocation;
    }

    private function requiredLocation(?string $location, string $message): string
    {
        $location = $this->normalizedLocation($location);
        if ($location === null) {
            throw new WarehouseDomainException($message, 422);
        }

        return $location;
    }

    private function normalizedLocation(?string $location): ?string
    {
        $location = trim((string) $location);
        if ($location === '') {
            return null;
        }
        if (! in_array($location, (array) config('warehouse.storage_locations', ['DS8', 'Deltamas']), true)) {
            throw new WarehouseDomainException('Lokasi hanya boleh DS8 atau Deltamas.', 422);
        }

        return $location;
    }

    private function deltaMilli(
        WarehouseStockCommand $command,
        string $quantity,
        ?WarehouseStockTransaction $original,
    ): int {
        $milli = WarehouseQuantity::toMilli($quantity);

        if ($original !== null) {
            return -(WarehouseQuantity::toMilli((string) $original->stock_after)
                - WarehouseQuantity::toMilli((string) $original->stock_before));
        }

        return match ($command->type) {
            WarehouseTransactionType::IN => $milli,
            WarehouseTransactionType::OUT => -$milli,
            WarehouseTransactionType::ADJUSTMENT => strtoupper((string) $command->adjustmentDirection) === 'OUT' ? -$milli : $milli,
            WarehouseTransactionType::TRANSFER => 0,
            WarehouseTransactionType::REVERSAL => throw new WarehouseDomainException('Reversal tidak lengkap.'),
        };
    }

    private function notes(WarehouseStockCommand $command, ?WarehouseStockTransaction $original): ?string
    {
        if ($command->type === WarehouseTransactionType::ADJUSTMENT) {
            return mb_substr(sprintf('[%s] %s', $command->adjustmentReasonCategory, $command->adjustmentReason), 0, 65535);
        }
        if ($original !== null) {
            return mb_substr((string) $command->notes, 0, 65535);
        }

        return $command->notes;
    }

    private function matchesReplay(WarehouseStockTransaction $existing, WarehouseStockCommand $command): bool
    {
        $condition = $command->itemCondition
            ?? ($command->type === WarehouseTransactionType::REVERSAL ? null : WarehouseItemCondition::NEW);
        $matches = $existing->transaction_type?->value === $command->type->value
            && (int) $existing->consumable_id === $command->consumableId
            && (int) $existing->verified_user_id === $command->verifiedUserId
            && WarehouseQuantity::compare((string) $existing->quantity, $command->quantity) === 0
            && (string) ($existing->reference_number ?? '') === (string) ($command->referenceNumber ?? '')
            && (string) ($existing->purpose ?? '') === (string) ($command->purpose ?? '')
            && (string) ($existing->notes ?? '') === $this->replayNotes($command)
            && (int) ($existing->reversal_of_id ?? 0) === (int) ($command->reversalOfId ?? 0)
            && (int) ($existing->location_shipment_id ?? 0) === (int) ($command->locationShipmentId ?? 0)
            && ($condition === null || ($existing->item_condition ?? WarehouseItemCondition::NEW) === $condition);

        if ($command->sourceLocation !== null) {
            $matches = $matches && (string) $existing->from_location === $command->sourceLocation;
        }
        $toLocation = $command->toLocation ?? $this->commandStorageLocation($command);
        if ($toLocation !== null) {
            $matches = $matches && (string) $existing->to_location === $toLocation;
        }
        if ($command->operationKey !== null) {
            $matches = $matches && (string) $existing->operation_key === $command->operationKey;
        }

        return $matches;
    }

    private function replayNotes(WarehouseStockCommand $command): string
    {
        if ($command->type === WarehouseTransactionType::ADJUSTMENT) {
            return mb_substr(sprintf('[%s] %s', $command->adjustmentReasonCategory, $command->adjustmentReason), 0, 65535);
        }

        return (string) ($command->notes ?? '');
    }

    private function idempotencyKey(?string $key): string
    {
        $key = $key ?: (string) Str::uuid();
        if (! Str::isUuid($key)) {
            throw new WarehouseDomainException('Idempotency key harus berupa UUID.');
        }

        return $key;
    }
}

final class WarehouseTransactionNumberGenerator
{
    public function generate(): string
    {
        return 'WH-'.now(config('app.timezone', 'Asia/Jakarta'))->format('Ymd-His').'-'.strtoupper(Str::random(8));
    }
}
