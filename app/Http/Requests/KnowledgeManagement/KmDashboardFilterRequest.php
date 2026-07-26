<?php

namespace App\Http\Requests\KnowledgeManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class KmDashboardFilterRequest extends FormRequest
{
    private const ALLOWED_QUERY_KEYS = [
        'q',
        'category',
        'tag_ids',
        'read_status',
        'date_from',
        'date_to',
        'bookmarked',
        'sort',
        'per_page',
        'page',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', Rule::exists('km_kategoris', 'id')],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'distinct:strict', Rule::exists('km_tags', 'id')],
            'read_status' => ['nullable', 'string', Rule::in(['unread', 'reading', 'completed'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'bookmarked' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string', Rule::in(['latest', 'oldest', 'title_asc', 'popular', 'relevance'])],
            'per_page' => ['nullable', 'integer', Rule::in([12, 24, 48])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.exists' => 'Kategori yang dipilih tidak valid.',
            'read_status.in' => 'Status baca tidak valid.',
            'date_from.date_format' => 'Tanggal awal harus berformat YYYY-MM-DD.',
            'date_to.date_format' => 'Tanggal akhir harus berformat YYYY-MM-DD.',
            'date_to.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
            'sort.in' => 'Urutan dokumen tidak valid.',
            'per_page.in' => 'Jumlah per halaman harus 12, 24, atau 48.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $unknown = array_diff(array_keys($this->query()), self::ALLOWED_QUERY_KEYS);
            if ($unknown !== []) {
                $validator->errors()->add('query', 'Parameter filter tidak didukung: '.implode(', ', $unknown).'.');
            }

            if ($this->input('sort') === 'relevance'
                && trim((string) $this->input('q')) === '') {
                $validator->errors()->add(
                    'sort',
                    'Urutan relevansi hanya dapat digunakan bersama kata pencarian.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('q')) {
            $query = trim((string) $this->input('q'));
            $this->merge(['q' => $query === '' ? null : $query]);
        }

        if ($this->has('bookmarked')) {
            $value = filter_var($this->input('bookmarked'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value !== null) {
                $this->merge(['bookmarked' => $value]);
            }
        }
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 12);
    }

    public function sortBy(): string
    {
        return (string) ($this->validated('sort') ?? 'latest');
    }

    public function hasSearchQuery(): bool
    {
        return is_string($this->validated('q')) && $this->validated('q') !== '';
    }
}
