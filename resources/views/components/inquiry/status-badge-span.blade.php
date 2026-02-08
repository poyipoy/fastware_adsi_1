@props(['status'])

@php
    use App\Enums\InquiryStatus;
    try {
        $statusEnum = InquiryStatus::from($status);
        $meta = $statusEnum->getMeta();
    } catch (\ValueError $e) {
        $meta = ['label' => 'Unknown', 'class' => 'btn-light'];
    }
@endphp

<span class="btn btn-sm {{ $meta['class'] }}">
    {{ $meta['label'] }}
</span>

