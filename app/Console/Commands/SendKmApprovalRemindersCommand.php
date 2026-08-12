<?php

namespace App\Console\Commands;

use App\Services\KnowledgeManagement\KmApprovalService;
use Illuminate\Console\Command;

final class SendKmApprovalRemindersCommand extends Command
{
    protected $signature = 'km:send-approval-reminders {--json : Keluarkan hasil machine-readable}';

    protected $description = 'Buat reminder dan overdue notification approval KM secara idempotent.';

    public function handle(KmApprovalService $approval): int
    {
        $result = $approval->generateDueReminders();

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            $this->info(sprintf(
                'Reminder KM selesai: %d dokumen, %d notification attempts.',
                $result['documents'],
                $result['notification_attempts'],
            ));
        }

        return self::SUCCESS;
    }
}
