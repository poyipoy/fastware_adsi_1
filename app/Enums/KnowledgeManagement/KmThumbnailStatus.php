<?php

namespace App\Enums\KnowledgeManagement;

enum KmThumbnailStatus: string
{
    case MISSING = 'missing';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case READY = 'ready';
    case UNSUPPORTED = 'unsupported';
    case UNAVAILABLE = 'unavailable';
    case FAILED = 'failed';

    /**
     * Status yang harus menampilkan thumbnail default (bukan gambar nyata).
     */
    public function shouldUseFallback(): bool
    {
        return match ($this) {
            self::MISSING,
            self::PENDING,
            self::PROCESSING,
            self::UNSUPPORTED,
            self::UNAVAILABLE,
            self::FAILED => true,
            self::READY => false,
        };
    }
}
