(() => {
    const form = document.querySelector('[data-warehouse-transaction-form]');
    if (!form) return;

    const csrf = form.querySelector('input[name="_token"]')?.value || '';
    const typeInput = form.querySelector('[data-warehouse-type-value]');
    const conditionInput = form.querySelector('[data-warehouse-condition]');
    const itemInput = form.querySelector('[data-warehouse-item-input]');
    const quantityInput = form.querySelector('[data-warehouse-quantity]');
    const locationInput = form.querySelector('[data-warehouse-location]');
    const sourceLocationWrap = form.querySelector('[data-warehouse-source-location-wrap]');
    const sourceLocationInput = form.querySelector('[data-warehouse-source-location]');
    const singleQuantityWrap = form.querySelector('[data-warehouse-single-quantity-wrap]');
    const machineAllocationWrap = form.querySelector('[data-warehouse-machine-allocation-wrap]');
    const allocationTotalDisplay = form.querySelector('[data-warehouse-allocation-total]');
    const machineChecks = form.querySelectorAll('[data-warehouse-machine-check]');
    const machineQtyInputs = form.querySelectorAll('[data-warehouse-machine-qty-input]');
    const userInput = form.querySelector('[data-warehouse-user-input]');
    const verifierPanel = form.querySelector('[data-warehouse-verifier-panel]');
    const verifierCopy = form.querySelector('[data-warehouse-verifier-copy]');
    const confirmInput = form.querySelector('[data-warehouse-confirm-check]');
    const confirmCopy = form.querySelector('[data-warehouse-confirm-copy]');
    const submitButton = form.querySelector('[data-warehouse-submit]');
    const lockMessage = form.querySelector('[data-warehouse-verifier-lock-message]');
    const returnToggle = form.querySelector('[data-warehouse-return-used]');
    const returnPanel = form.querySelector('[data-warehouse-used-return-panel]');
    const returnItemInput = form.querySelector('[data-warehouse-return-item-input]');
    const returnQuantity = form.querySelector('[data-warehouse-return-quantity]');
    const returnLocation = form.querySelector('[data-warehouse-return-location]');
    const summaryPanel = document.querySelector('[data-warehouse-summary]');
    const state = { item: null, returnItem: null, verifier: null, step: 1 };

    const catalogItemKey = (item) => String(item?.id ?? item?.barcode ?? item?.item_code ?? '');
    const syncCatalogSelection = (target) => {
        const selected = target === 'return' ? state.returnItem : state.item;
        const selectedKey = catalogItemKey(selected);
        form.querySelectorAll(`[data-warehouse-catalog="${target}"] [data-warehouse-catalog-item-key]`).forEach((card) => {
            const key = card.dataset.warehouseCatalogItemKey;
            const selectedCard = Boolean(
                selected && selectedKey !== '' && (
                    key === selectedKey ||
                    (selected.id && card.dataset.itemId === String(selected.id)) ||
                    (selected.item_code && card.dataset.itemCode === String(selected.item_code)) ||
                    (selected.barcode && card.dataset.barcode === String(selected.barcode))
                )
            );
            card.classList.toggle('is-selected', selectedCard);
            card.setAttribute('aria-pressed', String(selectedCard));
        });
    };

    const isPendingStockIn = () => typeInput.value === 'IN';

    const getMachineAllocationsTotal = () => {
        let total = 0;
        machineChecks.forEach((check) => {
            if (check.checked) {
                const code = check.dataset.warehouseMachineCheck;
                const input = form.querySelector(`[data-warehouse-machine-qty-input="${code}"]`);
                const val = parseInt(input?.value || '0', 10);
                if (val > 0) total += val;
            }
        });
        return total;
    };

    const syncMachineAllocationVisibility = () => {
        const isOut = typeInput.value === 'OUT';
        if (singleQuantityWrap) singleQuantityWrap.hidden = isOut;
        if (machineAllocationWrap) machineAllocationWrap.hidden = !isOut;

        if (isOut) {
            quantityInput.readOnly = true;
            syncMachineAllocations();
        } else {
            quantityInput.readOnly = false;
            machineQtyInputs.forEach((input) => { input.disabled = true; });
            updateProjection();
            updateSummary();
            syncVerifierAvailability();
        }
    };

    const syncMachineAllocations = () => {
        if (typeInput.value !== 'OUT') return;
        const total = getMachineAllocationsTotal();
        if (allocationTotalDisplay) {
            allocationTotalDisplay.textContent = String(total);
        }
        quantityInput.value = String(total);
        updateProjection();
        updateSummary();
        syncVerifierAvailability();
    };

    const syncWorkflowPresentation = () => {
        const pendingStockIn = isPendingStockIn();
        if (verifierPanel) verifierPanel.hidden = pendingStockIn;
        if (verifierCopy) verifierCopy.textContent = pendingStockIn
            ? 'Stock In dibuat Menunggu Validasi dan diperiksa melalui menu Validasi Stok.'
            : 'Pindai barcode NPK karyawan yang mengambil barang.';
        if (confirmCopy) confirmCopy.textContent = pendingStockIn
            ? 'Saya telah memeriksa item, jumlah, lokasi, dan catatan Stock In.'
            : 'Saya telah memeriksa barang, kondisi, lokasi, alokasi mesin, dan karyawan pengambil.';
    };

    const clearVerifier = () => {
        state.verifier = null;
        userInput.value = '';
        const result = form.querySelector('[data-warehouse-user-result]');
        result.innerHTML = '<span class="warehouse-muted">Belum ada karyawan dipilih.</span>';
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
    const renderStockStatusBadge = (statusOrPresentation) => {
        const presentation = typeof statusOrPresentation === 'object' && statusOrPresentation !== null
            ? statusOrPresentation
            : stockStatusPresentation(statusOrPresentation);
        const badge = document.createElement('span');
        badge.className = `warehouse-status-badge warehouse-status-badge-${presentation.tone}`;
        badge.textContent = presentation.label;
        return badge;
    };

    const getItemConditionStock = (item, condition) => {
        if (!item) return { current: 0, ds8: 0, deltamas: 0, totalCombined: 0 };
        const cond = String(condition || 'new').toLowerCase();
        const ds8 = Number(item.locations?.DS8?.[cond] ?? item[`stock_${cond}_ds8`] ?? (item.stock?.DS8?.[cond]) ?? 0);
        const deltamas = Number(item.locations?.Deltamas?.[cond] ?? item[`stock_${cond}_deltamas`] ?? (item.stock?.Deltamas?.[cond]) ?? 0);
        const current = (item[`stock_${cond}`] !== undefined)
            ? Number(item[`stock_${cond}`])
            : (cond === 'new' && item.available_stock !== undefined
                ? Number(item.available_stock)
                : ds8 + deltamas);
        const totalCombined = Number(item.current_stock ?? (item.physical_stock ?? (Number(item.locations?.DS8?.total ?? 0) + Number(item.locations?.Deltamas?.total ?? 0))));
        return { current, ds8, deltamas, totalCombined };
    };

    const getConditionStockStatus = (item, condition) => {
        if (!item) return { label: '—', tone: 'neutral' };
        const cond = String(condition || 'new').toLowerCase();
        const { current } = getItemConditionStock(item, cond);
        if (cond === 'new') {
            const min = Number(item.minimum_stock ?? 0);
            if (current <= 0) {
                return { label: 'Habis', tone: 'danger' };
            }
            if (current <= min) {
                return { label: 'Menipis', tone: 'warning' };
            }
            return { label: 'Aman', tone: 'success' };
        } else {
            if (current <= 0) {
                return { label: 'Kosong', tone: 'danger' };
            }
            return { label: 'Tersedia', tone: 'success' };
        }
    };

    const appendPreview = (target, item) => {
        target.replaceChildren();
        const wrapper = document.createElement('div');
        wrapper.className = 'warehouse-preview-content warehouse-selected-item-content';
        const stockCondition = target.matches('[data-warehouse-return-item-result]') ? 'used' : conditionInput.value.toLowerCase();
        const ds8Stock = item.locations?.DS8?.[stockCondition] ?? 0;
        const deltamasStock = item.locations?.Deltamas?.[stockCondition] ?? 0;
        wrapper.innerHTML = `<span class="warehouse-selected-item-image">${item.photo_url ? `<img src="${escapeHtml(item.photo_url)}" alt="" width="128" height="88">` : '<span aria-hidden="true">WH</span>'}</span><div class="warehouse-selected-item-copy"><strong class="warehouse-preview-title">${escapeHtml(item.item_name)}</strong><span class="warehouse-selected-item-code">${escapeHtml(item.item_code)}</span>${item.machine_type ? `<span class="warehouse-selected-item-machine">${escapeHtml(item.machine_type)}</span>` : ''}<dl class="warehouse-preview-facts"><div class="warehouse-preview-fact"><dt class="warehouse-preview-fact-label">DS8</dt><dd class="warehouse-preview-fact-value">${displayQuantity(ds8Stock)} ${escapeHtml(item.unit)}</dd></div><div class="warehouse-preview-fact"><dt class="warehouse-preview-fact-label">Deltamas</dt><dd class="warehouse-preview-fact-value">${displayQuantity(deltamasStock)} ${escapeHtml(item.unit)}</dd></div><div class="warehouse-preview-fact"><dt class="warehouse-preview-fact-label">Status stok</dt><dd class="warehouse-preview-fact-value" data-status></dd></div></dl></div>`;
        wrapper.querySelector('[data-status]').append(renderStockStatusBadge(getConditionStockStatus(item, stockCondition)));
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
            updateSummary();
        } else {
            state.item = item;
            itemInput.value = item.barcode || item.item_code;
            quantityInput.step = item.allow_fraction ? '0.001' : '1';
            quantityInput.min = quantityInput.step;
            appendPreview(form.querySelector('[data-warehouse-item-result]'), item);
            form.querySelector('[data-warehouse-next-detail]').disabled = false;
        }
        syncCatalogSelection(target);
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
            syncCatalogSelection(target);
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
                    card.dataset.warehouseCatalogItemKey = catalogItemKey(item);
                    card.dataset.itemId = String(item.id ?? '');
                    card.dataset.itemCode = String(item.item_code ?? '');
                    card.dataset.barcode = String(item.barcode ?? '');
                    card.setAttribute('aria-pressed', 'false');
                    card.innerHTML = `<span class="warehouse-catalog-image">${item.photo_url ? `<img src="${escapeHtml(item.photo_url)}" alt="" loading="lazy" width="320" height="220">` : '<span aria-hidden="true">WH</span>'}</span><span class="warehouse-catalog-copy"><strong>${escapeHtml(item.item_name)}</strong><small class="warehouse-catalog-code">${escapeHtml(item.item_code)}</small>${item.machine_type ? `<span class="warehouse-catalog-machine">${escapeHtml(item.machine_type)}</span>` : ''}<span class="warehouse-catalog-stock"><span><small>DS8</small><strong>${displayQuantity(item.locations.DS8[catalogCondition])} ${escapeHtml(item.unit)}</strong></span><span><small>Deltamas</small><strong>${displayQuantity(item.locations.Deltamas[catalogCondition])} ${escapeHtml(item.unit)}</strong></span></span></span>`;
                    card.setAttribute('aria-label', `Pilih ${item.item_name}`);
                    card.addEventListener('click', () => selectItem(item, target));
                    grid.append(card);
                });
                syncCatalogSelection(target);
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
        const projectionLocation = form.querySelector('[data-warehouse-projection-location]');
        if (projectionLocation) projectionLocation.textContent = locationInput.value || '—';
        form.querySelector('[data-warehouse-projection]').classList.toggle('is-invalid', after < 0);
    };

    const updateSummary = () => {
        if (!summaryPanel) return updateProjection();

        const itemElem = summaryPanel.querySelector('[data-warehouse-summary-item]');
        const itemCodeElem = summaryPanel.querySelector('[data-warehouse-summary-item-code]');
        const itemBarcodeElem = summaryPanel.querySelector('[data-warehouse-summary-item-barcode]');

        if (state.item) {
            itemElem.textContent = state.item.item_name || '—';
            itemCodeElem.textContent = state.item.item_code || '';
            
            // Only show barcode if present and different from item_code
            if (state.item.barcode && state.item.barcode !== state.item.item_code) {
                itemBarcodeElem.textContent = `Barcode: ${state.item.barcode}`;
                itemBarcodeElem.hidden = false;
            } else {
                itemBarcodeElem.textContent = '';
                itemBarcodeElem.hidden = true;
            }
        } else {
            itemElem.textContent = '—';
            itemCodeElem.textContent = '';
            itemBarcodeElem.textContent = '';
            itemBarcodeElem.hidden = true;
        }

        const condition = conditionInput.value;
        const conditionStocks = getItemConditionStock(state.item, condition);
        const conditionStockElem = summaryPanel.querySelector('[data-warehouse-summary-condition-stock]')
            || summaryPanel.querySelector('[data-warehouse-summary-current-stock]');
        const stockDetailElem = summaryPanel.querySelector('[data-warehouse-summary-stock-detail]');

        if (state.item && conditionStockElem) {
            conditionStockElem.textContent = `${displayQuantity(conditionStocks.current)} ${state.item.unit || ''}`.trim();
            if (stockDetailElem) {
                stockDetailElem.textContent = `DS8: ${displayQuantity(conditionStocks.ds8)} · Deltamas: ${displayQuantity(conditionStocks.deltamas)} (Total Semua: ${displayQuantity(conditionStocks.totalCombined)})`;
                stockDetailElem.hidden = false;
            }
        } else if (conditionStockElem) {
            conditionStockElem.textContent = '—';
            if (stockDetailElem) {
                stockDetailElem.textContent = '';
                stockDetailElem.hidden = true;
            }
        }

        const stockStatus = summaryPanel.querySelector('[data-warehouse-summary-stock-status]');
        if (stockStatus) {
            stockStatus.replaceChildren();
            if (state.item) stockStatus.append(renderStockStatusBadge(getConditionStockStatus(state.item, condition))); else stockStatus.textContent = '—';
        }
        summaryPanel.querySelector('[data-warehouse-summary-type]').textContent = transactionTypeLabels[typeInput.value] || '—';
        summaryPanel.querySelector('[data-warehouse-summary-location]').textContent = locationInput.value;
        summaryPanel.querySelector('[data-warehouse-summary-quantity]').textContent = displayQuantity(quantityInput.value);

        const returnWrap = summaryPanel.querySelector('[data-warehouse-summary-return-wrap]');
        if (returnWrap) {
            const isReturnActive = returnToggle && returnToggle.checked && typeInput.value === 'OUT';
            if (isReturnActive) {
                returnWrap.hidden = false;
                const returnItemElem = returnWrap.querySelector('[data-warehouse-summary-return-item]');
                const returnMetaElem = returnWrap.querySelector('[data-warehouse-summary-return-meta]');
                if (state.returnItem) {
                    returnItemElem.textContent = state.returnItem.item_name;
                    const retQty = displayQuantity(returnQuantity?.value || 0);
                    const retUnit = state.returnItem.unit || '';
                    const retLoc = returnLocation?.value || '—';
                    returnMetaElem.textContent = `${retQty} ${retUnit} ke ${retLoc}`.trim();
                    returnMetaElem.hidden = false;
                } else {
                    returnItemElem.textContent = 'Belum dipilih';
                    returnMetaElem.textContent = '';
                    returnMetaElem.hidden = true;
                }
            } else {
                returnWrap.hidden = true;
            }
        }

        const userElem = summaryPanel.querySelector('[data-warehouse-summary-user]');
        const userMetaElem = summaryPanel.querySelector('[data-warehouse-summary-user-meta]');
        if (state.verifier) {
            userElem.textContent = state.verifier.name;
            userMetaElem.textContent = `NPK ${state.verifier.npk} · ${state.verifier.section || '—'}`;
            userMetaElem.hidden = false;
        } else {
            userElem.textContent = '—';
            userMetaElem.textContent = '';
            userMetaElem.hidden = true;
        }

        updateProjection();
    };

    const setType = (type) => {
        const inbound = type === 'IN';
        typeInput.value = type;
        form.querySelectorAll('[data-warehouse-type]').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.warehouseType === type)));
        form.querySelector('[data-warehouse-type-caption]').textContent = type === 'IN' ? 'Penambahan stok' : 'Pengeluaran stok';
        if (sourceLocationWrap && sourceLocationInput) {
            sourceLocationWrap.hidden = !inbound;
            sourceLocationInput.disabled = !inbound;
            if (!inbound) sourceLocationInput.value = '';
        }
        syncMachineAllocationVisibility();
        syncWorkflowPresentation();
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
        updateSummary();
        syncVerifierAvailability();
    };

    const syncVerifierAvailability = () => {
        syncWorkflowPresentation();
        const isOut = typeInput.value === 'OUT';
        const returnComplete = !returnToggle?.checked || (state.returnItem && Number(returnQuantity.value) > 0 && returnLocation.value);
        let qtyValid = false;
        let isNegativeStock = false;
        let noMachineChecked = false;
        let invalidMachineQty = false;

        if (isOut) {
            const allocTotal = getMachineAllocationsTotal();
            const hasChecked = Array.from(machineChecks).some((c) => c.checked);
            const avail = available();
            isNegativeStock = allocTotal > 0 && (avail - allocTotal < 0);
            noMachineChecked = !hasChecked || allocTotal <= 0;
            qtyValid = hasChecked && allocTotal > 0 && !isNegativeStock;
            for (const check of machineChecks) {
                if (check.checked) {
                    const code = check.dataset.warehouseMachineCheck;
                    const input = form.querySelector(`[data-warehouse-machine-qty-input="${code}"]`);
                    const val = parseInt(input?.value || '0', 10);
                    if (!val || val < 1) {
                        invalidMachineQty = true;
                        qtyValid = false;
                        break;
                    }
                }
            }
        } else {
            const qty = Number(quantityInput.value || 0);
            qtyValid = qty > 0;
        }

        const valid = state.item && qtyValid && returnComplete;
        const requiresVerifier = !isPendingStockIn();
        userInput.disabled = !valid || !requiresVerifier;
        form.querySelector('[data-warehouse-scan-user]').disabled = !valid || !requiresVerifier;
        userInput.required = requiresVerifier;
        verifierPanel.classList.toggle('is-locked', !valid || !requiresVerifier);
        submitButton.disabled = !(valid && confirmInput.checked && (!requiresVerifier || state.verifier));

        if (lockMessage) {
            if (isNegativeStock) {
                const loc = locationInput.value || 'lokasi yang dipilih';
                const avail = available();
                const allocTotal = getMachineAllocationsTotal();
                lockMessage.textContent = `Stok di lokasi ${loc} tidak mencukupi (saat ini: ${displayQuantity(avail)} pcs, pengeluaran: ${displayQuantity(allocTotal)} pcs). Pengeluaran tidak boleh menyebabkan stok negatif.`;
            } else if (!returnComplete) {
                lockMessage.textContent = 'Silakan lengkapi data barang bekas yang dikembalikan terlebih dahulu.';
            } else if (invalidMachineQty) {
                lockMessage.textContent = 'Pastikan jumlah pengeluaran pada setiap mesin yang dicentang minimal 1 pcs.';
            } else if (noMachineChecked && isOut) {
                lockMessage.textContent = 'Silakan pilih mesin dan tentukan jumlah alokasi terlebih dahulu sebelum memilih karyawan.';
            } else {
                lockMessage.textContent = 'Silakan tentukan jumlah dan lokasi terlebih dahulu sebelum memilih karyawan.';
            }
        }
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
        if (step === 2) {
            syncMachineAllocationVisibility();
        }
        form.querySelectorAll('[data-warehouse-step]').forEach((section) => { section.hidden = Number(section.dataset.warehouseStep) !== step; });
        form.querySelectorAll('[data-warehouse-step-indicator]').forEach((indicator) => {
            const number = Number(indicator.dataset.warehouseStepIndicator);
            indicator.classList.toggle('is-active', number === step);
            indicator.classList.toggle('is-complete', number < step);
            const badge = indicator.querySelector('span');
            if (badge) {
                badge.textContent = number < step ? '✓' : String(number);
            }
            if (number === step) indicator.setAttribute('aria-current', 'step'); else indicator.removeAttribute('aria-current');
        });
        form.closest('.warehouse-transaction-layout').classList.toggle('is-receipt', step === 3);
        if (summaryPanel) summaryPanel.hidden = step === 3;
        
        // Smart focus
        if (step === 2) {
            const qtyInput = form.querySelector('input[name="quantity"]');
            if (qtyInput) {
                setTimeout(() => qtyInput.focus(), 80);
            }
        } else if (step === 1) {
            if (itemInput) {
                setTimeout(() => itemInput.focus(), 80);
            }
        } else {
            form.querySelector(`[data-warehouse-step="${step}"]`)?.querySelector('h2')?.focus?.();
        }
    };

    form.querySelectorAll('[data-warehouse-catalog="primary"]').forEach(initialiseCatalog);
    form.querySelector('[data-warehouse-scan-item]').addEventListener('click', () => scanItem('primary'));
    itemInput.addEventListener('input', () => {
        if (!state.item) return;
        state.item = null;
        syncCatalogSelection('primary');
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
        syncCatalogSelection('return');
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
    [quantityInput, locationInput, sourceLocationInput, form.querySelector('[data-warehouse-stock-in-notes]'), returnQuantity, returnLocation].filter(Boolean).forEach((field) => field.addEventListener('input', () => { invalidateApproval(true); updateSummary(); syncVerifierAvailability(); }));
    locationInput?.addEventListener('change', () => { invalidateApproval(true); updateSummary(); syncVerifierAvailability(); });
    sourceLocationInput?.addEventListener('change', () => { invalidateApproval(true); updateSummary(); syncVerifierAvailability(); });
    returnLocation?.addEventListener('change', () => { invalidateApproval(true); updateSummary(); syncVerifierAvailability(); });
    returnToggle?.addEventListener('change', () => { invalidateApproval(true); toggleReturn(); updateSummary(); });
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

    machineChecks.forEach((check) => {
        check.addEventListener('change', () => {
            const code = check.dataset.warehouseMachineCheck;
            const wrap = form.querySelector(`[data-warehouse-machine-qty-wrap="${code}"]`);
            const input = form.querySelector(`[data-warehouse-machine-qty-input="${code}"]`);
            const itemElem = form.querySelector(`[data-warehouse-machine-item="${code}"]`);
            if (check.checked) {
                if (wrap) wrap.hidden = false;
                if (input) {
                    input.disabled = false;
                    if (!input.value || parseInt(input.value, 10) < 1) {
                        input.value = '1';
                    }
                    setTimeout(() => input.focus(), 50);
                }
                itemElem?.classList.add('is-selected');
            } else {
                if (wrap) wrap.hidden = true;
                if (input) {
                    input.disabled = true;
                    input.value = '';
                }
                itemElem?.classList.remove('is-selected');
            }
            invalidateApproval(true);
            syncMachineAllocations();
        });
    });

    machineQtyInputs.forEach((input) => {
        input.addEventListener('input', () => {
            if (input.value && parseInt(input.value, 10) < 1) {
                input.value = '1';
            }
            invalidateApproval(true);
            syncMachineAllocations();
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (state.step !== 2 || submitButton.disabled || !form.reportValidity()) return;
        submitButton.disabled = true;
        submitButton.textContent = 'Menyimpan…';
        try {
            form.querySelector('[data-warehouse-submit-error]')?.remove();
            const formData = new FormData(form);
            const payload = {};
            const machineAllocations = [];
            for (const [key, val] of formData.entries()) {
                if (key.startsWith('machine_allocations[')) {
                    const match = key.match(/^machine_allocations\[([^\]]+)\]$/);
                    if (match && val !== '') {
                        machineAllocations.push({
                            machine_code: match[1],
                            quantity: parseInt(val, 10),
                        });
                    }
                } else {
                    payload[key] = val;
                }
            }
            if (typeInput.value === 'OUT' && machineAllocations.length > 0) {
                payload.machine_allocations = machineAllocations;
            }
            if (!returnToggle?.checked) delete payload.return_used;
            const response = await requestJson(form.action, payload);
            const receipt = form.querySelector('[data-warehouse-receipt-details]');
            if (response.pending_stock_in) {
                const receiptStep = form.querySelector('[data-warehouse-step="3"]');
                const receiptTitle = receiptStep.querySelector('h2');
                const receiptMessage = receiptStep.querySelector('p');
                receiptTitle.textContent = 'Stock In berhasil dicatat';
                receiptMessage.textContent = 'Status: Menunggu Validasi. Stok belum berubah sampai divalidasi.';
                let detailLink = receiptStep.querySelector('[data-warehouse-receipt-detail-link]');
                if (!detailLink) {
                    detailLink = document.createElement('a');
                    detailLink.className = 'btn btn-outline-primary';
                    detailLink.dataset.warehouseReceiptDetailLink = '';
                    detailLink.textContent = 'Lihat detail Stock In';
                    receiptStep.querySelector('.warehouse-step-actions')?.append(detailLink);
                }
                detailLink.href = response.data.detail_url || '#';
                detailLink.hidden = !response.data.detail_url;
                let validationLink = receiptStep.querySelector('[data-warehouse-validation-workspace-link]');
                if (!validationLink) {
                    validationLink = document.createElement('a');
                    validationLink.className = 'btn btn-outline-secondary';
                    validationLink.dataset.warehouseValidationWorkspaceLink = '';
                    validationLink.textContent = 'Buka Validasi Stok';
                    receiptStep.querySelector('.warehouse-step-actions')?.append(validationLink);
                }
                validationLink.href = form.dataset.validationWorkspaceUrl || '#';
                const facts = [['Nomor Stock In', response.data.stock_in_number], ['Status', 'Menunggu Validasi'], ['Barang', response.data.item], ['Jumlah Input', displayQuantity(response.data.quantity_expected)], ['Lokasi', response.data.destination_location], ['Sumber', response.data.source_location || 'Supplier / eksternal'], ['Stok', 'Belum berubah']];
                receipt.replaceChildren();
                facts.forEach(([label, value]) => { const dt = document.createElement('dt'); const dd = document.createElement('dd'); dt.textContent = label; dd.textContent = value ?? '—'; receipt.append(dt, dd); });
                updateStep(3);
                return;
            }
            const related = response.related_transactions?.length ? `${response.related_transactions.length} transaksi pengembalian terkait` : 'Tidak ada';
            const facts = [['Nomor transaksi', response.data.transaction_number], ['Tipe', transactionTypeLabels[response.data.transaction_type] || response.data.transaction_type], ['Kondisi', response.data.item_condition === 'USED' ? 'Bekas' : 'Baru'], ['Barang', response.data.item], ['Jumlah', displayQuantity(response.data.quantity)], ['Lokasi', response.data.display_location || '—'], ['Stok awal', displayQuantity(response.data.stock_before)], ['Stok akhir', displayQuantity(response.data.stock_after)], ['Karyawan', response.data.employee || response.data.verified_user_name], ['Transaksi terkait', related]];
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
