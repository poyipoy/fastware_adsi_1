<?php

namespace App\Services\Warehouse;

use App\Data\Warehouse\WarehouseStockCommand;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Exceptions\WarehouseDomainException;
use App\Models\User;
use App\Models\Warehouse\WarehouseRestrictedVerifier;
use App\Models\Warehouse\WarehouseStockTransaction;

final class WarehouseVerifierPolicy
{
    public const DIRECTION_IN = 'IN';

    public const DIRECTION_OUT = 'OUT';

    public function __construct(private readonly WarehouseAccessService $access)
    {
    }

    public function normalizeDirection(string $direction): string
    {
        $direction = strtoupper(trim($direction));

        if (! in_array($direction, [self::DIRECTION_IN, self::DIRECTION_OUT], true)) {
            throw new WarehouseDomainException('Arah verifikasi stok tidak valid.', 422);
        }

        return $direction;
    }

    public function assertUserCanVerify(User $user, string $direction, bool $restricted = false): User
    {
        $direction = $this->normalizeDirection($direction);
        if (! $this->access->hasModuleAccess($user)) {
            throw new WarehouseDomainException(
                'NPK karyawan tidak memiliki akses Warehouse untuk memverifikasi '.$this->label($direction).'.',
                422,
            );
        }

        if ($restricted && ! WarehouseRestrictedVerifier::query()
            ->where('user_id', $user->getKey())
            ->where('scope', 'ALL')
            ->where('is_active', true)
            ->exists()) {
            throw new WarehouseDomainException(
                'NPK karyawan tidak terdaftar sebagai verifikator Adjustment.',
                422,
            );
        }

        return $user;
    }

    public function directionForCommand(
        WarehouseStockCommand $command,
        ?WarehouseStockTransaction $original = null,
    ): string {
        return match ($command->type) {
            WarehouseTransactionType::IN => self::DIRECTION_IN,
            WarehouseTransactionType::OUT => self::DIRECTION_OUT,
            WarehouseTransactionType::ADJUSTMENT => $this->normalizeDirection((string) $command->adjustmentDirection),
            WarehouseTransactionType::REVERSAL => $this->directionForReversal($original),
            WarehouseTransactionType::TRANSFER => self::DIRECTION_OUT,
        };
    }

    public function commandRequiresRestrictedVerifier(
        WarehouseStockCommand $command,
        ?WarehouseStockTransaction $original = null,
    ): bool {
        return $command->type === WarehouseTransactionType::ADJUSTMENT
            || ($command->type === WarehouseTransactionType::REVERSAL
                && $original?->transaction_type === WarehouseTransactionType::ADJUSTMENT);
    }

    public function directionForReversal(?WarehouseStockTransaction $original): string
    {
        if ($original === null) {
            throw new WarehouseDomainException('Transaksi asal reversal tidak ditemukan.', 404);
        }

        $originalDelta = WarehouseQuantity::toMilli((string) $original->stock_after)
            - WarehouseQuantity::toMilli((string) $original->stock_before);

        if ($originalDelta > 0) {
            return self::DIRECTION_OUT;
        }

        if ($originalDelta < 0) {
            return self::DIRECTION_IN;
        }

        throw new WarehouseDomainException('Arah stok transaksi asal tidak dapat ditentukan.', 422);
    }

    public function label(string $direction): string
    {
        return $this->normalizeDirection($direction) === self::DIRECTION_IN
            ? 'Stock In'
            : 'Stock Out';
    }
}
