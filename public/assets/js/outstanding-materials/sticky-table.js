(function (window, document) {
    'use strict';

    function scrollContainerFor(table) {
        return table.closest('.om-table-wrap') || table.parentElement;
    }

    function syncStickyOffsets(table) {
        if (!table) {
            return;
        }

        var headerRow = table.querySelector('thead tr:first-child');
        if (!headerRow) {
            return;
        }

        var height = Math.ceil(headerRow.getBoundingClientRect().height);
        var container = scrollContainerFor(table);

        table.style.setProperty('--om-table-header-height', height + 'px');
        if (container) {
            container.style.setProperty('--om-table-header-height', height + 'px');
        }
    }

    function install(selector) {
        var table = document.querySelector(selector);
        if (!table) {
            return;
        }

        if (table.dataset.omStickyInstalled === 'true') {
            return;
        }
        table.dataset.omStickyInstalled = 'true';

        var headerRow = table.querySelector('thead tr:first-child');
        var filterRow = table.querySelector('thead tr.om-filter-row');
        var schedule = window.requestAnimationFrame
            ? function (callback) { window.requestAnimationFrame(callback); }
            : function (callback) { window.setTimeout(callback, 0); };

        var sync = function () {
            schedule(function () {
                syncStickyOffsets(table);
            });
        };

        sync();
        window.addEventListener('resize', sync, { passive: true });

        if (window.ResizeObserver) {
            var observer = new ResizeObserver(sync);
            observer.observe(headerRow || table);
            if (filterRow) {
                observer.observe(filterRow);
            }
        }

        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(sync).catch(function () {});
        }

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) {
            window.jQuery(table).on('init.dt draw.dt column-sizing.dt', sync);
        }
    }

    window.OutstandingMaterialStickyTable = {
        install: install,
        sync: function (selector) {
            syncStickyOffsets(document.querySelector(selector));
        },
    };
}(window, document));
