@props(['title' => 'Belum ada data', 'message' => null])

<div {{ $attributes->merge(['class' => 'warehouse-empty-state']) }} role="status">
    <strong>{{ $title }}</strong>
    @if ($message)<span>{{ $message }}</span>@endif
</div>
