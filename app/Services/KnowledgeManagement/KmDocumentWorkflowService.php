<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Exceptions\KnowledgeManagement\InvalidKmTransitionException;
use App\Models\KmPengajuan;
use Illuminate\Validation\ValidationException;

class KmDocumentWorkflowService
{
    public function __construct(
        private readonly KmVersioningService $versions,
    ) {
    }

    public function submit(KmPengajuan $document): KmPengajuan
    {
        if (! $document->isReadyForSubmission()) {
            throw ValidationException::withMessages([
                'file' => $document->isOfficeFile()
                    ? ($document->processingState() === 'ready'
                        ? 'File PPT/PPTX sudah diproses, tetapi submission Office belum diaktifkan oleh administrator.'
                        : 'File PPT/PPTX masih menunggu konversi dan belum dapat dikirim untuk persetujuan.')
                    : 'Dokumen harus memiliki file PDF yang valid sebelum dikirim untuk persetujuan.',
            ]);
        }

        return $this->transitionLocked($document, KmDocumentStatus::PENDING_APPROVAL);
    }

    public function approve(KmPengajuan $document): KmPengajuan
    {
        return $this->transitionLocked($document, KmDocumentStatus::PUBLISHED);
    }

    public function reject(KmPengajuan $document): KmPengajuan
    {
        return $this->transitionLocked($document, KmDocumentStatus::DRAFT);
    }

    public function deactivate(KmPengajuan $document): KmPengajuan
    {
        return $this->transitionLocked($document, KmDocumentStatus::INACTIVE);
    }

    public function assertCanTransition(KmPengajuan $document, KmDocumentStatus $target): void
    {
        $current = $document->documentStatus();
        if ($current === null || ! $this->isAllowed($current, $target)) {
            throw new InvalidKmTransitionException(
                $current ?? KmDocumentStatus::INACTIVE,
                $target,
            );
        }
    }

    public function transitionLocked(KmPengajuan $document, KmDocumentStatus $target): KmPengajuan
    {
        $this->assertCanTransition($document, $target);

        $document->forceFill([
            'status' => $target->value,
            'persetujuan' => $target->legacyApprovalValue(),
        ])->save();

        return $document->refresh();
    }

    private function isAllowed(KmDocumentStatus $from, KmDocumentStatus $to): bool
    {
        return match ($to) {
            KmDocumentStatus::PENDING_APPROVAL => in_array(
                $from,
                [KmDocumentStatus::DRAFT, KmDocumentStatus::PUBLISHED],
                true,
            ),
            KmDocumentStatus::PUBLISHED => $from === KmDocumentStatus::PENDING_APPROVAL,
            KmDocumentStatus::DRAFT => $from === KmDocumentStatus::PENDING_APPROVAL,
            KmDocumentStatus::INACTIVE => in_array(
                $from,
                [KmDocumentStatus::DRAFT, KmDocumentStatus::PUBLISHED],
                true,
            ),
        };
    }
}
