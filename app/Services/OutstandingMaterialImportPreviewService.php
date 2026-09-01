<?php

namespace App\Services;

use App\Imports\OutstandingMaterialImport;
use App\Models\OutstandingMaterial;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class OutstandingMaterialImportPreviewService
{
    public const TTL_MINUTES = 30;

    public const MAX_ROWS = 2000;

    public function __construct(
        private readonly OutstandingMaterialIdentityService $identity,
        private readonly OutstandingMaterialDocumentService $documents,
        ?OutstandingMaterialInvoiceService $invoices = null,
    ) {
        $this->invoices = $invoices ?? new OutstandingMaterialInvoiceService($identity);
    }

    private readonly OutstandingMaterialInvoiceService $invoices;

    /**
     * @return array{token:string,rows:list<array<string,mixed>>,errors:list<string>,warnings:list<string>,summary:array<string,int>,mode:string}
     */
    public function preview(UploadedFile $file, int $userId, string $mode = 'add'): array
    {
        $stagedPath = $this->stageUpload($file);

        try {
            $import = new OutstandingMaterialImport($userId);
            Excel::import($import, Storage::disk('local')->path($stagedPath));

            $rawRows = $import->rows();
            if (count($rawRows) > self::MAX_ROWS) {
                throw ValidationException::withMessages([
                    'import_file' => 'Import maksimal '.self::MAX_ROWS.' baris data.',
                ]);
            }

            $errors = $import->errors();
            $warnings = $import->warnings();
            $rows = [];

            foreach ($rawRows as $row) {
                try {
                    $canonical = $this->identity->canonicalizeInvoice(
                        (string) $row['supplier'],
                        (string) $row['number_invoice'],
                    );
                } catch (\Throwable $exception) {
                    $errors[] = 'Baris '.($row['_row_number'] ?? '?').': '.$exception->getMessage();

                    continue;
                }

                $rows[] = [
                    ...$row,
                    ...$canonical,
                    'source_row' => (int) ($row['_row_number'] ?? 0),
                ];
            }

            if ($mode === 'replace') {
                $result = $this->classifyReplace($rows, $errors, $warnings);
            } else {
                $result = $this->classifyAdd($rows, $errors, $warnings);
            }

            $result['mode'] = $mode;
            $result['available_fields'] = $import->availableFields();

            $token = (string) Str::uuid();
            $result['token'] = $token;
            Cache::put($this->cacheKey($userId, $token), $result, now()->addMinutes(self::TTL_MINUTES));

            return $result;
        } finally {
            Storage::disk('local')->delete($stagedPath);
        }
    }

    /**
     * Classify rows for Add mode — everything is 'new'.
     */
    private function classifyAdd(array $rows, array $errors, array $warnings): array
    {
        $classified = [];
        $summary = ['new' => 0, 'errors' => 0];

        foreach ($rows as $row) {
            $summary['new']++;
            $classified[] = [
                'source_row' => $row['source_row'],
                'supplier' => $row['supplier'],
                'number_invoice' => $row['number_invoice'],
                'type' => $row['type'],
                'status' => $row['status'],
                'classification' => 'new',
                'payload' => $row,
            ];
        }

        $errors = array_values(array_unique($errors));
        $summary['errors'] = count($errors);

        return [
            'rows' => $classified,
            'errors' => $errors,
            'warnings' => array_values(array_unique($warnings)),
            'summary' => $summary,
        ];
    }

    /**
     * Classify rows for Replace mode.
     *
     * Match key: number_invoice + type + thickness + width + diameter
     * - 'matched'   → existing material found, will be updated
     * - 'unmatched' → no existing match, row is an error (replace must NOT fallback to add)
     */
    private function classifyReplace(array $rows, array $errors, array $warnings): array
    {
        $classified = [];
        $summary = ['matched' => 0, 'unmatched' => 0, 'errors' => 0];

        foreach ($rows as $row) {
            $matchKey = $this->buildReplaceMatchKey($row);

            // Find existing material(s) matching the composite key.
            $existing = OutstandingMaterial::query()
                ->where('number_invoice', $row['number_invoice'])
                ->where('type', $row['type'])
                ->where(function ($q) use ($row) {
                    $thickness = $row['thickness'] ?? null;
                    $thickness === null ? $q->whereNull('thickness') : $q->where('thickness', $thickness);
                })
                ->where(function ($q) use ($row) {
                    $width = $row['width'] ?? null;
                    $width === null ? $q->whereNull('width') : $q->where('width', $width);
                })
                ->where(function ($q) use ($row) {
                    $diameter = $row['diameter'] ?? null;
                    $diameter === null ? $q->whereNull('diameter') : $q->where('diameter', $diameter);
                })
                ->pluck('id')
                ->all();

            if (count($existing) > 0) {
                $summary['matched']++;
                $classified[] = [
                    'source_row' => $row['source_row'],
                    'supplier' => $row['supplier'],
                    'number_invoice' => $row['number_invoice'],
                    'type' => $row['type'],
                    'status' => $row['status'],
                    'classification' => 'matched',
                    'match_ids' => $existing,
                    'match_key' => $matchKey,
                    'payload' => $row,
                ];
            } else {
                $summary['unmatched']++;
                $errors[] = sprintf(
                    'Baris %d: Tidak ditemukan material dengan Invoice "%s", Type "%s", Thickness "%s", Width "%s", Diameter "%s".',
                    $row['source_row'],
                    $row['number_invoice'] ?? '',
                    $row['type'] ?? '',
                    $row['thickness'] ?? '-',
                    $row['width'] ?? '-',
                    $row['diameter'] ?? '-',
                );
                $classified[] = [
                    'source_row' => $row['source_row'],
                    'supplier' => $row['supplier'],
                    'number_invoice' => $row['number_invoice'],
                    'type' => $row['type'],
                    'status' => $row['status'],
                    'classification' => 'unmatched',
                    'payload' => $row,
                ];
            }
        }

        $errors = array_values(array_unique($errors));
        $summary['errors'] = count($errors);

        return [
            'rows' => $classified,
            'errors' => $errors,
            'warnings' => array_values(array_unique($warnings)),
            'summary' => $summary,
        ];
    }

    private function buildReplaceMatchKey(array $row): string
    {
        return implode('|', [
            strtolower(trim((string) ($row['number_invoice'] ?? ''))),
            strtolower(trim((string) ($row['type'] ?? ''))),
            (string) ($row['thickness'] ?? ''),
            (string) ($row['width'] ?? ''),
            (string) ($row['diameter'] ?? ''),
        ]);
    }

    private function stageUpload(UploadedFile $file): string
    {
        $sourcePath = $file->getPathname();
        if (! $file->isValid() || ! is_string($sourcePath) || trim($sourcePath) === '' || ! is_readable($sourcePath)) {
            throw ValidationException::withMessages([
                'import_file' => 'File upload sementara tidak tersedia. Silakan pilih ulang file lalu coba kembali.',
            ]);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            $extension = 'xlsx';
        }

        $stagedPath = 'imports/outstanding-material/'.Str::uuid().'.'.$extension;
        $sourceStream = fopen($sourcePath, 'rb');
        if ($sourceStream === false) {
            throw ValidationException::withMessages([
                'import_file' => 'File import tidak dapat dibaca. Silakan pilih ulang file lalu coba kembali.',
            ]);
        }

        try {
            $stored = Storage::disk('local')->put($stagedPath, $sourceStream);
        } finally {
            fclose($sourceStream);
        }

        if (! $stored) {
            throw ValidationException::withMessages([
                'import_file' => 'File import tidak dapat disiapkan untuk proses import.',
            ]);
        }

        return $stagedPath;
    }

    /**
     * @return array{inserted:int,replaced:int,mode:string}
     */
    public function execute(string $token, int $ownerId, ?int $actorId = null): array
    {
        $key = $this->cacheKey($ownerId, $token);
        $preview = Cache::get($key);
        if (! is_array($preview)) {
            throw ValidationException::withMessages([
                'token' => 'Preview import tidak ditemukan atau sudah kedaluwarsa.',
            ]);
        }
        if (! empty($preview['errors'])) {
            throw ValidationException::withMessages([
                'import_file' => 'Import belum dapat dijalankan karena preview masih memiliki error.',
            ]);
        }

        $mode = $preview['mode'] ?? 'add';
        $availableFields = $preview['available_fields'] ?? [];

        $counts = DB::transaction(function () use ($preview, $actorId, $mode, $availableFields): array {
            if ($mode === 'replace') {
                return $this->executeReplace($preview, $actorId, $availableFields);
            }

            return $this->executeAdd($preview, $actorId);
        });

        Cache::forget($key);

        return array_merge($counts, ['mode' => $mode]);
    }

    private function executeAdd(array $preview, ?int $actorId): array
    {
        $counts = ['inserted' => 0, 'replaced' => 0];
        foreach ($preview['rows'] as $item) {
            $row = $item['payload'];
            $invoiceHeader = $this->invoices->ensureForIdentityKey(
                $row['invoice_identity_key'],
                $row['supplier'],
                $row['number_invoice'],
                $actorId,
            );
            $inheritance = $this->documents->inheritanceForIdentityKey($row['invoice_identity_key'], true);
            OutstandingMaterial::create([
                ...$this->materialFields($row),
                'invoice_id' => $invoiceHeader->getKey(),
                'packing_list_path' => $inheritance['packing_list_path'],
                'mtc_path' => $inheritance['mtc_path'],
                'attachment_path' => null,
                'created_by' => $actorId,
                'updated_by' => null,
            ]);
            $counts['inserted']++;
        }

        return $counts;
    }

    /**
     * Execute Replace mode — update existing materials matched by composite key.
     * Packing List and MTC are NEVER overwritten during replace.
     * The operation is fully atomic (single DB::transaction wrapping this call).
     */
    private function executeReplace(array $preview, ?int $actorId, array $availableFields): array
    {
        $counts = ['inserted' => 0, 'replaced' => 0];

        // Determine which fields are safe to update (only fields present in source).
        $updatableFields = [
            'supplier', 'number_invoice', 'type', 'thickness', 'width', 'diameter',
            'length', 'qty_pcs', 'est_qty_kg', 'status',
            'estimasi_eta_port', 'estimasi_eta_warehouse', 'estimasi_bulan_eta',
            'keterangan', 'estimasi_delay_eta_port', 'estimasi_delay_eta_warehouse',
            'port', 'number_po', 'remarks',
        ];

        // Only update fields that were actually present in the uploaded file.
        $fieldsToUpdate = ! empty($availableFields)
            ? array_intersect($updatableFields, $availableFields)
            : $updatableFields;

        foreach ($preview['rows'] as $item) {
            if (($item['classification'] ?? '') !== 'matched') {
                continue;
            }

            $row = $item['payload'];
            $matchIds = $item['match_ids'] ?? [];

            if (empty($matchIds)) {
                continue;
            }

            $materialData = $this->materialFields($row);
            $updateData = collect($materialData)->only($fieldsToUpdate)->all();
            $updateData['updated_by'] = $actorId;

            // Lock and update all matched records.
            $matched = OutstandingMaterial::query()
                ->whereIn('id', $matchIds)
                ->lockForUpdate()
                ->get();

            foreach ($matched as $material) {
                // Re-canonicalize invoice for the (possibly changed) supplier.
                try {
                    $canonical = $this->identity->canonicalizeInvoice(
                        (string) ($updateData['supplier'] ?? $material->supplier),
                        (string) ($updateData['number_invoice'] ?? $material->number_invoice),
                    );
                    $invoiceHeader = $this->invoices->ensureForIdentityKey(
                        $canonical['invoice_identity_key'],
                        $updateData['supplier'] ?? $material->supplier,
                        $updateData['number_invoice'] ?? $material->number_invoice,
                        $actorId,
                    );
                    $updateData['invoice_identity_key'] = $canonical['invoice_identity_key'];
                    $updateData['invoice_id'] = $invoiceHeader->getKey();
                } catch (\Throwable) {
                    // If canonicalization fails, keep existing invoice.
                }

                // NEVER overwrite packing_list_path, mtc_path, attachment_path.
                $material->update($updateData);
            }

            $counts['replaced'] += count($matched);
        }

        return $counts;
    }

    public function get(string $token, int $userId): ?array
    {
        $value = Cache::get($this->cacheKey($userId, $token));

        return is_array($value) ? $value : null;
    }

    /** @return array<string,mixed> */
    private function materialFields(array $row): array
    {
        return collect($row)
            ->only([
                'supplier', 'number_invoice', 'invoice_identity_key',
                'type', 'thickness', 'width', 'diameter', 'length', 'qty_pcs', 'est_qty_kg',
                'status', 'estimasi_eta_port', 'estimasi_eta_warehouse', 'estimasi_bulan_eta',
                'keterangan', 'estimasi_delay_eta_port', 'estimasi_delay_eta_warehouse',
                'port', 'number_po', 'remarks',
            ])
            ->all();
    }

    private function cacheKey(int $userId, string $token): string
    {
        return 'outstanding-material-import-preview:v3:'.$userId.':'.$token;
    }
}
