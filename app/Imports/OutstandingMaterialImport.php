<?php

namespace App\Imports;

use App\Models\OutstandingMaterial;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class OutstandingMaterialImport implements ToCollection, WithStartRow, SkipsEmptyRows
{
    private int $userId;
    private array $rows = [];
    private array $errors = [];
    private array $warnings = [];

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $rawRow) {
            $rowNumber = $index + 2;
            $row = is_array($rawRow) ? $rawRow : $rawRow->toArray();

            if ($this->isEmptyRow($row)) {
                continue;
            }

            if ($this->isHeaderRow($row)) {
                continue;
            }

            $payload = [
                'supplier' => $this->normalizeString($this->valueAt($row, 1)),
                'type' => $this->normalizeString($this->valueAt($row, 2)),
                'thickness' => $this->normalizeNumber($this->valueAt($row, 3)),
                'width' => $this->normalizeNumber($this->valueAt($row, 4)),
                'diameter' => $this->normalizeNumber($this->valueAt($row, 5)),
                'length' => $this->normalizeLength($this->valueAt($row, 6)),
                'qty_pcs' => $this->normalizeNumber($this->valueAt($row, 7)),
                'est_qty_kg' => $this->normalizeNumber($this->valueAt($row, 8)),
                'number_invoice' => $this->normalizeString($this->valueAt($row, 9)),
                'status' => $this->normalizeOption($this->valueAt($row, 10), OutstandingMaterial::statusOptions()),
                'estimasi_eta_port' => $this->normalizeDateText($this->valueAt($row, 11), $rowNumber, 'Estimasi ETA Port'),
                'estimasi_eta_warehouse' => $this->normalizeDateText($this->valueAt($row, 12), $rowNumber, 'Estimasi ETA Warehouse'),
                'estimasi_bulan_eta' => $this->normalizeString($this->valueAt($row, 13)),
                'keterangan' => $this->normalizeOption($this->valueAt($row, 14), OutstandingMaterial::keteranganOptions(), true),
                'estimasi_delay_eta_port' => $this->normalizeDateText($this->valueAt($row, 15), $rowNumber, 'Estimasi Delay ETA Port'),
                'estimasi_delay_eta_warehouse' => $this->normalizeDateText($this->valueAt($row, 16), $rowNumber, 'Estimasi Delay ETA Warehouse'),
            ];

            $validator = Validator::make($payload, [
                'supplier' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'thickness' => 'nullable|numeric',
                'width' => 'nullable|numeric',
                'diameter' => 'nullable|numeric',
                'length' => 'nullable|string|max:255',
                'qty_pcs' => 'nullable|numeric',
                'est_qty_kg' => 'nullable|numeric',
                'number_invoice' => 'nullable|string|max:255',
                'status' => ['required', 'string', Rule::in(OutstandingMaterial::statusOptions())],
                'estimasi_eta_port' => 'nullable|string|max:100',
                'estimasi_eta_warehouse' => 'nullable|string|max:100',
                'estimasi_bulan_eta' => 'nullable|string|max:255',
                'keterangan' => ['nullable', 'string', Rule::in(OutstandingMaterial::keteranganOptions())],
                'estimasi_delay_eta_port' => 'nullable|string|max:100',
                'estimasi_delay_eta_warehouse' => 'nullable|string|max:100',
            ], [], $this->validationAttributes());

            if ($validator->fails()) {
                $this->errors[] = 'Baris ' . $rowNumber . ': ' . implode(', ', $validator->errors()->all());
                continue;
            }

            $data = $validator->validated();
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

    private function valueAt(array $row, int $index): mixed
    {
        return array_key_exists($index, $row) ? $row[$index] : null;
    }

    private function isEmptyRow(array $row): bool
    {
        for ($index = 1; $index <= 19; $index++) {
            if ($this->normalizeString($this->valueAt($row, $index)) !== null) {
                return false;
            }
        }

        return true;
    }

    private function isHeaderRow(array $row): bool
    {
        $matches = 0;
        $headerMap = [
            0 => ['no'],
            1 => ['supplier'],
            2 => ['type'],
            3 => ['thickness'],
            4 => ['width'],
            5 => ['diameter'],
            6 => ['length'],
            7 => ['qtypcs'],
            8 => ['estqtykg'],
            9 => ['numberinvoice'],
            10 => ['status'],
            11 => ['estimasietaport'],
            12 => ['estimasietawarehouse', 'estimasietawarehose'],
            13 => ['estimasibulaneta'],
            14 => ['keterangan'],
            15 => ['estimasidelayetaport'],
            16 => ['estimasidelayetawarehouse'],
        ];

        foreach ($headerMap as $index => $expectedValues) {
            $value = $this->normalizeHeaderValue($this->valueAt($row, $index));
            if ($value === '') {
                continue;
            }

            if (in_array($value, $expectedValues, true)) {
                $matches++;
            }
        }

        return $matches >= 3;
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
        if ($value === '') {
            return null;
        }

        if (in_array(strtolower($value), ['-', '--', 'n/a', 'na'], true)) {
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
            if (substr_count($value, ',') > 1) {
                $value = str_replace(',', '', $value);
            } else {
                $value = str_replace(',', '.', $value);
            }
        }

        return is_numeric($value) ? (float) $value : '__INVALID_NUMBER__';
    }

    private function normalizeLength(mixed $value): ?string
    {
        $value = $this->normalizeString($value);

        if ($value === null) {
            return null;
        }

        return preg_replace('/\s+/', '', $value);
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
            } catch (\Throwable $exception) {
                $rawValue = trim((string) $value);
                $this->warnings[] = sprintf('Baris %d: %s "%s" bukan tanggal valid, disimpan sebagai teks.', $rowNumber, $label, $rawValue);

                return $rawValue;
            }
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'd M Y', 'd F Y', 'M Y', 'F Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable $exception) {
                // Try the next format.
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $exception) {
            $this->warnings[] = sprintf('Baris %d: %s "%s" bukan tanggal valid, disimpan sebagai teks.', $rowNumber, $label, $value);

            return $value;
        }
    }

    private function validationAttributes(): array
    {
        return [
            'supplier' => 'Supplier',
            'type' => 'TYPE',
            'thickness' => 'Thickness',
            'width' => 'Width',
            'diameter' => 'Diameter',
            'length' => 'Length',
            'qty_pcs' => 'QTY (PCS)',
            'est_qty_kg' => 'Est QTY (KG)',
            'number_invoice' => 'Number Invoice',
            'status' => 'Status',
            'estimasi_eta_port' => 'Estimasi ETA Port',
            'estimasi_eta_warehouse' => 'Estimasi ETA Warehouse',
            'estimasi_bulan_eta' => 'Estimasi Bulan ETA',
            'keterangan' => 'Keterangan',
            'estimasi_delay_eta_port' => 'Estimasi Delay ETA Port',
            'estimasi_delay_eta_warehouse' => 'Estimasi Delay ETA Warehouse',
        ];
    }
}
