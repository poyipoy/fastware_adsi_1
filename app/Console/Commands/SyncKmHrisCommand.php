<?php

namespace App\Console\Commands;

use App\Services\KnowledgeManagement\KmHrisOutboundService;
use Illuminate\Console\Command;
use RuntimeException;

class SyncKmHrisCommand extends Command
{
    protected $signature = 'km:sync-hris {--limit=50} {--stage-only}';
    protected $description = 'Stage dan kirim completion KM ke HRIS secara satu arah dan idempotent.';

    public function handle(KmHrisOutboundService $service): int
    {
        $staged = $service->stage((int) $this->option('limit') * 10);
        $this->line("Event baru di-stage: {$staged}.");
        if ($this->option('stage-only')) {
            return self::SUCCESS;
        }
        try {
            $result = $service->send((int) $this->option('limit'));
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());
            foreach ($service->gateStatus() as $gate => $passed) {
                $this->line(($passed ? '[PASS] ' : '[BLOCKED] ').$gate);
            }
            return self::FAILURE;
        }
        $this->components->info("Terkirim: {$result['sent']}; gagal/retry: {$result['failed']}.");
        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
