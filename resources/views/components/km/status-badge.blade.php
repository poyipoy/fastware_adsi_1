@props(['status'])

@php
    $statusEnum = $status instanceof \App\Enums\KnowledgeManagement\KmDocumentStatus
        ? $status
        : \App\Enums\KnowledgeManagement\KmDocumentStatus::from((int) $status);

    $statusMeta = match ($statusEnum) {
        \App\Enums\KnowledgeManagement\KmDocumentStatus::INACTIVE => [
            'label' => 'Tidak Aktif',
            'tone' => 'inactive',
            'icon' => 'bi-slash-circle',
        ],
        \App\Enums\KnowledgeManagement\KmDocumentStatus::DRAFT => [
            'label' => 'Draf',
            'tone' => 'draft',
            'icon' => 'bi-pencil-square',
        ],
        \App\Enums\KnowledgeManagement\KmDocumentStatus::PENDING_APPROVAL => [
            'label' => 'Menunggu Persetujuan',
            'tone' => 'pending',
            'icon' => 'bi-hourglass-split',
        ],
        \App\Enums\KnowledgeManagement\KmDocumentStatus::PUBLISHED => [
            'label' => 'Terbit',
            'tone' => 'published',
            'icon' => 'bi-check-circle',
        ],
    };
@endphp

<span {{ $attributes->class(['km-status', 'km-status--'.$statusMeta['tone']]) }}>
    <i class="bi {{ $statusMeta['icon'] }}" aria-hidden="true"></i>
    <span>{{ $statusMeta['label'] }}</span>
</span>

