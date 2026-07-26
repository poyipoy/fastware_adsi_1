<?php

namespace App\Exceptions\KnowledgeManagement;

use RuntimeException;

class KmAutosaveConflictException extends RuntimeException
{
    public function __construct(
        public readonly int $serverRevision,
        public readonly string $serverAutosavedAt,
    ) {
        parent::__construct('Konflik autosave: revision yang dikirim sudah usang.');
    }
}
