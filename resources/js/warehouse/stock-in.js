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
    const quantity = validationForm.querySelector('[data-warehouse-validation-quantity]');
    const difference = validationForm.querySelector('[data-warehouse-validation-difference]');
    const notes = validationForm.querySelector('[name="validation_notes"]');
    const resultInputs = validationForm.querySelectorAll('[data-warehouse-validation-result]');
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
    validationForm.querySelector('[name="received_item_barcode"]')?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            verifyScan(event.currentTarget, validationForm.dataset.scanItemUrl, { code: event.currentTarget.value.trim() });
        }
    });

    const display = (value) => Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    const sync = () => {
        const actual = Number(quantity?.value || 0);
        const delta = actual - expected;
        if (difference) difference.textContent = `${delta > 0 ? '+' : ''}${display(delta)}`;
        const manual = [...resultInputs].find((input) => input.checked)?.value === 'MANUAL_ADJUSTMENT';
        if (notes) {
            notes.required = manual || delta !== 0;
            notes.closest('[data-warehouse-validation-notes-wrap]')?.classList.toggle('warehouse-required', notes.required);
        }
    };
    quantity?.addEventListener('input', sync);
    resultInputs.forEach((input) => input.addEventListener('change', sync));
    sync();
})();
