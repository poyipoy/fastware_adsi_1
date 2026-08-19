(() => {
    const form = document.querySelector('[data-warehouse-transfer-form]');
    if (!form) return;

    const csrf = form.querySelector('input[name="_token"]')?.value || '';
    const itemInput = form.querySelector('[data-transfer-item]');
    const conditionInput = form.querySelector('[data-transfer-condition]');
    const quantityInput = form.querySelector('[data-transfer-quantity]');
    const fromInput = form.querySelector('[data-transfer-from]');
    const toInput = form.querySelector('[data-transfer-to]');
    const verifierInput = form.querySelector('[data-transfer-verifier]');
    const verifyButton = form.querySelector('[data-transfer-verify]');
    const verifierResult = form.querySelector('[data-transfer-verifier-result]');
    const confirmInput = form.querySelector('[data-transfer-confirm]');
    const submitButton = form.querySelector('[data-transfer-submit]');
    const projection = form.querySelector('[data-transfer-projection]');
    let verifiedUser = null;

    const displayQuantity = (value) => Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 3 });
    const selectedData = () => itemInput.selectedOptions[0]?.dataset || null;
    const fieldKey = (condition, location) => `${condition.toLowerCase()}${location === 'DS8' ? 'Ds8' : 'Deltamas'}`;
    const available = () => Number(selectedData()?.[fieldKey(conditionInput.value, fromInput.value)] || 0);
    const quantity = () => Number(quantityInput.value || 0);

    const detailsAreValid = () => Boolean(
        itemInput.value
        && quantity() > 0
        && fromInput.value !== toInput.value
        && quantity() <= available(),
    );

    const clearVerifier = (message = 'Verifikasi NPK setelah rincian transfer valid.') => {
        verifiedUser = null;
        verifierInput.value = '';
        verifierResult.textContent = message;
        verifierResult.classList.remove('is-valid', 'is-invalid');
        confirmInput.checked = false;
    };

    const syncActions = () => {
        const valid = detailsAreValid();
        verifierInput.disabled = !valid;
        verifyButton.disabled = !valid;
        submitButton.disabled = !(valid && verifiedUser && confirmInput.checked);
    };

    const renderBalance = () => {
        const data = selectedData();
        const mappings = [
            ['[data-transfer-new-ds8]', 'newDs8'],
            ['[data-transfer-used-ds8]', 'usedDs8'],
            ['[data-transfer-total-ds8]', 'totalDs8'],
            ['[data-transfer-new-deltamas]', 'newDeltamas'],
            ['[data-transfer-used-deltamas]', 'usedDeltamas'],
            ['[data-transfer-total-deltamas]', 'totalDeltamas'],
        ];
        mappings.forEach(([selector, key]) => {
            form.querySelector(selector).textContent = itemInput.value ? displayQuantity(data?.[key]) : '—';
        });
        form.querySelector('[data-transfer-unit]').textContent = itemInput.value ? (data?.unit || 'Unit') : '—';

        const stock = available();
        const after = stock - quantity();
        form.querySelector('[data-transfer-available]').textContent = itemInput.value ? displayQuantity(stock) : '—';
        form.querySelector('[data-transfer-after]').textContent = itemInput.value && quantity() > 0 ? displayQuantity(after) : '—';

        const message = form.querySelector('[data-transfer-message]');
        projection.classList.toggle('is-invalid', itemInput.value && quantity() > 0 && after < 0);
        if (!itemInput.value) {
            message.textContent = 'Pilih barang untuk melihat saldo per lokasi.';
        } else if (fromInput.value === toInput.value) {
            message.textContent = 'Lokasi asal dan tujuan harus berbeda.';
        } else if (quantity() <= 0) {
            message.textContent = 'Masukkan jumlah transfer lebih dari nol.';
        } else if (after < 0) {
            message.textContent = `Stok ${conditionInput.value === 'USED' ? 'Bekas' : 'Baru'} di ${fromInput.value} tidak mencukupi.`;
        } else {
            message.textContent = `${displayQuantity(quantity())} ${data?.unit || 'unit'} akan dipindahkan dari ${fromInput.value} ke ${toInput.value}.`;
        }
        syncActions();
    };

    const handleDetailChange = () => {
        clearVerifier();
        const data = selectedData();
        quantityInput.step = data?.allowFraction === '1' ? '0.001' : '1';
        quantityInput.min = quantityInput.step;
        renderBalance();
    };

    const keepLocationsDifferent = (changed) => {
        if (fromInput.value !== toInput.value) return;
        const replacement = [...(changed === fromInput ? toInput.options : fromInput.options)]
            .find((option) => option.value !== changed.value)?.value;
        if (changed === fromInput) toInput.value = replacement || ''; else fromInput.value = replacement || '';
    };

    const verify = async () => {
        const code = verifierInput.value.trim();
        if (!detailsAreValid() || !code) return;
        verifyButton.disabled = true;
        verifierResult.textContent = 'Memverifikasi NPK…';
        verifierResult.classList.remove('is-valid', 'is-invalid');
        try {
            const response = await fetch(form.dataset.scanUserUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ code, type: 'TRANSFER' }),
            });
            const body = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(body.message || 'NPK tidak dapat diverifikasi.');
            verifiedUser = body.data;
            verifierResult.textContent = `${body.data.name} · NPK ${body.data.npk}${body.data.section ? ` · ${body.data.section}` : ''}`;
            verifierResult.classList.add('is-valid');
        } catch (error) {
            verifiedUser = null;
            verifierResult.textContent = error.message;
            verifierResult.classList.add('is-invalid');
        }
        syncActions();
    };

    [itemInput, conditionInput, quantityInput].forEach((field) => field.addEventListener('input', handleDetailChange));
    [fromInput, toInput].forEach((field) => field.addEventListener('change', () => {
        keepLocationsDifferent(field);
        handleDetailChange();
    }));
    verifierInput.addEventListener('input', () => {
        if (!verifiedUser) return;
        verifiedUser = null;
        confirmInput.checked = false;
        verifierResult.textContent = 'Verifikasi ulang setelah NPK diubah.';
        verifierResult.classList.remove('is-valid');
        syncActions();
    });
    verifierInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            verify();
        }
    });
    verifyButton.addEventListener('click', verify);
    confirmInput.addEventListener('change', syncActions);
    form.addEventListener('submit', (event) => {
        if (!detailsAreValid() || !verifiedUser || !confirmInput.checked) {
            event.preventDefault();
            verifierResult.textContent = 'Lengkapi rincian, verifikasi NPK, lalu centang konfirmasi.';
            verifierResult.classList.add('is-invalid');
        }
    });

    keepLocationsDifferent(fromInput);
    handleDetailChange();
})();
