@props(['status', 'label' => null, 'context' => null])
@php
    $value = strtoupper((string) $status);
    $displayLabel = $label;
    if ($displayLabel === null) {
        $displayLabel = match ($context) {
            'transaction' => match ($value) {
                'IN' => 'Masuk',
                'OUT' => 'Keluar',
                'ADJUSTMENT' => 'Penyesuaian',
                'REVERSAL' => 'Pembatalan',
                default => $status,
            },
            'stock' => match ($value) {
                'HEALTHY' => 'Aman',
                'LOW' => 'Menipis',
                'OUT' => 'Habis',
                default => $status,
            },
            'activity' => match ($value) {
                'ACTIVE' => 'Aktif',
                'INACTIVE' => 'Tidak aktif',
                default => $status,
            },
            default => $status,
        };
    }
    $tone = $context === 'stock' && $value === 'OUT'
        ? 'danger'
        : match ($value) {
            'IN', 'HEALTHY', 'ACTIVE', 'SUCCESS' => 'success',
            'OUT', 'LOW', 'WARNING', 'PENDING' => 'warning',
            'DANGER', 'ERROR', 'INACTIVE', 'REVERSED' => 'danger',
            default => 'neutral',
        };
@endphp
<span {{ $attributes->merge(['class' => 'warehouse-status-badge warehouse-status-badge-'.$tone]) }}>
    {{ $displayLabel }}
</span>
