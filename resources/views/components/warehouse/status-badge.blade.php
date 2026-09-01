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
                'TRANSFER' => 'Transfer Antar Lokasi',
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
            'shipment' => match ($value) {
                'WAITING_VALIDATION' => 'Menunggu Validasi',
                'VALIDATED' => 'Sesuai / Selesai',
                'DISCREPANCY' => 'Tidak Sesuai',
                'CANCELLED' => 'Dibatalkan',
                default => $status,
            },
            'stock-in' => match ($value) {
                'WAITING_VALIDATION' => 'Menunggu Validasi',
                'VALIDATED' => 'Tervalidasi',
                'CANCELLED' => 'Dibatalkan',
                default => $status,
            },
            default => $status,
        };
    }
    $tone = $context === 'stock' && $value === 'OUT'
        ? 'danger'
        : match ($value) {
            'IN', 'HEALTHY', 'ACTIVE', 'SUCCESS', 'VALIDATED' => 'success',
            'OUT', 'LOW', 'WARNING', 'PENDING', 'WAITING_VALIDATION' => 'warning',
            'DISCREPANCY' => 'danger',
            'DANGER', 'ERROR', 'INACTIVE', 'REVERSED' => 'danger',
            'TRANSFER' => 'neutral',
            default => 'neutral',
        };
@endphp
<span {{ $attributes->merge(['class' => 'warehouse-status-badge warehouse-status-badge-'.$tone]) }}>
    <span class="warehouse-status-dot" aria-hidden="true"></span>
    <span>{{ $displayLabel }}</span>
</span>
