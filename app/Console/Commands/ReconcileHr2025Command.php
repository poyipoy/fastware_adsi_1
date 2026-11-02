<?php

namespace App\Console\Commands;

use App\Services\HR\Reconciliation\Hr2025DryRun;
use Illuminate\Console\Command;
use Throwable;

class ReconcileHr2025Command extends Command
{
    protected $signature = 'hr:reconcile-2025
        {--legacy= : Required immutable legacy snapshot}
        {--target= : Required immutable target snapshot; never a live connection}
        {--year=2025 : Only business year 2025 is allowed}
        {--crosswalk= : Optional externally reviewed, hash-bound crosswalk JSON}';

    protected $description = 'Read-only offline HR 2025 reconciliation; aggregate JSON only, no apply/delete mode.';

    public function handle(Hr2025DryRun $dryRun): int
    {
        if (! $this->option('legacy') || ! $this->option('target') || (string) $this->option('year') !== '2025') {
            $this->error('Explicit --legacy and --target snapshots and year 2025 are required.');

            return self::INVALID;
        }
        try {
            $this->output->write($dryRun->json($dryRun->run((string) $this->option('legacy'), (string) $this->option('target'), 2025, $this->option('crosswalk') ?: null)));
        } catch (Throwable) {
            // Never forward parser/JSON/path exception contents into public logs.
            $this->error('HR2025_DRY_RUN_REJECTED: check immutable hashes, schema and review-input contract.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
