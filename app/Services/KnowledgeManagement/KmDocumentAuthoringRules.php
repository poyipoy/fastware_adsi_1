<?php

namespace App\Services\KnowledgeManagement;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class KmDocumentAuthoringRules
{
    public static function rules(int $ownerId, bool $sometimes = false): array
    {
        $presence = $sometimes ? 'sometimes' : 'present';

        return [
            'reading_minutes' => [$presence, 'nullable', 'integer', 'min:1', 'max:1440'],
            'tags' => [$presence, 'array', 'max:10'],
            'tags.*' => ['required', 'string', 'max:50'],
            'co_author_ids' => [$presence, 'array', 'max:10'],
            'co_author_ids.*' => [
                'required',
                'integer',
                'distinct:strict',
                Rule::notIn([$ownerId]),
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'tags.max' => 'Maksimal 10 tag diizinkan per dokumen.',
            'tags.*.max' => 'Setiap tag maksimal 50 karakter.',
            'co_author_ids.max' => 'Maksimal 10 co-author diizinkan per dokumen.',
            'co_author_ids.*.distinct' => 'Co-author tidak boleh duplikat.',
            'co_author_ids.*.not_in' => 'Pemilik dokumen tidak boleh dipilih sebagai co-author.',
            'co_author_ids.*.exists' => 'Co-author harus merupakan user aktif.',
        ];
    }

    public static function normalizeTags(mixed $value): array
    {
        $tags = is_array($value) ? $value : explode(',', (string) ($value ?? ''));
        $normalized = [];

        foreach ($tags as $tag) {
            $name = preg_replace('/\s+/u', ' ', trim((string) $tag)) ?? '';
            $slug = Str::lower(Str::slug($name));

            if ($name !== '' && $slug !== '' && ! array_key_exists($slug, $normalized)) {
                $normalized[$slug] = $name;
            }
        }

        return array_values($normalized);
    }
}
