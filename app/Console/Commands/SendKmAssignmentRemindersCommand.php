<?php

namespace App\Console\Commands;

use App\Services\KnowledgeManagement\KmAssignmentService;
use Illuminate\Console\Command;

class SendKmAssignmentRemindersCommand extends Command
{
    protected $signature = 'km:send-assignment-reminders';
    protected $description = 'Kirim reminder assignment KM H-3, H-1, dan overdue H+1.';

    public function handle(KmAssignmentService $service): int
    {
        $result = $service->sendReminders();
        $this->components->info("H-3: {$result['h3']}; H-1: {$result['h1']}; H+1: {$result['overdue']}.");
        return self::SUCCESS;
    }
}
