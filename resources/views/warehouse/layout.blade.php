@extends('layout')

@section('bodyClass', 'warehouse-page')

@push('styles')
    @vite('resources/css/warehouse/foundation.css')
@endpush

@section('content')
    <main class="warehouse-shell" data-warehouse-shell>
        @yield('warehouse-content')
    </main>
@endsection

@push('scripts')
    <script>
        (function() {
            const storageKey = 'wh_scroll_pos_' + window.location.pathname;

            // 1. Restore scroll position if saved from a previous filter or pagination action
            try {
                const raw = sessionStorage.getItem(storageKey);
                if (raw) {
                    sessionStorage.removeItem(storageKey);
                    const data = JSON.parse(raw);
                    if (data && typeof data.scrollY === 'number' && (Date.now() - data.timestamp < 30000)) {
                        if ('scrollRestoration' in history) {
                            history.scrollRestoration = 'manual';
                        }
                        const targetY = data.scrollY;
                        window.scrollTo(0, targetY);
                        requestAnimationFrame(function() {
                            window.scrollTo(0, targetY);
                        });
                        setTimeout(function() {
                            window.scrollTo(0, targetY);
                        }, 50);
                        setTimeout(function() {
                            window.scrollTo(0, targetY);
                        }, 180);
                    }
                }
            } catch (err) {
                // Ignore storage errors
            }

            // 2. Save scroll position on filter form submit
            document.addEventListener('submit', function(e) {
                if (e.target && e.target.closest('.warehouse-shell')) {
                    try {
                        sessionStorage.setItem(storageKey, JSON.stringify({
                            scrollY: window.scrollY,
                            timestamp: Date.now()
                        }));
                    } catch (err) {}
                }
            }, true);

            // 3. Save scroll position on filter input/dropdown change (e.g. onchange="this.form.submit()")
            document.addEventListener('change', function(e) {
                if (e.target && e.target.closest('.warehouse-shell') && e.target.form) {
                    try {
                        sessionStorage.setItem(storageKey, JSON.stringify({
                            scrollY: window.scrollY,
                            timestamp: Date.now()
                        }));
                    } catch (err) {}
                }
            }, true);

            // 4. Save scroll position on pagination, reset, and workspace tab clicks
            document.addEventListener('click', function(e) {
                const target = e.target.closest('a');
                if (!target || !target.closest('.warehouse-shell')) return;

                const isPagination = target.closest('.pagination, .warehouse-panel-pagination');
                const text = (target.textContent || '').trim().toLowerCase();
                const isReset = target.classList.contains('btn') && (text.includes('reset') || text.includes('atur ulang'));
                const isWorkspaceTab = target.closest('.warehouse-workspace-tabs');

                if (isPagination || isReset || isWorkspaceTab) {
                    try {
                        sessionStorage.setItem(storageKey, JSON.stringify({
                            scrollY: window.scrollY,
                            timestamp: Date.now()
                        }));
                    } catch (err) {}
                }
            }, true);
        })();
    </script>
@endpush
