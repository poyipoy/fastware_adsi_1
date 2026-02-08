@props(['status'])

@php
    use App\Enums\DetailInquiryStatus;
    try {
        $statusEnum = DetailInquiryStatus::from($status);
        $meta = $statusEnum->getMeta();
    } catch (\ValueError $e) {
        $meta = ['label' => 'Pending', 'class' => 'badge bg-secondary'];
    }
@endphp

<span class="{{ $meta['class'] }}">
    {{ $meta['label'] }}
</span>

