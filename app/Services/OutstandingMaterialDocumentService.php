<?php

namespace App\Services;

use App\Models\OutstandingMaterial;
use App\Models\OutstandingMaterialInvoice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class OutstandingMaterialDocumentService
{
    public const PRIVATE_PREFIX = 'private/outstanding-materials/';

    private const LEGACY_PUBLIC_PREFIX = 'outstanding-materials/';

    public function __construct(
        private readonly ?OutstandingMaterialIdentityService $identity = null,
    ) {
    }

    /**
     * Replace the supplied document types on every active row in one invoice.
     * New files are created before the transaction and removed if persistence fails.
     * Old files are considered for deletion only after the transaction succeeds.
     *
     * @return array{updated:int, old_paths:list<string>, new_paths:list<string>}
     */
    public function uploadForInvoice(
        string $invoice,
        ?UploadedFile $packingList,
        ?UploadedFile $mtc,
        ?int $updatedBy,
    ): array {
        $newPaths = [];
        $persisted = false;

        try {
            if ($packingList) {
                $newPaths['packing_list_path'] = $this->storePrivateFile(
                    $packingList,
                    'packing-list',
                );
            }

            if ($mtc) {
                $newPaths['mtc_path'] = $this->storePrivateFile($mtc, 'mtc');
            }

            $oldPaths = [];
            $updated = DB::transaction(function () use (
                $invoice,
                $updatedBy,
                $newPaths,
                &$oldPaths,
            ): int {
                $rows = OutstandingMaterial::query()
                    ->where('number_invoice', $invoice)
                    ->lockForUpdate()
                    ->get(['id', 'packing_list_path', 'mtc_path']);

                if ($rows->isEmpty()) {
                    throw ValidationException::withMessages([
                        'invoice' => 'The selected invoice does not contain active materials.',
                    ]);
                }

                $oldPaths = $rows
                    ->flatMap(fn (OutstandingMaterial $row): array => [
                        $row->packing_list_path,
                        $row->mtc_path,
                    ])
                    ->filter()
                    ->map(fn (mixed $path): string => (string) $path)
                    ->unique()
                    ->values()
                    ->all();

                $updates = ['updated_by' => $updatedBy];
                foreach (['packing_list_path', 'mtc_path'] as $field) {
                    if (array_key_exists($field, $newPaths)) {
                        $updates[$field] = $newPaths[$field];
                    }
                }

                return OutstandingMaterial::query()
                    ->where('number_invoice', $invoice)
                    ->update($updates);
            });
            $persisted = true;

            $this->deleteIfUnreferenced(
                array_values(array_diff($oldPaths, array_values($newPaths))),
            );

            return [
                'updated' => $updated,
                'old_paths' => $oldPaths,
                'new_paths' => array_values($newPaths),
            ];
        } catch (Throwable $exception) {
            if (!$persisted) {
                $this->deletePaths($newPaths);
            }
            throw $exception;
        }
    }

    /**
     * Identity-scoped document replacement used by the invoice workspace.
     * The identity is resolved by the controller from a bound material anchor.
     *
     * @return array{updated:int, old_paths:list<string>, new_paths:list<string>}
     */
    public function uploadForIdentityKey(
        string $identityKey,
        ?UploadedFile $packingList,
        ?UploadedFile $mtc,
        ?int $updatedBy,
    ): array {
        $newPaths = [];
        $persisted = false;

        try {
            if ($packingList) {
                $newPaths['packing_list_path'] = $this->storePrivateFile($packingList, 'packing-list');
            }
            if ($mtc) {
                $newPaths['mtc_path'] = $this->storePrivateFile($mtc, 'mtc');
            }

            $oldPaths = [];
            $updated = DB::transaction(function () use ($identityKey, $updatedBy, $newPaths, &$oldPaths): int {
                $rows = OutstandingMaterial::query()
                    ->where('invoice_identity_key', $identityKey)
                    ->lockForUpdate()
                    ->get(['id', 'packing_list_path', 'mtc_path']);

                if ($rows->isEmpty()) {
                    throw ValidationException::withMessages([
                        'anchor_id' => 'Invoice tidak memiliki material aktif.',
                    ]);
                }

                $oldPaths = $rows
                    ->flatMap(fn (OutstandingMaterial $row): array => [$row->packing_list_path, $row->mtc_path])
                    ->filter()
                    ->map(fn (mixed $path): string => (string) $path)
                    ->unique()
                    ->values()
                    ->all();

                $updates = ['updated_by' => $updatedBy];
                foreach (['packing_list_path', 'mtc_path'] as $field) {
                    if (array_key_exists($field, $newPaths)) {
                        $updates[$field] = $newPaths[$field];
                    }
                }

                $updatedCount = OutstandingMaterial::query()
                    ->where('invoice_identity_key', $identityKey)
                    ->update($updates);

                $header = OutstandingMaterialInvoice::query()
                    ->where('invoice_identity_key', $identityKey)
                    ->lockForUpdate()
                    ->first();
                if ($header) {
                    $headerUpdates = ['updated_by' => $updatedBy, 'document_review_required' => false];
                    foreach (['packing_list_path', 'mtc_path'] as $field) {
                        if (array_key_exists($field, $newPaths)) {
                            $headerUpdates[$field] = $newPaths[$field];
                        }
                    }
                    $header->update($headerUpdates);
                }

                return $updatedCount;
            });
            $persisted = true;

            $this->deleteIfUnreferenced(array_values(array_diff($oldPaths, array_values($newPaths))));

            return [
                'updated' => $updated,
                'old_paths' => $oldPaths,
                'new_paths' => array_values($newPaths),
            ];
        } catch (Throwable $exception) {
            if (!$persisted) {
                $this->deletePaths($newPaths);
            }
            throw $exception;
        }
    }

    /**
     * Return deterministic, safe document inheritance data for an invoice.
     * A path is inherited only when exactly one distinct effective path exists.
     *
     * @return array{packing_list_path:?string,mtc_path:?string,warnings:list<string>}
     */
    public function inheritanceForInvoice(?string $invoice, bool $lock = false): array
    {
        if ($invoice === null || trim($invoice) === '') {
            return [
                'packing_list_path' => null,
                'mtc_path' => null,
                'warnings' => [],
            ];
        }

        $query = OutstandingMaterial::query()
            ->where('number_invoice', $invoice)
            ->select(['id', 'attachment_path', 'packing_list_path', 'mtc_path']);

        if ($lock) {
            $query->lockForUpdate();
        }

        $rows = $query->get();
        $packingPaths = $rows
            ->map(fn (OutstandingMaterial $row): ?string => $this->effectivePackingListPath($row))
            ->filter()
            ->unique()
            ->values();
        $mtcPaths = $rows
            ->map(fn (OutstandingMaterial $row): ?string => $this->nonEmptyPath($row->mtc_path))
            ->filter()
            ->unique()
            ->values();

        $warnings = [];
        if ($packingPaths->count() > 1) {
            $warnings[] = 'packing_list_inconsistent';
        }
        if ($mtcPaths->count() > 1) {
            $warnings[] = 'mtc_inconsistent';
        }

        return [
            'packing_list_path' => $packingPaths->count() === 1 ? $packingPaths->first() : null,
            'mtc_path' => $mtcPaths->count() === 1 ? $mtcPaths->first() : null,
            'warnings' => $warnings,
        ];
    }

    /**
     * Identity-scoped equivalent used by the new invoice workflow.
     *
     * @return array{packing_list_path:?string,mtc_path:?string,warnings:list<string>}
     */
    public function inheritanceForIdentityKey(?string $identityKey, bool $lock = false): array
    {
        if ($identityKey === null || trim($identityKey) === '') {
            return [
                'packing_list_path' => null,
                'mtc_path' => null,
                'warnings' => [],
            ];
        }

        $headerQuery = OutstandingMaterialInvoice::query()
            ->where('invoice_identity_key', $identityKey);
        if ($lock) {
            $headerQuery->lockForUpdate();
        }
        $header = $headerQuery->first(['packing_list_path', 'mtc_path', 'document_review_required']);
        if ($header && !$header->document_review_required && ($header->packing_list_path || $header->mtc_path)) {
            return [
                'packing_list_path' => $this->nonEmptyPath($header->packing_list_path),
                'mtc_path' => $this->nonEmptyPath($header->mtc_path),
                'warnings' => [],
            ];
        }

        $query = OutstandingMaterial::query()
            ->where('invoice_identity_key', $identityKey)
            ->select(['id', 'attachment_path', 'packing_list_path', 'mtc_path']);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $this->inheritanceFromRows($query->get());
    }

    /**
     * Resolve only known application storage prefixes. The returned disk/path
     * pair is suitable for Storage::disk(), response()->file(), or download().
     *
     * @return array{disk:string,path:string}|null
     */
    public function resolvePath(?string $path): ?array
    {
        $path = $this->nonEmptyPath($path);
        if (!$path || str_contains($path, '..') || str_contains($path, '\\')) {
            return null;
        }

        if (str_starts_with($path, self::PRIVATE_PREFIX)) {
            return ['disk' => 'local', 'path' => $path];
        }

        if (str_starts_with($path, self::LEGACY_PUBLIC_PREFIX)) {
            return ['disk' => 'public', 'path' => $path];
        }

        return null;
    }

    public function isStored(?string $path): bool
    {
        $resolved = $this->resolvePath($path);

        return $resolved !== null && Storage::disk($resolved['disk'])->exists($resolved['path']);
    }

    /** @param list<string> $paths */
    public function deleteIfUnreferenced(array $paths): void
    {
        foreach (array_values(array_unique(array_filter($paths))) as $path) {
            $path = (string) $path;

            $referenced = OutstandingMaterial::withTrashed()
                ->where(function ($query) use ($path): void {
                    $query
                        ->where('attachment_path', $path)
                        ->orWhere('packing_list_path', $path)
                        ->orWhere('mtc_path', $path);
                })
                ->exists();

            if (!$referenced) {
                $referenced = OutstandingMaterialInvoice::query()
                    ->where(function ($query) use ($path): void {
                        $query
                            ->where('packing_list_path', $path)
                            ->orWhere('mtc_path', $path);
                    })
                    ->exists();
            }

            if ($referenced) {
                continue;
            }

            $resolved = $this->resolvePath($path);
            if (!$resolved || !Storage::disk($resolved['disk'])->exists($resolved['path'])) {
                continue;
            }

            if (!Storage::disk($resolved['disk'])->delete($resolved['path'])) {
                Log::warning('Outstanding Material document cleanup failed.', [
                    'path' => $path,
                    'disk' => $resolved['disk'],
                ]);
            }
        }
    }

    /** @param array<string, string|null> $paths */
    public function deletePaths(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            $resolved = $this->resolvePath((string) $path);
            if ($resolved && Storage::disk($resolved['disk'])->exists($resolved['path'])) {
                Storage::disk($resolved['disk'])->delete($resolved['path']);
            }
        }
    }

    private function storePrivateFile(UploadedFile $file, string $directory): string
    {
        // Files uploaded through the Windows web SAPI can occasionally expose
        // an empty getRealPath() even though getPathname() still points to the
        // temporary upload.  FilesystemAdapter::putFileAs() relies exclusively
        // on getRealPath(), so write the verified temporary file as a stream.
        $sourcePath = $file->getRealPath();
        if (!is_string($sourcePath) || $sourcePath === '' || !is_file($sourcePath)) {
            $sourcePath = $file->getPathname();
        }

        if (!is_string($sourcePath) || $sourcePath === '' || !is_file($sourcePath)) {
            throw ValidationException::withMessages([
                $directory => 'Temporary file upload tidak tersedia. Silakan pilih ulang file dan coba lagi.',
            ]);
        }

        $extension = preg_replace(
            '/[^A-Za-z0-9]+/',
            '',
            strtolower((string) $file->getClientOriginalExtension()),
        );
        $name = bin2hex(random_bytes(20)) . ($extension !== '' ? '.' . $extension : '');
        $path = trim(self::PRIVATE_PREFIX . $directory . '/' . $name, '/');
        $stream = fopen($sourcePath, 'rb');

        if ($stream === false) {
            throw ValidationException::withMessages([
                $directory => 'File upload tidak dapat dibaca. Silakan pilih ulang file dan coba lagi.',
            ]);
        }

        try {
            $stored = Storage::disk('local')->put($path, $stream);
        } finally {
            fclose($stream);
        }

        if (!$stored) {
            throw new RuntimeException('Unable to store the uploaded document.');
        }

        return $path;
    }

    private function effectivePackingListPath(OutstandingMaterial $row): ?string
    {
        return $this->nonEmptyPath($row->packing_list_path)
            ?: $this->nonEmptyPath($row->attachment_path);
    }

    private function nonEmptyPath(mixed $path): ?string
    {
        $path = trim((string) ($path ?? ''));

        return $path === '' ? null : $path;
    }

    /** @param \Illuminate\Support\Collection<int, OutstandingMaterial> $rows */
    private function inheritanceFromRows($rows): array
    {
        $packingPaths = $rows
            ->map(fn (OutstandingMaterial $row): ?string => $this->effectivePackingListPath($row))
            ->filter()
            ->unique()
            ->values();
        $mtcPaths = $rows
            ->map(fn (OutstandingMaterial $row): ?string => $this->nonEmptyPath($row->mtc_path))
            ->filter()
            ->unique()
            ->values();

        $warnings = [];
        if ($packingPaths->count() > 1) {
            $warnings[] = 'packing_list_inconsistent';
        }
        if ($mtcPaths->count() > 1) {
            $warnings[] = 'mtc_inconsistent';
        }

        return [
            'packing_list_path' => $packingPaths->count() === 1 ? $packingPaths->first() : null,
            'mtc_path' => $mtcPaths->count() === 1 ? $mtcPaths->first() : null,
            'warnings' => $warnings,
        ];
    }
}
