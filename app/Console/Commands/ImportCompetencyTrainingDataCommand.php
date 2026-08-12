<?php

namespace App\Console\Commands;

use App\Services\HR\CompetencyTrainingDataImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportCompetencyTrainingDataCommand extends Command
{
    protected $signature = 'hr:import-competency-training
        {--source=dms_adasi_rev1 (4).sql : Path to the legacy SQL dump}
        {--dry-run : Parse and report without writing}
        {--apply : Apply insert-only import in one transaction}
        {--dummy-assessments=50 : Dummy competency assessments per year}
        {--dummy-training=25 : Dummy training records per year}';

    protected $description = 'Import competency assessment/history and deterministic training dummy data safely.';

    public function handle(CompetencyTrainingDataImportService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $apply = (bool) $this->option('apply');
        if ($dryRun === $apply) {
            $this->error('Pilih tepat satu mode: --dry-run atau --apply.');
            return self::INVALID;
        }

        $dummyAssessments = filter_var($this->option('dummy-assessments'), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        $dummyTraining = filter_var($this->option('dummy-training'), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        if ($dummyAssessments === null || $dummyTraining === null || $dummyAssessments < 0 || $dummyTraining < 0) {
            $this->error('Nilai --dummy-assessments dan --dummy-training harus integer >= 0.');
            return self::INVALID;
        }

        $source = (string) $this->option('source');
        if (! $this->isAbsolutePath($source)) {
            $source = base_path($source);
        }

        try {
            $report = $dryRun
                ? $service->inspect($source, $dummyAssessments, $dummyTraining)
                : $service->import($source, $dummyAssessments, $dummyTraining);
        } catch (Throwable $exception) {
            $this->error('Import dibatalkan: ' . $exception->getMessage());
            return self::FAILURE;
        }

        $this->renderReport($report, $dryRun);
        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderReport(array $report, bool $dryRun): void
    {
        $this->info($dryRun ? 'DRY-RUN: tidak ada perubahan database.' : 'IMPORT: transaksi berhasil commit.');
        $this->line('Source: ' . $report['source_path']);
        $this->line('');
        $this->line('Master competency sumber:');
        foreach ($report['source_tables'] as $table => $stats) {
            $this->line(sprintf(
                '  %-18s total=%d mapped=%d skipped=%d to_insert=%d',
                $table,
                $stats['total'],
                $stats['mappable'],
                $stats['skipped'],
                $stats['to_insert'],
            ));
        }
        $this->line('  Master target insert-only: ' . $report['masters']['to_insert']);

        $this->renderRowReport('Penilaian sumber 2025', $report['source_assessments_2025']);
        $this->renderRowReport('History sumber 2025', $report['source_details_2025']);
        $this->line(sprintf(
            'Training sumber 2025: total=%d skipped=%d (training sumber tahun lain=%d)',
            $report['source_training_2025']['total'],
            $report['source_training_2025']['skipped'],
            $report['source_training_2025']['source_rows_other_years'],
        ));

        foreach ($report['dummy'] as $year => $dummy) {
            $this->line('');
            $this->line('Dummy ' . $year . ':');
            $this->renderRowReport('  Penilaian', $dummy['assessments']);
            $this->renderRowReport('  History', $dummy['details']);
            $this->renderRowReport('  Training', $dummy['training']);
        }

        if ($report['skipped'] !== []) {
            $this->line('');
            $this->line('Skip reasons:');
            foreach ($report['skipped'] as $reason => $count) {
                $this->line(sprintf('  %s: %d', $reason, $count));
            }
        }

        if (! empty($report['mapping']['position_methods'])) {
            $this->line('');
            $this->line('Mapping position: ' . json_encode($report['mapping']['position_methods'], JSON_UNESCAPED_UNICODE));
        }

        if (! $dryRun) {
            $this->line('');
            $this->line('Applied: ' . json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * @param array<string, mixed> $stats
     */
    private function renderRowReport(string $label, array $stats): void
    {
        $this->line(sprintf(
            '%s: total=%d mapped=%d skipped=%d duplicate=%d existing=%d to_insert=%d',
            $label,
            $stats['total'],
            $stats['mappable'],
            $stats['skipped'],
            $stats['duplicates'] ?? 0,
            $stats['existing'],
            $stats['to_insert'],
        ));
    }

    private function isAbsolutePath(string $path): bool
    {
        return (bool) preg_match('/^(?:[A-Za-z]:[\\\\]|[\\\\]{2}|\/)/', $path);
    }
}
