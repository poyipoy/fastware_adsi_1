<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReportKmHrisReconciliationCommand extends Command
{
    protected $signature = 'km:hris-reconciliation {--from=} {--to=} {--json}';
    protected $description = 'Tampilkan rekonsiliasi event outbound HRIS per periode.';

    public function handle(): int
    {
        $from = $this->option('from') ?: now()->startOfMonth()->toDateString();
        $to = $this->option('to') ?: now()->toDateString();
        $base = DB::table('km_hris_outbound_events')->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);
        $report = [
            'from' => $from, 'to' => $to,
            'completion_events' => DB::table('km_completion_events')->whereDate('completed_at', '>=', $from)->whereDate('completed_at', '<=', $to)->count(),
            'staged' => (clone $base)->count(),
            'sent' => (clone $base)->where('status', 'sent')->count(),
            'pending' => (clone $base)->whereIn('status', ['pending', 'processing', 'retry_pending'])->count(),
            'failed' => (clone $base)->where('status', 'failed')->count(),
        ];
        $report['reconciliation_percent'] = $report['completion_events'] === 0
            ? 100.0 : round(($report['staged'] / $report['completion_events']) * 100, 3);
        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            $this->table(['Metrik', 'Nilai'], collect($report)->map(static fn ($value, $key) => [$key, $value])->values()->all());
        }
        return $report['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
