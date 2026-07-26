<?php

namespace App\Exceptions\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use DomainException;

class InvalidKmTransitionException extends DomainException
{
    public function __construct(
        public readonly KmDocumentStatus $from,
        public readonly KmDocumentStatus $to,
    ) {
        parent::__construct(sprintf(
            'Transisi status Knowledge Management dari %s ke %s tidak diizinkan.',
            $from->name,
            $to->name,
        ));
    }
}
