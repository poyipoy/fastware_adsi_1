<?php

namespace App\Services\Warehouse;

use App\Data\Warehouse\WarehouseStockCommand;
use App\Data\Warehouse\WarehouseStockResult;
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
        $idempotencyKey = $command->idempotencyKey ?: (string) Str::uuid();

        if (! Str::isUuid($idempotencyKey)) {
            throw new WarehouseDomainException('Idempotency key harus berupa UUID.');
        }

        return DB::transaction(function () use ($command, $idempotencyKey): WarehouseStockResult {
            $actorId = $command->createdBy ?? $command->verifiedUserId;
            $actor = User::query()->lockForUpdate()->find($actorId);

            if (! $this->access->isLoginEnabled($actor)) {
                throw new WarehouseDomainException('Actor transaksi tidak aktif atau tidak ditemukan.', 403);
            }

            $this->authorizeType($actor, $command->type);
            $this->validateTransactionFields($command);

            $existing = WarehouseStockTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if (! $this->matchesReplay($existing, $command)) {
                    throw new WarehouseDomainException('Idempotency key sudah digunakan untuk payload berbeda.', 409);
                }

                return new WarehouseStockResult($existing, true);
            }

            $verifiedUser = User::query()->lockForUpdate()->find($command->verifiedUserId);

            if (! $this->access->isLoginEnabled($verifiedUser)) {
                throw new WarehouseDomainException('User terverifikasi tidak aktif atau tidak ditemukan.', 422);
            }

            $original = null;
            if ($command->type === WarehouseTransactionType::REVERSAL) {
                if ($command->reversalOfId === null) {
                    throw new WarehouseDomainException('Transaksi reversal harus memiliki transaksi asal.');
                }

                $original = WarehouseStockTransaction::query()
                    ->lockForUpdate()
                    ->find($command->reversalOfId);

                if ($original === null) {
                    throw new WarehouseDomainException('Transaksi asal tidak ditemukan.', 404);
                }

                if (WarehouseStockTransaction::query()->where('reversal_of_id', $original->getKey())->exists()) {
                    throw new WarehouseDomainException('Transaksi tersebut sudah pernah direverse.', 409);
                }
            }

            $verificationDirection = $this->verifierPolicy->directionForCommand($command, $original);
            $this->verifierPolicy->assertUserCanVerify($verifiedUser, $verificationDirection);

            $consumableId = $original?->consumable_id ?? $command->consumableId;
            $item = WarehouseConsumable::query()->lockForUpdate()->find($consumableId);

            if ($item === null || ! $item->is_active) {
                throw new WarehouseDomainException('Consumable tidak aktif atau tidak ditemukan.', 422);
            }

            $requestedStorageLocation = $this->commandStorageLocation($command);
            $storageLocation = $command->type === WarehouseTransactionType::IN
                ? $this->normalizedStorageLocation($requestedStorageLocation)
                : null;

            $quantity = $original
                ? WarehouseQuantity::normalize((string) $original->quantity, (bool) $item->allow_fraction)
                : WarehouseQuantity::normalize($command->quantity, (bool) $item->allow_fraction);

            $stockBefore = WarehouseQuantity::fromMilli(WarehouseQuantity::toMilli((string) $item->current_stock));
            $delta = $this->deltaMilli($command, $quantity, $original);
            $stockAfterMilli = WarehouseQuantity::toMilli($stockBefore) + $delta;

            if ($stockAfterMilli < 0) {
                throw new WarehouseDomainException('Stok tidak mencukupi.', 422);
            }

            $stockAfter = WarehouseQuantity::fromMilli($stockAfterMilli);
            $transaction = WarehouseStockTransaction::query()->create([
                'transaction_number' => $this->numberGenerator->generate(),
                'idempotency_key' => $idempotencyKey,
                'transaction_type' => $command->type->value,
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
                'usage_location' => $storageLocation,
                'notes' => $this->notes($command, $original),
                'reversal_of_id' => $original?->getKey(),
                'transaction_at' => now(),
                'created_by' => $actor->getKey(),
            ]);

            $itemUpdates = ['current_stock' => $stockAfter, 'updated_by' => $actor->getKey()];
            if ($command->type === WarehouseTransactionType::IN) {
                $itemUpdates['storage_location'] = $storageLocation;
            }
            $item->forceFill($itemUpdates)->save();

            if ($command->verificationCodeHash !== null) {
                WarehouseVerificationLog::query()->create([
                    'scanned_code_hash' => $command->verificationCodeHash,
                    'user_id' => $verifiedUser->getKey(),
                    'transaction_id' => $transaction->getKey(),
                    'status' => 'SUCCESS',
                    'verified_at' => now(),
                ]);
            }

            return new WarehouseStockResult($transaction->refresh());
        });
    }

    public function reverse(
        WarehouseStockTransaction $original,
        int $actorId,
        int $verifiedUserId,
        string $reason,
        ?string $idempotencyKey = null,
    ): WarehouseStockResult {
        $reason = trim($reason);

        if ($reason === '') {
            throw new WarehouseDomainException('Alasan reversal wajib diisi.');
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
    ): WarehouseStockResult {
        $direction = strtoupper(trim($direction));

        if (! in_array($direction, ['IN', 'OUT'], true)) {
            throw new WarehouseDomainException('Direction adjustment tidak valid.');
        }

        if (trim($reasonCategory) === '' || trim($reason) === '') {
            throw new WarehouseDomainException('Kategori dan alasan adjustment wajib diisi.');
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
        ));
    }

    private function authorizeType(User $actor, WarehouseTransactionType $type): void
    {
        $ability = match ($type) {
            WarehouseTransactionType::IN => 'warehouse.stock-in.create',
            WarehouseTransactionType::OUT => 'warehouse.stock-out.create',
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

    private function validateTransactionFields(WarehouseStockCommand $command): void
    {
        if ($command->type === WarehouseTransactionType::IN
            && config('warehouse.transaction.require_storage_location_for_in', true)
            && trim((string) $this->commandStorageLocation($command)) === '') {
            throw new WarehouseDomainException('Lokasi penyimpanan Stock In wajib diisi.');
        }

        if ($command->type !== WarehouseTransactionType::IN
            && trim((string) $this->commandStorageLocation($command)) !== '') {
            throw new WarehouseDomainException('Lokasi penyimpanan hanya boleh dikirim untuk Stock In.');
        }
    }

    private function commandStorageLocation(WarehouseStockCommand $command): ?string
    {
        return $command->storageLocation ?? $command->usageLocation;
    }

    private function normalizedStorageLocation(?string $location): ?string
    {
        $location = trim((string) $location);

        if ($location === '') {
            return null;
        }

        $allowedLocations = (array) config('warehouse.storage_locations', ['DS8', 'Deltamas']);
        if (! in_array($location, $allowedLocations, true)) {
            throw new WarehouseDomainException('Lokasi penyimpanan hanya boleh DS8 atau Deltamas.');
        }

        return mb_substr($location, 0, 120);
    }

    private function deltaMilli(
        WarehouseStockCommand $command,
        string $quantity,
        ?WarehouseStockTransaction $original,
    ): int {
        $milli = WarehouseQuantity::toMilli($quantity);

        if ($original !== null) {
            $originalDelta = WarehouseQuantity::toMilli((string) $original->stock_after)
                - WarehouseQuantity::toMilli((string) $original->stock_before);

            return -$originalDelta;
        }

        return match ($command->type) {
            WarehouseTransactionType::IN => $milli,
            WarehouseTransactionType::OUT => -$milli,
            WarehouseTransactionType::ADJUSTMENT => strtoupper((string) $command->adjustmentDirection) === 'OUT'
                ? -$milli
                : $milli,
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
        return $existing->transaction_type?->value === $command->type->value
            && (int) $existing->consumable_id === $command->consumableId
            && (int) $existing->verified_user_id === $command->verifiedUserId
            && WarehouseQuantity::compare((string) $existing->quantity, $command->quantity) === 0
            && (string) ($existing->reference_number ?? '') === (string) ($command->referenceNumber ?? '')
            && (string) ($existing->purpose ?? '') === (string) ($command->purpose ?? '')
            && (string) ($existing->usage_location ?? '') === (string) ($this->commandStorageLocation($command) ?? '')
            && (string) ($existing->notes ?? '') === $this->replayNotes($command)
            && (int) ($existing->reversal_of_id ?? 0) === (int) ($command->reversalOfId ?? 0);
    }

    private function replayNotes(WarehouseStockCommand $command): string
    {
        if ($command->type === WarehouseTransactionType::ADJUSTMENT) {
            return mb_substr(sprintf('[%s] %s', $command->adjustmentReasonCategory, $command->adjustmentReason), 0, 65535);
        }

        return (string) ($command->notes ?? '');
    }
}
