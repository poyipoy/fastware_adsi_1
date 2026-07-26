<?php

namespace App\Enums\KnowledgeManagement;

enum KmApprovalAction: string
{
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case DEACTIVATED = 'deactivated';
}
