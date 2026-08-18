<?php

namespace App\Services\Warehouse;

use App\Enums\Warehouse\WarehouseVerificationStatus;
use App\Exceptions\WarehouseDomainException;
use App\Models\User;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseVerificationLog;

final class WarehouseIdentityResolver
{
    public function __construct(
        private readonly WarehouseAccessService $access,
        private readonly WarehouseVerifierPolicy $verifierPolicy,
    ) {
    }

    public function normalize(?string $code): string
    {
        $code = (string) $code;
        $code = preg_replace('/[\r\n\t]+$/', '', $code) ?? $code;

        if (config('warehouse.identity.normalization.trim_surrounding_whitespace', true)) {
            $code = trim($code);
        }

        if ($code === '' || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $code)) {
            throw new \InvalidArgumentException('Kode scan tidak valid.');
        }

        return $code;
    }

    public function hash(?string $code): string
    {
        return hash('sha256', $this->normalize($code));
    }

    public function resolveItem(?string $code): ?WarehouseConsumable
    {
        $normalized = $this->normalize($code);

        $items = WarehouseConsumable::query()
            ->where('is_active', true)
            ->where(function ($query) use ($normalized): void {
                $query->whereRaw('BINARY item_code = ?', [$normalized])
                    ->orWhereRaw('BINARY barcode = ?', [$normalized]);
            })
            ->limit(2)
            ->get();

        if ($items->count() > 1) {
            throw new \InvalidArgumentException('Item Code cocok dengan lebih dari satu barang.');
        }

        return $items->first();
    }

    public function resolveUser(?string $code): ?User
    {
        $npk = $this->normalizeNpk($code);
        $activeValue = (int) config('warehouse.identity.active_user_value', 0);

        $users = User::query()
            ->where('npk', $npk)
            ->where('is_active', $activeValue)
            ->orderBy('id')
            ->get();

        if ($users->count() === 1) {
            return $users->first();
        }

        if ($users->count() > 1) {
            $administrators = $users->filter(
                fn (User $user): bool => $this->access->isAdministrator($user),
            );

            if ($administrators->count() === 1) {
                return $administrators->first();
            }

            throw new WarehouseDomainException('NPK terdaftar pada lebih dari satu user.', 422);
        }

        return null;
    }

    /**
     * Resolve the employee directly by users.npk and enforce Warehouse access.
     */
    public function resolveUserForDirection(?string $code, string $direction): ?User
    {
        $user = $this->resolveUser($code);

        if ($user === null) {
            return null;
        }

        $this->verifierPolicy->assertUserCanVerify($user, $direction);

        return $user;
    }

    public function logFailure(
        string $code,
        string $reason,
        ?string $ip = null,
        ?string $userAgent = null,
    ): WarehouseVerificationLog {
        return WarehouseVerificationLog::query()->create([
            'scanned_code_hash' => $this->hashForAudit($code),
            'status' => WarehouseVerificationStatus::FAILED,
            'failure_reason' => mb_substr($reason, 0, 120),
            'verified_at' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
        ]);
    }

    public function logSuccess(
        string $code,
        ?int $userId = null,
        ?int $transactionId = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): WarehouseVerificationLog {
        return WarehouseVerificationLog::query()->create([
            'scanned_code_hash' => $this->hashForAudit($code),
            'user_id' => $userId,
            'transaction_id' => $transactionId,
            'status' => WarehouseVerificationStatus::SUCCESS,
            'verified_at' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
        ]);
    }

    private function hashForAudit(string $code): string
    {
        try {
            return $this->hash($code);
        } catch (\InvalidArgumentException) {
            return hash('sha256', $code);
        }
    }

    private function normalizeNpk(?string $code): int
    {
        $normalized = $this->normalize($code);

        if (! preg_match('/^\d+$/', $normalized)) {
            throw new \InvalidArgumentException('Barcode NPK harus berisi angka.');
        }

        $canonical = ltrim($normalized, '0');
        if ($canonical === '' || strlen($canonical) > 10 || (int) $canonical > 2147483647) {
            throw new \InvalidArgumentException('Barcode NPK tidak valid.');
        }

        return (int) $canonical;
    }
}
