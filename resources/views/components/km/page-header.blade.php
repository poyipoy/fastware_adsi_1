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

    <div class="km-page-header__actions">
        @isset($actions)
            {{ $actions }}
        @endisset

        <div class="dropdown">
            <button type="button" class="btn btn-outline-secondary km-notification-trigger"
                id="km-notification-trigger" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                aria-expanded="false" aria-controls="km-notification-menu"
                aria-label="Buka notifikasi Knowledge Management">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <span class="d-none d-sm-inline">Notifikasi</span>
                <span class="km-notification-badge" data-km-notification-badge hidden></span>
            </button>

            <div class="dropdown-menu dropdown-menu-end km-notification-menu"
                id="km-notification-menu" aria-labelledby="km-notification-trigger">
                <div class="km-notification-header">
                    <div>
                        <strong>Notifikasi KM</strong>
                        <span class="km-notification-summary" data-km-notification-summary>Memuat...</span>
                    </div>
                    <button type="button" class="btn btn-link btn-sm" data-km-notification-read-all disabled>
                        Tandai semua dibaca
                    </button>
                </div>
                <div class="km-notification-list" data-km-notification-list aria-live="polite" aria-busy="true">
                    <div class="km-notification-loading" data-km-notification-loading>
                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                        <span>Memuat notifikasi...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
