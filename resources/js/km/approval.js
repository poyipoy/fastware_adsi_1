import { confirmAction, notify, setFormBusy } from './ui-feedback.js';

const page = document.querySelector('[data-km-approval-page]');

if (page) {
    const bulkForm = document.querySelector('[data-km-bulk-form]');
    const bulkCheckboxes = Array.from(document.querySelectorAll('[data-km-bulk-checkbox]'));
    const selectAll = document.querySelector('[data-km-bulk-select-all]');
    const bulkAction = document.querySelector('[data-km-bulk-action]');
    const reasonGroup = document.querySelector('[data-km-bulk-reason-group]');
    const reason = document.querySelector('[data-km-bulk-reason]');
    const countLabel = document.querySelector('[data-km-bulk-count]');
    const bulkSubmit = document.querySelector('[data-km-bulk-submit]');

    const selectedCheckboxes = () => bulkCheckboxes.filter((checkbox) => checkbox.checked);

    const synchronizeBulkControls = () => {
        const selected = selectedCheckboxes();
        const approving = bulkAction?.value === 'approve';

        bulkCheckboxes.forEach((checkbox) => {
            const category = checkbox.closest('tr')?.querySelector('[data-km-bulk-category]');
            if (!category) {
                return;
            }

            category.disabled = !checkbox.checked || !approving;
            category.required = checkbox.checked && approving;
        });

        if (reasonGroup && reason) {
            const rejecting = bulkAction?.value === 'reject';
            reasonGroup.hidden = !rejecting;
            reason.required = rejecting;
            if (!rejecting) {
                reason.setCustomValidity('');
            }
        }

        if (selectAll) {
            selectAll.checked = bulkCheckboxes.length > 0 && selected.length === bulkCheckboxes.length;
            selectAll.indeterminate = selected.length > 0 && selected.length < bulkCheckboxes.length;
        }

        if (countLabel) {
            countLabel.textContent = `${selected.length} dipilih`;
        }
        if (bulkSubmit) {
            bulkSubmit.disabled = selected.length === 0;
        }
    };

    bulkCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', synchronizeBulkControls);
    });
    selectAll?.addEventListener('change', () => {
        bulkCheckboxes.forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
        });
        synchronizeBulkControls();
    });
    bulkAction?.addEventListener('change', synchronizeBulkControls);
    reason?.addEventListener('input', () => reason.setCustomValidity(''));

    let bulkSubmitting = false;
    bulkForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        synchronizeBulkControls();
        const selectedCount = selectedCheckboxes().length;

        if (selectedCount === 0) {
            notify({
                title: 'Belum ada dokumen dipilih',
                message: 'Pilih minimal satu dokumen yang menunggu persetujuan.',
                tone: 'warning',
            });
            selectAll?.focus();
            return;
        }

        if (bulkAction?.value === 'reject' && reason && reason.value.trim() === '') {
            reason.setCustomValidity('Alasan penolakan wajib diisi.');
            reason.reportValidity();
            return;
        }

        if (bulkSubmitting) {
            return;
        }

        const actionLabel = bulkAction?.value === 'reject' ? 'menolak' : 'menyetujui';
        const confirmed = await confirmAction({
            title: bulkAction?.value === 'reject' ? 'Tolak dokumen terpilih?' : 'Setujui dokumen terpilih?',
            message: `Anda akan ${actionLabel} ${selectedCount} dokumen dalam satu transaksi all-or-nothing.`,
            confirmLabel: bulkAction?.value === 'reject' ? 'Tolak Dokumen' : 'Setujui Dokumen',
            tone: bulkAction?.value === 'reject' ? 'danger' : 'success',
            trigger: event.submitter ?? bulkSubmit,
        });
        if (!confirmed) {
            return;
        }

        bulkSubmitting = true;
        setFormBusy(bulkForm, bulkSubmit, 'Memproses…');
        bulkForm.submit();
    });

    synchronizeBulkControls();
    document.querySelector('[data-km-bulk-error]')?.focus();

    const singleForm = document.getElementById('singleApprovalForm');
    const modalElement = document.getElementById('editKmModal');
    const actionInput = document.getElementById('approvalAction');
    const singleReasonGroup = document.getElementById('rejectReasonGroup');
    const singleReason = document.getElementById('rejectReason');
    const categorySelect = document.getElementById('editKategori');
    const detailUrlTemplate = page.dataset.detailUrlTemplate || '';
    let fileUrl = '';

    const routeFor = (id) => detailUrlTemplate.replace('__KM_ID__', encodeURIComponent(String(id)));

    const prepareApprovalAction = (action) => {
        const validAction = ['approved', 'rejected'].includes(action) ? action : '';
        const rejected = validAction === 'rejected';

        if (actionInput) {
            actionInput.value = validAction;
        }
        if (singleReasonGroup && singleReason) {
            singleReasonGroup.hidden = !rejected;
            singleReason.required = rejected;
            if (!rejected) {
                singleReason.setCustomValidity('');
            }
        }
    };

    const renderApprovalHistory = (events) => {
        const history = document.getElementById('approvalHistory');
        if (!history) {
            return;
        }

        history.replaceChildren();
        if (!events.length) {
            history.textContent = 'Belum ada riwayat.';
            return;
        }

        events.forEach((event) => {
            const item = document.createElement('div');
            item.className = 'km-history-item border-bottom py-2';
            const actor = event.actor_name || 'Pengguna tidak tersedia';
            const reasonText = event.reason ? ` — ${event.reason}` : '';
            item.textContent = `${event.acted_at || '-'} · ${actor} · ${event.action}${reasonText}`;
            history.appendChild(item);
        });
    };

    const showModal = () => {
        if (!modalElement || !window.bootstrap?.Modal) {
            throw new Error('Komponen modal Bootstrap tidak tersedia.');
        }
        window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
    };

    const openEditKmModal = async (id, restoreOldInput = false) => {
        const preserved = restoreOldInput
            ? {
                judul: document.getElementById('editJudul')?.value || '',
                keterangan: document.getElementById('editKeterangan')?.value || '',
                posisi: document.getElementById('editPosisi')?.value || '',
                kategori: categorySelect?.value || '',
                reason: singleReason?.value || '',
                action: actionInput?.value || '',
            }
            : null;

        try {
            const response = await fetch(routeFor(id), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Detail persetujuan tidak dapat dimuat.');
            }

            document.getElementById('editId').value = data.km.id;
            document.getElementById('editJudul').value = preserved?.judul ?? data.km.judul ?? '';
            document.getElementById('editKeterangan').value = preserved?.keterangan ?? data.km.keterangan ?? '';
            document.getElementById('editPosisi').value = preserved?.posisi ?? data.km.posisi ?? '';

            if (categorySelect) {
                categorySelect.replaceChildren();
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = '----- Pilih Kategori -----';
                categorySelect.appendChild(placeholder);
                (data.kategoris || []).forEach((category) => {
                    const option = document.createElement('option');
                    option.value = String(category.id);
                    option.textContent = category.nama_kategori;
                    categorySelect.appendChild(option);
                });
                categorySelect.value = preserved?.kategori ?? data.km.id_km_kategori ?? '';
            }

            const fileLink = document.getElementById('editFileLink');
            const fileButton = document.getElementById('editFileButton');
            if (data.km.has_file) {
                fileUrl = data.km.previewable ? data.km.preview_url : data.km.download_url;
                fileButton.textContent = data.km.previewable ? 'Tampilkan PDF' : 'Unduh Dokumen Office';
                fileLink.classList.remove('d-none');
            } else {
                fileUrl = '';
                fileLink.classList.add('d-none');
            }

            document.getElementById('approveButton').hidden = !data.km.can_approve;
            document.getElementById('rejectButton').hidden = !data.km.can_reject;
            renderApprovalHistory(data.km.approval_events || []);

            if (preserved) {
                singleReason.value = preserved.reason;
                prepareApprovalAction(preserved.action);
            } else {
                singleReason.value = '';
                prepareApprovalAction('');
            }

            showModal();
        } catch (error) {
            console.error(error);
            notify({
                title: 'Detail belum dapat dibuka',
                message: error.message || 'Detail persetujuan tidak dapat dimuat.',
                tone: 'danger',
            });
        }
    };

    document.querySelectorAll('[data-km-open-approval]').forEach((button) => {
        button.addEventListener('click', () => openEditKmModal(button.dataset.kmOpenApproval));
    });
    document.querySelectorAll('[data-km-single-action]').forEach((button) => {
        button.addEventListener('click', () => prepareApprovalAction(button.dataset.kmSingleAction));
    });
    document.getElementById('editFileButton')?.addEventListener('click', () => {
        if (fileUrl) {
            window.open(fileUrl, '_blank', 'noopener');
        }
    });

    let singleSubmitting = false;
    singleForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (actionInput?.value === 'rejected' && singleReason?.value.trim() === '') {
            singleReason.setCustomValidity('Alasan penolakan wajib diisi.');
            singleReason.reportValidity();
            return;
        }
        if (singleSubmitting) {
            return;
        }

        const rejecting = actionInput?.value === 'rejected';
        const confirmed = await confirmAction({
            title: rejecting ? 'Tolak dokumen ini?' : 'Setujui dokumen ini?',
            message: rejecting
                ? 'Dokumen akan ditolak dengan alasan yang Anda berikan.'
                : 'Dokumen akan diterbitkan sesuai kategori dan bagian yang dipilih.',
            confirmLabel: rejecting ? 'Tolak Dokumen' : 'Setujui Dokumen',
            tone: rejecting ? 'danger' : 'success',
            trigger: event.submitter,
        });
        if (!confirmed) {
            return;
        }

        singleSubmitting = true;
        setFormBusy(singleForm, event.submitter, 'Memproses…');
        singleForm.submit();
    });
    singleReason?.addEventListener('input', () => singleReason.setCustomValidity(''));

    window.openEditKmModal = openEditKmModal;
    window.openPdf = () => {
        if (fileUrl) {
            window.open(fileUrl, '_blank', 'noopener');
        }
    };
    window.prepareApprovalAction = prepareApprovalAction;

    const restoreId = singleForm?.dataset.restoreId;
    if (restoreId) {
        openEditKmModal(restoreId, true);
    }
}
