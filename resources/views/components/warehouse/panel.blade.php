@props(['title' => null, 'subtitle' => null, 'tag' => null])

<section {{ $attributes->merge(['class' => 'warehouse-panel']) }}>
    @if ($title || $subtitle || $tag || isset($header))
        <div class="warehouse-panel-header">
            <div>
                @if ($title)<h2>{{ $title }}</h2>@endif
                @if ($subtitle)<p>{{ $subtitle }}</p>@endif
            </div>
            @if ($tag)<span class="warehouse-panel-tag">{{ $tag }}</span>@endif
            @isset($header){{ $header }}@endisset
        </div>
    @endif
    <div class="warehouse-panel-body">{{ $slot }}</div>
</section>
