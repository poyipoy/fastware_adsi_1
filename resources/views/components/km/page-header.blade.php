@props([
    'eyebrow' => 'Knowledge Management',
    'title',
    'description' => null,
])

<header {{ $attributes->class(['km-page-header']) }}>
    <div class="km-page-header__content">
        <p class="km-page-header__eyebrow">{{ $eyebrow }}</p>
        <h1>{{ $title }}</h1>
        @if ($description)
            <p class="km-page-header__description">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="km-page-header__actions">
            {{ $actions }}
        </div>
    @endisset
</header>

