<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Models\KmPengajuan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class KmPopularMaterialFilterRequest extends FormRequest
{
    private const ALLOWED_QUERY_KEYS = ['category', 'tag_ids', 'page'];

    public function authorize(): bool
    {
        return $this->user()?->can('viewPopularAnalytics', KmPengajuan::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'integer', Rule::exists('km_kategoris', 'id')],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'distinct:strict', Rule::exists('km_tags', 'id')],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $unknown = array_diff(array_keys($this->query()), self::ALLOWED_QUERY_KEYS);
            if ($unknown !== []) {
                $validator->errors()->add(
                    'query',
                    'Parameter laporan tidak didukung: '.implode(', ', $unknown).'.'
                );
            }
        });
    }

    /**
     * @return array{category: int|null, tag_ids: list<int>}
     */
    public function filters(): array
    {
        $tagIds = collect($this->validated('tag_ids') ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        return [
            'category' => $this->validated('category') === null
                ? null
                : (int) $this->validated('category'),
            'tag_ids' => $tagIds,
        ];
    }
}
