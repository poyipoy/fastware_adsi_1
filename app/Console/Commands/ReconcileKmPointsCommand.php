<?php

namespace App\Console\Commands;

use App\Services\KnowledgeManagement\KmPointLedgerService;
use Illuminate\Console\Command;

final class ReconcileKmPointsCommand extends Command
{
    protected $signature = 'km:reconcile-points
        {--repair : Samakan cached aggregate users.km_total_poin dengan ledger setelah review}
        {--json : Keluarkan hasil machine-readable}';

    protected $description = 'Bandingkan append-only KM point ledger dengan cached aggregate pengguna.';

    public function handle(KmPointLedgerService $ledger): int
    {
        $repair = (bool) $this->option('repair');
        $drift = $ledger->reconcile($repair);

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode([
                'repaired' => $repair,
                'drift_count' => $drift->count(),
                'items' => $drift->values()->all(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return $drift->isEmpty() || $repair ? self::SUCCESS : self::FAILURE;
        }

        if ($drift->isEmpty()) {
            $this->info('PASS: cached aggregate poin sama dengan KM point ledger.');

            return self::SUCCESS;
        }

        $this->table(
            ['User ID', 'Nama', 'Cached', 'Ledger', 'Drift'],
            $drift->map(static fn (array $row): array => [
                $row['user_id'],
                $row['name'],
                $row['cached_points'],
                $row['ledger_points'],
                $row['drift'],
            ])->all(),
        );

        if ($repair) {
            $this->warn('Cached aggregate telah disamakan dengan ledger. Simpan output ini sebagai audit evidence.');

            return self::SUCCESS;
        }

        $this->error('FAIL: ditemukan drift poin. Review lalu jalankan kembali dengan --repair bila disetujui.');

        return self::FAILURE;
    }
}
