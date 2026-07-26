@props([
    'icon' => 'bi-inbox',
    'title',
    'description' => null,
])

<div {{ $attributes->class(['km-empty-state']) }} role="status">
    <i class="bi {{ $icon }} km-empty-state__icon" aria-hidden="true"></i>
    <h2 class="km-empty-state__title">{{ $title }}</h2>
    @if ($description)
        <p class="km-empty-state__description">{{ $description }}</p>
    @endif
    @isset($actions)
        <div class="km-action-group mt-2">
            {{ $actions }}
        </div>
    @endisset
</div>

