<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\ItemCode;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ItemCodeImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private const NEW_PRODUCT_COLUMNS = [
        'nomor_pengajuan',
        'tanggal',
        'creator',
        'category',
        'supplier',
        'product_code',
        'description',
        'qty',
        'unit',
        'currency',
        'price',
        'reason',
    ];

    private const NEW_PRODUCT_COLUMNS_LEGACY = [
        'tanggal',
        'creator',
        'category',
        'supplier',
        'product_code',
        'description',
        'qty',
        'unit',
        'currency',
        'price',
    ];

    private const NEW_PRODUCT_COLUMNS_LEGACY_REASON_NEW_PRICE = [
        'nomor_pengajuan',
        'tanggal',
        'creator',
        'category',
        'supplier',
        'product_code',
        'description',
        'qty',
        'unit',
        'currency',
        'price',
        'reason_new_price',
    ];

    private const UPDATE_PRICE_COLUMNS = [
        'nomor_pengajuan',
        'tanggal',
        'creator',
        'category',
        'supplier',
        'product_code',
        'description',
        'qty',
        'unit',
        'currency',
        'effective_date_current',
        'current_price',
        'effective_date_new',
        'new_price',
        'reason',
        'selisih',
    ];

    private const UPDATE_PRICE_COLUMNS_LEGACY = [
        'tanggal',
        'creator',
        'category',
        'supplier',
        'product_code',
        'description',
        'qty',
        'unit',
        'currency',
        'effective_date_current',
        'current_price',
        'effective_date_new',
        'new_price',
        'reason_new_price',
        'selisih',
    ];

    private int $userId;
    private string $importType;
    private array $rows = [];
    private array $errors = [];
    private array $creatorIdCache = [];
    private array $templateColumns = [];
    private array $seenNomorProductPairs = [];

    public function __construct(int $userId, string $importType = 'new_product')
    {
        $this->userId = $userId;
        $this->importType = in_array($importType, ['new_product', 'update_price'], true)
            ? $importType
            : 'new_product';
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $firstRow = $rows->first();
        $firstRowArray = is_array($firstRow) ? $firstRow : $firstRow->toArray();

        if (!$this->validateTemplateColumns($firstRowArray)) {
            return;
        }

        foreach ($rows as $index => $rawRow) {
            $rowNumber = $index + 2;
            $row = is_array($rawRow) ? $rawRow : $rawRow->toArray();

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $payload = [
                'nomor_pengajuan' => $this->normalizeNomorPengajuan($row['nomor_pengajuan'] ?? null),
                'type' => $this->importType,
                'creator' => $this->normalizeString($row['creator'] ?? null),
                'tanggal' => $this->normalizeDate($row['tanggal'] ?? null),
                'category' => $this->normalizeCategory($row['category'] ?? null),
                'supplier' => $this->normalizeString($row['supplier'] ?? null),
                'product_code' => $this->normalizeString($row['product_code'] ?? null),
                'description' => $this->normalizeString($row['description'] ?? null),
                'qty' => $this->normalizeQuantity($row['qty'] ?? null),
                'unit' => $this->normalizeString($row['unit'] ?? null),
                'currency' => $this->normalizeCurrency($row['currency'] ?? null),
                'price_per_pcs' => $this->importType === 'update_price'
                    ? $this->normalizeNumber($row['current_price'] ?? null)
                    : $this->normalizeNumber($row['price'] ?? null),
                'tanggal_lama' => $this->importType === 'update_price'
                    ? $this->normalizeDate($row['effective_date_current'] ?? null)
                    : null,
                'harga_baru' => $this->importType === 'update_price'
                    ? $this->normalizeNumber($row['new_price'] ?? null)
                    : null,
                'tanggal_harga_baru' => $this->importType === 'update_price'
                    ? $this->normalizeDate($row['effective_date_new'] ?? null)
                    : null,
                'reason_new_price' => $this->normalizeString($row['reason'] ?? ($row['reason_new_price'] ?? null)),
                'selisih_input' => $this->importType === 'update_price'
                    ? $this->normalizeNumber($row['selisih'] ?? null)
                    : null,
            ];

            $rules = [
                'nomor_pengajuan' => 'nullable|string|max:255',
                'type' => 'required|in:new_product,update_price',
                'creator' => 'required|string|max:255',
                'tanggal' => 'required|date',
                'category' => 'nullable|in:Material,Non Material',
                'supplier' => 'required|string|max:255',
                'product_code' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'qty' => 'required|integer|gt:0',
                'unit' => 'required|string|max:50',
                'currency' => 'required|' . ItemCode::currencyValidationRule(),
                'price_per_pcs' => 'required|numeric|min:0',
            ];

            if ($this->importType === 'update_price') {
                $rules['tanggal_lama'] = 'required|date';
                $rules['harga_baru'] = 'required|numeric|min:0';
                $rules['tanggal_harga_baru'] = 'required|date';
                $rules['reason_new_price'] = 'required|string|max:2000';
                $rules['selisih_input'] = 'nullable|numeric';
            } else {
                $rules['tanggal_lama'] = 'nullable|date';
                $rules['harga_baru'] = 'nullable|numeric|min:0';
                $rules['tanggal_harga_baru'] = 'nullable|date';
                $rules['reason_new_price'] = 'nullable|string|max:2000';
                $rules['selisih_input'] = 'nullable';
            }

            $validator = Validator::make($payload, $rules);

            if ($validator->fails()) {
                $this->errors[] = 'Baris ' . $rowNumber . ': ' . implode(', ', $validator->errors()->all());
                continue;
            }

            $data = $validator->validated();
            $data['category'] = $data['category'] ?? 'Material';
            $resolvedCreatorId = $this->resolveCreatorId($data['creator']);

            if ($data['nomor_pengajuan'] !== null) {
                if (!$this->validateNomorProductPair((string) $data['nomor_pengajuan'], (string) $data['product_code'], $rowNumber)) {
                    continue;
                }
            }

            if ($data['type'] === 'new_product') {
                $data['tanggal_lama'] = null;
                $data['harga_baru'] = null;
                $data['tanggal_harga_baru'] = null;
                $data['attachment'] = null;
                $data['selisih'] = null;
            } else {
                $data['selisih'] = $data['selisih_input'] ?? $this->calculateSelisih($data['price_per_pcs'], $data['harga_baru']);
                $data['attachment'] = null;
            }

            unset($data['creator'], $data['selisih_input']);

            $data['status'] = 'draft';
            $data['created_by'] = $resolvedCreatorId;
            $data['approved_by'] = null;
            $data['finished_by'] = null;

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

    private function normalizeCategory(mixed $value): ?string
    {
        $normalized = strtolower((string) $this->normalizeString($value));

        return match ($normalized) {
            'material' => 'Material',
            'non material', 'non_material', 'non-material' => 'Non Material',
            default => null,
        };
    }

    private function isValidNomorPengajuanFormat(string $nomor): bool
    {
        return (bool) preg_match('/^\d{4}\/(IC|NP)\/PROC\/\d{2}\/\d{2}$/', $nomor);
    }

    private function normalizeCurrency(mixed $value): ?string
    {
        $value = strtoupper((string) $this->normalizeString($value));

        return in_array($value, ItemCode::currencyList(), true) ? $value : null;
    }

    private function calculateSelisih(mixed $hargaLama, mixed $hargaBaru): ?float
    {
        if ($hargaLama === null || $hargaBaru === null) {
            return null;
        }

        return (float) $hargaLama - (float) $hargaBaru;
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeNomorPengajuan(mixed $value): ?string
    {
        $normalized = $this->normalizeString($value);

        return $normalized !== null ? strtoupper($normalized) : null;
    }

    private function normalizeNumber(mixed $value): ?float
    {
        $raw = $value;
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            \Log::info('normalizeNumber numeric', ['raw' => $raw, 'result' => (float) $value]);
            return (float) $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
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

        $result = is_numeric($value) ? (float) $value : null;
        \Log::info('normalizeNumber', ['raw' => $raw, 'result' => $result]);
        return $result;
    }

    private function normalizeInteger(mixed $value): ?int
    {
        $number = $this->normalizeNumber($value);
        if ($number === null) {
            return null;
        }

        if (abs($number - round($number)) > 0.0000001) {
            return null;
        }

        return (int) round($number);
    }

    private function normalizeQuantity(mixed $value): mixed
    {
        $number = $this->normalizeNumber($value);

        if ($number === null) {
            return null;
        }

        if (abs($number - round($number)) < 0.0000001) {
            return (int) round($number);
        }

        return $number;
    }

    private function normalizeDate(mixed $value): ?string
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
                return null;
            }
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable $exception) {
                // Continue trying other formats.
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function isEmptyRow(array $row): bool
    {
        $columns = $this->expectedTemplateColumns();

        foreach ($columns as $column) {
            $value = $row[$column] ?? null;
            if ($this->normalizeString($value) !== null) {
                return false;
            }
        }

        return true;
    }

    private function validateTemplateColumns(array $row): bool
    {
        $columns = array_map('strval', array_keys($row));
        $columns = array_values(array_filter($columns, static fn (string $value): bool => $value !== '' && !is_numeric($value)));
        $allowedColumnSets = $this->allowedTemplateColumns();

        foreach ($allowedColumnSets as $allowedColumns) {
            if ($columns === $allowedColumns) {
                $this->templateColumns = $allowedColumns;
                return true;
            }
        }

        $label = $this->importType === 'update_price' ? 'Update Harga' : 'Produk Baru';
        $preferred = $this->preferredTemplateColumns();

        $this->errors[] = sprintf(
            'Header template import %s tidak sesuai. Kolom wajib persis: %s. Header terbaca: %s.',
            $label,
            implode(', ', $preferred),
            count($columns) > 0 ? implode(', ', $columns) : '-'
        );

        return false;
    }

    private function expectedTemplateColumns(): array
    {
        if (count($this->templateColumns) > 0) {
            return $this->templateColumns;
        }

        return $this->preferredTemplateColumns();
    }

    private function preferredTemplateColumns(): array
    {
        return $this->importType === 'update_price'
            ? self::UPDATE_PRICE_COLUMNS
            : self::NEW_PRODUCT_COLUMNS;
    }

    private function allowedTemplateColumns(): array
    {
        if ($this->importType === 'update_price') {
            return [
                self::UPDATE_PRICE_COLUMNS,
                self::UPDATE_PRICE_COLUMNS_LEGACY,
            ];
        }

        return [
            self::NEW_PRODUCT_COLUMNS,
            self::NEW_PRODUCT_COLUMNS_LEGACY,
            self::NEW_PRODUCT_COLUMNS_LEGACY_REASON_NEW_PRICE,
        ];
    }

    private function validateNomorProductPair(string $nomorPengajuan, string $productCode, int $rowNumber): bool
    {
        $normalizedProductCode = trim($productCode);
        $pairKey = $nomorPengajuan . '|' . strtoupper($normalizedProductCode);

        if (isset($this->seenNomorProductPairs[$pairKey])) {
            $this->errors[] = sprintf(
                'Baris %d: kombinasi Nomor Pengajuan "%s" dan Product Code "%s" duplikat dalam file import.',
                $rowNumber,
                $nomorPengajuan,
                $normalizedProductCode
            );

            return false;
        }

        $exists = ItemCode::query()
            ->where('nomor_pengajuan', $nomorPengajuan)
            ->where('product_code', $normalizedProductCode)
            ->exists();

        if ($exists) {
            $this->errors[] = sprintf(
                'Baris %d: kombinasi Nomor Pengajuan "%s" dan Product Code "%s" sudah ada di sistem.',
                $rowNumber,
                $nomorPengajuan,
                $normalizedProductCode
            );

            return false;
        }

        $this->seenNomorProductPairs[$pairKey] = true;

        return true;
    }

    private function resolveCreatorId(?string $creatorName): int
    {
        $name = $this->normalizeString($creatorName);

        if ($name === null) {
            return $this->userId;
        }

        if (array_key_exists($name, $this->creatorIdCache)) {
            return $this->creatorIdCache[$name];
        }

        $matchedId = User::query()
            ->where('name', $name)
            ->value('id');

        $resolved = is_numeric($matchedId) ? (int) $matchedId : $this->userId;
        $this->creatorIdCache[$name] = $resolved;

        return $resolved;
    }
}
