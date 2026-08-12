<?php

namespace Tests\Unit;

use App\Services\HR\CompetencyTrainingDataImportService;
use Tests\TestCase;

class CompetencyTrainingDataImportServiceTest extends TestCase
{
    public function test_parser_handles_multi_row_insert_and_semicolon_inside_quoted_value(): void
    {
        $service = new CompetencyTrainingDataImportService();
        $sql = <<<'SQL'
INSERT INTO `detail_penilaian_tcs` (`id`, `id_job_position`, `name`, `catatan`) VALUES
(1, 'Position A', 'A', 'Catatan; tetap satu field'),
(2, 'Position B', 'B', 'O\'Brien');
SQL;

        $rows = $service->parseTableRows($sql, 'detail_penilaian_tcs');

        $this->assertCount(2, $rows);
        $this->assertSame('Catatan; tetap satu field', $rows[0]['catatan']);
        $this->assertSame("O'Brien", $rows[1]['catatan']);
    }

    public function test_position_normalization_and_explicit_alias_are_deterministic(): void
    {
        $service = new CompetencyTrainingDataImportService();
        $positions = [
            ['id' => 80, 'position_name' => 'Production Heat Treatment  Sect. Head'],
            ['id' => 39, 'position_name' => 'MC Operator'],
        ];

        $this->assertSame(
            'produksi ht sec head',
            $service->normalizePositionName("Produksi HT Sec. Head\r\n"),
        );
        $this->assertSame(
            80,
            $service->resolvePosition('Produksi HT Sec. Head', $positions)['position']['id'],
        );
        $this->assertSame(
            39,
            $service->resolvePosition('Machining Operator', $positions)['position']['id'],
        );
    }

    public function test_ambiguous_user_fallback_is_not_forced(): void
    {
        $service = new CompetencyTrainingDataImportService();
        $result = $service->resolvePosition(
            'Legacy Unknown Position',
            [
                ['id' => 2, 'position_name' => 'Accounting Staff'],
                ['id' => 3, 'position_name' => 'Admin Cutting Sheet (ACS)'],
            ],
            [2, 3],
            true,
        );

        $this->assertSame('ambiguous', $result['status']);
        $this->assertNull($result['position']);
    }
}
