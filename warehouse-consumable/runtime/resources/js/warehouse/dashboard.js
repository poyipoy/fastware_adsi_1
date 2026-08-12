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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialiseTrendFilter, { once: true });
    } else {
        initialiseTrendFilter();
    }
})();
