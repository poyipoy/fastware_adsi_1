@props([
    'target',
    'message' => 'Memuat...',
    'spinnerClass' => 'text-primary',
    'containerClass' => 'chart-fill',
    'showContainer' => false,
])

<div id="{{ $target }}-placeholder" class="chart-placeholder">
    <div class="spinner-border {{ $spinnerClass }}"></div>
    <span class="mt-2">{{ $message }}</span>
</div>

<div
    id="{{ $target }}"
    class="{{ $containerClass }}"
    style="{{ $showContainer ? '' : 'display:none;' }}"
>
    {{ $slot }}
</div>

