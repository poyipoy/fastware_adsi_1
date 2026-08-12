<?php

namespace App\Services\HR;

class EmployeeIdentityFormatter
{
    public static function npk(mixed $npk): string
    {
        $value = trim((string) ($npk ?? ''));

        return $value === '' || $value === '0' ? '-' : $value;
    }

    public static function label(mixed $user, string $separator = ' - '): string
    {
        if ($user === null) {
            return '-';
        }

        $npk = is_array($user) ? ($user['npk'] ?? null) : ($user->npk ?? null);
        $name = trim((string) (is_array($user) ? ($user['name'] ?? '') : ($user->name ?? '')));

        return self::npk($npk).$separator.($name !== '' ? $name : '-');
    }
}
