<?php

namespace Tests\Unit;

use App\Services\HR\WorkingExperienceValidationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WorkingExperienceValidationServiceTest extends TestCase
{
    #[DataProvider('normalizationProvider')]
    public function test_unicode_whitespace_and_case_are_normalized(mixed $value, ?string $expected): void
    {
        $this->assertSame($expected, (new WorkingExperienceValidationService())->normalize($value));
    }

    public static function normalizationProvider(): array
    {
        return [
            'null' => [null, null],
            'empty' => ['  ', null],
            'case' => ['  STAFF Administrasi ', 'staff administrasi'],
            'unicode whitespace' => ["Staff\u{00A0}\tAdministrasi", 'staff administrasi'],
            'repeated spaces' => ['Staff     Administrasi', 'staff administrasi'],
        ];
    }
}
