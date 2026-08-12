<?php

namespace App\Services;

use App\Models\OutstandingMaterialInvoice;
use Illuminate\Support\Facades\DB;

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
}
