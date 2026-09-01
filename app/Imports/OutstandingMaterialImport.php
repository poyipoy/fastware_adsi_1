<?php

namespace App\Imports;

use App\Models\OutstandingMaterial;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Parses both the legacy positional template and the canonical named-header
 * template used by Import Multi-Invoice.
 */
class OutstandingMaterialImport implements SkipsEmptyRows, ToCollection
{
    private int $userId;

    private array $rows = [];

    private array $errors = [];

    private array $warnings = [];

    private array $headerIndexes = [];

    private array $availableFields = [];

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function collection(Collection $rows): void
    {
        $rows = $rows->values();
        $first = $rows->first();
        if ($first === null) {
            return;
        }

        $firstArray = $this->toArray($first);
        if ($this->looksLikeHeader($firstArray)) {
            $this->headerIndexes = $this->buildHeaderIndexes($firstArray);
            $rows = $rows->slice(1)->values();
            $startRow = 2;
        } else {
            // Keep reading the legacy positional template. Its old column
            // layout already contains all fields needed for append-only import.
            $this->headerIndexes = $this->legacyHeaderIndexes();
            $this->availableFields = array_keys($this->headerIndexes);
            $startRow = 1;
        }

        foreach ($rows as $index => $rawRow) {
            $rowNumber = $startRow + $index;
            $row = $this->toArray($rawRow);

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $errorsBeforeNormalization = count($this->errors);
            $payload = [
                'supplier' => $this->normalizeString($this->value($row, 'supplier')),
                'number_invoice' => $this->normalizeString($this->value($row, 'number_invoice')),
                'type' => $this->normalizeString($this->value($row, 'type')),
                'thickness' => $this->normalizeNumber($this->value($row, 'thickness')),
                'width' => $this->normalizeNumber($this->value($row, 'width')),
                'diameter' => $this->normalizeNumber($this->value($row, 'diameter')),
                'length' => $this->normalizeLength($this->value($row, 'length')),
                'qty_pcs' => $this->normalizeNumber($this->value($row, 'qty_pcs')),
                'est_qty_kg' => $this->normalizeNumber($this->value($row, 'est_qty_kg')),
                'status' => $this->normalizeOption($this->value($row, 'status'), OutstandingMaterial::statusOptions()),
                'estimasi_eta_port' => $this->normalizeDateText($this->value($row, 'estimasi_eta_port'), $rowNumber, 'Estimasi ETA Port'),
                'estimasi_eta_warehouse' => $this->normalizeDateText($this->value($row, 'estimasi_eta_warehouse'), $rowNumber, 'Estimasi ETA Warehouse'),
                'estimasi_bulan_eta' => $this->normalizeString($this->value($row, 'estimasi_bulan_eta')),
                'keterangan' => $this->normalizeOption($this->value($row, 'keterangan'), OutstandingMaterial::keteranganOptions(), true),
                'estimasi_delay_eta_port' => $this->normalizeDateText($this->value($row, 'estimasi_delay_eta_port'), $rowNumber, 'Estimasi Delay ETA Port'),
                'estimasi_delay_eta_warehouse' => $this->normalizeDateText($this->value($row, 'estimasi_delay_eta_warehouse'), $rowNumber, 'Estimasi Delay ETA Warehouse'),
                'port' => $this->normalizeOption($this->value($row, 'port'), OutstandingMaterial::portOptions(), true),
                'number_po' => $this->normalizeString($this->value($row, 'number_po')),
                'remarks' => $this->normalizeString($this->value($row, 'remarks')),
            ];

            $validator = Validator::make($payload, [
                'supplier' => 'required|string|max:255',
                'number_invoice' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'thickness' => 'nullable|numeric',
                'width' => 'nullable|numeric',
                'diameter' => 'nullable|numeric',
                'length' => 'nullable|string|max:255',
                'qty_pcs' => 'nullable|numeric',
                'est_qty_kg' => 'nullable|numeric',
                'status' => ['required', 'string', Rule::in(OutstandingMaterial::statusOptions())],
                'estimasi_eta_port' => 'nullable|string|max:100',
                'estimasi_eta_warehouse' => 'nullable|string|max:100',
                'estimasi_bulan_eta' => 'nullable|string|max:255',
                'keterangan' => ['nullable', 'string', Rule::in(OutstandingMaterial::keteranganOptions())],
                'estimasi_delay_eta_port' => 'nullable|string|max:100',
                'estimasi_delay_eta_warehouse' => 'nullable|string|max:100',
                'port' => ['nullable', 'string', Rule::in(OutstandingMaterial::portOptions())],
                'number_po' => 'nullable|string|max:255',
                'remarks' => 'nullable|string|max:2000',
            ], [], $this->validationAttributes());

            $normalizationFailed = count($this->errors) > $errorsBeforeNormalization;
            if ($validator->fails() || $this->containsInvalidNumber($payload) || $normalizationFailed) {
                $messages = $validator->errors()->all();
                if ($this->containsInvalidNumber($payload)) {
                    $messages[] = 'Nilai angka tidak valid.';
                }
                if (! $normalizationFailed || $messages !== []) {
                    $this->errors[] = 'Baris '.$rowNumber.': '.implode(', ', array_unique($messages));
                }

                continue;
            }

            $data = $validator->validated();
            $data['_row_number'] = $rowNumber;
            $data['created_by'] = $this->userId;
            $data['updated_by'] = null;
            $this->rows[] = $data;
        }
    }

