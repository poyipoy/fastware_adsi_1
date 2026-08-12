<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Models\KmPengajuan;
use Illuminate\Validation\Validator;

class UpdateKmReadingProgressRequest extends KmDocumentInteractionRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'last_page' => ['required', 'integer', 'min:1', 'lte:pages_total'],
            'pages_total' => ['required', 'integer', 'min:1', 'max:10000'],
            'pages' => ['required', 'array', 'max:200'],
            'pages.*' => ['integer', 'min:1', 'distinct'],
            'active_delta' => ['required', 'integer', 'min:0', 'max:600'],
            'session_token' => ['nullable', 'string', 'max:100'],
            'device_token' => ['nullable', 'string', 'max:100'],
            'session_active_seconds' => ['nullable', 'integer', 'min:0', 'max:604800'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $pagesTotal = $this->integer('pages_total');
            foreach ((array) $this->input('pages', []) as $index => $page) {
                if (is_numeric($page) && (int) $page > $pagesTotal) {
                    $validator->errors()->add(
                        "pages.{$index}",
                        'Nomor halaman tidak boleh melebihi jumlah halaman dokumen.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'last_page.required' => 'Halaman terakhir wajib dikirim.',
            'last_page.lte' => 'Halaman terakhir tidak boleh melebihi jumlah halaman.',
            'pages_total.required' => 'Jumlah halaman dokumen wajib dikirim.',
            'pages_total.max' => 'Jumlah halaman dokumen tidak valid.',
            'pages.required' => 'Daftar halaman yang sudah dibuka wajib dikirim.',
            'pages.max' => 'Maksimal 200 halaman dapat disinkronkan dalam satu request.',
            'pages.*.distinct' => 'Daftar halaman tidak boleh mengandung duplikat.',
            'active_delta.required' => 'Durasi membaca aktif wajib dikirim.',
            'active_delta.max' => 'Durasi aktif per request maksimal 600 detik.',
        ];
    }
}
