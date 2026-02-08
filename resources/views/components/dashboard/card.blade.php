@props([
    'title' => null,
    'subtitle' => null,
    'bodyClass' => '',
])

<div {{ $attributes->class(['card']) }}>
    @if($title || isset($actions))
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                @if($title)
                    <h5 class="mb-0">{{ $title }}</h5>
                @endif
                @if($subtitle)
                    <p class="mb-0 text-muted small">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div class="{{ trim('card-body ' . $bodyClass) }}">
        {{ $slot }}
    </div>
</div>

