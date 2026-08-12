<?php

namespace App\Services\HR;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Reads the competency/training tables from a legacy SQL dump without
 * executing the dump itself.
 *
 * The importer is deliberately insert-only for competency masters and never
 * writes to users, positions, roles, sections, departments, or categories.
 */
class CompetencyTrainingDataImportService
{
    /** @var array<string, int>|null */
    private ?array $masterIdsCache = null;

    private const MASTER_TABLES = [
        'mst_tcs',
        'mst_soft_skills',
        'mst_additionals',
    ];

    private const PROTECTED_TABLES = [
        'users',
        'roles',
        'mst_job_positions',
        'mst_departments',
        'mst_sections',
        'tc_poin_kategoris',
        'user_job_positions',
    ];

    private const SOURCE_YEAR = 2025;

    /**
     * Parse one or more INSERT statements for a named table.
     *
     * This parser intentionally handles quoted semicolons, escaped quotes,
     * and multi-row INSERT statements. It does not evaluate arbitrary SQL.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseTableRows(string $sql, string $table): array
    {
        $quotedTable = preg_quote($table, '/');
        $pattern = '/INSERT\s+(?:IGNORE\s+)?INTO\s+(?:`' . $quotedTable . '`|' . $quotedTable . ')\s*\((.*?)\)\s*VALUES\s*/is';
        preg_match_all($pattern, $sql, $matches, PREG_OFFSET_CAPTURE);

        $rows = [];

