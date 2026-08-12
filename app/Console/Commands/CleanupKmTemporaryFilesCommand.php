<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CleanupKmTemporaryFilesCommand extends Command
{
    protected $signature = 'km:cleanup-temporary-files';

    protected $description = 'Menghapus temporary processing KM yang melewati retention operasional.';

    public function handle(): int
    {
        $root = (string) config('knowledge_management.processing.temporary_directory');
        if (! File::isDirectory($root)) {
            $this->info('Tidak ada temporary directory KM.');

            return self::SUCCESS;
        }
        $cutoff = now()->subMinutes(max(1, (int) config('knowledge_management.processing.cleanup_after_minutes', 60)))->timestamp;
        $removed = 0;
        foreach (File::directories($root) as $directory) {
            if (File::lastModified($directory) <= $cutoff) {
                File::deleteDirectory($directory);
                $removed++;
            }
        }
        $this->info("removed={$removed}");

        return self::SUCCESS;
    }
}
