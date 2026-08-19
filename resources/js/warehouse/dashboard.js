(() => {
    const initialiseTrendFilter = () => {
        const toggle = document.querySelector('[data-warehouse-trend-filter-toggle]');
        const targetSelector = toggle?.getAttribute('data-bs-target');
        const target = targetSelector ? document.querySelector(targetSelector) : null;
        const Collapse = window.bootstrap?.Collapse;

        if (!toggle || !target || !Collapse) {
            return;
        }

        const collapse = Collapse.getOrCreateInstance(target, { toggle: false });

        toggle.addEventListener('click', () => collapse.toggle());
        target.addEventListener('shown.bs.collapse', () => toggle.setAttribute('aria-expanded', 'true'));
        target.addEventListener('hidden.bs.collapse', () => toggle.setAttribute('aria-expanded', 'false'));
    };

    const initialiseCharts = () => {
        if (typeof window.Chart !== 'function') {
            return;
        }

        document.querySelectorAll('[data-warehouse-bar-chart]').forEach((canvas) => {
            const source = document.getElementById(canvas.dataset.source || '');
            if (!source) return;

            let data;
            try { data = JSON.parse(source.textContent || '{}'); } catch { return; }
            if (!Array.isArray(data.labels) || data.labels.length === 0) {
                canvas.closest('.warehouse-chart-frame')?.setAttribute('hidden', 'hidden');
                return;
            }

            new window.Chart(canvas, {
                type: 'bar',
                data: { labels: data.labels, datasets: [{ label: 'Jumlah Stock Out', data: data.values, backgroundColor: '#2d5fb8', borderRadius: 4 }] },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : undefined,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } },
                },
            });
        });
    };

    const initialise = () => {
        initialiseTrendFilter();
        initialiseCharts();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialise, { once: true });
    } else {
        initialise();
    }
})();