        foreach ($matches[0] as $index => $match) {
            $statementStart = $match[1] + strlen($match[0]);
            $statement = $this->readUntilStatementEnd($sql, $statementStart);
            $columns = $this->splitSqlList($matches[1][$index][0]);

            foreach ($this->parseValueTuples($statement) as $values) {
                if (count($columns) !== count($values)) {
                    throw new RuntimeException(sprintf(
                        'Kolom dan nilai pada INSERT %s tidak cocok (%d kolom, %d nilai).',
                        $table,
                        count($columns),
                        count($values),
                    ));
                }

                $row = [];
                foreach ($columns as $columnIndex => $column) {
                    $row[$column] = $this->decodeSqlValue($values[$columnIndex]);
                }

                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Normalize legacy and target position labels for exact comparison.
     */
    public function normalizePositionName(?string $value): string
    {
        $value = trim((string) $value);
        $value = str_replace(['\\r\\n', '\\n', '\\r', "\r\n", "\n", "\r"], ' ', $value);
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * Resolve a legacy position label against target positions.
     *
     * @param array<int, array{id:int, position_name:string}> $targetPositions
     * @param array<int, int>                                  $userPositionCandidates
     * @return array{status:string, position:?array, method:?string}
     */
    public function resolvePosition(
        ?string $sourceName,
        array $targetPositions,
        array $userPositionCandidates = [],
        bool $allowUserFallback = false,
    ): array {
        $targetByKey = [];
        foreach ($targetPositions as $position) {
            $targetByKey[$this->normalizePositionName($position['position_name'])] = $position;
        }

        $sourceKey = $this->normalizePositionName($sourceName);
        if ($sourceKey !== '' && isset($targetByKey[$sourceKey])) {
            return [
                'status' => 'matched',
                'position' => $targetByKey[$sourceKey],
                'method' => 'exact',
            ];
        }

        $aliases = $this->positionAliases();
        $aliasTargetKey = $aliases[$sourceKey] ?? null;
        if ($aliasTargetKey !== null && isset($targetByKey[$aliasTargetKey])) {
            return [
                'status' => 'matched',
                'position' => $targetByKey[$aliasTargetKey],
                'method' => 'alias',
            ];
        }

        if ($allowUserFallback) {
            $candidateIds = array_values(array_unique(array_map('intval', $userPositionCandidates)));
            if (count($candidateIds) === 1) {
                foreach ($targetPositions as $position) {
                    if ((int) $position['id'] === $candidateIds[0]) {
                        return [
                            'status' => 'matched',
                            'position' => $position,
                            'method' => 'unique_active_user_position',
                        ];
                    }
                }
            }

            if (count($candidateIds) > 1) {
                return ['status' => 'ambiguous', 'position' => null, 'method' => null];
            }
        }

        return ['status' => 'missing', 'position' => null, 'method' => null];
    }

    /**
     * Build a read-only import plan and report.
     *
     * @return array<string, mixed>
     */
    public function inspect(
        string $sourcePath,
        int $dummyAssessmentsPerYear = 50,
        int $dummyTrainingPerYear = 25,
        array $years = [2025, 2026],
    ): array {
        return $this->preparePlan($sourcePath, $dummyAssessmentsPerYear, $dummyTrainingPerYear, $years)['report'];
    }

    /**
     * Apply an already validated plan in one transaction.
     *
     * @return array<string, mixed>
     */
    public function import(
        string $sourcePath,
        int $dummyAssessmentsPerYear = 50,
        int $dummyTrainingPerYear = 25,
        array $years = [2025, 2026],
    ): array {
        $plan = $this->preparePlan($sourcePath, $dummyAssessmentsPerYear, $dummyTrainingPerYear, $years);

        return DB::transaction(function () use ($plan): array {
            $beforeProtected = $this->fingerprintTables(self::PROTECTED_TABLES);
            $beforeMasters = $this->rowsById(self::MASTER_TABLES);

            $result = $this->applyPlan($plan);

            $afterProtected = $this->fingerprintTables(self::PROTECTED_TABLES);
            $afterMasters = $this->rowsById(self::MASTER_TABLES);

            if ($beforeProtected !== $afterProtected) {
                throw new RuntimeException('Importer mendeteksi perubahan pada master protected; transaksi dibatalkan.');
            }

            foreach ($beforeMasters as $table => $rows) {
                foreach ($rows as $id => $row) {
                    if (($afterMasters[$table][$id] ?? null) !== $row) {
                        throw new RuntimeException(sprintf(
                            'Importer mendeteksi perubahan pada row master %s #%s; transaksi dibatalkan.',
                            $table,
                            $id,
                        ));
                    }
                }
            }

            return array_merge($plan['report'], [
                'applied' => $result,
                'protected_master_unchanged' => true,
                'existing_competency_master_rows_unchanged' => true,
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function preparePlan(
        string $sourcePath,
        int $dummyAssessmentsPerYear,
        int $dummyTrainingPerYear,
        array $years,
    ): array {
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new RuntimeException('File SQL sumber tidak ditemukan atau tidak dapat dibaca: ' . $sourcePath);
        }

        if ($dummyAssessmentsPerYear < 0 || $dummyTrainingPerYear < 0) {
            throw new RuntimeException('Jumlah dummy tidak boleh negatif.');
        }

        $years = array_values(array_unique(array_map('intval', $years)));
        sort($years);
        if ($years !== [2025, 2026]) {
            throw new RuntimeException('Importer ini hanya mendukung dummy tahun 2025 dan 2026.');
        }

        $this->validateTargetSchema();

        $sql = file_get_contents($sourcePath);
        if ($sql === false) {
            throw new RuntimeException('Gagal membaca file SQL sumber.');
        }

        $source = [
            'mst_tcs' => $this->parseTableRows($sql, 'mst_tcs'),
            'mst_soft_skills' => $this->parseTableRows($sql, 'mst_soft_skills'),
            'mst_additionals' => $this->parseTableRows($sql, 'mst_additionals'),
            'tc_job_positions' => $this->parseTableRows($sql, 'tc_job_positions'),
            'detail_penilaian_tcs' => $this->parseTableRows($sql, 'detail_penilaian_tcs'),
            'trs_penilaian_tcs' => $this->parseTableRows($sql, 'trs_penilaian_tcs'),
            'mst_pd_pengajuans' => $this->parseTableRows($sql, 'mst_pd_pengajuans'),
        ];

        $targetPositions = DB::table('mst_job_positions')
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'position_name'])
            ->map(static fn ($row): array => [
                'id' => (int) $row->id,
                'position_name' => (string) $row->position_name,
            ])
            ->all();

        $userPositionCandidates = $this->loadActiveUserPositionCandidates();
        $targetUserIds = DB::table('users')->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $targetUserIdSet = array_fill_keys($targetUserIds, true);

        $sourcePositionNames = [];
        foreach ($source['tc_job_positions'] as $row) {
            if (isset($row['id'])) {
                $sourcePositionNames[(int) $row['id']] = (string) ($row['job_position'] ?? '');
            }
        }

        $targetCategoryIds = DB::table('tc_poin_kategoris')->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $targetCategoryIdSet = array_fill_keys($targetCategoryIds, true);

        $masterPlan = $this->planMasterRows(
            $source,
            $sourcePositionNames,
            $targetPositions,
            $targetCategoryIdSet,
        );

        $masterOptions = $this->buildMasterOptions($masterPlan['inserts']);
        $assignments = $this->loadActiveAssignments($targetPositions);

        if (($dummyAssessmentsPerYear > 0 || $dummyTrainingPerYear > 0) && $assignments !== []) {
            $this->addFallbackDummyMasters(
                $masterPlan,
                $masterOptions,
                $assignments,
                $targetPositions,
                $dummyAssessmentsPerYear,
            );
            $masterOptions = $this->buildMasterOptions($masterPlan['inserts']);
        }

        $sourceAssessments = $this->planSourceAssessments(
            $source['trs_penilaian_tcs'],
            $targetPositions,
            $userPositionCandidates,
            $targetUserIdSet,
            $masterPlan['source_map'],
        );

        $sourceDetails = $this->planSourceDetails(
            $source['detail_penilaian_tcs'],
            $targetPositions,
        );

        $dummyPlan = $this->planDummyRows(
            $years,
            $dummyAssessmentsPerYear,
            $dummyTrainingPerYear,
            $assignments,
            $masterOptions,
            $targetPositions,
        );

        $sourceTraining2025 = array_values(array_filter(
            $source['mst_pd_pengajuans'],
            fn (array $row): bool => $this->sourceTrainingYear($row) === self::SOURCE_YEAR,
        ));

        $report = [
            'source_path' => $sourcePath,
            'source_tables' => [
                'mst_tcs' => $masterPlan['stats']['mst_tcs'],
                'mst_soft_skills' => $masterPlan['stats']['mst_soft_skills'],
                'mst_additionals' => $masterPlan['stats']['mst_additionals'],
            ],
            'masters' => [
                'source_rows_mappable' => $masterPlan['stats']['mappable'],
                'source_rows_skipped' => $masterPlan['stats']['skipped'],
                'to_insert' => count($masterPlan['inserts']),
            ],
            'source_assessments_2025' => $this->reportRows(
                $sourceAssessments['rows'],
                'trs_penilaian_tcs',
                'source',
                $sourceAssessments['total_2025'],
            ),
            'source_details_2025' => $this->reportRows(
                $sourceDetails['rows'],
                'detail_penilaian_tcs',
                'source',
                $sourceDetails['total_2025'],
            ),
            'source_training_2025' => [
                'total' => count($sourceTraining2025),
                'source_rows_other_years' => count($source['mst_pd_pengajuans']) - count($sourceTraining2025),
                'to_insert' => 0,
                'skipped' => count($sourceTraining2025),
            ],
            'dummy' => [],
            'skipped' => $this->mergeSkipStats(
                $masterPlan['skips'],
                $sourceAssessments['skips'],
                $sourceDetails['skips'],
            ),
            'mapping' => [
                'position_methods' => $this->mergeMethodStats(
                    $masterPlan['methods'],
                    $sourceAssessments['methods'],
                    $sourceDetails['methods'],
                ),
            ],
        ];

        foreach ($dummyPlan as $year => $rows) {
            $report['dummy'][(string) $year] = [
                'assessments' => $this->reportRows($rows['assessments'], 'trs_penilaian_tcs', 'dummy'),
                'details' => $this->reportRows($rows['details'], 'detail_penilaian_tcs', 'dummy'),
                'training' => $this->reportRows($rows['training'], 'mst_pd_pengajuans', 'dummy'),
            ];
        }

        return [
            'source' => $source,
            'master_plan' => $masterPlan,
            'source_assessments' => $sourceAssessments,
            'source_details' => $sourceDetails,
            'dummy' => $dummyPlan,
            'report' => $report,
            'target_positions' => $targetPositions,
        ];
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $source
     * @param array<int, string>                               $sourcePositionNames
     * @param array<int, array{id:int, position_name:string}>  $targetPositions
     * @param array<int, bool>                                  $targetCategoryIdSet
     * @return array<string, mixed>
     */
    private function planMasterRows(
        array $source,
        array $sourcePositionNames,
        array $targetPositions,
        array $targetCategoryIdSet,
    ): array {
        $existingKeys = $this->loadExistingMasterKeys();
        $sourceMap = [];
        $inserts = [];
        $skips = [];
        $methods = [];
        $stats = [
            'mappable' => 0,
            'skipped' => 0,
            'mst_tcs' => ['total' => count($source['mst_tcs']), 'mappable' => 0, 'skipped' => 0, 'to_insert' => 0],
            'mst_soft_skills' => ['total' => count($source['mst_soft_skills']), 'mappable' => 0, 'skipped' => 0, 'to_insert' => 0],
            'mst_additionals' => ['total' => count($source['mst_additionals']), 'mappable' => 0, 'skipped' => 0, 'to_insert' => 0],
        ];

        foreach (self::MASTER_TABLES as $table) {
            foreach ($source[$table] as $row) {
                $sourceId = (int) ($row['id'] ?? 0);
                $oldPositionId = (int) ($row['id_job_position'] ?? 0);
                $sourcePositionName = $sourcePositionNames[$oldPositionId] ?? null;
                $resolution = $this->resolvePosition($sourcePositionName, $targetPositions);

                if ($resolution['status'] !== 'matched') {
                    $this->recordSkip($skips, $table . ':position_' . $resolution['status']);
                    $stats['skipped']++;
                    $stats[$table]['skipped']++;
                    continue;
                }

                $methods[$resolution['method']] = ($methods[$resolution['method']] ?? 0) + 1;
                $categoryId = (int) ($row['id_poin_kategori'] ?? 0);
                if (! isset($targetCategoryIdSet[$categoryId])) {
                    $this->recordSkip($skips, $table . ':missing_existing_category');
                    $stats['skipped']++;
                    $stats[$table]['skipped']++;
                    continue;
                }

                $data = $this->masterData($table, $row, (int) $resolution['position']['id']);
                $key = $this->masterKey($table, $data);
                $sourceMap[$table][$sourceId] = [
                    'key' => $key,
                    'source_position_name' => $sourcePositionName,
                    'target_position_id' => (int) $resolution['position']['id'],
                ];

                $stats['mappable']++;
                $stats[$table]['mappable']++;

                if (isset($existingKeys[$key]) || isset($inserts[$key])) {
                    continue;
                }

                $inserts[$key] = [
                    'table' => $table,
                    'key' => $key,
                    'data' => $data,
                ];
                $stats[$table]['to_insert']++;
            }
        }

        return [
            'inserts' => $inserts,
            'source_map' => $sourceMap,
            'skips' => $skips,
            'methods' => $methods,
            'stats' => $stats,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, array{id:int, position_name:string}> $targetPositions
     * @param array<int, int> $userPositionCandidatesByUser
     * @param array<int, bool> $targetUserIdSet
     * @param array<string, array<int, array<string, mixed>>> $sourceMap
     * @return array<string, mixed>
     */
    private function planSourceAssessments(
        array $rows,
        array $targetPositions,
        array $userPositionCandidatesByUser,
        array $targetUserIdSet,
        array $sourceMap,
    ): array {
        $planned = [];
        $skips = [];
        $methods = [];

        foreach ($rows as $row) {
            if ($this->yearFromDate($row['created_at'] ?? null) !== self::SOURCE_YEAR) {
                continue;
            }

            $sourceId = (int) ($row['id'] ?? 0);
            $userId = (int) ($row['id_user'] ?? 0);
            if ($userId <= 0 || ! isset($targetUserIdSet[$userId])) {
                $this->recordSkip($skips, 'trs_penilaian_tcs:user_not_found');
                continue;
            }

            $resolution = $this->resolvePosition(
                (string) ($row['id_job_position'] ?? ''),
                $targetPositions,
                $userPositionCandidatesByUser[$userId] ?? [],
                true,
            );
            if ($resolution['status'] !== 'matched') {
                $this->recordSkip($skips, 'trs_penilaian_tcs:position_' . $resolution['status']);
                continue;
            }
            $methods[$resolution['method']] = ($methods[$resolution['method']] ?? 0) + 1;

            $masterKeys = [];
            $hasCompetency = false;
            $invalidCompetency = false;
            foreach ([
                'id_tc' => 'mst_tcs',
                'id_sk' => 'mst_soft_skills',
                'id_ad' => 'mst_additionals',
            ] as $field => $masterTable) {
                $value = $this->nullableInt($row[$field] ?? null);
                if ($value === null) {
                    $masterKeys[$field] = null;
                    continue;
                }

                $hasCompetency = true;
                $mapping = $sourceMap[$masterTable][$value] ?? null;
                if ($mapping === null) {
                    $invalidCompetency = true;
                    break;
                }
                $masterKeys[$field] = $mapping['key'];
            }

            if (! $hasCompetency) {
                $this->recordSkip($skips, 'trs_penilaian_tcs:no_competency_reference');
                continue;
            }
            if ($invalidCompetency) {
                $this->recordSkip($skips, 'trs_penilaian_tcs:competency_not_mappable');
                continue;
            }

            $planned[] = [
                'kind' => 'source',
                'source_id' => $sourceId,
                'master_keys' => $masterKeys,
                'data' => [
                    'id_job_position' => (int) $resolution['position']['id'],
                    'id_user' => $userId,
                    'nilai_tc' => $this->nullableInt($row['nilai_tc'] ?? null),
                    'nilai_sk' => $this->nullableInt($row['nilai_sk'] ?? null),
                    'nilai_ad' => $this->nullableInt($row['nilai_ad'] ?? null),
                    'total_nilai' => $this->nullableInt($row['total_nilai'] ?? null),
                    'status' => (int) ($row['status'] ?? 0),
                    'tahun_penilaian' => self::SOURCE_YEAR,
                    'is_locked' => 1,
                    'created_at' => $row['created_at'] ?? null,
                    'updated_at' => $row['updated_at'] ?? null,
                    'modified_at' => $this->nullableInt($row['modified_at'] ?? null),
                    'modified_updated' => $row['modified_updated'] ?? null,
                ],
            ];
        }

        return [
            'rows' => $planned,
            'skips' => $skips,
            'methods' => $methods,
            'total_2025' => $this->countRowsForYear($rows, self::SOURCE_YEAR),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, array{id:int, position_name:string}> $targetPositions
     * @return array<string, mixed>
     */
    private function planSourceDetails(array $rows, array $targetPositions): array
    {
        $planned = [];
        $skips = [];
        $methods = [];

        foreach ($rows as $row) {
            if ($this->yearFromDate($row['created_at'] ?? null) !== self::SOURCE_YEAR) {
                continue;
            }

            $resolution = $this->resolvePosition((string) ($row['id_job_position'] ?? ''), $targetPositions);
            if ($resolution['status'] !== 'matched') {
                $this->recordSkip($skips, 'detail_penilaian_tcs:position_' . $resolution['status']);
                continue;
            }
            $methods[$resolution['method']] = ($methods[$resolution['method']] ?? 0) + 1;

            $planned[] = [
                'kind' => 'source',
                'source_id' => (int) ($row['id'] ?? 0),
                'data' => [
                    // The active controller routes history by the numeric target
                    // position id, while this legacy column remains VARCHAR.
                    'id_job_position' => (string) $resolution['position']['id'],
                    'name' => $row['name'] ?? null,
                    'keterangan_detail' => $row['keterangan_detail'] ?? null,
                    'keterangan_sebelum' => $row['keterangan_sebelum'] ?? null,
                    'catatan' => $row['catatan'] ?? null,
                    'created_at' => $row['created_at'] ?? null,
                    'updated_at' => $row['updated_at'] ?? null,
                    'modified_at' => $row['modified_at'] ?? '',
                ],
            ];
        }

        return [
            'rows' => $planned,
            'skips' => $skips,
            'methods' => $methods,
            'total_2025' => $this->countRowsForYear($rows, self::SOURCE_YEAR),
        ];
    }

    /**
     * @param array<int> $years
     * @param array<int, array{user_id:int, position_id:int, section_id:?int, role_id:?int, user_name:string, position_name:string}> $assignments
     * @param array<string, array<string, mixed>> $masterOptions
     * @param array<int, array{id:int, position_name:string}> $targetPositions
     * @return array<int, array<string, array<int, array<string, mixed>>>>
     */
    private function planDummyRows(
        array $years,
        int $dummyAssessmentsPerYear,
        int $dummyTrainingPerYear,
        array $assignments,
        array $masterOptions,
        array $targetPositions,
    ): array {
        $optionsByPosition = [];
        foreach ($masterOptions as $option) {
            $optionsByPosition[(int) $option['position_id']][] = $option;
        }
        foreach ($optionsByPosition as &$options) {
            usort($options, static function (array $a, array $b): int {
                return [$a['table'], $a['name'], $a['key']] <=> [$b['table'], $b['name'], $b['key']];
            });
        }
        unset($options);

        $candidates = [];
        foreach ($assignments as $assignment) {
            foreach ($optionsByPosition[$assignment['position_id']] ?? [] as $option) {
                $candidates[] = [
                    'assignment' => $assignment,
                    'option' => $option,
                    'candidate_key' => implode('|', [
                        $assignment['user_id'],
                        $assignment['position_id'],
                        $option['key'],
                    ]),
                ];
            }
        }

        $result = [];
        foreach ($years as $year) {
            $selected = $this->selectDummyCandidates($candidates, $dummyAssessmentsPerYear, (int) $year);
            $assessmentRows = [];
            $detailRows = [];
            $trainingRows = [];

            foreach ($selected as $index => $candidate) {
                $sequence = $index + 1;
                $assignment = $candidate['assignment'];
                $option = $candidate['option'];
                $status = $this->dummyAssessmentStatus((int) $year, $sequence, $dummyAssessmentsPerYear);
                $standard = max(1, (int) $option['standard']);
                $actual = $standard > 1 ? $standard - 1 : 0;
                $category = $option['category'];
                $masterKeys = [
                    'id_tc' => null,
                    'id_sk' => null,
                    'id_ad' => null,
                ];
                $masterKeys[$this->masterFieldForTable($option['table'])] = $option['key'];

                $assessmentMarker = sprintf('DUMMY-HR-CTD|TRS|%d|%03d', $year, $sequence);
                $detailMarker = sprintf('DUMMY-HR-CTD|DETAIL|%d|%03d', $year, $sequence);
                $timestamp = sprintf('%d-%02d-%02d %02d:00:00', $year, 1 + (($sequence - 1) % 12), 1 + (($sequence - 1) % 27), 8 + (($sequence - 1) % 9));

                $assessmentRows[] = [
                    'kind' => 'dummy',
                    'marker' => $assessmentMarker,
                    'sequence' => $sequence,
                    'master_keys' => $masterKeys,
                    'data' => [
                        'id_job_position' => $assignment['position_id'],
                        'id_user' => $assignment['user_id'],
                        'nilai_tc' => $masterKeys['id_tc'] !== null ? $actual : null,
                        'nilai_sk' => $masterKeys['id_sk'] !== null ? $actual : null,
                        'nilai_ad' => $masterKeys['id_ad'] !== null ? $actual : null,
                        'total_nilai' => $actual,
                        'status' => $status,
                        'tahun_penilaian' => $year,
                        'is_locked' => $year === 2025 ? 1 : 0,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                        'modified_at' => $assignment['user_id'],
                        'modified_updated' => $assessmentMarker,
                    ],
                ];

                $detailRows[] = [
                    'kind' => 'dummy',
                    'marker' => $detailMarker,
                    'sequence' => $sequence,
                    'data' => [
                        'id_job_position' => (string) $assignment['position_id'],
                        'name' => $assignment['user_name'],
                        'keterangan_detail' => sprintf(
                            '%s: %s = %d',
                            $this->categoryLabel($category),
                            $option['name'],
                            $actual,
                        ),
                        'keterangan_sebelum' => null,
                        'catatan' => $detailMarker,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                        'modified_at' => $detailMarker,
                    ],
                ];
            }

            $trainingLimit = min($dummyTrainingPerYear, $dummyAssessmentsPerYear);
            $trainingCandidates = $this->trainingCandidateIndexes($year, $dummyAssessmentsPerYear, $trainingLimit);
            foreach ($trainingCandidates as $trainingIndex => $assessmentIndex) {
                if (! isset($selected[$assessmentIndex])) {
                    continue;
                }

                $candidate = $selected[$assessmentIndex];
                $assessment = $assessmentRows[$assessmentIndex];
                $assignment = $candidate['assignment'];
                $option = $candidate['option'];
                $sequence = $assessment['sequence'];
                $status2 = $this->dummyTrainingStatus($year, $trainingIndex);
                $done = $status2 === 'Done';
                $trainingMarker = sprintf('DUMMY-HR-CTD|TRAINING|%d|%03d', $year, $trainingIndex + 1);
                $standard = max(1, (int) $option['standard']);
                $actual = $standard > 1 ? $standard - 1 : 0;

                $trainingRows[] = [
                    'kind' => 'dummy',
                    'marker' => $trainingMarker,
                    'assessment_marker' => $assessment['marker'],
                    'sequence' => $sequence,
                    'master_keys' => $assessment['master_keys'],
                    'data' => $this->dummyTrainingData(
                        $year,
                        $assignment,
                        $option,
                        $assessment['master_keys'],
                        $assessment['marker'],
                        $trainingMarker,
                        $status2,
                        $done,
                        $standard,
                        $actual,
                    ),
                ];
            }

            $result[(int) $year] = [
                'assessments' => $assessmentRows,
                'details' => $detailRows,
                'training' => $trainingRows,
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $candidates
     * @return array<int, array<string, mixed>>
     */
    private function selectDummyCandidates(array $candidates, int $limit, int $year): array
    {
        if ($limit === 0 || $candidates === []) {
            return [];
        }

        $selected = [];
        $used = [];
        $start = (($year - 2025) * 37) % count($candidates);
        for ($offset = 0; $offset < count($candidates) && count($selected) < $limit; $offset++) {
            $candidate = $candidates[($start + $offset) % count($candidates)];
            if (isset($used[$candidate['candidate_key']])) {
                continue;
            }
            $used[$candidate['candidate_key']] = true;
            $selected[] = $candidate;
        }

        if (count($selected) < $limit) {
            throw new RuntimeException(sprintf(
                'Kandidat dummy tidak cukup untuk %d penilaian tahun %d; hanya %d yang tersedia.',
                $limit,
                $year,
                count($selected),
            ));
        }

        return $selected;
    }

    /**
     * @return array<int>
     */
    private function trainingCandidateIndexes(int $year, int $assessmentCount, int $trainingCount): array
    {
        if ($trainingCount === 0 || $assessmentCount === 0) {
            return [];
        }

        if ($year === 2025) {
            return range(0, min($trainingCount, $assessmentCount) - 1);
        }

        // 2026 uses the last 25 final/gap rows (statuses 4).
        $finalCount = min($trainingCount, $assessmentCount);
        $firstFinalIndex = max(0, $assessmentCount - $finalCount);
        return range($firstFinalIndex, min($assessmentCount - 1, $firstFinalIndex + $finalCount - 1));
    }

    private function dummyAssessmentStatus(int $year, int $sequence, int $total): int
    {
        if ($year === 2025) {
            return 4;
        }

        if ($sequence <= min(10, $total)) {
            return 1;
        }
        if ($sequence <= min(18, $total)) {
            return 2;
        }
        if ($sequence <= min(25, $total)) {
            return 3;
        }

        return 4;
    }

    private function dummyTrainingStatus(int $year, int $index): string
    {
        if ($year === 2025) {
            return 'Done';
        }

        return match ($index % 5) {
            0 => 'Pending',
            1 => 'Mencari Vendor',
            2 => 'Proses Pendaftaran',
            3 => 'On Progress',
            default => 'Done',
        };
    }

    /**
     * @param array{user_id:int, position_id:int, section_id:?int, role_id:?int, user_name:string, position_name:string} $assignment
     * @param array<string, mixed> $option
     * @param array<string, string|null> $masterKeys
     * @return array<string, mixed>
     */
    private function dummyTrainingData(
        int $year,
        array $assignment,
        array $option,
        array $masterKeys,
        string $assessmentMarker,
        string $trainingMarker,
        string $status2,
        bool $done,
        int $standard,
        int $actual,
    ): array {
        $date = sprintf('%d-12-%02d', $year, 10 + (($assignment['user_id'] + $option['source_id']) % 18));
        $evaluation = $done ? [
            'relevansi' => 'Ya',
            'alasan_relevansi' => 'Materi sesuai gap competency.',
            'rekomendasi' => 'Lanjutkan',
            'alasan_rekomendasi' => 'Peserta dapat menerapkan materi.',
            'kelengkapan_materi' => 'Lengkap',
            'metode_pengajaran' => 'Mudah Dimengerti',
            'fasilitas' => 'Lengkap',
            'lainnya_1' => null,
            'metode_evaluasi' => 'Post-test',
            'minat' => 'Tinggi',
            'daya_serap' => 'Menguasai Materi',
            'penerapan' => 'Cepat',
            'lainnya_2' => null,
            'diketahui' => $assignment['user_name'],
            'dievaluasi' => 'HRGA DUMMY',
            'tgl_pengajuan' => $date,
            'tgl_konfirm' => $date,
            'lokasi' => 'Training Room',
            'efektif' => 'Efektif',
            'catatan_tambahan' => 'Data dummy untuk pengujian workflow.',
        ] : [
            'relevansi' => null,
            'alasan_relevansi' => null,
            'rekomendasi' => null,
            'alasan_rekomendasi' => null,
            'kelengkapan_materi' => null,
            'metode_pengajaran' => null,
            'fasilitas' => null,
            'lainnya_1' => null,
            'metode_evaluasi' => null,
            'minat' => null,
            'daya_serap' => null,
            'penerapan' => null,
            'lainnya_2' => null,
            'diketahui' => null,
            'dievaluasi' => null,
            'tgl_pengajuan' => null,
            'tgl_konfirm' => null,
            'lokasi' => null,
            'efektif' => null,
            'catatan_tambahan' => null,
        ];

        $data = array_merge([
            'id_role' => $assignment['role_id'],
            'id_job_position' => $assignment['position_id'],
            'id_user' => $assignment['user_id'],
            'section_id' => $assignment['section_id'],
            'id_trs' => null,
            'program_training' => sprintf('[DUMMY %d] Development - %s', $year, $option['name']),
            'program_training_plan' => $done ? sprintf('[DUMMY %d] Development - %s', $year, $option['name']) : null,
            'kategori_competency' => $option['category'],
            'competency' => sprintf('%s - std: %d - aktual: %d', $option['name'], $standard, $actual),
            'due_date' => $date,
            'due_date_plan' => $done ? $date : null,
            'lembaga' => 'ADASI Learning Center',
            'lembaga_plan' => $done ? 'ADASI Learning Center' : null,
            'keterangan_tujuan' => 'Menutup gap competency untuk kebutuhan pekerjaan.',
            'keterangan_plan' => $done ? 'Target pembelajaran tercapai.' : null,
            'keterangan_tolak' => null,
            'biaya' => '5000000',
            'biaya_plan' => $done ? '4500000' : null,
            'tahun_aktual' => (string) $year,
            'tahun_usulan' => (string) $year,
            'file' => null,
            'file_name' => null,
            'status_1' => 3,
            'status_2' => $status2,
            'modified_at' => $assignment['user_name'],
            'modified_updated' => $trainingMarker,
            'is_sharing_knowledge' => 0,
            'objective_learning' => 'Peserta memahami dan mempraktikkan competency terkait.',
            'objective_learning_aktual' => $done ? 'Peserta memahami dan mempraktikkan competency terkait.' : null,
            'sharing_knowledge' => null,
            'assessment_marker' => $assessmentMarker,
        ], $evaluation);

        unset($data['assessment_marker']);

        return $data;
    }

    /**
     * Add deterministic technical masters only when a target position has no
     * usable source/current competency for dummy generation.
     *
     * @param array<string, mixed> $masterPlan
     * @param array<string, array<string, mixed>> $masterOptions
     * @param array<int, array{user_id:int, position_id:int, section_id:?int, role_id:?int, user_name:string, position_name:string}> $assignments
     * @param array<int, array{id:int, position_name:string}> $targetPositions
     */
    private function addFallbackDummyMasters(
        array &$masterPlan,
        array &$masterOptions,
        array $assignments,
        array $targetPositions,
        int $dummyAssessmentsPerYear,
    ): void {
        if ($dummyAssessmentsPerYear === 0) {
            return;
        }

        $optionsByPosition = [];
        foreach ($masterOptions as $option) {
            $optionsByPosition[(int) $option['position_id']] = true;
        }

        $positionNames = [];
        foreach ($targetPositions as $position) {
            $positionNames[(int) $position['id']] = $position['position_name'];
        }

        foreach ($assignments as $assignment) {
            if (isset($optionsByPosition[$assignment['position_id']])) {
                continue;
            }

            $positionName = $positionNames[$assignment['position_id']] ?? ('Position ' . $assignment['position_id']);
            $data = [
                'id_job_position' => $assignment['position_id'],
                'id_poin_kategori' => 1,
                'keterangan_tc' => '[DUMMY] Competency - ' . $positionName,
                'deskripsi_tc' => 'Competency dummy untuk pengujian data training development.',
                'nilai' => 3,
                'created_at' => '2025-01-01 00:00:00',
                'updated_at' => '2025-01-01 00:00:00',
            ];
            $key = $this->masterKey('mst_tcs', $data);
            if (! isset($masterPlan['inserts'][$key])) {
                $masterPlan['inserts'][$key] = [
                    'table' => 'mst_tcs',
                    'key' => $key,
                    'data' => $data,
                ];
                $masterPlan['stats']['mst_tcs']['to_insert']++;
            }
            $optionsByPosition[$assignment['position_id']] = true;
        }
    }

    /**
     * @param array<string, array<string, mixed>> $plannedInserts
     * @return array<string, array<string, mixed>>
     */
    private function buildMasterOptions(array $plannedInserts): array
    {
        $options = [];
        foreach ($this->loadExistingMasterRows() as $table => $rows) {
            foreach ($rows as $row) {
                $data = (array) $row;
                $key = $this->masterKey($table, $data);
                $options[$key] = $this->masterOption($table, $key, $data, (int) ($data['id'] ?? 0));
            }
        }

        foreach ($plannedInserts as $entry) {
            $options[$entry['key']] = $this->masterOption($entry['table'], $entry['key'], $entry['data'], null);
        }

        return $options;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function loadExistingMasterKeys(): array
    {
        $keys = [];
        foreach ($this->loadExistingMasterRows() as $table => $rows) {
            foreach ($rows as $row) {
                $data = (array) $row;
                $keys[$this->masterKey($table, $data)] = (int) ($data['id'] ?? 0);
            }
        }

        return $keys;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function loadExistingMasterRows(): array
    {
        $rows = [];
        foreach (self::MASTER_TABLES as $table) {
            $rows[$table] = DB::table($table)->orderBy('id')->get()->map(static fn ($row): array => (array) $row)->all();
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function masterOption(string $table, string $key, array $data, ?int $id): array
    {
        $nameField = match ($table) {
            'mst_tcs' => 'keterangan_tc',
            'mst_soft_skills' => 'keterangan_sk',
            default => 'keterangan_ad',
        };
        $category = match ($table) {
            'mst_tcs' => 'technical',
            'mst_soft_skills' => 'nontechnical',
            default => 'additional',
        };

        return [
            'table' => $table,
            'key' => $key,
            'id' => $id,
            'position_id' => (int) ($data['id_job_position'] ?? 0),
            'name' => (string) ($data[$nameField] ?? '[DUMMY] Competency'),
            'standard' => max(1, (int) ($data['nilai'] ?? 3)),
            'category' => $category,
            'source_id' => (int) ($data['id'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function masterData(string $table, array $row, int $targetPositionId): array
    {
        $fields = match ($table) {
            'mst_tcs' => [
                'id_job_position' => $targetPositionId,
                'id_poin_kategori' => (int) ($row['id_poin_kategori'] ?? 0),
                'keterangan_tc' => $row['keterangan_tc'] ?? null,
                'deskripsi_tc' => $row['deskripsi_tc'] ?? null,
                'nilai' => $this->nullableInt($row['nilai'] ?? null),
            ],
            'mst_soft_skills' => [
                'id_job_position' => $targetPositionId,
                'id_poin_kategori' => (int) ($row['id_poin_kategori'] ?? 0),
                'keterangan_sk' => $row['keterangan_sk'] ?? null,
                'deskripsi_sk' => $row['deskripsi_sk'] ?? null,
                'nilai' => $this->nullableInt($row['nilai'] ?? null),
            ],
            default => [
                'id_job_position' => $targetPositionId,
                'id_poin_kategori' => $this->nullableInt($row['id_poin_kategori'] ?? null),
                'keterangan_ad' => $row['keterangan_ad'] ?? null,
                'deskripsi_ad' => $row['deskripsi_ad'] ?? null,
                'nilai' => $this->nullableInt($row['nilai'] ?? null),
            ],
        };

        foreach (['sub_kategori', 'deskripsi_level_1', 'deskripsi_level_2', 'deskripsi_level_3', 'deskripsi_level_4'] as $optional) {
            if (array_key_exists($optional, $row) && $this->targetHasColumn($table, $optional)) {
                $fields[$optional] = $row[$optional];
            }
        }

        foreach (['created_at', 'updated_at'] as $timestamp) {
            if (array_key_exists($timestamp, $row) && $row[$timestamp] !== null && $this->targetHasColumn($table, $timestamp)) {
                $fields[$timestamp] = $row[$timestamp];
            }
        }

        return $this->onlyTargetColumns($table, $fields);
    }

    /**
     * Natural key deliberately excludes mutable description/score fields. If a
     * master already exists for position/category/name, it is reused and never
     * updated by this importer.
     *
     * @param array<string, mixed> $data
     */
    private function masterKey(string $table, array $data): string
    {
        $nameField = match ($table) {
            'mst_tcs' => 'keterangan_tc',
            'mst_soft_skills' => 'keterangan_sk',
            default => 'keterangan_ad',
        };

        return implode('|', [
            $table,
            (int) ($data['id_job_position'] ?? 0),
            (int) ($data['id_poin_kategori'] ?? 0),
            $this->normalizeText((string) ($data[$nameField] ?? '')),
        ]);
    }

    private function masterFieldForTable(string $table): string
    {
        return match ($table) {
            'mst_tcs' => 'id_tc',
            'mst_soft_skills' => 'id_sk',
            default => 'id_ad',
        };
    }

    private function categoryLabel(string $category): string
    {
        return match ($category) {
            'technical' => 'Technical Competency',
            'nontechnical' => 'Non-Competency (Soft Skills)',
            default => 'Additional',
        };
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function countRowsForYear(array $rows, int $year): int
    {
        return count(array_filter($rows, fn (array $row): bool => $this->yearFromDate($row['created_at'] ?? null) === $year));
    }

    private function sourceTrainingYear(array $row): ?int
    {
        foreach (['tahun_aktual', 'tahun_usulan'] as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if (preg_match('/^\d{4}$/', $value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function yearFromDate(mixed $value): ?int
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-/', $value, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{rows:array<int, array<string, mixed>>, skips:array<string, int>, methods:array<string, int>, total_2025:int}
     */
    private function reportRows(array $rows, string $table, string $kind, ?int $sourceTotal = null): array
    {
        $total = count($rows);
        $existing = 0;
        $duplicates = 0;
        $seen = [];
        foreach ($rows as $row) {
            $signature = $this->plannedRowSignature($table, $row);
            if (isset($seen[$signature])) {
                $duplicates++;
                continue;
            }
            $seen[$signature] = true;

            if ($this->plannedRowExists($table, $row, $kind)) {
                $existing++;
            }
        }

        $reportedTotal = $kind === 'source' && $sourceTotal !== null ? $sourceTotal : $total;

        return [
            'total' => $reportedTotal,
            'mappable' => $total,
            'skipped' => max(0, $reportedTotal - $total),
            'existing' => $existing,
            'duplicates' => $duplicates,
            'to_insert' => max(0, $total - $existing - $duplicates),
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function plannedRowSignature(string $table, array $row): string
    {
        $payload = [
            'table' => $table,
            'data' => $row['data'] ?? $row,
            'master_keys' => $row['master_keys'] ?? null,
        ];

        return hash('sha256', (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function plannedRowExists(string $table, array $row, string $kind): bool
    {
        if ($kind === 'dummy') {
            $marker = $row['marker'] ?? null;
            if ($marker === null) {
                return false;
            }
            $markerColumn = $table === 'detail_penilaian_tcs' ? 'modified_at' : 'modified_updated';
            return DB::table($table)->where($markerColumn, $marker)->exists();
        }

        if ($table === 'trs_penilaian_tcs' && isset($row['data'])) {
            foreach ($row['master_keys'] ?? [] as $masterKey) {
                if ($masterKey !== null && ! isset($this->loadExistingMasterIds()[$masterKey])) {
                    return false;
                }
            }

            return $this->rowExists(
                $table,
                $this->materializeAssessmentData($row, $this->loadExistingMasterIds()),
            );
        }

        if ($table === 'detail_penilaian_tcs' && isset($row['data'])) {
            return $this->rowExists($table, $row['data']);
        }

        return false;
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $skipSets
     * @return array<string, int>
     */
    private function mergeSkipStats(array ...$skipSets): array
    {
        $result = [];
        foreach ($skipSets as $skipSet) {
            foreach ($skipSet as $reason => $count) {
                $result[$reason] = ($result[$reason] ?? 0) + (int) $count;
            }
        }

        ksort($result);
        return $result;
    }

    /**
     * @param array<string, array<string, int>> $methodSets
     * @return array<string, int>
     */
    private function mergeMethodStats(array ...$methodSets): array
    {
        $result = [];
        foreach ($methodSets as $methods) {
            foreach ($methods as $method => $count) {
                $result[$method] = ($result[$method] ?? 0) + (int) $count;
            }
        }

        ksort($result);
        return $result;
    }

    /**
     * @param array<string, int> $skips
     */
    private function recordSkip(array &$skips, string $reason): void
    {
        $skips[$reason] = ($skips[$reason] ?? 0) + 1;
    }

    /**
     * @param array<string, mixed> $plan
     * @return array<string, mixed>
     */
    private function applyPlan(array $plan): array
    {
        $masterIds = $this->loadExistingMasterIds();
        $insertedMasters = 0;
        foreach ($plan['master_plan']['inserts'] as $entry) {
            $id = $masterIds[$entry['key']] ?? null;
            if ($id === null) {
                $id = DB::table($entry['table'])->insertGetId($entry['data']);
                $insertedMasters++;
            }
            $masterIds[$entry['key']] = (int) $id;
            $this->masterIdsCache[$entry['key']] = (int) $id;
        }

        $sourceAssessmentInserted = 0;
        $sourceAssessmentExisting = 0;
        foreach ($plan['source_assessments']['rows'] as $row) {
            $data = $this->materializeAssessmentData($row, $masterIds);
            if ($this->rowExists('trs_penilaian_tcs', $data)) {
                $sourceAssessmentExisting++;
                continue;
            }
            DB::table('trs_penilaian_tcs')->insert($data);
            $sourceAssessmentInserted++;
        }

        $sourceDetailInserted = 0;
        $sourceDetailExisting = 0;
        foreach ($plan['source_details']['rows'] as $row) {
            if ($this->rowExists('detail_penilaian_tcs', $row['data'])) {
                $sourceDetailExisting++;
                continue;
            }
            DB::table('detail_penilaian_tcs')->insert($this->onlyTargetColumns('detail_penilaian_tcs', $row['data']));
            $sourceDetailInserted++;
        }

        $dummyResult = [];
        foreach ($plan['dummy'] as $year => $dummyRows) {
            $assessmentIdsByMarker = [];
            $assessmentInserted = 0;
            $assessmentExisting = 0;
            foreach ($dummyRows['assessments'] as $row) {
                $data = $this->materializeAssessmentData($row, $masterIds);
                $existing = DB::table('trs_penilaian_tcs')
                    ->where('modified_updated', $row['marker'])
                    ->first(['id']);
                if ($existing !== null) {
                    $assessmentIdsByMarker[$row['marker']] = (int) $existing->id;
                    $assessmentExisting++;
                    continue;
                }
                $assessmentIdsByMarker[$row['marker']] = (int) DB::table('trs_penilaian_tcs')->insertGetId($data);
                $assessmentInserted++;
            }

            $detailInserted = 0;
            $detailExisting = 0;
            foreach ($dummyRows['details'] as $row) {
                if (DB::table('detail_penilaian_tcs')->where('modified_at', $row['marker'])->exists()) {
                    $detailExisting++;
                    continue;
                }
                DB::table('detail_penilaian_tcs')->insert($this->onlyTargetColumns('detail_penilaian_tcs', $row['data']));
                $detailInserted++;
            }

            $trainingInserted = 0;
            $trainingExisting = 0;
            foreach ($dummyRows['training'] as $row) {
                $assessmentId = $assessmentIdsByMarker[$row['assessment_marker']] ?? null;
                if ($assessmentId === null) {
                    throw new RuntimeException('Training dummy tidak memiliki assessment dummy terkait: ' . $row['marker']);
                }
                $data = $row['data'];
                $data['id_trs'] = $assessmentId;
                $data = $this->onlyTargetColumns('mst_pd_pengajuans', $data);
                if (DB::table('mst_pd_pengajuans')->where('modified_updated', $row['marker'])->exists()) {
                    $trainingExisting++;
                    continue;
                }
                DB::table('mst_pd_pengajuans')->insert($data);
                $trainingInserted++;
            }

            $dummyResult[(string) $year] = [
                'assessments' => ['inserted' => $assessmentInserted, 'existing' => $assessmentExisting],
                'details' => ['inserted' => $detailInserted, 'existing' => $detailExisting],
                'training' => ['inserted' => $trainingInserted, 'existing' => $trainingExisting],
            ];
        }

        return [
            'masters' => ['inserted' => $insertedMasters],
            'source_assessments_2025' => [
                'inserted' => $sourceAssessmentInserted,
                'existing' => $sourceAssessmentExisting,
            ],
            'source_details_2025' => [
                'inserted' => $sourceDetailInserted,
                'existing' => $sourceDetailExisting,
            ],
            'source_training_2025' => ['inserted' => 0, 'existing' => 0],
            'dummy' => $dummyResult,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, int> $masterIds
     * @return array<string, mixed>
     */
    private function materializeAssessmentData(array $row, array $masterIds): array
    {
        $data = $row['data'];
        foreach (['id_tc', 'id_sk', 'id_ad'] as $field) {
            $key = $row['master_keys'][$field] ?? null;
            $data[$field] = $key === null ? null : ($masterIds[$key] ?? null);
            if ($key !== null && $data[$field] === null) {
                throw new RuntimeException('Tidak dapat menemukan ID competency target untuk key: ' . $key);
            }
        }

        return $this->onlyTargetColumns('trs_penilaian_tcs', $data);
    }

    /**
     * @return array<string, int>
     */
    private function loadExistingMasterIds(): array
    {
        if ($this->masterIdsCache !== null) {
            return $this->masterIdsCache;
        }

        $ids = [];
        foreach ($this->loadExistingMasterRows() as $table => $rows) {
            foreach ($rows as $row) {
                $data = (array) $row;
                $ids[$this->masterKey($table, $data)] = (int) ($data['id'] ?? 0);
            }
        }

        $this->masterIdsCache = $ids;
        return $ids;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function rowExists(string $table, array $data): bool
    {
        $query = DB::table($table);
        foreach ($this->onlyTargetColumns($table, $data) as $column => $value) {
            if ($value === null) {
                $query->whereNull($column);
            } else {
                $query->where($column, $value);
            }
        }

        return $query->exists();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function onlyTargetColumns(string $table, array $data): array
    {
        $columns = array_flip($this->targetColumns($table));
        return array_intersect_key($data, $columns);
    }

    /**
     * @return array<int, string>
     */
    private function targetColumns(string $table): array
    {
        static $cache = [];
        if (! isset($cache[$table])) {
            $cache[$table] = Schema::getColumnListing($table);
        }

        return $cache[$table];
    }

    private function targetHasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->targetColumns($table), true);
    }

    private function validateTargetSchema(): void
    {
        $required = [
            'mst_tcs' => ['id', 'id_job_position', 'id_poin_kategori', 'keterangan_tc', 'deskripsi_tc', 'nilai'],
            'mst_soft_skills' => ['id', 'id_job_position', 'id_poin_kategori', 'keterangan_sk', 'deskripsi_sk', 'nilai'],
            'mst_additionals' => ['id', 'id_job_position', 'id_poin_kategori', 'keterangan_ad', 'deskripsi_ad', 'nilai'],
            'tc_poin_kategoris' => ['id'],
            'mst_job_positions' => ['id', 'position_name', 'is_active'],
            'users' => ['id'],
            'user_job_positions' => ['user_id', 'mst_job_position_id', 'is_active'],
            'trs_penilaian_tcs' => [
                'id', 'id_job_position', 'id_tc', 'id_sk', 'id_ad', 'id_user',
                'nilai_tc', 'nilai_sk', 'nilai_ad', 'total_nilai', 'status',
                'tahun_penilaian', 'is_locked', 'created_at', 'updated_at',
                'modified_at', 'modified_updated',
            ],
            'detail_penilaian_tcs' => [
                'id', 'id_job_position', 'name', 'keterangan_detail',
                'keterangan_sebelum', 'catatan', 'created_at', 'updated_at', 'modified_at',
            ],
            'mst_pd_pengajuans' => [
                'id', 'id_role', 'id_job_position', 'id_user', 'section_id',
                'id_tc', 'id_sk', 'id_ad', 'id_trs', 'program_training',
                'kategori_competency', 'competency', 'tahun_aktual', 'tahun_usulan',
                'status_1', 'status_2', 'created_at', 'updated_at', 'modified_at', 'modified_updated',
            ],
        ];

        foreach ($required as $table => $columns) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException('Table target tidak ditemukan: ' . $table);
            }
            foreach ($columns as $column) {
                if (! $this->targetHasColumn($table, $column)) {
                    throw new RuntimeException(sprintf(
                        'Schema target belum siap: %s.%s tidak tersedia. Tidak ada migration yang dijalankan oleh importer.',
                        $table,
                        $column,
                    ));
                }
            }
        }
    }

    /**
     * @param array<int, array{id:int, position_name:string}> $targetPositions
     * @return array<int, array{user_id:int, position_id:int, section_id:?int, role_id:?int, user_name:string, position_name:string}>
     */
    private function loadActiveAssignments(array $targetPositions): array
    {
        $positionsById = [];
        foreach ($targetPositions as $position) {
            $positionsById[(int) $position['id']] = $position;
        }

        $rows = DB::table('user_job_positions as ujp')
            ->join('users as u', 'u.id', '=', 'ujp.user_id')
            ->join('mst_job_positions as p', 'p.id', '=', 'ujp.mst_job_position_id')
            ->where('ujp.is_active', true)
            ->where('p.is_active', true)
            ->where(function ($query): void {
                $query->whereNull('ujp.effective_from')->orWhereDate('ujp.effective_from', '<=', now()->toDateString());
            })
            ->where(function ($query): void {
                $query->whereNull('ujp.effective_until')->orWhereDate('ujp.effective_until', '>=', now()->toDateString());
            })
            ->orderBy('ujp.user_id')
            ->orderBy('ujp.mst_job_position_id')
            ->get([
                'ujp.user_id',
                'ujp.mst_job_position_id as position_id',
                'p.section_id',
                'u.role_id',
                'u.name as user_name',
                'p.position_name',
            ]);

        return $rows->map(static fn ($row): array => [
            'user_id' => (int) $row->user_id,
            'position_id' => (int) $row->position_id,
            'section_id' => $row->section_id === null ? null : (int) $row->section_id,
            'role_id' => $row->role_id === null ? null : (int) $row->role_id,
            'user_name' => (string) $row->user_name,
            'position_name' => (string) $row->position_name,
        ])->all();
    }

    /**
     * @return array<int, array<int>>
     */
    private function loadActiveUserPositionCandidates(): array
    {
        $rows = DB::table('user_job_positions as ujp')
            ->join('mst_job_positions as p', 'p.id', '=', 'ujp.mst_job_position_id')
            ->where('ujp.is_active', true)
            ->where('p.is_active', true)
            ->where(function ($query): void {
                $query->whereNull('ujp.effective_from')->orWhereDate('ujp.effective_from', '<=', now()->toDateString());
            })
            ->where(function ($query): void {
                $query->whereNull('ujp.effective_until')->orWhereDate('ujp.effective_until', '>=', now()->toDateString());
            })
            ->get(['ujp.user_id', 'ujp.mst_job_position_id']);

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row->user_id][] = (int) $row->mst_job_position_id;
        }

        foreach ($result as $userId => $positionIds) {
            $result[$userId] = array_values(array_unique($positionIds));
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function rowsById(array $tables): array
    {
        $result = [];
        foreach ($tables as $table) {
            $result[$table] = [];
            foreach (DB::table($table)->orderBy('id')->get() as $row) {
                $data = (array) $row;
                $result[$table][(string) ($data['id'] ?? '')] = $data;
            }
        }

        return $result;
    }

    /**
     * @param array<int, string> $tables
     * @return array<string, string>
     */
    private function fingerprintTables(array $tables): array
    {
        $fingerprints = [];
        foreach ($tables as $table) {
            $rows = DB::table($table)->orderBy('id')->get()->map(static fn ($row): array => (array) $row)->all();
            $fingerprints[$table] = hash('sha256', (string) json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return $fingerprints;
    }

    /**
     * @return array<string, string>
     */
    private function positionAliases(): array
    {
        $aliases = [
            'leader ht' => 'ht leader',
            'operator ht' => 'ht operator',
            'machining operator' => 'mc operator',
            'operator mc' => 'mc operator',
            'machining custom sec head' => 'machining mc custom sec head',
            'mc custom sec head' => 'machining mc custom sec head',
            'finance accounting sec head' => 'finance accounting sec head',
            'foreman ct' => 'cutting foreman',
            'produksi ht sec head' => 'production heat treatment sect head',
            'production ht sec head' => 'production heat treatment sect head',
            'logistic foreman' => 'logistic warehouse foreman',
            'sales engineer reg 4' => 'sales engineer region 4',
            'hr legal staff' => 'hrga legal staff',
            'dept head fin acc hrga' => 'finance accounting hrga dept head',
            'dept head pdca proc inv it' => 'pdca proc inv it dept head',
            'sales dept 1 2' => 'sales dept head region 1 2',
            'sales dept 3 4' => 'sales dept head region 3 4',
            'procurement material staff' => 'procurement staff',
            'ppic staff' => 'ppc staff',
        ];

        $normalized = [];
        foreach ($aliases as $source => $target) {
            $normalized[$this->normalizePositionName($source)] = $this->normalizePositionName($target);
        }

        return $normalized;
    }

    private function normalizeText(string $value): string
    {
        $value = trim($value);
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @return array<int, string>
     */
    private function splitSqlList(string $value): array
    {
        $parts = [];
        $buffer = '';
        $inQuote = false;
        $escape = false;
        $length = strlen($value);

        for ($index = 0; $index < $length; $index++) {
            $character = $value[$index];
            if ($inQuote) {
                $buffer .= $character;
                if ($escape) {
                    $escape = false;
                } elseif ($character === '\\') {
                    $escape = true;
                } elseif ($character === "'") {
                    if (($value[$index + 1] ?? '') === "'") {
                        $buffer .= $value[++$index];
                    } else {
                        $inQuote = false;
                    }
                }
                continue;
            }

            if ($character === "'") {
                $inQuote = true;
                $buffer .= $character;
            } elseif ($character === ',') {
                $parts[] = trim($buffer, " `\t\r\n");
                $buffer = '';
            } else {
                $buffer .= $character;
            }
        }

        if (trim($buffer) !== '') {
            $parts[] = trim($buffer, " `\t\r\n");
        }

        return $parts;
    }

    private function readUntilStatementEnd(string $sql, int $start): string
    {
        $inQuote = false;
        $escape = false;
        $length = strlen($sql);

        for ($index = $start; $index < $length; $index++) {
            $character = $sql[$index];
            if ($inQuote) {
                if ($escape) {
                    $escape = false;
                } elseif ($character === '\\') {
                    $escape = true;
                } elseif ($character === "'") {
                    if (($sql[$index + 1] ?? '') === "'") {
                        $index++;
                    } else {
                        $inQuote = false;
                    }
                }
                continue;
            }

            if ($character === "'") {
                $inQuote = true;
            } elseif ($character === ';') {
                return substr($sql, $start, $index - $start);
            }
        }

        return substr($sql, $start);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseValueTuples(string $values): array
    {
        $tuples = [];
        $length = strlen($values);
        $inQuote = false;
        $escape = false;
        $depth = 0;
        $tupleStart = null;

        for ($index = 0; $index < $length; $index++) {
            $character = $values[$index];
            if ($inQuote) {
                if ($escape) {
                    $escape = false;
                } elseif ($character === '\\') {
                    $escape = true;
                } elseif ($character === "'") {
                    if (($values[$index + 1] ?? '') === "'") {
                        $index++;
                    } else {
                        $inQuote = false;
                    }
                }
                continue;
            }

            if ($character === "'") {
                $inQuote = true;
                continue;
            }
            if ($character === '(') {
                if ($depth === 0) {
                    $tupleStart = $index + 1;
                }
                $depth++;
                continue;
            }
            if ($character === ')') {
                $depth--;
                if ($depth === 0 && $tupleStart !== null) {
                    $tuples[] = $this->splitSqlList(substr($values, $tupleStart, $index - $tupleStart));
                    $tupleStart = null;
                }
            }
        }

        return $tuples;
    }

    private function decodeSqlValue(string $token): mixed
    {
        $token = trim($token);
        if (strcasecmp($token, 'NULL') === 0) {
            return null;
        }

        if (strlen($token) >= 2 && $token[0] === "'" && $token[strlen($token) - 1] === "'") {
            $value = substr($token, 1, -1);
            $value = str_replace("''", "'", $value);
            $value = preg_replace_callback('/\\\\(.)/s', static function (array $matches): string {
                return match ($matches[1]) {
                    '0' => "\0",
                    'b' => "\x08",
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    'Z' => "\x1A",
                    default => $matches[1],
                };
            }, $value) ?? $value;

            return $value;
        }

        return $token;
    }
}
