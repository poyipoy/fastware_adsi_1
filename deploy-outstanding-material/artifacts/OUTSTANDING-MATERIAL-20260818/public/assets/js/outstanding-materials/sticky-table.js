(function (window, document) {
    'use strict';

    function scrollContainerFor(table) {
        return table.closest('.om-table-wrap') || table.parentElement;
    }

    function stickyRows(table) {
        var filterRow = table.querySelector('thead tr.om-filter-row');
        var columnHeaderRow = table.querySelector('thead tr.om-column-header')
            || table.querySelector('thead tr:not(.om-filter-row)');

        return {
            filterRow: filterRow,
            columnHeaderRow: columnHeaderRow,
        };
    }

    function pinCells(row, top, zIndex) {
        if (!row) {
            return;
        }

        Array.prototype.forEach.call(row.cells, function (cell) {
            // DataTables adds its own header-position rules.  Apply the final
            // sticky values directly to each cell so the column-label row
            // remains pinned even after DataTables redraws or resizes it.
            cell.style.setProperty('position', 'sticky', 'important');
            cell.style.setProperty('top', top + 'px', 'important');
            cell.style.setProperty('z-index', String(zIndex), 'important');
        });
    }

    function syncStickyOffsets(table) {
        if (!table) {
            return;
        }

        var rows = stickyRows(table);
        if (!rows.columnHeaderRow) {
            return;
        }

        var filterHeight = rows.filterRow
            ? Math.ceil(rows.filterRow.getBoundingClientRect().height)
            : 0;
        var headerHeight = Math.ceil(rows.columnHeaderRow.getBoundingClientRect().height);
        var container = scrollContainerFor(table);

        table.style.setProperty('--om-table-filter-height', filterHeight + 'px');
        table.style.setProperty('--om-table-header-height', headerHeight + 'px');
        pinCells(rows.filterRow, 0, 6);
        pinCells(rows.columnHeaderRow, filterHeight, 5);
        if (container) {
            container.style.setProperty('--om-table-filter-height', filterHeight + 'px');
            container.style.setProperty('--om-table-header-height', headerHeight + 'px');
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

        var rows = stickyRows(table);
        var headerRow = rows.columnHeaderRow;
        var filterRow = rows.filterRow;
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
