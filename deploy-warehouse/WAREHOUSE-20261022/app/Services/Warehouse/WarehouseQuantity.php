<?php

namespace App\Services\Warehouse;

use App\Exceptions\WarehouseDomainException;

final class WarehouseQuantity
{
    public static function normalize(string|int|float $value, bool $allowFraction = true): string
    {
        $milli = self::parseMilli($value);

        if ($milli <= 0) {
            throw new WarehouseDomainException('Quantity harus lebih besar dari nol.');
        }

        if (! $allowFraction && $milli % 1000 !== 0) {
            throw new WarehouseDomainException('Item ini hanya menerima quantity bilangan bulat.');
        }

        return self::fromMilli($milli);
    }

    public static function toMilli(string|int|float $value): int
    {
        return self::parseMilli($value);
    }

    public static function fromMilli(int $value): string
    {
        if ($value < 0) {
            throw new WarehouseDomainException('Stock tidak boleh negatif.');
        }

        return number_format($value / 1000, 3, '.', '');
    }

    public static function add(string|int|float $left, string|int|float $right): string
    {
        return self::fromMilli(self::toMilli($left) + self::toMilli($right));
    }

    public static function subtract(string|int|float $left, string|int|float $right): string
    {
        $result = self::toMilli($left) - self::toMilli($right);

        if ($result < 0) {
            throw new WarehouseDomainException('Stok tidak mencukupi.');
        }

        return self::fromMilli($result);
    }

    public static function compare(string|int|float $left, string|int|float $right): int
    {
        return self::toMilli($left) <=> self::toMilli($right);
    }

    /**
     * Format a persisted quantity for human-facing Warehouse screens.
     *
     * Stored values remain DECIMAL(15,3) for arithmetic compatibility; whole
     * quantities are shown as `3` instead of `3.000`, while a genuinely
     * fractional value remains precise (for example `1.25`).
     */
    public static function display(string|int|float|null $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '0';
        }

        $value = trim((string) $value);
        if (! preg_match('/^(\d+)(?:\.(\d{1,3}))?$/', $value, $matches)) {
            return $value;
        }

        $whole = ltrim($matches[1], '0') ?: '0';
        $fraction = rtrim($matches[2] ?? '', '0');

        return $fraction === '' ? $whole : $whole.'.'.$fraction;
    }

    private static function parseMilli(string|int|float $value): int
    {
        $value = trim((string) $value);

        if ($value === '' || ! preg_match('/^\d+(?:\.\d{1,3})?$/', $value)) {
            throw new WarehouseDomainException('Quantity harus berupa angka positif dengan maksimal tiga desimal.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = ltrim($whole, '0') ?: '0';
        if (strlen($whole) > 12) {
            throw new WarehouseDomainException('Quantity melebihi batas DECIMAL(15,3).');
        }
        $fraction = str_pad($fraction, 3, '0');

        return ((int) $whole * 1000) + (int) substr($fraction, 0, 3);
    }
}
