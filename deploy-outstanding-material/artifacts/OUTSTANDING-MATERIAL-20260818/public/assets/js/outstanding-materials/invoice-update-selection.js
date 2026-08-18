(function (window, document) {
    'use strict';

    function install() {
        var modal = document.getElementById('invoiceUpdateModal');
        var body = document.getElementById('modalMaterialsBody');
        if (!modal || !body || modal.dataset.omSelectionInstalled === 'true') {
            return;
        }

        modal.dataset.omSelectionInstalled = 'true';

        var headerCell = modal.querySelector('table thead th:first-child');
        if (headerCell && !headerCell.querySelector('#selectAllInvoiceMaterials')) {
            headerCell.innerHTML = [
                '<label class="om-select-all-label mb-0" title="Select all materials">',
                '<input type="checkbox" id="selectAllInvoiceMaterials" class="form-check-input"',
                ' aria-label="Select all materials">',
                '</label>',
            ].join('');
        }

        var selectAll = document.getElementById('selectAllInvoiceMaterials');
        var updateButton = document.getElementById('btnSaveInvoiceUpdate');

        function checks() {
            return Array.prototype.slice.call(body.querySelectorAll('.material-check'));
        }

        function sync() {
            var rows = checks();
            var selected = rows.filter(function (checkbox) { return checkbox.checked; });

            rows.forEach(function (checkbox) {
                var row = checkbox.closest('tr');
                if (!row) {
                    return;
                }

                row.classList.toggle('is-selected', checkbox.checked);
                row.setAttribute('role', 'checkbox');
                row.setAttribute('aria-checked', checkbox.checked ? 'true' : 'false');
                row.setAttribute('tabindex', '0');
            });

            if (selectAll) {
                selectAll.checked = rows.length > 0 && selected.length === rows.length;
                selectAll.indeterminate = selected.length > 0 && selected.length < rows.length;
                selectAll.disabled = rows.length === 0;
            }

            if (updateButton) {
                updateButton.disabled = selected.length === 0;
            }
        }

        function reset() {
            checks().forEach(function (checkbox) {
                checkbox.checked = false;
            });
            sync();
        }

        function toggleRow(row) {
            var checkbox = row && row.querySelector('.material-check');
            if (!checkbox) {
                return;
            }

            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            sync();
        }

        body.addEventListener('click', function (event) {
            if (event.target.closest('input, button, a, select, textarea, label')) {
                sync();
                return;
            }

            var row = event.target.closest('tr');
            if (row && row.closest('#modalMaterialsBody')) {
                toggleRow(row);
            }
        });

        body.addEventListener('keydown', function (event) {
            if (event.target !== event.currentTarget && event.target.closest('input, button, a, select, textarea')) {
                return;
            }

            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            var row = event.target.closest('tr');
            if (!row || !row.closest('#modalMaterialsBody')) {
                return;
            }

            event.preventDefault();
            toggleRow(row);
        });

        body.addEventListener('change', function (event) {
            if (event.target.matches('.material-check')) {
                sync();
            }
        });

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checks().forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
                sync();
            });
        }

        if (window.MutationObserver) {
            var observer = new MutationObserver(sync);
            observer.observe(body, { childList: true, subtree: true });
        }

        modal.addEventListener('shown.bs.modal', reset);
        modal.addEventListener('hidden.bs.modal', reset);
        sync();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', install);
    } else {
        install();
    }
}(window, document));
