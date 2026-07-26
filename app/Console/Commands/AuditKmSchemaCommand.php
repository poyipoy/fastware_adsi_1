<?php

namespace App\Console\Commands;

use App\Services\KnowledgeManagement\KmSchemaAuditService;
use Illuminate\Console\Command;
use Throwable;

final class AuditKmSchemaCommand extends Command
{
    protected $signature = 'km:audit-schema
        {--write-manifest : Write a private JSON manifest for repair and deployment evidence}
        {--strict : Return a failure code when hardening is not safe}';

    protected $description = 'Read-only audit of the legacy Knowledge Management schema and data integrity.';

    public function handle(KmSchemaAuditService $auditService): int
    {
        try {
            $report = $auditService->audit();
        } catch (Throwable $exception) {
            $this->error('KM schema audit failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Knowledge Management schema audit');
        $this->line('Connection: '.$report['connection']);
        $this->line('Driver: '.$report['driver']);
        $this->line('Database: '.$report['database']);
        $this->line('Generated: '.$report['generated_at']);
        $this->newLine();

        $rows = collect($report['summary']['blocking_counts'])
            ->map(fn (int $count, string $check): array => [
                $check,
                $count === 0 ? 'PASS' : 'FAIL',
                $count,
            ])
            ->values()
            ->all();
        $this->table(['Check', 'Result', 'Count'], $rows);

        if ($report['summary']['safe_for_hardening']) {
            $this->info('PASS: schema data is safe for the KM hardening migration.');
        } else {
            $this->warn(
                'FAIL: hardening is blocked. Review the manifest and run km:repair-schema only after backup.'
            );
        }

        if ((bool) $this->option('write-manifest')) {
            try {
                $path = $auditService->writeManifest($report);
                $this->info("Manifest written: {$path}");
            } catch (Throwable $exception) {
                $this->error('Unable to write audit manifest: '.$exception->getMessage());

                return self::FAILURE;
            }
        }

        if ((bool) $this->option('strict') && ! $report['summary']['safe_for_hardening']) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
