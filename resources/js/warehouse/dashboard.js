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

    const initialiseMovementChart = () => {
        if (typeof window.Chart !== 'function') {
            return;
        }

        const canvas = document.querySelector('[data-warehouse-movement-chart]');
        if (!canvas) return;

        const source = document.getElementById(canvas.dataset.source || '');
        if (!source) return;

        let data;
        try {
            data = JSON.parse(source.textContent || '{}');
        } catch {
            return;
        }

        if (!Array.isArray(data.labels) || data.labels.length === 0) {
            canvas.closest('.warehouse-chart-frame')?.setAttribute('hidden', 'hidden');
            return;
        }

        const labelCount = data.labels.length;
        const frame = canvas.closest('.warehouse-movement-chart-frame');
        const scrollWrap = canvas.closest('[data-warehouse-movement-scroll]');

        if (frame) {
            const minColWidth = 44;
            const calculatedWidth = labelCount * minColWidth;
            if (calculatedWidth > 0) {
                frame.style.width = `max(100%, ${calculatedWidth}px)`;
            }
        }

        new window.Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        type: 'line',
                        label: 'Rata-rata 7 Hari',
                        data: data.average_7_days,
                        borderColor: '#2c2c2c',
                        backgroundColor: '#2c2c2c',
                        borderWidth: 1.6,
                        borderDash: [4, 4],
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#2c2c2c',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 1.5,
                        fill: false,
                        tension: 0,
                        order: 1,
                    },
                    {
                        type: 'bar',
                        label: 'Masuk',
                        data: data.stock_in,
                        backgroundColor: '#3f6fd1',
                        hoverBackgroundColor: '#335cb0',
                        borderRadius: 2,
                        borderSkipped: false,
                        barPercentage: 0.5,
                        categoryPercentage: 0.55,
                        order: 2,
                    },
                    {
                        type: 'bar',
                        label: 'Keluar',
                        data: data.stock_out,
                        backgroundColor: '#c15a1e',
                        hoverBackgroundColor: '#a84c17',
                        borderRadius: 2,
                        borderSkipped: false,
                        barPercentage: 0.5,
                        categoryPercentage: 0.55,
                        order: 3,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : undefined,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: '#1a2233',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        titleFont: {
                            family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
                            size: 12,
                            weight: '600',
                        },
                        bodyFont: {
                            family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
                            size: 12,
                        },
                        padding: { top: 8, bottom: 8, left: 10, right: 10 },
                        cornerRadius: 6,
                        callbacks: {
                            title: (items) => {
                                const idx = items[0]?.dataIndex;
                                return data.dates && data.dates[idx] ? data.dates[idx] : items[0]?.label;
                            },
                            label: (item) => {
                                const label = item.dataset.label || '';
                                if (label === 'Rata-rata 7 Hari') {
                                    const val = typeof item.raw === 'number' ? Math.ceil(item.raw) : item.raw;
                                    return `${label}: ${val}`;
                                }
                                const val = typeof item.raw === 'number' ? (Number.isInteger(item.raw) ? item.raw : item.raw.toFixed(2)) : item.raw;
                                return `${label}: ${val}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        border: {
                            display: false,
                        },
                        ticks: {
                            color: '#8a93a3',
                            font: {
                                family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
                                size: 12,
                            },
                            maxRotation: 0,
                            autoSkip: false,
                        },
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#eef0f3',
                            lineWidth: 1,
                        },
                        border: {
                            display: false,
                        },
                        ticks: {
                            precision: 0,
                            color: '#8a93a3',
                            font: {
                                family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
                                size: 12,
                            },
                        },
                    },
                },
            },
        });

        if (scrollWrap && scrollWrap.scrollWidth > scrollWrap.clientWidth) {
            scrollWrap.scrollLeft = scrollWrap.scrollWidth - scrollWrap.clientWidth;
        }
    };

    const initialiseTabs = () => {
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach((tabBtn) => {
            tabBtn.addEventListener('shown.bs.tab', () => {
                window.dispatchEvent(new Event('resize'));
            });
        });
    };

    const initialise = () => {
        initialiseTrendFilter();
        initialiseCharts();
        initialiseMovementChart();
        initialiseTabs();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialise, { once: true });
    } else {
        initialise();
    }
})();
