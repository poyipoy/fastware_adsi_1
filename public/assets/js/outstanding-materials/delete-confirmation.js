(function (window, document) {
    'use strict';

    function description(form) {
        var supplier = form.dataset.supplier || '-';
        var type = form.dataset.type || '-';
        var invoice = form.dataset.invoice || '-';

        return 'Supplier: ' + supplier + '\n'
            + 'TYPE: ' + type + '\n'
            + 'Invoice: ' + invoice + '\n\n'
            + 'Data ini akan dihapus dan tidak dapat dibatalkan.';
    }

    function confirmDelete(form) {
        if (form.dataset.confirmed === 'true') {
            return;
        }

        if (typeof window.Swal === 'undefined') {
            if (window.confirm('Hapus data Outstanding Material ini?\n\n' + description(form))) {
                form.dataset.confirmed = 'true';
                HTMLFormElement.prototype.submit.call(form);
            }
            return;
        }

        window.Swal.fire({
            title: 'Hapus Outstanding Material?',
            text: description(form),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true,
        }).then(function (result) {
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
        document.addEventListener('submit', function (event) {
            var form = event.target.closest('.js-outstanding-delete-form');
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
