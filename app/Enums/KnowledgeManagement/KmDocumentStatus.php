<?php

namespace App\Enums\KnowledgeManagement;

enum KmDocumentStatus: int
{
    case INACTIVE = 0;
    case DRAFT = 1;
    case PENDING_APPROVAL = 2;
    case PUBLISHED = 3;

    public function legacyApprovalValue(): int
    {
        return match ($this) {
            self::INACTIVE => 0,
            self::DRAFT, self::PENDING_APPROVAL => 1,
            self::PUBLISHED => 2,
        };
    }
}
