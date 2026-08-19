<?php

namespace App\Services\Warehouse;

use App\Data\Warehouse\WarehouseStockCommand;
use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseStockInStatus;
use App\Enums\Warehouse\WarehouseStockInValidationResult;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Exceptions\WarehouseDomainException;
use App\Models\User;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockIn;
use App\Models\Warehouse\WarehouseStockTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Stock In is a pending business record.  This service is the only place that
 * moves a pending record into the stock ledger, and it delegates the balance
 * mutation to WarehouseStockService.
 */
final class WarehouseStockInService
{
    public function __construct(
        private readonly WarehouseAccessService $access,
        private readonly WarehouseVerifierPolicy $verifierPolicy,
        private readonly WarehouseStockReservationService $reservations,
        private readonly WarehouseStockService $stockService,
    ) {
    }

    public function create(
        int $actorId,
        int $consumableId,
        WarehouseItemCondition|string $itemCondition,
        string $quantityExpected,
        string $destinationLocation,
        ?string $sourceLocation = null,
        ?string $notes = null,
        ?string $idempotencyKey = null,
    ): WarehouseStockIn {
        $this->assertAccess($actorId, 'warehouse.stock-in.create');
        $condition = $this->condition($itemCondition);
        $creationKey = $this->uuid($idempotencyKey);
        $destination = $this->location($destinationLocation, 'Lokasi tujuan Stock In wajib diisi.');
        $source = $sourceLocation === null || trim($sourceLocation) === ''
            ? null
            : $this->location($sourceLocation, 'Lokasi sumber Stock In tidak valid.');

        return DB::transaction(function () use (
            $actorId,
            $consumableId,
            $condition,
            $quantityExpected,
            $destination,
            $source,
            $notes,
            $creationKey,
        ): WarehouseStockIn {
            $existing = WarehouseStockIn::query()
                ->where('creation_idempotency_key', $creationKey)
                ->lockForUpdate()
                ->first();
            if ($existing !== null) {
                $this->assertCreationReplay($existing, $consumableId, $condition, $quantityExpected, $destination, $source, $notes);
                $existing->setAttribute('_idempotent_replay', true);

                return $existing->load(['consumable', 'creator']);
            }

            $actor = User::query()->lockForUpdate()->find($actorId);
            if (! $this->access->isLoginEnabled($actor)) {
                throw new WarehouseDomainException('Actor Stock In tidak aktif atau tidak ditemukan.', 403);
            }

            $item = WarehouseConsumable::query()->lockForUpdate()->find($consumableId);
            if ($item === null || ! $item->is_active) {
                throw new WarehouseDomainException('Consumable tidak aktif atau tidak ditemukan.', 422);
            }

            $quantity = WarehouseQuantity::normalize($quantityExpected, (bool) $item->allow_fraction);
            if ($source !== null) {
                if ($source === $destination) {
                    throw new WarehouseDomainException('Lokasi sumber dan tujuan Stock In harus berbeda.', 422);
                }

                // A source reservation is created by the pending record. It
                // does not change the stored source balance.
                $this->reservations->assertAvailable($item, $source, $condition, $quantity);
            }

            $stockIn = WarehouseStockIn::query()->create([
                'stock_in_number' => $this->number(),
                'creation_idempotency_key' => $creationKey,
                'status' => WarehouseStockInStatus::WAITING_VALIDATION->value,
                'consumable_id' => $item->getKey(),
                'item_condition' => $condition->value,
                'quantity_expected' => $quantity,
                'destination_location' => $destination,
                'source_location' => $source,
                'notes' => $this->text($notes),
                'created_by' => $actor->getKey(),
                'creator_npk_snapshot' => $actor->npk === null ? null : (string) $actor->npk,
                'creator_name_snapshot' => mb_substr((string) $actor->name, 0, 180),
            ]);

            $stockIn->setAttribute('_idempotent_replay', false);

            return $stockIn->load(['consumable', 'creator']);
        }, 3);
    }

