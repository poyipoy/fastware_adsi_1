(function (window, document) {
    'use strict';

    function isInvoiceDelete(form) {
        return form.classList.contains('js-outstanding-invoice-delete-form');
    }

    function description(form) {
        var supplier = form.dataset.supplier || '-';
        var invoice = form.dataset.invoice || '-';

        if (isInvoiceDelete(form)) {
            var materialCount = form.dataset.materialCount || '0';

            return 'Supplier: ' + supplier + '\n'
                + 'Invoice: ' + invoice + '\n'
                + 'Total material: ' + materialCount + '\n\n'
                + 'Seluruh material dan dokumen invoice yang tidak dipakai data lain akan dihapus permanen dan tidak dapat dibatalkan.';
        }

        var type = form.dataset.type || '-';

        return 'Supplier: ' + supplier + '\n'
            + 'TYPE: ' + type + '\n'
            + 'Invoice: ' + invoice + '\n\n'
            + 'Data ini akan dihapus dan tidak dapat dibatalkan.';
    }

    function confirmDelete(form) {
        if (form.dataset.confirmed === 'true' || form.dataset.confirmationPending === 'true') {
            return;
        }

        form.dataset.confirmationPending = 'true';

        if (typeof window.Swal === 'undefined') {
            var fallbackTitle = isInvoiceDelete(form)
                ? 'Hapus invoice ini secara permanen?'
                : 'Hapus data Outstanding Material ini?';
            if (window.confirm(fallbackTitle + '\n\n' + description(form))) {
                form.dataset.confirmed = 'true';
                delete form.dataset.confirmationPending;
                HTMLFormElement.prototype.submit.call(form);
                return;
            }

            delete form.dataset.confirmationPending;
            return;
        }

        window.Swal.fire({
            title: isInvoiceDelete(form) ? 'Hapus Invoice Permanen?' : 'Hapus Outstanding Material?',
            text: description(form),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: isInvoiceDelete(form) ? 'Ya, hapus permanen' : 'Ya, hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true,
        }).then(function (result) {
            delete form.dataset.confirmationPending;
            if (!result.isConfirmed) {
                return;
            }

            form.dataset.confirmed = 'true';
            HTMLFormElement.prototype.submit.call(form);
        });
    }

    function install() {
        if (document.documentElement.dataset.omDeleteConfirmationInstalled === 'true') {
            return;
        }

        document.documentElement.dataset.omDeleteConfirmationInstalled = 'true';
        document.addEventListener('click', function (event) {
            var button = event.target.closest('.js-outstanding-invoice-delete-form [type="submit"]');
            var form = button ? button.closest('form') : null;

            if (!form || form.dataset.confirmed === 'true') {
                return;
            }

            // Show the invoice-specific warning as soon as its trash button is
            // clicked.  The submit listener below remains as a keyboard/API
            // fallback and protects dynamically rendered DataTables rows.
            event.preventDefault();
            confirmDelete(form);
        }, true);

        document.addEventListener('submit', function (event) {
            var form = event.target.closest('.js-outstanding-delete-form, .js-outstanding-invoice-delete-form');
            if (!form || form.dataset.confirmed === 'true') {
                return;
            }

            event.preventDefault();
            confirmDelete(form);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', install);
    } else {
        install();
    }
}(window, document));
