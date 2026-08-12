(function () {
    'use strict';

    const ready = (callback) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    };

    ready(() => {
        const toolbar = document.getElementById('training-followup-toolbar');
        const viewport = document.querySelector('.training-followup-table-viewport');
        const tableButton = document.getElementById('btn-table-view');
        const cardButton = document.getElementById('btn-card-view');
        const year = document.getElementById('active-year-display')?.textContent?.trim() || 'all';
        const storageKey = `hr.training-follow-up.view.${year}`;

        const updateStickyMeasurements = () => {
            const header = document.querySelector('#header, header.header, .app-header');
            const headerHeight = Math.ceil(header?.getBoundingClientRect().height || 68);
            toolbar?.style.setProperty('--training-toolbar-top', `${headerHeight}px`);

            const firstHeadRow = viewport?.querySelector('thead tr:first-child');
            const firstHeight = Math.ceil(firstHeadRow?.getBoundingClientRect().height || 48);
            viewport?.style.setProperty('--training-head-first-height', `${firstHeight}px`);
        };

        const setPressedState = (mode) => {
            tableButton?.setAttribute('aria-pressed', String(mode === 'table'));
            cardButton?.setAttribute('aria-pressed', String(mode === 'card'));
            localStorage.setItem(storageKey, mode);
            requestAnimationFrame(updateStickyMeasurements);
        };

        tableButton?.addEventListener('click', () => setPressedState('table'));
        cardButton?.addEventListener('click', () => setPressedState('card'));

        const preferred = localStorage.getItem(storageKey) === 'table' ? 'table' : 'card';
        (preferred === 'table' ? tableButton : cardButton)?.click();

        updateStickyMeasurements();
        window.addEventListener('resize', updateStickyMeasurements, { passive: true });

        if ('ResizeObserver' in window) {
            const observer = new ResizeObserver(updateStickyMeasurements);
            if (toolbar) observer.observe(toolbar);
            const firstHeadRow = viewport?.querySelector('thead tr:first-child');
            if (firstHeadRow) observer.observe(firstHeadRow);
        }
    });
})();
