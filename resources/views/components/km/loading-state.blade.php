@props([
    'title' => 'Memuat data',
    'description' => 'Mohon tunggu, data sedang disiapkan.',
    'hidden' => true,
])

<div {{ $attributes->class(['km-loading-state']) }} role="status" aria-live="polite"
    @if ($hidden) hidden @endif>
    <span class="spinner-border km-loading-state__icon" aria-hidden="true"></span>
    <span class="km-loading-state__title">{{ $title }}</span>
    <span class="km-loading-state__description">{{ $description }}</span>
</div>

