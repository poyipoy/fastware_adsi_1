<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmApprovalAction;
use App\Models\KmPengajuan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkKmApprovalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $action = match ($this->input('action')) {
            'approve', KmApprovalAction::APPROVED->value => KmApprovalAction::APPROVED->value,
            'reject', KmApprovalAction::REJECTED->value => KmApprovalAction::REJECTED->value,
            default => $this->input('action'),
        };

        $items = $this->input('items');
        if (is_array($items)) {
            $items = array_values($items);
        }

        $this->merge([
            'action' => $action,
            'items' => $items,
            'reason' => is_string($this->input('reason')) ? trim($this->input('reason')) : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('bulkApprove', KmPengajuan::class) ?? false;
    }

    public function rules(): array
    {
        $approve = $this->input('action') === KmApprovalAction::APPROVED->value;

        return [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.document_id' => [
                'required',
                'integer',
                'distinct:strict',
                Rule::exists('km_pengajuans', 'id'),
            ],
            'items.*.id_km_kategori' => [
                Rule::excludeIf(! $approve),
                Rule::requiredIf($approve),
                'nullable',
                'integer',
                Rule::exists('km_kategoris', 'id'),
            ],
            'action' => [
                'required',
                Rule::in([
                    KmApprovalAction::APPROVED->value,
                    KmApprovalAction::REJECTED->value,
                ]),
            ],
            'reason' => [
                Rule::requiredIf(! $approve),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function action(): KmApprovalAction
    {
        return KmApprovalAction::from((string) $this->validated('action'));
    }
}
