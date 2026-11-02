(() => {
    const createForm = document.querySelector('[data-warehouse-stock-in-create-form]');
    if (createForm) {
        const select = createForm.querySelector('[data-warehouse-stock-in-item]');
        const barcode = createForm.querySelector('[data-warehouse-stock-in-item-barcode]');
        const syncItemBarcode = () => {
            const option = select?.selectedOptions?.[0];
            if (barcode && option) barcode.value = option.dataset.itemBarcode || '';
        };
        select?.addEventListener('change', syncItemBarcode);
        syncItemBarcode();
    }

    const validationForm = document.querySelector('[data-warehouse-stock-in-validation-form]');
    if (!validationForm) return;

    const expected = Number(validationForm.dataset.expectedQuantity || 0);
    const expectedRaw = validationForm.dataset.expectedQuantity || '0';
    const barcodeInput = validationForm.querySelector('[name="received_item_barcode"]');
    const quantity = validationForm.querySelector('[data-warehouse-validation-quantity]');
    const quantityHelp = validationForm.querySelector('[data-warehouse-validation-quantity-help]');
    const difference = validationForm.querySelector('[data-warehouse-validation-difference]');
    const notes = validationForm.querySelector('[name="validation_notes"]');
    const notesLabel = validationForm.querySelector('[data-warehouse-validation-notes-label]');
    const notesWrap = validationForm.querySelector('[data-warehouse-validation-notes-wrap]');
    const resultInputs = validationForm.querySelectorAll('[data-warehouse-validation-result]');
    const submitBtn = validationForm.querySelector('[data-warehouse-validation-submit]');
    const rejectTriggerBtn = validationForm.querySelector('[data-warehouse-rejection-trigger]');
    const rejectionSection = validationForm.querySelector('[data-warehouse-rejection-section]');
    const rejectionNotes = validationForm.querySelector('[data-warehouse-rejection-notes]');
    const rejectionCategoryInputs = validationForm.querySelectorAll('[data-warehouse-rejection-category]');

    const rejectionModalEl = document.getElementById('rejectionConfirmModal');
    const rejectionForm = document.querySelector('[data-warehouse-stock-in-rejection-form]');
    const finalReasonInput = document.querySelector('[data-warehouse-final-rejection-reason]');
    const rejectionPreview = document.querySelector('[data-warehouse-rejection-preview]');
    const csrf = validationForm.querySelector('input[name="_token"]')?.value || '';

    const verifyScan = async (input, url, payload) => {
        if (!input?.value.trim() || !url) return;
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload),
            });
            if (!response.ok) throw new Error('Scan tidak valid.');
            input.setCustomValidity('');
            input.setAttribute('aria-invalid', 'false');
        } catch (error) {
            input.setCustomValidity(error.message);
            input.setAttribute('aria-invalid', 'true');
        }
    };
    barcodeInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            verifyScan(event.currentTarget, validationForm.dataset.scanItemUrl, { code: event.currentTarget.value.trim() });
        }
    });

    const display = (value) => Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 3 });

    const getSelectedResult = () => [...resultInputs].find((input) => input.checked)?.value || 'MATCH';

    const getFormattedRejectionReason = () => {
        const category = [...rejectionCategoryInputs].find((input) => input.checked)?.value || '[PENOLAKAN FISIK: SALAH BARANG]';
        const text = rejectionNotes?.value.trim() || '';
        return text ? `${category} ${text}` : '';
    };

    const updateRejectionPreview = () => {
        const formatted = getFormattedRejectionReason();
        if (finalReasonInput) finalReasonInput.value = formatted;
        if (rejectionPreview) rejectionPreview.textContent = formatted || '—';
    };

    const sync = () => {
        const selected = getSelectedResult();
        const isMatch = selected === 'MATCH';
        const isManual = selected === 'MANUAL_ADJUSTMENT';
        const isReject = selected === 'REJECT';

        if (barcodeInput) {
            if (isReject) {
                barcodeInput.required = false;
                barcodeInput.setCustomValidity('');
                barcodeInput.setAttribute('aria-invalid', 'false');
            } else {
                barcodeInput.required = true;
            }
        }

        if (quantity) {
            if (isMatch) {
                quantity.readOnly = true;
                quantity.disabled = false;
                quantity.value = expectedRaw;
            } else if (isManual) {
                quantity.readOnly = false;
                quantity.disabled = false;
            } else if (isReject) {
                quantity.readOnly = true;
                quantity.disabled = true;
            }
        }

        if (quantityHelp) {
            if (isMatch) {
                quantityHelp.textContent = 'Terkunci sesuai data pengajuan. Pilih "Ada Selisih / Input Manual" untuk mengubah.';
            } else if (isManual) {
                quantityHelp.textContent = 'Masukkan jumlah fisik aktual yang diterima.';
            } else if (isReject) {
                quantityHelp.textContent = 'Barang ditolak. Kuantitas tidak akan dimasukkan ke dalam stok gudang.';
            }
        }

        if (difference) {
            if (isReject) {
                difference.textContent = 'Ditolak (0 masuk)';
            } else {
                const actual = Number(quantity?.value || 0);
                const delta = Math.round((actual - expected) * 1000) / 1000;
                if (Math.abs(delta) < 0.0001) {
                    difference.textContent = '0';
                } else {
                    difference.textContent = `${delta > 0 ? '+' : ''}${display(delta)}`;
                }
            }
        }

        if (notesWrap) {
            if (isManual) {
                notesWrap.classList.remove('d-none');
            } else {
                notesWrap.classList.add('d-none');
            }
        }

        if (notes) {
            const isNotesRequired = isManual;
            notes.required = isNotesRequired;
            notesLabel?.classList.toggle('warehouse-required', isNotesRequired);
            notesWrap?.classList.toggle('warehouse-required', isNotesRequired);
        }

        if (rejectionSection) {
            if (isReject) {
                rejectionSection.classList.remove('d-none');
            } else {
                rejectionSection.classList.add('d-none');
            }
        }

        if (rejectionNotes) {
            rejectionNotes.required = isReject;
        }

        if (submitBtn) {
            submitBtn.classList.toggle('d-none', isReject);
        }

        if (rejectTriggerBtn) {
            rejectTriggerBtn.classList.toggle('d-none', !isReject);
        }

        updateRejectionPreview();
    };

    quantity?.addEventListener('input', sync);

    resultInputs.forEach((input) => input.addEventListener('change', (e) => {
        sync();
        if (e.target.value === 'MANUAL_ADJUSTMENT' && quantity) {
            quantity.focus();
            quantity.select();
        } else if (e.target.value === 'REJECT' && rejectionNotes) {
            rejectionNotes.focus();
        }
    }));

    rejectionNotes?.addEventListener('input', updateRejectionPreview);
    rejectionCategoryInputs.forEach((input) => input.addEventListener('change', updateRejectionPreview));

    rejectTriggerBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const reasonText = rejectionNotes?.value.trim() || '';
        if (reasonText.length < 3) {
            if (rejectionNotes) {
                rejectionNotes.focus();
                rejectionNotes.reportValidity();
            }
            return;
        }
        updateRejectionPreview();
        if (rejectionModalEl && window.bootstrap?.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(rejectionModalEl).show();
        }
    });

    validationForm.addEventListener('submit', (event) => {
        if (getSelectedResult() === 'REJECT') {
            event.preventDefault();
            rejectTriggerBtn?.click();
        }
    });

    rejectionForm?.addEventListener('submit', (event) => {
        updateRejectionPreview();
        if ((finalReasonInput?.value || '').trim().length < 3) {
            event.preventDefault();
            alert('Alasan penolakan wajib diisi minimal 3 karakter.');
        }
    });

    if (barcodeInput && !barcodeInput.value) {
        barcodeInput.focus();
    }

    sync();
})();
