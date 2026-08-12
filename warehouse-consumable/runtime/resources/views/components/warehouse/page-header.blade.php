@props([
    'eyebrow' => 'Warehouse',
    'title',
    'subtitle' => null,
])

<header class="warehouse-page-header" {{ $attributes->merge(['aria-labelledby' => 'warehouse-page-title']) }}>
    <div class="warehouse-page-header-copy">
        <p class="warehouse-eyebrow">{{ $eyebrow }}</p>
        <h1 id="warehouse-page-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="warehouse-page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if (trim((string) $slot) !== '')
        <div class="warehouse-page-actions">{{ $slot }}</div>
    @endif
</header>
