<?php

namespace App\Services;

use App\Models\OutstandingMaterial;

/**
 * Owns the canonical identity used by Outstanding Material invoice workflows.
 * Display fields remain human-readable while the keys are stable for matching.
 */
class OutstandingMaterialIdentityService
{
    public function normalizeSupplier(?string $supplier): ?string
    {
        return $this->normalizeDisplay($supplier);
    }

    public function normalizeInvoice(?string $invoice): ?string
    {
        $value = $this->normalizeDisplay($invoice);

        return $value === null ? null : mb_strtoupper($value);
    }

    public function supplierKey(?string $supplier): ?string
    {
        $value = $this->normalizeSupplier($supplier);

        return $value === null ? null : mb_strtoupper($value);
    }

    public function invoiceKey(?string $supplier, ?string $invoice): ?string
    {
        $supplierKey = $this->supplierKey($supplier);
        $invoiceKey = $this->normalizeInvoice($invoice);

        if ($supplierKey === null || $invoiceKey === null) {
            return null;
        }

        return hash('sha256', $supplierKey . "\x1F" . $invoiceKey);
    }

    /**
     * @return array{supplier:string,number_invoice:string,invoice_identity_key:string}
     */
    public function canonicalizeInvoice(string $supplier, string $invoice): array
    {
        $supplierValue = $this->normalizeSupplier($supplier);
        $invoiceValue = $this->normalizeInvoice($invoice);

        if ($supplierValue === null || $invoiceValue === null) {
            throw new \InvalidArgumentException('Supplier and Number Invoice are required.');
        }

        return [
            'supplier' => $supplierValue,
            'number_invoice' => $invoiceValue,
            'invoice_identity_key' => $this->invoiceKey($supplierValue, $invoiceValue),
        ];
    }

    public function invoiceKeyForMaterial(OutstandingMaterial $material): ?string
    {
        return $material->invoice_identity_key
            ?: $this->invoiceKey($material->supplier, $material->number_invoice);
    }

    public function hasAssignedInvoice(?OutstandingMaterial $material): bool
    {
        return $material !== null
            && $this->invoiceKeyForMaterial($material) !== null;
    }

    private function normalizeDisplay(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        return $value === '' ? null : $value;
    }
}