    public function rows(): array
    {
        return $this->rows;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function warnings(): array
    {
        return $this->warnings;
    }

    /** @return list<string> Fields that had columns in the source file. */
    public function availableFields(): array
    {
        return $this->availableFields;
    }

    private function toArray(mixed $row): array
    {
        return is_array($row) ? $row : $row->toArray();
    }

    private function value(array $row, string $field): mixed
    {
        $index = $this->headerIndexes[$field] ?? null;

        return $index === null ? null : ($row[$index] ?? null);
    }

    private function legacyHeaderIndexes(): array
    {
        return [
            'supplier' => 1,
            'type' => 2,
            'thickness' => 3,
            'width' => 4,
            'diameter' => 5,
            'length' => 6,
            'qty_pcs' => 7,
            'est_qty_kg' => 8,
            'number_invoice' => 9,
            'status' => 10,
            'estimasi_eta_port' => 11,
            'estimasi_eta_warehouse' => 12,
            'estimasi_bulan_eta' => 13,
            'keterangan' => 14,
            'estimasi_delay_eta_port' => 15,
            'estimasi_delay_eta_warehouse' => 16,
            // Legacy template does not have port/number_po/remarks.
        ];
    }

    private function buildHeaderIndexes(array $row): array
    {
        $aliases = [
            'supplier' => ['supplier'],
            'number_invoice' => ['numberinvoice', 'invoice', 'invoicenumber'],
            'type' => ['type'],
            'thickness' => ['thickness'],
            'width' => ['width'],
            'diameter' => ['diameter'],
            'length' => ['length'],
            'qty_pcs' => ['qtypcs', 'qtypcs'],
            'est_qty_kg' => ['estqtykg', 'estimatedqtykg'],
            'status' => ['status'],
            'estimasi_eta_port' => ['estimasietaport'],
            'estimasi_eta_warehouse' => ['estimasietawarehouse', 'estimasietawarehose'],
            'estimasi_bulan_eta' => ['estimasibulaneta'],
            'keterangan' => ['keterangan'],
            'estimasi_delay_eta_port' => ['estimasidelayetaport'],
            'estimasi_delay_eta_warehouse' => ['estimasidelayetawarehouse'],
            'port' => ['port', 'pelabuhan'],
            'number_po' => ['numberpo', 'nomorpo', 'po', 'ponumber', 'nopo'],
            'remarks' => ['remarks', 'remark', 'catatan'],
        ];
        $indexes = [];
        foreach ($row as $index => $value) {
            $normalized = $this->normalizeHeaderValue($value);
            foreach ($aliases as $field => $expected) {
                if (in_array($normalized, $expected, true)) {
                    $indexes[$field] = $index;
                    break;
                }
            }
        }

        // Track which fields are present in the source for Replace mode.
        $this->availableFields = array_keys($indexes);

        $allFields = array_merge($this->legacyHeaderIndexes(), [
            'port' => null,
            'number_po' => null,
            'remarks' => null,
        ]);

        return array_merge(array_fill_keys(array_keys($allFields), null), $indexes);
    }

    private function looksLikeHeader(array $row): bool
    {
        $headerValues = array_map(fn (mixed $value): string => $this->normalizeHeaderValue($value), $row);
        $known = ['supplier', 'type', 'numberinvoice', 'status', 'thickness', 'width', 'diameter', 'port', 'po', 'remarks'];

        return count(array_intersect($headerValues, $known)) >= 3;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->normalizeString($value) !== null) {
                return false;
            }
        }

