<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmApprovalAction;
use App\Models\KmPengajuan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveKmDocumentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $action = $this->input('action');
        if ($this->has('approve')) {
            $action = KmApprovalAction::APPROVED->value;
        } elseif ($this->has('reject')) {
            $action = KmApprovalAction::REJECTED->value;
        }

        $this->merge([
            'action' => $action,
            'reason' => is_string($this->input('reason')) ? trim($this->input('reason')) : null,
        ]);
    }

    public function authorize(): bool
    {
        $document = KmPengajuan::query()->find($this->integer('id'));
        if ($this->user() === null) {
            return false;
        }

        if ($document === null) {
            return true;
        }

        return match ($this->input('action')) {
            KmApprovalAction::APPROVED->value => $this->user()->can('approve', $document),
            KmApprovalAction::REJECTED->value => $this->user()->can('reject', $document),
            default => $this->user()->can('approve', $document)
                || $this->user()->can('reject', $document),
        };
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:km_pengajuans,id'],
            'action' => [
                'required',
                Rule::in([
                    KmApprovalAction::APPROVED->value,
                    KmApprovalAction::REJECTED->value,
                ]),
            ],
            'posisi' => ['required', 'string', 'max:255'],
            'id_km_kategori' => ['required', 'integer', 'exists:km_kategoris,id'],
            'judul' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:3000'],
            'reason' => [
                Rule::requiredIf($this->input('action') === KmApprovalAction::REJECTED->value),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