    public function validate(
        int $actorId,
        WarehouseStockIn|int $stockIn,
        User|int $validator,
        string $quantityReceived,
        WarehouseStockInValidationResult|string|null $validationResult = null,
        ?string $validationNotes = null,
        WarehouseItemCondition|string|null $receivedCondition = null,
        ?int $receivedConsumableId = null,
        ?string $idempotencyKey = null,
        ?string $verificationCodeHash = null,
    ): WarehouseStockIn {
        $this->assertAccess($actorId, 'warehouse.stock-in.validate');
        $validationKey = $this->uuid($idempotencyKey);

        return DB::transaction(function () use (
            $actorId,
            $stockIn,
            $validator,
            $quantityReceived,
            $validationResult,
            $validationNotes,
            $receivedCondition,
            $receivedConsumableId,
            $validationKey,
            $verificationCodeHash,
        ): WarehouseStockIn {
            $existingByKey = WarehouseStockIn::query()
                ->where('validation_idempotency_key', $validationKey)
                ->lockForUpdate()
                ->first();
            if ($existingByKey !== null) {
                $this->assertValidationReplay(
                    $existingByKey,
                    $stockIn instanceof WarehouseStockIn ? (int) $stockIn->getKey() : (int) $stockIn,
                    $validator,
                    $quantityReceived,
                    $validationResult,
                    $validationNotes,
                    $receivedCondition,
                    $receivedConsumableId,
                );
                $existingByKey->setAttribute('_idempotent_replay', true);

                return $existingByKey->load(['consumable', 'creator', 'validator', 'stockTransaction']);
            }

            $record = WarehouseStockIn::query()
                ->lockForUpdate()
                ->find($stockIn instanceof WarehouseStockIn ? $stockIn->getKey() : $stockIn);
            if ($record === null) {
                throw new WarehouseDomainException('Stock In tidak ditemukan.', 404);
            }
            if (! $record->canValidate()) {
                throw new WarehouseDomainException('Stock In sudah tidak menunggu Validasi.', 409);
            }

            $actor = User::query()->lockForUpdate()->find($actorId);
            $validatorUser = $validator instanceof User
                ? User::query()->lockForUpdate()->find($validator->getKey())
                : User::query()->lockForUpdate()->find($validator);
            if (! $this->access->isLoginEnabled($actor)) {
                throw new WarehouseDomainException('Actor Validasi Stock In tidak aktif atau tidak ditemukan.', 403);
            }
            if (! $this->access->isLoginEnabled($validatorUser)) {
                throw new WarehouseDomainException('Validator Stock In tidak aktif atau tidak ditemukan.', 422);
            }

            // This is deliberately checked inside the transaction as well as
            // at the HTTP boundary so service callers cannot bypass the NPK
            // restricted-verifier policy.
            $this->verifierPolicy->assertUserCanVerify($validatorUser, WarehouseVerifierPolicy::DIRECTION_IN, true);

            $item = WarehouseConsumable::query()->lockForUpdate()->find($record->consumable_id);
            if ($item === null || ! $item->is_active) {
                throw new WarehouseDomainException('Consumable Stock In tidak aktif atau tidak ditemukan.', 422);
            }

            $receivedId = $receivedConsumableId ?? (int) $record->consumable_id;
            if ($receivedId !== (int) $record->consumable_id) {
                throw new WarehouseDomainException('Item fisik berbeda dari item Stock In. Validasi ditolak tanpa mutasi stok.', 422);
            }

            $expectedCondition = $record->item_condition instanceof WarehouseItemCondition
                ? $record->item_condition
                : WarehouseItemCondition::from((string) $record->item_condition);
            $actualCondition = $receivedCondition === null || trim((string) $receivedCondition) === ''
                ? $expectedCondition
                : $this->condition($receivedCondition);
            if ($actualCondition !== $expectedCondition) {
                throw new WarehouseDomainException('Condition fisik berbeda dari condition Stock In. Validasi ditolak tanpa mutasi stok.', 422);
            }

            $actual = WarehouseQuantity::normalize($quantityReceived, (bool) $item->allow_fraction);
            $expected = WarehouseQuantity::normalize((string) $record->quantity_expected, (bool) $item->allow_fraction);
            $difference = WarehouseQuantity::toMilli($actual) - WarehouseQuantity::toMilli($expected);
            $result = $validationResult === null || trim((string) $validationResult) === ''
                ? ($difference === 0 ? WarehouseStockInValidationResult::MATCH : WarehouseStockInValidationResult::MANUAL_ADJUSTMENT)
                : $this->validationResult($validationResult);

            if ($result === WarehouseStockInValidationResult::MATCH && $difference !== 0) {
                throw new WarehouseDomainException('Hasil Sesuai hanya dapat dipilih bila quantity fisik sama dengan quantity yang diharapkan.', 422);
            }
            $notes = $this->text($validationNotes);
            if (($difference !== 0 || $result === WarehouseStockInValidationResult::MANUAL_ADJUSTMENT) && $notes === null) {
                throw new WarehouseDomainException('Catatan Validasi wajib diisi untuk Input Manual atau quantity yang berbeda.', 422);
            }

            $type = $record->source_location === null
                ? WarehouseTransactionType::IN
                : WarehouseTransactionType::TRANSFER;
            $command = new WarehouseStockCommand(
                type: $type,
                consumableId: (int) $record->consumable_id,
                quantity: $actual,
                verifiedUserId: (int) $validatorUser->getKey(),
                notes: $this->validationLedgerNotes($record, $notes),
                idempotencyKey: $validationKey,
                createdBy: (int) $actor->getKey(),
                verificationCodeHash: $verificationCodeHash,
                itemCondition: $expectedCondition,
                sourceLocation: $record->source_location,
                toLocation: $record->destination_location,
                storageLocation: $record->destination_location,
                operationKey: $validationKey,
                stockInId: (int) $record->getKey(),
            );

            $ledger = $this->stockService->execute($command)->transaction;

            $record->forceFill([
                'status' => WarehouseStockInStatus::VALIDATED->value,
                'validation_result' => $result->value,
                'quantity_received' => $actual,
                'received_consumable_id' => $receivedId,
                'received_condition' => $actualCondition->value,
                'validation_notes' => $notes,
                'validated_at' => now(),
                'validator_user_id' => $validatorUser->getKey(),
                'validator_npk_snapshot' => $validatorUser->npk === null ? null : (string) $validatorUser->npk,
                'validator_name_snapshot' => mb_substr((string) $validatorUser->name, 0, 180),
                'validation_idempotency_key' => $validationKey,
                'stock_transaction_id' => $ledger->getKey(),
            ])->save();
            $record->setAttribute('_idempotent_replay', false);

            return $record->load(['consumable', 'creator', 'validator', 'stockTransaction']);
        }, 3);
    }

