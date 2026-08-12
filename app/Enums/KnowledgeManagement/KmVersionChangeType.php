<?php

namespace App\Enums\KnowledgeManagement;

enum KmVersionChangeType: string
{
    case MAJOR = 'major';
    case MINOR = 'minor';
}
