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
     * @return array{token:string,rows:list<array<string,mixed>>,errors:list<string>,warnings:list<string>,summary:array<string,int>}
     */
    public function preview(UploadedFile $file, int $userId): array
    {
        $stagedPath = $this->stageUpload($file);

        try {
            // Importing the UploadedFile object directly makes Laravel Excel
            // call getRealPath(), which can be empty on the local Windows
            // upload runtime. A readable local path is deterministic.
            $import = new OutstandingMaterialImport($userId);
            Excel::import($import, Storage::disk('local')->path($stagedPath));

            $rawRows = $import->rows();
            if (count($rawRows) > self::MAX_ROWS) {
                throw ValidationException::withMessages([
                    'import_file' => 'Import maksimal ' . self::MAX_ROWS . ' baris data.',
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
                    $errors[] = 'Baris ' . ($row['_row_number'] ?? '?') . ': ' . $exception->getMessage();
                    continue;
                }

                $rows[] = [
                    ...$row,
                    ...$canonical,
                    'source_row' => (int) ($row['_row_number'] ?? 0),
                ];
            }

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

            $token = (string) Str::uuid();
            $result = [
                'token' => $token,
                'rows' => $classified,
                'errors' => $errors,
                'warnings' => array_values(array_unique($warnings)),
                'summary' => $summary,
            ];
            Cache::put($this->cacheKey($userId, $token), $result, now()->addMinutes(self::TTL_MINUTES));

            return $result;
        } finally {
            Storage::disk('local')->delete($stagedPath);
        }
    }

    private function stageUpload(UploadedFile $file): string
    {
        $sourcePath = $file->getPathname();
        if (!$file->isValid() || !is_string($sourcePath) || trim($sourcePath) === '' || !is_readable($sourcePath)) {
            throw ValidationException::withMessages([
                'import_file' => 'File upload sementara tidak tersedia. Silakan pilih ulang file lalu coba kembali.',
            ]);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            $extension = 'xlsx';
        }

        $stagedPath = 'imports/outstanding-material/' . Str::uuid() . '.' . $extension;
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

        if (!$stored) {
            throw ValidationException::withMessages([
                'import_file' => 'File import tidak dapat disiapkan untuk proses import.',
            ]);
        }

        return $stagedPath;
    }

    /**
     * @return array{inserted:int}
     */
    public function execute(string $token, int $ownerId, ?int $actorId = null): array
    {
        $key = $this->cacheKey($ownerId, $token);
        $preview = Cache::get($key);
        if (!is_array($preview)) {
            throw ValidationException::withMessages([
                'token' => 'Preview import tidak ditemukan atau sudah kedaluwarsa.',
            ]);
        }
        if (!empty($preview['errors'])) {
            throw ValidationException::withMessages([
                'import_file' => 'Import belum dapat dijalankan karena preview masih memiliki error.',
            ]);
        }

        $counts = DB::transaction(function () use ($preview, $actorId): array {
            $counts = ['inserted' => 0];
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
        });

        Cache::forget($key);

        return ['inserted' => $counts['inserted']];
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
            ])
            ->all();
    }

    private function cacheKey(int $userId, string $token): string
    {
        return 'outstanding-material-import-preview:v2:' . $userId . ':' . $token;
    }
}