    public function cancel(
        int $actorId,
        WarehouseStockIn|int $stockIn,
        string $reason,
        ?string $idempotencyKey = null,
    ): WarehouseStockIn {
        $this->assertAccess($actorId, 'warehouse.stock-in.create');
        $key = $this->uuid($idempotencyKey);
        $reason = $this->text($reason);
        if ($reason === null) {
            throw new WarehouseDomainException('Alasan pembatalan Stock In wajib diisi.', 422);
        }

        return DB::transaction(function () use ($actorId, $stockIn, $reason, $key): WarehouseStockIn {
            $record = WarehouseStockIn::query()
                ->lockForUpdate()
                ->find($stockIn instanceof WarehouseStockIn ? $stockIn->getKey() : $stockIn);
            if ($record === null) {
                throw new WarehouseDomainException('Stock In tidak ditemukan.', 404);
            }
            if ($record->status === WarehouseStockInStatus::CANCELLED
                && (string) $record->cancellation_idempotency_key === $key) {
                $record->setAttribute('_idempotent_replay', true);

                return $record->load(['consumable', 'creator']);
            }
            if (! $record->canCancel()) {
                throw new WarehouseDomainException('Stock In yang sudah tervalidasi tidak dapat dibatalkan.', 409);
            }

            $record->forceFill([
                'status' => WarehouseStockInStatus::CANCELLED->value,
                'cancellation_idempotency_key' => $key,
                'cancellation_reason' => $reason,
                'cancelled_by_user_id' => $actorId,
                'cancelled_at' => now(),
            ])->save();
            $record->setAttribute('_idempotent_replay', false);

            return $record->load(['consumable', 'creator']);
        }, 3);
    }

    private function assertAccess(int $userId, string $ability): void
    {
        $user = User::query()->find($userId);
        if (! $this->access->can($user, $ability)) {
            throw new WarehouseDomainException('Actor tidak memiliki hak Stock In ini.', 403);
        }
    }

