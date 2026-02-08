@props(['title' => null])

<div class="card">
    @if($title)
        <div class="card-body">
            <h5 class="card-title font-sii text-center">{{ $title }}</h5>
        </div>
    @endif
    <div class="card-body">
        {{ $slot }}
    </div>
</div>

