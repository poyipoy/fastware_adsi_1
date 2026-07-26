<?php

namespace App\Http\Requests\KnowledgeManagement\Concerns;

use App\Services\KnowledgeManagement\KmDocumentAuthoringRules;

trait NormalizesKmAuthoringMetadata
{
    protected function normalizeAuthoringMetadata(bool $alwaysPresent): void
    {
        if ($alwaysPresent || $this->has('tags') || $this->has('tags_csv')) {
            $this->merge([
                'tags' => KmDocumentAuthoringRules::normalizeTags(
                    $this->has('tags') ? $this->input('tags') : $this->input('tags_csv')
                ),
            ]);
        }

        if ($alwaysPresent || $this->has('co_author_ids')) {
            $coAuthors = $this->input('co_author_ids', []);
            $this->merge(['co_author_ids' => is_array($coAuthors) ? array_values($coAuthors) : []]);
        }

        if ($alwaysPresent && ! $this->has('reading_minutes')) {
            $this->merge(['reading_minutes' => null]);
        }
    }
}
