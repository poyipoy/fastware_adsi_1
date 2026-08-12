<?php

namespace Tests\Unit;

use App\Services\HR\EmployeeIdentityFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EmployeeIdentityFormatterTest extends TestCase
{
    #[DataProvider('npkProvider')]
    public function test_npk_fallback_and_normalization(mixed $value, string $expected): void
    {
        $this->assertSame($expected, EmployeeIdentityFormatter::npk($value));
    }

    public static function npkProvider(): array
    {
        return [
            'null' => [null, '-'],
            'empty' => ['', '-'],
            'whitespace' => ['   ', '-'],
            'legacy zero integer' => [0, '-'],
            'legacy zero string' => ['0', '-'],
            'valid' => [' 00123 ', '00123'],
        ];
    }

    public function test_label_uses_npk_and_name(): void
    {
        $this->assertSame('00123 - Budi', EmployeeIdentityFormatter::label(['npk' => '00123', 'name' => ' Budi ']));
        $this->assertSame('- - Budi', EmployeeIdentityFormatter::label(['npk' => 0, 'name' => 'Budi']));
    }
}
