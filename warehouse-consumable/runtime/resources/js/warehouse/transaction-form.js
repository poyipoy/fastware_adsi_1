(() => {
    const form = document.querySelector('[data-warehouse-transaction-form]');
    if (!form) return;

    const csrf = form.querySelector('input[name="_token"]')?.value || '';
    const typeInput = form.querySelector('[data-warehouse-type-value]');
    const conditionInput = form.querySelector('[data-warehouse-condition]');
    const itemInput = form.querySelector('[data-warehouse-item-input]');
    const quantityInput = form.querySelector('[data-warehouse-quantity]');
    const locationInput = form.querySelector('[data-warehouse-location]');
    const userInput = form.querySelector('[data-warehouse-user-input]');
    const confirmInput = form.querySelector('[data-warehouse-confirm-check]');
    const submitButton = form.querySelector('[data-warehouse-submit]');
    const returnToggle = form.querySelector('[data-warehouse-return-used]');
    const returnPanel = form.querySelector('[data-warehouse-used-return-panel]');
    const returnItemInput = form.querySelector('[data-warehouse-return-item-input]');
    const returnQuantity = form.querySelector('[data-warehouse-return-quantity]');
    const returnLocation = form.querySelector('[data-warehouse-return-location]');
    const summaryPanel = document.querySelector('[data-warehouse-summary]');
    const state = { item: null, returnItem: null, verifier: null, step: 1 };

    const clearVerifier = () => {
        state.verifier = null;
        userInput.value = '';
        const result = form.querySelector('[data-warehouse-user-result]');
        result.innerHTML = '<span class="warehouse-muted">Belum diverifikasi.</span>';
        result.classList.remove('is-valid');
    };

    const invalidateApproval = (resetVerifier = false) => {
        confirmInput.checked = false;
        if (resetVerifier) clearVerifier();
    };

    const uuid = () => window.crypto?.randomUUID?.() || 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => {
        const random = Math.random() * 16 | 0;
        return (character === 'x' ? random : (random & 0x3 | 0x8)).toString(16);
    });
    form.querySelector('[data-warehouse-idempotency-key]').value = uuid();

    const requestJson = async (url, payload = null, signal = undefined) => {
        const options = { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, signal };
        if (payload !== null) {
            options.method = 'POST';
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(payload);
        }
        const response = await fetch(url, options);
        const body = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(body.message || 'Permintaan tidak dapat diproses.');
        return body;
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
    const displayQuantity = (value) => Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 3 });
    const transactionTypeLabels = { IN: 'Stock In', OUT: 'Stock Out' };
    const stockStatusLabels = { HEALTHY: 'Aman', LOW: 'Menipis', OUT: 'Habis' };
    const stockStatusTones = { HEALTHY: 'success', LOW: 'warning', OUT: 'danger' };
    const stockStatusPresentation = (status) => ({ label: stockStatusLabels[status] || status || '—', tone: stockStatusTones[status] || 'neutral' });
    const renderStockStatusBadge = (status) => {
        const presentation = stockStatusPresentation(status);
        const badge = document.createElement('span');
        badge.className = `warehouse-status-badge warehouse-status-badge-${presentation.tone}`;
        badge.textContent = presentation.label;
        return badge;
    };

    const appendPreview = (target, item) => {
        target.replaceChildren();
        const wrapper = document.createElement('div');
        wrapper.className = 'warehouse-preview-content';
        wrapper.innerHTML = `<strong class="warehouse-preview-title">${escapeHtml(item.item_name)}</strong><dl class="warehouse-preview-facts"><div class="warehouse-preview-fact"><dt class="warehouse-preview-fact-label">Item Code</dt><dd class="warehouse-preview-fact-value">${escapeHtml(item.item_code)}</dd></div><div class="warehouse-preview-fact"><dt class="warehouse-preview-fact-label">Stok saat ini</dt><dd class="warehouse-preview-fact-value">${displayQuantity(item.current_stock)} ${escapeHtml(item.unit)}</dd></div><div class="warehouse-preview-fact"><dt class="warehouse-preview-fact-label">Status stok</dt><dd class="warehouse-preview-fact-value" data-status></dd></div><div class="warehouse-preview-fact"><dt class="warehouse-preview-fact-label">Lokasi</dt><dd class="warehouse-preview-fact-value">DS8 ${displayQuantity(item.stock_ds8 ?? item.locations?.DS8?.total)} · Deltamas ${displayQuantity(item.stock_deltamas ?? item.locations?.Deltamas?.total)}</dd></div></dl>`;
        wrapper.querySelector('[data-status]').append(renderStockStatusBadge(item.stock_status));
        target.append(wrapper);
        target.classList.add('is-valid');
    };

    const selectItem = (item, target = 'primary') => {
        invalidateApproval(true);
        if (target === 'return') {
            state.returnItem = item;
            returnItemInput.value = item.barcode || item.item_code;
            returnQuantity.step = item.allow_fraction ? '0.001' : '1';
            returnQuantity.min = returnQuantity.step;
            appendPreview(form.querySelector('[data-warehouse-return-item-result]'), item);
        } else {
            state.item = item;
            itemInput.value = item.barcode || item.item_code;
            quantityInput.step = item.allow_fraction ? '0.001' : '1';
            quantityInput.min = quantityInput.step;
            appendPreview(form.querySelector('[data-warehouse-item-result]'), item);
            form.querySelector('[data-warehouse-next-detail]').disabled = false;
        }
        updateSummary();
        syncVerifierAvailability();
    };

    const scanItem = async (target = 'primary') => {
        const input = target === 'return' ? returnItemInput : itemInput;
        if (!input?.value.trim()) return;
        const button = target === 'return' ? form.querySelector('[data-warehouse-scan-return-item]') : form.querySelector('[data-warehouse-scan-item]');
        button.disabled = true;
        try {
            const response = await requestJson(form.dataset.scanItemUrl, { code: input.value.trim() });
            selectItem(response.data, target);
        } catch (error) {
            const result = form.querySelector(target === 'return' ? '[data-warehouse-return-item-result]' : '[data-warehouse-item-result]');
            if (target === 'return') {
                state.returnItem = null;
            } else {
                state.item = null;
                form.querySelector('[data-warehouse-next-detail]').disabled = true;
            }
            invalidateApproval(true);
            result.innerHTML = `<span class="text-danger">${escapeHtml(error.message)}</span>`;
            result.classList.remove('is-valid');
            updateSummary();
            syncVerifierAvailability();
        } finally { button.disabled = false; }
    };

    const initialiseCatalog = (catalog) => {
        const target = catalog.dataset.warehouseCatalog;
        const search = catalog.querySelector('[data-warehouse-catalog-search]');
        const grid = catalog.querySelector('[data-warehouse-catalog-grid]');
        const status = catalog.querySelector('[data-warehouse-catalog-status]');
        const more = catalog.querySelector('[data-warehouse-catalog-more]');
        let page = 1;
        let timer;
        let controller;

        const load = async (append = false) => {
            controller?.abort();
            controller = new AbortController();
            if (!append) { page = 1; grid.replaceChildren(); }
            status.textContent = 'Memuat katalog…';
            more.hidden = true;
            const url = new URL(form.dataset.catalogUrl, window.location.origin);
            url.searchParams.set('page', String(page));
            if (search.value.trim()) url.searchParams.set('search', search.value.trim());
            try {
                const response = await requestJson(url.toString(), null, controller.signal);
                response.data.forEach((item) => {
                    const catalogCondition = target === 'return' ? 'used' : conditionInput.value.toLowerCase();
                    const card = document.createElement('button');
                    card.type = 'button';
                    card.className = 'warehouse-catalog-card';
                    card.innerHTML = `<span class="warehouse-catalog-image">${item.photo_url ? `<img src="${escapeHtml(item.photo_url)}" alt="" loading="lazy" width="320" height="220">` : '<span aria-hidden="true">WH</span>'}</span><span class="warehouse-catalog-copy"><strong>${escapeHtml(item.item_name)}</strong><small>${escapeHtml(item.item_code)}${item.machine_type ? ` · ${escapeHtml(item.machine_type)}` : ''}</small><span>DS8 ${displayQuantity(item.locations.DS8[catalogCondition])} · Deltamas ${displayQuantity(item.locations.Deltamas[catalogCondition])}</span></span>`;
                    card.setAttribute('aria-label', `Pilih ${item.item_name}`);
                    card.addEventListener('click', () => selectItem(item, target));
                    grid.append(card);
                });
                status.textContent = response.meta.total ? `${response.meta.total} barang ditemukan.` : 'Barang tidak ditemukan.';
                more.hidden = !response.meta.has_more;
            } catch (error) {
                if (error.name !== 'AbortError') status.textContent = error.message;
            }
        };
        search.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => load(false), 300); });
        search.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const scanInput = target === 'return' ? returnItemInput : itemInput;
            scanInput.value = search.value;
            scanItem(target);
        });
        more.addEventListener('click', () => { page += 1; load(true); });
        load(false);
    };

    const available = () => {
        if (!state.item) return 0;
        const location = locationInput.value;
        const condition = conditionInput.value.toLowerCase();
        return Number(state.item.locations?.[location]?.[condition] ?? state.item[`stock_${condition}_${location.toLowerCase()}`] ?? 0);
    };

    const updateProjection = () => {
        const before = available();
        const quantity = Number(quantityInput.value || 0);
        const change = typeInput.value === 'IN' ? quantity : -quantity;
        const after = before + change;
        form.querySelector('[data-warehouse-projection-before]').textContent = displayQuantity(before);
        form.querySelector('[data-warehouse-projection-change]').textContent = `${change >= 0 ? '+' : '−'}${displayQuantity(Math.abs(change))}`;
        form.querySelector('[data-warehouse-projection-after]').textContent = displayQuantity(after);
        form.querySelector('[data-warehouse-projection]').classList.toggle('is-invalid', after < 0);
    };

    const updateSummary = () => {
        if (!summaryPanel) return updateProjection();
        summaryPanel.querySelector('[data-warehouse-summary-item]').textContent = state.item?.item_name || '—';
        summaryPanel.querySelector('[data-warehouse-summary-item-code]').textContent = state.item?.item_code || '—';
        summaryPanel.querySelector('[data-warehouse-summary-item-barcode]').textContent = state.item?.barcode || '—';
        summaryPanel.querySelector('[data-warehouse-summary-current-stock]').textContent = state.item ? displayQuantity(state.item.current_stock) : '—';
        const stockStatus = summaryPanel.querySelector('[data-warehouse-summary-stock-status]');
        stockStatus.replaceChildren();
        if (state.item) stockStatus.append(renderStockStatusBadge(state.item.stock_status)); else stockStatus.textContent = '—';
        summaryPanel.querySelector('[data-warehouse-summary-type]').textContent = transactionTypeLabels[typeInput.value] || '—';
        summaryPanel.querySelector('[data-warehouse-summary-location]').textContent = locationInput.value;
        summaryPanel.querySelector('[data-warehouse-summary-quantity]').textContent = displayQuantity(quantityInput.value);
        summaryPanel.querySelector('[data-warehouse-summary-user]').textContent = state.verifier?.name || '—';
        summaryPanel.querySelector('[data-warehouse-summary-user-meta]').textContent = state.verifier ? `NPK ${state.verifier.npk} · ${state.verifier.section || '—'}` : '—';
        updateProjection();
    };

    const setType = (type) => {
        typeInput.value = type;
        form.querySelectorAll('[data-warehouse-type]').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.warehouseType === type)));
        form.querySelector('[data-warehouse-type-caption]').textContent = type === 'IN' ? 'Penambahan stok' : 'Pengeluaran stok';
        if (returnToggle) {
            returnToggle.disabled = inbound;
            if (inbound) { returnToggle.checked = false; toggleReturn(); }
        }
        invalidateApproval(true);
        updateSummary();
        syncVerifierAvailability();
    };

    const toggleReturn = () => {
        if (!returnPanel) return;
        const enabled = returnToggle.checked && typeInput.value === 'OUT';
        returnPanel.hidden = !enabled;
        returnPanel.querySelectorAll('input, select').forEach((field) => { field.disabled = !enabled; });
        if (enabled && !returnPanel.dataset.loaded) {
            initialiseCatalog(returnPanel.querySelector('[data-warehouse-catalog="return"]'));
            returnPanel.dataset.loaded = 'true';
        }
        syncVerifierAvailability();
    };

    const syncVerifierAvailability = () => {
        const returnComplete = !returnToggle?.checked || (state.returnItem && Number(returnQuantity.value) > 0 && returnLocation.value);
        const valid = state.item && Number(quantityInput.value) > 0 && available() + (typeInput.value === 'IN' ? Number(quantityInput.value) : -Number(quantityInput.value)) >= 0 && returnComplete;
        userInput.disabled = !valid;
        form.querySelector('[data-warehouse-scan-user]').disabled = !valid;
        form.querySelector('[data-warehouse-verifier-panel]').classList.toggle('is-locked', !valid);
        submitButton.disabled = !(valid && state.verifier && confirmInput.checked);
    };

    const scanUser = async () => {
        const code = userInput.value.trim();
        if (!code) return;
        const button = form.querySelector('[data-warehouse-scan-user]');
        button.disabled = true;
        try {
            const response = await requestJson(form.dataset.scanUserUrl, { code, type: typeInput.value });
            state.verifier = response.data;
            const result = form.querySelector('[data-warehouse-user-result]');
            result.innerHTML = `<div class="warehouse-preview-content"><strong class="warehouse-preview-title">${escapeHtml(state.verifier.name)}</strong><dl class="warehouse-preview-facts"><div><dt class="warehouse-preview-fact-label">NPK</dt><dd class="warehouse-preview-fact-value">${escapeHtml(state.verifier.npk)}</dd></div><div><dt class="warehouse-preview-fact-label">Bagian</dt><dd class="warehouse-preview-fact-value">${escapeHtml(state.verifier.section || '—')}</dd></div></dl></div>`;
            result.classList.add('is-valid');
        } catch (error) {
            state.verifier = null;
            const result = form.querySelector('[data-warehouse-user-result]');
            result.innerHTML = `<span class="text-danger">${escapeHtml(error.message)}</span>`;
            result.classList.remove('is-valid');
        }
        updateSummary();
        syncVerifierAvailability();
    };

    const updateStep = (step) => {
        state.step = step;
        form.querySelectorAll('[data-warehouse-step]').forEach((section) => { section.hidden = Number(section.dataset.warehouseStep) !== step; });
        form.querySelectorAll('[data-warehouse-step-indicator]').forEach((indicator) => {
            const number = Number(indicator.dataset.warehouseStepIndicator);
            indicator.classList.toggle('is-active', number === step);
            indicator.classList.toggle('is-complete', number < step);
            if (number === step) indicator.setAttribute('aria-current', 'step'); else indicator.removeAttribute('aria-current');
        });
        form.closest('.warehouse-transaction-layout').classList.toggle('is-receipt', step === 3);
        if (summaryPanel) summaryPanel.hidden = step === 3;
        form.querySelector(`[data-warehouse-step="${step}"]`)?.querySelector('h2')?.focus?.();
    };

    form.querySelectorAll('[data-warehouse-catalog="primary"]').forEach(initialiseCatalog);
    form.querySelector('[data-warehouse-scan-item]').addEventListener('click', () => scanItem('primary'));
    itemInput.addEventListener('input', () => {
        if (!state.item) return;
        state.item = null;
        invalidateApproval(true);
        const result = form.querySelector('[data-warehouse-item-result]');
        result.innerHTML = '<span class="warehouse-muted">Pindai atau pilih ulang barang setelah Item Code diubah.</span>';
        result.classList.remove('is-valid');
        form.querySelector('[data-warehouse-next-detail]').disabled = true;
        updateSummary();
        syncVerifierAvailability();
    });
    itemInput.addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); scanItem('primary'); } });
    form.querySelector('[data-warehouse-scan-return-item]')?.addEventListener('click', () => scanItem('return'));
    returnItemInput?.addEventListener('input', () => {
        if (!state.returnItem) return;
        state.returnItem = null;
        invalidateApproval(true);
        const result = form.querySelector('[data-warehouse-return-item-result]');
        result.innerHTML = '<span class="warehouse-muted">Pindai atau pilih ulang barang bekas setelah Item Code diubah.</span>';
        result.classList.remove('is-valid');
        updateSummary();
        syncVerifierAvailability();
    });
    returnItemInput?.addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); scanItem('return'); } });
    form.querySelector('[data-warehouse-next-detail]').addEventListener('click', () => updateStep(2));
    form.querySelector('[data-warehouse-back-item]').addEventListener('click', () => updateStep(1));
    form.querySelectorAll('[data-warehouse-type]').forEach((button) => button.addEventListener('click', () => setType(button.dataset.warehouseType)));
    form.querySelector('[data-warehouse-quantity-down]').addEventListener('click', () => { quantityInput.stepDown(); quantityInput.dispatchEvent(new Event('input')); });
    form.querySelector('[data-warehouse-quantity-up]').addEventListener('click', () => { quantityInput.stepUp(); quantityInput.dispatchEvent(new Event('input')); });
    [quantityInput, locationInput, returnQuantity, returnLocation].filter(Boolean).forEach((field) => field.addEventListener('input', () => { invalidateApproval(true); updateSummary(); syncVerifierAvailability(); }));
    returnToggle?.addEventListener('change', () => { invalidateApproval(true); toggleReturn(); });
    userInput.addEventListener('input', () => {
        if (!state.verifier) return;
        state.verifier = null;
        confirmInput.checked = false;
        const result = form.querySelector('[data-warehouse-user-result]');
        result.innerHTML = '<span class="warehouse-muted">Verifikasi ulang setelah NPK diubah.</span>';
        result.classList.remove('is-valid');
        updateSummary();
        syncVerifierAvailability();
    });
    userInput.addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); scanUser(); } });
    form.querySelector('[data-warehouse-scan-user]').addEventListener('click', scanUser);
    confirmInput.addEventListener('change', syncVerifierAvailability);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (state.step !== 2 || submitButton.disabled || !form.reportValidity()) return;
        submitButton.disabled = true;
        submitButton.textContent = 'Menyimpan…';
        try {
            form.querySelector('[data-warehouse-submit-error]')?.remove();
            const payload = Object.fromEntries(new FormData(form).entries());
            if (!returnToggle?.checked) delete payload.return_used;
            const response = await requestJson(form.action, payload);
            const receipt = form.querySelector('[data-warehouse-receipt-details]');
            const related = response.related_transactions?.length ? `${response.related_transactions.length} transaksi pengembalian terkait` : 'Tidak ada';
            const facts = [['Nomor transaksi', response.data.transaction_number], ['Tipe', transactionTypeLabels[response.data.transaction_type] || response.data.transaction_type], ['Kondisi', response.data.item_condition === 'USED' ? 'Bekas' : 'Baru'], ['Barang', response.data.item], ['Jumlah', displayQuantity(response.data.quantity)], ['Lokasi', response.data.to_location || response.data.from_location || '—'], ['Stok total', `${displayQuantity(response.data.stock_before)} → ${displayQuantity(response.data.stock_after)}`], ['Karyawan verifikator', response.data.verified_user_name], ['Transaksi terkait', related]];
            receipt.replaceChildren();
            facts.forEach(([label, value]) => { const dt = document.createElement('dt'); const dd = document.createElement('dd'); dt.textContent = label; dd.textContent = value ?? '—'; receipt.append(dt, dd); });
            updateStep(3);
        } catch (error) {
            submitButton.disabled = false;
            submitButton.textContent = 'Simpan transaksi';
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger';
            alert.role = 'alert';
            alert.dataset.warehouseSubmitError = '';
            alert.textContent = error.message;
            form.querySelector('[data-warehouse-step="2"]').prepend(alert);
            alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    setType(typeInput.value);
    toggleReturn();
    if (form.dataset.warehouseInitialBarcode) scanItem('primary');
})();
