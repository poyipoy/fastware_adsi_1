<?php

namespace App\Enums\KnowledgeManagement;

enum KmProcessingStatus: string
{
    case PENDING = 'pending_processing';
    case PROCESSING = 'processing';
    case READY = 'ready';
    case RETRY_PENDING = 'retry_pending';
    case FAILED = 'processing_failed';
    case QUARANTINED = 'quarantined';

    public function isTerminal(): bool
    {
        return in_array($this, [self::READY, self::FAILED, self::QUARANTINED], true);
    }
}
