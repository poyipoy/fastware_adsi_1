<?php

namespace App\Console\Commands;

use App\Services\KnowledgeManagement\KmDocumentProcessingService;
use Illuminate\Console\Command;

class KmDocumentCapabilitiesCommand extends Command
{
    protected $signature = 'km:document-capabilities {--json}';

    protected $description = 'Memeriksa binary pemrosesan dokumen Knowledge Management.';

    public function handle(KmDocumentProcessingService $processor): int
    {
        $capabilities = $processor->capabilities();
        if ($this->option('json')) {
            $this->line(json_encode($capabilities, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            foreach ($capabilities as $name => $capability) {
                $this->line(sprintf(
                    '%s %s (%s)',
                    $capability['available'] ? 'PASS' : 'FAIL',
                    $name,
                    $capability['configured'],
                ));
            }
        }

        return collect($capabilities)->every('available') ? self::SUCCESS : self::FAILURE;
    }
}
