<?php

namespace App\Console\Commands;

use App\Services\KnowledgeManagement\KmPublicationNotificationService;
use Illuminate\Console\Command;

class DispatchKmPublicationNotificationsCommand extends Command
{
    protected $signature = 'km:dispatch-publication-notifications {--limit=5}';

    protected $description = 'Kembangkan batch publikasi KM dan kirim notifikasi secara idempotent.';

    public function handle(KmPublicationNotificationService $service): int
    {
        $result = $service->dispatch((int) $this->option('limit'));
        $this->components->info(sprintf(
            'Batch: %d; penerima: %d; notifikasi: %d.',
            $result['batches'],
            $result['recipients'],
            $result['notifications'],
        ));

        return self::SUCCESS;
    }
}
