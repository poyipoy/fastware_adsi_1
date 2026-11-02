<?php

namespace App\Services;

use App\Models\OutstandingMaterial;
use App\Models\OutstandingMaterialInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutstandingMaterialInvoiceService
{
    public function __construct(private readonly OutstandingMaterialIdentityService $identity)
    {
    }

    public function ensure(
        string $supplier,
        string $invoice,
        ?int $userId = null,
    ): OutstandingMaterialInvoice {
        $canonical = $this->identity->canonicalizeInvoice($supplier, $invoice);
        $key = $canonical['invoice_identity_key'];

        $header = OutstandingMaterialInvoice::query()
            ->where('invoice_identity_key', $key)
            ->lockForUpdate()
            ->first();

        if ($header) {
            return $header;
        }

        return OutstandingMaterialInvoice::create([
            'supplier' => $canonical['supplier'],
            'number_invoice' => $canonical['number_invoice'],
            'invoice_identity_key' => $key,
            'document_review_required' => false,
            'created_by' => $userId,
            'updated_by' => null,
        ]);
    }

    public function ensureForIdentityKey(
        string $identityKey,
        string $supplier,
        string $invoice,
        ?int $userId = null,
    ): OutstandingMaterialInvoice {
        $header = OutstandingMaterialInvoice::query()
            ->where('invoice_identity_key', $identityKey)
            ->lockForUpdate()
            ->first();

        return $header ?: OutstandingMaterialInvoice::create([
            'supplier' => $this->identity->normalizeSupplier($supplier),
            'number_invoice' => $this->identity->normalizeInvoice($invoice),
            'invoice_identity_key' => $identityKey,
            'document_review_required' => false,
            'created_by' => $userId,
            'updated_by' => null,
        ]);
    }

    public function assertNotArchived(?OutstandingMaterialInvoice $header, string $action = 'modifikasi'): void
    {
        if ($header !== null && $header->isArchived()) {
            throw ValidationException::withMessages([
                'number_invoice' => "Invoice ini telah diarsipkan dan tidak dapat dilakukan {$action}. Silakan pulihkan invoice terlebih dahulu.",
            ]);
        }
    }

    public function assertMaterialNotArchived(OutstandingMaterial $material, string $action = 'modifikasi'): void
    {
        if ($material->isArchived()) {
            throw ValidationException::withMessages([
                'material' => "Tidak dapat melakukan {$action} pada material yang sudah diarsipkan. Silakan pulihkan material terlebih dahulu.",
            ]);
        }

        if ($material->invoiceHeader !== null && $material->invoiceHeader->isArchived()) {
            $this->assertNotArchived($material->invoiceHeader, $action);
        }
    }

    public function assertNotArchivedByIdentityKey(?string $identityKey, string $action = 'modifikasi'): void
    {
        if ($identityKey === null || trim($identityKey) === '') {
            return;
        }

        $header = OutstandingMaterialInvoice::query()
            ->where('invoice_identity_key', $identityKey)
            ->first();

        $this->assertNotArchived($header, $action);
    }

    public function archiveMaterials(array $materialIds, User|int|null $user = null): array
    {
        $userId = $user instanceof User ? (int) $user->id : (int) $user;

        if (empty($materialIds)) {
            throw ValidationException::withMessages([
                'material_ids' => 'Pilih setidaknya satu material untuk diarsipkan.',
            ]);
        }

        return DB::transaction(function () use ($materialIds, $userId): array {
            $materials = OutstandingMaterial::query()
                ->whereIn('id', $materialIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            if ($materials->isEmpty()) {
                throw ValidationException::withMessages([
                    'material_ids' => 'Material tidak ditemukan.',
                ]);
            }

            // Check eligibility: each material must be Received and Closed
            $ineligible = $materials->filter(function (OutstandingMaterial $m): bool {
                $statusOk = strtolower(trim((string) $m->status)) === strtolower(OutstandingMaterial::STATUS_RECEIVED);
                $keteranganOk = strtolower(trim((string) $m->keterangan)) === strtolower(OutstandingMaterial::KETERANGAN_CLOSED);

                return ! ($statusOk && $keteranganOk);
            });

            if ($ineligible->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'material_ids' => 'Material hanya dapat diarsipkan jika berstatus "Received" dan keterangan "Closed". Terdapat '.$ineligible->count().' material yang belum memenuhi syarat.',
                ]);
            }

            $now = now();

            // Set archived_at on materials
            OutstandingMaterial::query()
                ->whereIn('id', $materials->pluck('id'))
                ->update([
                    'archived_at' => $now,
                    'updated_by' => $userId ?: null,
                ]);

            // Reconcile and check parent invoice headers
            $affectedKeys = $materials->pluck('invoice_identity_key')->filter()->unique();
            $affectedInvoiceIds = $materials->pluck('invoice_id')->filter()->unique();

            $headers = OutstandingMaterialInvoice::query()
                ->where(function ($q) use ($affectedKeys, $affectedInvoiceIds): void {
                    if ($affectedKeys->isNotEmpty()) {
                        $q->whereIn('invoice_identity_key', $affectedKeys);
                    }
                    if ($affectedInvoiceIds->isNotEmpty()) {
                        $q->orWhereIn('id', $affectedInvoiceIds);
                    }
                })
                ->lockForUpdate()
                ->get();

            foreach ($headers as $header) {
                // Link any unlinked materials in this group to the header
                OutstandingMaterial::query()
                    ->where('invoice_identity_key', $header->invoice_identity_key)
                    ->whereNull('invoice_id')
                    ->update(['invoice_id' => $header->getKey()]);

                // Check if all materials in this invoice group are now archived
                $hasActiveMaterials = OutstandingMaterial::query()
                    ->where('invoice_id', $header->getKey())
                    ->whereNull('archived_at')
                    ->exists();

                if (! $hasActiveMaterials && ! $header->isArchived()) {
                    $header->update([
                        'archived_at' => $now,
                        'updated_by' => $userId ?: null,
                    ]);
                }
            }

            return [
                'archived_count' => $materials->count(),
                'archived_ids' => $materials->pluck('id')->all(),
            ];
        }, 3);
    }

    public function restoreMaterials(array $materialIds, User|int|null $user = null): array
    {
        $userId = $user instanceof User ? (int) $user->id : (int) $user;

        if (empty($materialIds)) {
            throw ValidationException::withMessages([
                'material_ids' => 'Pilih setidaknya satu material untuk dipulihkan.',
            ]);
        }

        return DB::transaction(function () use ($materialIds, $userId): array {
            $materials = OutstandingMaterial::query()
                ->whereIn('id', $materialIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            if ($materials->isEmpty()) {
                throw ValidationException::withMessages([
                    'material_ids' => 'Material tidak ditemukan.',
                ]);
            }

            // Set archived_at = null on materials
            OutstandingMaterial::query()
                ->whereIn('id', $materials->pluck('id'))
                ->update([
                    'archived_at' => null,
                    'updated_by' => $userId ?: null,
                ]);

            // Reset parent invoice headers archived_at to null since they now have active materials
            $affectedKeys = $materials->pluck('invoice_identity_key')->filter()->unique();
            $affectedInvoiceIds = $materials->pluck('invoice_id')->filter()->unique();

            OutstandingMaterialInvoice::query()
                ->where(function ($q) use ($affectedKeys, $affectedInvoiceIds): void {
                    if ($affectedKeys->isNotEmpty()) {
                        $q->whereIn('invoice_identity_key', $affectedKeys);
                    }
                    if ($affectedInvoiceIds->isNotEmpty()) {
                        $q->orWhereIn('id', $affectedInvoiceIds);
                    }
                })
                ->whereNotNull('archived_at')
                ->update([
                    'archived_at' => null,
                    'updated_by' => $userId ?: null,
                ]);

            return [
                'restored_count' => $materials->count(),
                'restored_ids' => $materials->pluck('id')->all(),
            ];
        }, 3);
    }

    public function archiveInvoiceGroup(OutstandingMaterial $anchor, User|int|null $user = null): OutstandingMaterialInvoice
    {
        $userId = $user instanceof User ? (int) $user->id : (int) $user;

        return DB::transaction(function () use ($anchor, $userId): OutstandingMaterialInvoice {
            $identityKey = $this->identity->invoiceKeyForMaterial($anchor);
            if ($identityKey === null) {
                throw ValidationException::withMessages([
                    'invoice' => 'Invoice tidak memiliki nomor invoice dan supplier yang valid.',
                ]);
            }

            // Step 1: Lock Parent Header First
            $header = OutstandingMaterialInvoice::query()
                ->where('invoice_identity_key', $identityKey)
                ->lockForUpdate()
                ->first();

            if (! $header && $anchor->invoice_id) {
                $header = OutstandingMaterialInvoice::query()
                    ->whereKey($anchor->invoice_id)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $header) {
                $header = $this->ensure(
                    (string) $anchor->supplier,
                    (string) $anchor->number_invoice,
                    $userId,
                );
            }

            if ($header->isArchived()) {
                throw ValidationException::withMessages([
                    'invoice' => 'Invoice ini sudah diarsipkan sebelumnya.',
                ]);
            }

            // Step 2: Lock All Child Materials in ID ASC order
            $materials = OutstandingMaterial::query()
                ->where(function ($query) use ($header, $identityKey): void {
                    $query->where('invoice_id', $header->getKey())
                        ->orWhere('invoice_identity_key', $identityKey);
                })
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            // Validate non-empty: count > 0
            if ($materials->isEmpty()) {
                throw ValidationException::withMessages([
                    'invoice' => 'Invoice tidak memiliki material untuk diarsipkan.',
                ]);
            }

            // Validate eligibility predicate: all rows Received AND Closed
            $ineligible = $materials->filter(function (OutstandingMaterial $m): bool {
                $statusOk = strtolower(trim((string) $m->status)) === strtolower(OutstandingMaterial::STATUS_RECEIVED);
                $keteranganOk = strtolower(trim((string) $m->keterangan)) === strtolower(OutstandingMaterial::KETERANGAN_CLOSED);

                return ! ($statusOk && $keteranganOk);
            });

            if ($ineligible->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'invoice' => 'Invoice hanya dapat diarsipkan jika semua material berstatus "Received" dan keterangan "Closed". Terdapat '.$ineligible->count().' material yang belum selesai.',
                ]);
            }

            $now = now();

            // Step 3: Reconcile any unlinked legacy rows in this exact group
            OutstandingMaterial::query()
                ->whereIn('id', $materials->pluck('id'))
                ->whereNull('invoice_id')
                ->update(['invoice_id' => $header->getKey()]);

            // Step 4: Stamp archive marker on child materials and header
            OutstandingMaterial::query()
                ->whereIn('id', $materials->pluck('id'))
                ->update([
                    'archived_at' => $now,
                    'updated_by' => $userId ?: null,
                ]);

            $header->update([
                'archived_at' => $now,
                'updated_by' => $userId ?: null,
            ]);

            return $header;
        }, 3);
    }

    public function restoreInvoiceGroup(OutstandingMaterial $anchor, User|int|null $user = null): OutstandingMaterialInvoice
    {
        $userId = $user instanceof User ? (int) $user->id : (int) $user;

        return DB::transaction(function () use ($anchor, $userId): OutstandingMaterialInvoice {
            $identityKey = $this->identity->invoiceKeyForMaterial($anchor);
            if ($identityKey === null) {
                throw ValidationException::withMessages([
                    'invoice' => 'Invoice tidak memiliki nomor invoice dan supplier yang valid.',
                ]);
            }

            // Lock parent header
            $header = OutstandingMaterialInvoice::query()
                ->where('invoice_identity_key', $identityKey)
                ->lockForUpdate()
                ->first();

            if (! $header && $anchor->invoice_id) {
                $header = OutstandingMaterialInvoice::query()
                    ->whereKey($anchor->invoice_id)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $header) {
                throw ValidationException::withMessages([
                    'invoice' => 'Header invoice tidak ditemukan.',
                ]);
            }

            // Reset archived_at to null on header and child materials
            $header->update([
                'archived_at' => null,
                'updated_by' => $userId ?: null,
            ]);

            OutstandingMaterial::query()
                ->where(function ($query) use ($header, $identityKey): void {
                    $query->where('invoice_id', $header->getKey())
                        ->orWhere('invoice_identity_key', $identityKey);
                })
                ->update([
                    'archived_at' => null,
                    'updated_by' => $userId ?: null,
                ]);

            return $header;
        }, 3);
    }
}