    private function assertCreationReplay(
        WarehouseStockIn $existing,
        int $consumableId,
        WarehouseItemCondition $condition,
        string $quantity,
        string $destination,
        ?string $source,
        ?string $notes,
    ): void {
        $normalized = WarehouseQuantity::normalize($quantity, true);
        $matches = (int) $existing->consumable_id === $consumableId
            && ($existing->item_condition instanceof WarehouseItemCondition
                ? $existing->item_condition
                : WarehouseItemCondition::from((string) $existing->item_condition)) === $condition
            && WarehouseQuantity::compare((string) $existing->quantity_expected, $normalized) === 0
            && (string) $existing->destination_location === $destination
            && (string) ($existing->source_location ?? '') === (string) ($source ?? '')
            && (string) ($existing->notes ?? '') === (string) ($notes ?? '');

        if (! $matches) {
            throw new WarehouseDomainException('Creation idempotency key sudah digunakan untuk payload berbeda.', 409);
        }
    }

    private function assertValidationReplay(
        WarehouseStockIn $existing,
        int $stockInId,
        User|int $validator,
        string $quantity,
        WarehouseStockInValidationResult|string|null $result,
        ?string $notes,
        WarehouseItemCondition|string|null $condition,
        ?int $receivedConsumableId,
    ): void {
        $validatorId = $validator instanceof User ? (int) $validator->getKey() : (int) $validator;
        $resolvedResult = $result === null || trim((string) $result) === ''
            ? null
            : $this->validationResult($result);
        $resolvedCondition = $condition === null || trim((string) $condition) === ''
            ? null
            : $this->condition($condition);
        $matches = (int) $existing->getKey() === $stockInId
            && (int) $existing->validator_user_id === $validatorId
            && WarehouseQuantity::compare((string) ($existing->quantity_received ?? '0'), WarehouseQuantity::normalize($quantity, true)) === 0
            && (string) ($existing->validation_notes ?? '') === (string) ($notes ?? '')
            && ($resolvedResult === null || $existing->validation_result === $resolvedResult)
            && ($resolvedCondition === null || $existing->received_condition === $resolvedCondition)
            && ($receivedConsumableId === null || (int) $existing->received_consumable_id === $receivedConsumableId);

        if (! $matches) {
            throw new WarehouseDomainException('Validation idempotency key sudah digunakan untuk payload berbeda.', 409);
        }
    }

    private function condition(WarehouseItemCondition|string $condition): WarehouseItemCondition
    {
        if ($condition instanceof WarehouseItemCondition) {
            return $condition;
        }

        $parsed = WarehouseItemCondition::tryFrom(strtoupper(trim($condition)));
        if ($parsed === null) {
            throw new WarehouseDomainException('Condition Stock In tidak valid.', 422);
        }

        return $parsed;
    }

    private function validationResult(WarehouseStockInValidationResult|string $result): WarehouseStockInValidationResult
    {
        if ($result instanceof WarehouseStockInValidationResult) {
            return $result;
        }

        $parsed = WarehouseStockInValidationResult::tryFrom(strtoupper(trim($result)));
        if ($parsed === null) {
            throw new WarehouseDomainException('Hasil Validasi Stock In tidak valid.', 422);
        }

        return $parsed;
    }

    private function location(string $location, string $message): string
    {
        $location = trim($location);
        if ($location === '' || ! in_array($location, (array) config('warehouse.storage_locations', ['DS8', 'Deltamas']), true)) {
            throw new WarehouseDomainException($message, 422);
        }

        return $location;
    }

    private function uuid(?string $key): string
    {
        $key = $key ?: (string) Str::uuid();
        if (! Str::isUuid($key)) {
            throw new WarehouseDomainException('Idempotency key harus berupa UUID.', 422);
        }

        return $key;
    }

    private function number(): string
    {
        return 'WH-IN-'.now(config('app.timezone', 'Asia/Jakarta'))->format('Ymd-His').'-'.strtoupper(Str::random(8));
    }

    private function text(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, 65535);
    }

    private function validationLedgerNotes(WarehouseStockIn $record, ?string $validationNotes): string
    {
        $parts = ['Stock In '.$record->stock_in_number];
        if ($record->notes !== null && trim((string) $record->notes) !== '') {
            $parts[] = 'Catatan awal: '.trim((string) $record->notes);
        }
        if ($validationNotes !== null) {
            $parts[] = 'Validasi: '.$validationNotes;
        }

        return mb_substr(implode(' | ', $parts), 0, 65535);
    }
}