        return true;
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeHeaderValue(mixed $value): string
    {
        $value = strtolower((string) $this->normalizeString($value));

        return preg_replace('/[^a-z0-9]+/', '', $value) ?: '';
    }

    private function normalizeOption(mixed $value, array $options, bool $nullable = false): ?string
    {
        $value = $this->normalizeString($value);
        if ($value === null) {
            return $nullable ? null : $value;
        }

        foreach ($options as $option) {
            if (strtolower($option) === strtolower($value)) {
                return $option;
            }
        }

        return $value;
    }

    private function normalizeNumber(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        if ($value === '' || in_array(strtolower($value), ['-', '--', 'n/a', 'na'], true)) {
            return null;
        }
        $value = str_replace(' ', '', $value);
        if (str_contains($value, ',') && str_contains($value, '.')) {
            if (strrpos($value, ',') > strrpos($value, '.')) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif (str_contains($value, ',')) {
            $value = substr_count($value, ',') > 1 ? str_replace(',', '', $value) : str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : '__INVALID_NUMBER__';
    }

    private function normalizeLength(mixed $value): ?string
    {
        $value = $this->normalizeString($value);

        return $value === null ? null : preg_replace('/\s+/', '', $value);
    }

    private function normalizeDateText(mixed $value, int $rowNumber, string $label): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }
        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
            } catch (\Throwable) {
                $this->errors[] = sprintf('Baris %d: %s bukan tanggal valid.', $rowNumber, $label);

                return null;
            }
        }
        $value = trim((string) $value);
        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'd M Y', 'd F Y', 'M Y', 'F Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable) {
                // Continue with the next accepted format.
            }
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            $this->errors[] = sprintf('Baris %d: %s "%s" bukan tanggal valid.', $rowNumber, $label, $value);

            return null;
        }
    }

    private function containsInvalidNumber(array $payload): bool
    {
        foreach (['thickness', 'width', 'diameter', 'qty_pcs', 'est_qty_kg'] as $field) {
            if (($payload[$field] ?? null) === '__INVALID_NUMBER__') {
                return true;
            }
        }

        return false;
    }

    private function validationAttributes(): array
    {
        return [
            'supplier' => 'Supplier',
            'number_invoice' => 'Number Invoice',
            'type' => 'TYPE',
            'thickness' => 'Thickness',
            'width' => 'Width',
            'diameter' => 'Diameter',
            'length' => 'Length',
            'qty_pcs' => 'QTY (PCS)',
            'est_qty_kg' => 'Est QTY (KG)',
            'status' => 'Status',
            'estimasi_eta_port' => 'Estimasi ETA Port',
            'estimasi_eta_warehouse' => 'Estimasi ETA Warehouse',
            'estimasi_bulan_eta' => 'Estimasi Bulan ETA',
            'keterangan' => 'Keterangan',
            'estimasi_delay_eta_port' => 'Estimasi Delay ETA Port',
            'estimasi_delay_eta_warehouse' => 'Estimasi Delay ETA Warehouse',
            'port' => 'Port',
            'number_po' => 'Nomor PO',
            'remarks' => 'Remarks',
        ];
    }
}
