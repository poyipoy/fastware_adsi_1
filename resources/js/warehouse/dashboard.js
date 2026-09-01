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
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Jumlah Stock Out',
                        data: data.values,
                        backgroundColor: '#2563eb',
                        hoverBackgroundColor: '#1d4ed8',
                        borderRadius: 6,
                        borderSkipped: false,
                        barThickness: 20,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : undefined,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#f8fafc',
                            bodyColor: '#f8fafc',
                            padding: 10,
                            cornerRadius: 8,
                            displayColors: false,
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: '#64748b', font: { size: 11 } },
                            grid: { color: '#f1f5f9' },
                        },
                        y: {
                            ticks: { color: '#1e293b', font: { size: 12, weight: '500' } },
                            grid: { display: false },
                        },
                    },
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
