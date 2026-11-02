<?php

namespace App\Services\HR\Reconciliation;

use RuntimeException;

class EmployeeIdentityCrosswalk
{
    public static function id(mixed $value): ?string
    {
        if ($value === null || ! preg_match('/\A\d+\z/', trim((string) $value))) {
            return null;
        }

        return ltrim(trim((string) $value), '0') ?: '0';
    }

    public static function npk(mixed $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/\A\d+\z/', $value) && trim($value, '0') !== '' ? $value : null;
    }

    public static function label(mixed $value): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/u', ' ', (string) $value) ?? ''), 'UTF-8');
    }

    public static function index(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $id = self::id($row['id'] ?? null);
            if ($id === null || isset($result[$id])) {
                throw new RuntimeException('INVALID_OR_DUPLICATE_SNAPSHOT_PRIMARY_KEY');
            }
            $result[$id] = $row;
        }

        return $result;
    }

    public static function reviewed(array $entry): bool
    {
        return ($entry['decision'] ?? null) === 'APPROVED'
            && is_string($entry['hr_review'] ?? null) && trim($entry['hr_review']) !== ''
            && is_string($entry['dba_review'] ?? null) && trim($entry['dba_review']) !== ''
            && is_string($entry['evidence'] ?? null) && trim($entry['evidence']) !== '';
    }

    public function build(array $source, array $target): array
    {
        $source = self::index($source);
        $target = self::index($target);
        $sourceNpks = $targetNpks = [];
        foreach ($source as $id => $row) {
            if (($npk = self::npk($row['npk'] ?? null)) !== null) {
                $sourceNpks[$npk][] = (string) $id;
            }
        }
        foreach ($target as $id => $row) {
            if (($npk = self::npk($row['npk'] ?? null)) !== null) {
                $targetNpks[$npk][] = (string) $id;
            }
        }
        $resolved = $reasons = [];
        foreach ($source as $id => $row) {
            $npk = self::npk($row['npk'] ?? null);
            if ($npk === null) {
                $reasons[$id] = 'INVALID_OR_ZERO_NPK';
            } elseif (count($sourceNpks[$npk]) !== 1) {
                $reasons[$id] = 'SOURCE_NPK_AMBIGUOUS';
            } elseif (! isset($targetNpks[$npk])) {
                $reasons[$id] = 'TARGET_NPK_MISSING';
            } elseif (count($targetNpks[$npk]) !== 1) {
                $reasons[$id] = 'TARGET_NPK_AMBIGUOUS';
            } else {
                $resolved[$id] = $targetNpks[$npk][0];
            }
        }

        return ['resolved' => $resolved, 'reasons' => $reasons, 'source' => $source, 'target' => $target];
    }
}
