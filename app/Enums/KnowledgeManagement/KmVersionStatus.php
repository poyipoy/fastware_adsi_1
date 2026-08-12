<?php

namespace App\Enums\KnowledgeManagement;

enum KmVersionStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case PUBLISHED = 'published';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';
}
