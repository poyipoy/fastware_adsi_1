<?php

namespace App\Services;

use App\Models\OutstandingMaterial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OutstandingMaterialBatchService
{
    public const MAX_ROWS = 100;

    public function __construct(
        private readonly OutstandingMaterialIdentityService $identity,
        private readonly OutstandingMaterialDocumentService $documents,
        ?OutstandingMaterialInvoiceService $invoices = null,
    ) {
        $this->invoices = $invoices ?? new OutstandingMaterialInvoiceService($identity);
    }

    private readonly OutstandingMaterialInvoiceService $invoices;

    /**
     * Persist a complete Add Material batch atomically.
     *
     * @param  array<string, mixed>  $header
     * @param  list<array<string, mixed>>  $rows
     * @return Collection<int, OutstandingMaterial>
     */
    public function create(
        array $header,
        array $rows,
        ?OutstandingMaterial $contextAnchor,
        ?int $userId,
    ): Collection {
        if ($rows === [] || count($rows) > self::MAX_ROWS) {
            throw ValidationException::withMessages([
                'materials' => 'Add Material harus berisi 1 sampai '.self::MAX_ROWS.' baris.',
            ]);
        }

        $supplier = (string) ($contextAnchor?->supplier ?? $header['supplier'] ?? '');
        $invoice = (string) ($contextAnchor?->number_invoice ?? $header['number_invoice'] ?? '');
        $canonicalHeader = $this->identity->canonicalizeInvoice($supplier, $invoice);
        $invoiceIdentityKey = $canonicalHeader['invoice_identity_key'];

        // Rows are intentionally not de-duplicated: identical materials may
        // represent separate physical lines and must remain separate records.
        $canonicalRows = $rows;

        return DB::transaction(function () use (
            $canonicalHeader,
            $canonicalRows,
            $header,
            $invoiceIdentityKey,
            $userId,
        ): Collection {
            $invoiceHeader = $this->invoices->ensureForIdentityKey(
                $invoiceIdentityKey,
                $canonicalHeader['supplier'],
                $canonicalHeader['number_invoice'],
                $userId,
            );
            $inheritance = $this->documents->inheritanceForIdentityKey($invoiceIdentityKey, true);
            $created = new Collection();

            foreach ($canonicalRows as $row) {
                $payload = array_merge($row, [
                    'supplier' => $canonicalHeader['supplier'],
                    'invoice_id' => $invoiceHeader->getKey(),
                    'number_invoice' => $canonicalHeader['number_invoice'],
                    'invoice_identity_key' => $invoiceIdentityKey,
                    'status' => $header['status'],
                    'estimasi_eta_port' => $header['estimasi_eta_port'] ?? null,
                    'estimasi_eta_warehouse' => $header['estimasi_eta_warehouse'] ?? null,
                    'estimasi_bulan_eta' => $header['estimasi_bulan_eta'] ?? null,
                    'keterangan' => $header['keterangan'] ?? null,
                    'estimasi_delay_eta_port' => $header['estimasi_delay_eta_port'] ?? null,
                    'estimasi_delay_eta_warehouse' => $header['estimasi_delay_eta_warehouse'] ?? null,
                    'port' => $header['port'] ?? null,
                    'number_po' => $header['number_po'] ?? null,
                    'remarks' => $header['remarks'] ?? null,
                    'packing_list_path' => $inheritance['packing_list_path'],
                    'mtc_path' => $inheritance['mtc_path'],
                    'attachment_path' => null,
                    'created_by' => $userId,
                    'updated_by' => null,
                ]);

                $created->push(OutstandingMaterial::create($payload));
            }

            return $created;
        });
    }
}
