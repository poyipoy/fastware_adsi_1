const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

function cleanScan(value) {
    return String(value ?? '').replace(/[\r\n\t]+$/, '').trim();
}

function formatQuantity(value) {
    const text = String(value ?? '').trim();
    if (!text) return '0';
    if (!/^\d+(?:\.\d{1,3})?$/.test(text)) return text;
    const [whole, fraction = ''] = text.split('.');
    const normalizedWhole = whole.replace(/^0+(?=\d)/, '') || '0';
    const normalizedFraction = fraction.replace(/0+$/, '');
    return normalizedFraction ? `${normalizedWhole}.${normalizedFraction}` : normalizedWhole;
}

const stockStatusLabels = {
    HEALTHY: 'Aman',
    LOW: 'Menipis',
    OUT: 'Habis',
};

const stockStatusTones = {
    HEALTHY: 'success',
    LOW: 'warning',
    OUT: 'danger',
};

const transactionTypeLabels = {
    IN: 'Stock In',
    OUT: 'Stock Out',
    ADJUSTMENT: 'Penyesuaian',
    REVERSAL: 'Pembatalan',
};

const stockStatusPresentation = (value) => {
    const normalized = String(value ?? '').toUpperCase();

    return {
        label: stockStatusLabels[normalized] || 'Status belum tersedia',
        tone: stockStatusTones[normalized] || 'neutral',
    };
};

const displayStockStatus = (value) => stockStatusPresentation(value).label;
const displayTransactionType = (value) => transactionTypeLabels[String(value ?? '').toUpperCase()] || value || '—';

// Code 128 B symbols (bar/space widths) kept local so the summary does not
// need a package, CDN, canvas, or a server-side image endpoint.
const CODE128_PATTERNS = [
    '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312',
    '132212', '221213', '221312', '231212', '112232', '122132', '122231', '113222',
    '123122', '123221', '223211', '221132', '221231', '213212', '223112', '312131',
    '311222', '321122', '321221', '312212', '322112', '322211', '212123', '212321',
    '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
    '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121',
    '313121', '211331', '231131', '213113', '213311', '213131', '311123', '311321',
    '331121', '312113', '312311', '332111', '314111', '221411', '431111', '111224',
    '111422', '121124', '121421', '141122', '141221', '112214', '112412', '122114',
    '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
    '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112',
    '421211', '212141', '214121', '412121', '111143', '111341', '131141', '114113',
    '114311', '411113', '411311', '113141', '114131', '311141', '411131', '211412',
    '211214', '211232', '233111', '200000',
];

// The barcode on the summary is intentionally a visual aid, not the source
// of truth for scanning. Flex runs keep it visible in browsers that do not
// paint dynamically-created SVG children consistently.
function renderBarcodeVisual(container, value) {
    if (!container) return false;
    const barcode = String(value ?? '');
    if (!barcode) {
        container.hidden = true;
        container.replaceChildren();
        return false;
    }

    const characters = Array.from(barcode);
    const supported = characters.every((character) => {
        const code = character.charCodeAt(0);
        return code >= 32 && code <= 127;
    });
    const runs = [];
    const addRun = (bar, width) => {
        const normalizedWidth = Math.max(1, Number(width) || 1);
        const previous = runs[runs.length - 1];
        if (previous && previous.bar === bar) previous.width += normalizedWidth;
        else runs.push({ bar, width: normalizedWidth });
    };

    addRun(false, 8);
    if (supported) {
        const data = characters.map((character) => character.charCodeAt(0) - 32);
        const checksum = (104 + data.reduce((sum, code, index) => sum + (code * (index + 1)), 0)) % 103;
        [104, ...data, checksum, 106, 107].forEach((symbol) => {
            let isBar = true;
            Array.from(CODE128_PATTERNS[symbol], Number).forEach((width) => {
                addRun(isBar, width);
                isBar = !isBar;
            });
        });
    } else {
        // A deterministic stripe preview keeps unusual barcode characters
        // visible while the monospace value remains the authoritative text.
        characters.forEach((character, index) => {
            const bits = ((character.charCodeAt(0) + (index * 17)) % 256).toString(2).padStart(8, '0');
            Array.from(bits, (bit) => bit === '1').forEach((bar) => addRun(bar, 1));
        });
    }
    addRun(false, 8);

    container.replaceChildren();
    runs.forEach(({ bar, width }) => {
        const segment = document.createElement('span');
        segment.className = bar ? 'warehouse-summary-barcode-segment is-bar' : 'warehouse-summary-barcode-segment';
        segment.style.flex = `${width} 0 0`;
        container.appendChild(segment);
    });
    container.dataset.barcodeMode = supported ? 'code128-preview' : 'visual-fallback';
    container.hidden = false;
    return supported;
}

function requestJson(url, payload) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
    }).then(async (response) => {
        const body = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(body.message || 'Permintaan Warehouse gagal.');
            error.details = body.errors || {};
            error.status = response.status;
            throw error;
        }
        return body;
    });
}

function uuid() {
    return window.crypto?.randomUUID?.() || 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => {
        const random = Math.random() * 16 | 0;
        const value = character === 'x' ? random : (random & 0x3 | 0x8);
        return value.toString(16);
    });
}

function initWarehouseTransactionForm(form) {
    const transactionLayout = form.closest('.warehouse-transaction-layout') || form;
    const summaryRoot = transactionLayout;
    const itemInput = form.querySelector('[data-warehouse-item-input]');
    const userInput = form.querySelector('[data-warehouse-user-input]');
    const typeInput = form.querySelector('[data-warehouse-type-value]');
    const quantityInput = form.querySelector('[data-warehouse-quantity]');
    const storageLocationInput = form.querySelector('[data-warehouse-storage-location]');
    const storageLocationField = form.querySelector('[data-warehouse-storage-location-field]');
    const transactionFields = form.querySelector('.warehouse-transaction-fields');
    const itemSummary = form.querySelector('[data-warehouse-item-summary]');
    const userSummary = form.querySelector('[data-warehouse-user-summary]');
    const projection = form.querySelector('[data-warehouse-projection]');
    const submitButton = form.querySelector('[data-warehouse-submit]');
    const submitError = form.querySelector('[data-warehouse-submit-error]');
    const warning = form.querySelector('[data-warehouse-type-warning]');
    const verifierPanel = form.querySelector('[data-warehouse-verifier-panel]');
    const verifierButton = form.querySelector('[data-warehouse-scan-user]');
    const verifierLockMessage = form.querySelector('[data-warehouse-verifier-lock-message]');
    const confirmationCheck = form.querySelector('[data-warehouse-confirm-check]');
    const requirements = JSON.parse(form.dataset.warehouseRequirements || '{}');
    const recent = new Map();
    const steps = [...form.querySelectorAll('[data-warehouse-step]')];
    const indicators = [...form.querySelectorAll('[data-warehouse-step-indicator]')];
    const typeButtons = [...form.querySelectorAll('[data-warehouse-type]')];
    let item = null;
    let verified = null;
    let currentStep = 1;
    let pendingType = null;
    let submitted = false;

    const text = (selector, value) => {
        const element = summaryRoot.querySelector(selector);
        if (element) element.textContent = value == null ? '' : String(value);
        return element;
    };

    const duplicate = (key) => {
        const now = Date.now();
        const previous = recent.get(key) || 0;
        recent.set(key, now);
        return now - previous < Number(document.body.dataset.warehouseDuplicateWindow || 1500);
    };

    const setError = (selector, message) => text(selector, message || '');

    const setBusy = (button, spinnerSelector, labelSelector, busy) => {
        if (!button) return;
        button.disabled = busy;
        const spinner = form.querySelector(spinnerSelector);
        const label = form.querySelector(labelSelector);
        if (spinner) spinner.hidden = !busy;
        if (label && busy) label.dataset.previousLabel = label.textContent;
        if (label && !busy && label.dataset.previousLabel) {
            label.textContent = label.dataset.previousLabel;
            delete label.dataset.previousLabel;
        }
    };

    const clearElement = (element) => {
        if (element) element.replaceChildren();
    };

    const appendLine = (parent, tag, value, className = '') => {
        const element = document.createElement(tag);
        element.textContent = value;
        if (className) element.className = className;
        parent.appendChild(element);
        return element;
    };

    const appendPreview = (parent, title, facts, variant = '') => {
        const content = document.createElement('div');
        content.className = `warehouse-preview-content${variant ? ` ${variant}` : ''}`;
        appendLine(content, 'strong', title, 'warehouse-preview-title');

        const factList = document.createElement('dl');
        factList.className = 'warehouse-preview-facts';
        facts.forEach(([label, value, tone = null]) => {
            const fact = document.createElement('div');
            fact.className = 'warehouse-preview-fact';
            appendLine(fact, 'dt', label, 'warehouse-preview-fact-label');
            const valueElement = document.createElement('dd');
            valueElement.className = 'warehouse-preview-fact-value';
            if (tone) {
                const badge = document.createElement('span');
                badge.className = `warehouse-status-badge warehouse-status-badge-${tone}`;
                badge.textContent = value;
                valueElement.appendChild(badge);
            } else {
                valueElement.textContent = value;
            }
            fact.appendChild(valueElement);
            factList.appendChild(fact);
        });

        content.appendChild(factList);
        parent.appendChild(content);
        return content;
    };

    const summaryEmpty = '\u2014';

    const renderStockStatusBadge = (container, value) => {
        if (!container) return;
        const presentation = stockStatusPresentation(value);
        const badge = document.createElement('span');
        badge.className = `warehouse-status-badge warehouse-status-badge-${presentation.tone}`;
        badge.textContent = presentation.label;
        container.replaceChildren(badge);
        container.hidden = false;
    };

    const currentLocation = () => typeInput.value === 'IN'
        ? cleanScan(storageLocationInput?.value)
        : cleanScan(item?.storage_location);

    const renderLocationSummary = () => {
        text('[data-warehouse-summary-location]', currentLocation() || 'Belum diatur');
    };

    const getProjection = () => {
        const quantity = Number(quantityInput?.value);
        const before = Number(item?.current_stock);
        if (!item || !Number.isFinite(quantity) || quantity <= 0 || !Number.isFinite(before)) return null;
        const after = typeInput.value === 'OUT' ? before - quantity : before + quantity;
        return { quantity, before, after };
    };

    const renderSummary = () => {
        const isIn = typeInput.value === 'IN';
        text('[data-warehouse-summary-type]', isIn ? 'Stock In' : 'Stock Out');

        const itemCode = summaryRoot.querySelector('[data-warehouse-summary-item-code]');
        const itemCategory = summaryRoot.querySelector('[data-warehouse-summary-item-category]');
        const itemBarcode = summaryRoot.querySelector('[data-warehouse-summary-item-barcode]');
        const barcodeWrap = summaryRoot.querySelector('[data-warehouse-summary-item-barcode-wrap]');
        const barcodeVisual = summaryRoot.querySelector('[data-warehouse-summary-item-barcode-visual]');
        const barcodeSvg = summaryRoot.querySelector('[data-warehouse-summary-item-barcode-svg]');
        const barcodeFallback = summaryRoot.querySelector('[data-warehouse-summary-item-barcode-fallback]');
        const currentStock = summaryRoot.querySelector('[data-warehouse-summary-current-stock]');
        const stockStatus = summaryRoot.querySelector('[data-warehouse-summary-stock-status]');
        const userMeta = summaryRoot.querySelector('[data-warehouse-summary-user-meta]');
        const projectedStock = summaryRoot.querySelector('[data-warehouse-summary-stock]');

        if (!item) {
            text('[data-warehouse-summary-item]', 'Belum dipilih');
            [itemCode, itemCategory, itemBarcode, barcodeWrap, barcodeVisual, stockStatus].forEach((element) => { if (element) element.hidden = true; });
            if (stockStatus) stockStatus.replaceChildren();
            if (currentStock) { currentStock.hidden = false; currentStock.textContent = summaryEmpty; }
            if (barcodeSvg) { barcodeSvg.hidden = true; barcodeSvg.replaceChildren(); }
            if (barcodeFallback) barcodeFallback.hidden = true;
            text('[data-warehouse-summary-quantity]', summaryEmpty);
            text('[data-warehouse-summary-stock]', summaryEmpty);
            if (projectedStock) projectedStock.classList.remove('is-invalid');
        } else {
            const barcode = String(item.barcode ?? '');
            text('[data-warehouse-summary-item]', item.item_name || 'Barang tanpa nama');
            if (itemCode) { itemCode.hidden = !item.item_code; itemCode.textContent = item.item_code ? `Item Code ${item.item_code}` : ''; }
            if (itemCategory) { itemCategory.hidden = !item.category; itemCategory.textContent = item.category || ''; }
            if (itemBarcode) { itemBarcode.hidden = !barcode; itemBarcode.textContent = barcode ? `Barcode ${barcode}` : ''; }
            if (barcodeWrap) barcodeWrap.hidden = !barcode;
            const barcodeSupported = renderBarcodeVisual(barcodeVisual, barcode);
            if (barcodeSvg) { barcodeSvg.hidden = true; barcodeSvg.replaceChildren(); }
            if (barcodeFallback) {
                barcodeFallback.hidden = !barcode || barcodeSupported;
                barcodeFallback.textContent = barcodeSupported ? '' : 'Barcode ditampilkan sebagai teks karena karakter tidak mendukung Code 128.';
            }
            if (currentStock) { currentStock.hidden = false; currentStock.textContent = `${formatQuantity(item.current_stock)} ${item.unit || ''}`.trim(); }
            renderStockStatusBadge(stockStatus, item.stock_status);

            const projectionData = getProjection();
            text('[data-warehouse-summary-quantity]', projectionData ? `${formatQuantity(projectionData.quantity)} ${item.unit || ''}`.trim() : summaryEmpty);
            if (projectedStock) {
                projectedStock.classList.toggle('is-invalid', Boolean(projectionData && projectionData.after < 0));
                projectedStock.textContent = projectionData
                    ? `${formatQuantity(projectionData.after.toFixed(3))} ${item.unit || ''}${projectionData.after < 0 ? ' · stok tidak cukup' : ''}`.trim()
                    : summaryEmpty;
            }
        }

        renderLocationSummary();
        text('[data-warehouse-summary-user]', verified?.name || 'Belum diverifikasi');
        if (userMeta) {
            userMeta.hidden = !verified;
            userMeta.textContent = verified ? `NPK ${verified.npk || summaryEmpty} · Bagian ${verified.section || summaryEmpty}` : '';
        }
    };

    const renderItem = () => {
        clearElement(itemSummary);
        if (!item) {
            itemSummary.classList.remove('is-valid');
            appendLine(itemSummary, 'span', 'Barang belum dipilih.', 'warehouse-muted');
            if (storageLocationInput) storageLocationInput.value = '';
            form.querySelector('[data-warehouse-next-item]').disabled = true;
            renderSummary();
            return;
        }
        itemSummary.classList.add('is-valid');
        const stockStatus = stockStatusPresentation(item.stock_status);
        appendPreview(itemSummary, item.item_name || 'Barang tanpa nama', [
            ['Item Code', item.item_code || summaryEmpty],
            ['Stok saat ini', `${formatQuantity(item.current_stock)} ${item.unit || ''}`.trim() || summaryEmpty],
            ['Status stok', stockStatus.label, stockStatus.tone],
            ['Lokasi', item.storage_location || 'Belum diatur'],
        ]);
        if (typeInput.value === 'IN' && storageLocationInput) storageLocationInput.value = item.storage_location || '';
        quantityInput.step = item.allow_fraction ? '0.001' : '1';
        quantityInput.min = item.allow_fraction ? '0.001' : '1';
        form.querySelector('[data-warehouse-next-item]').disabled = false;
        renderSummary();
    };

    const renderUser = () => {
        clearElement(userSummary);
        if (!verified) {
            userSummary.classList.remove('is-valid');
            appendLine(userSummary, 'span', 'Karyawan belum diverifikasi.', 'warehouse-muted');
            if (confirmationCheck) { confirmationCheck.checked = false; confirmationCheck.disabled = true; }
            renderSummary();
            return;
        }
        userSummary.classList.add('is-valid');
        appendPreview(userSummary, verified.name || 'Karyawan tanpa nama', [
            ['NPK', verified.npk || summaryEmpty],
            ['Bagian', verified.section || summaryEmpty],
        ]);
        if (confirmationCheck) confirmationCheck.disabled = false;
        renderSummary();
    };

    const renderProjection = () => {
        clearElement(projection);
        projection.classList.remove('is-invalid');
        const projectionData = getProjection();
        if (!projectionData) {
            appendLine(projection, 'span', 'Perkiraan stok akan tampil setelah jumlah valid.', 'warehouse-muted');
            return;
        }
        const direction = typeInput.value === 'OUT' ? '-' : '+';
        const cells = [
            ['Stok sebelum', `${formatQuantity(projectionData.before.toFixed(3))} ${item.unit}`],
            ['Transaksi', `${direction}${formatQuantity(projectionData.quantity)} ${item.unit}`],
            ['Perkiraan stok', `${formatQuantity(projectionData.after.toFixed(3))} ${item.unit}`],
        ];
        cells.forEach(([label, value]) => {
            const cell = document.createElement('div');
            appendLine(cell, 'span', label, 'projection-label');
            appendLine(cell, 'strong', value, 'projection-value');
            projection.appendChild(cell);
        });
        if (projectionData.after < 0) projection.classList.add('is-invalid');
    };

    const renderConfirmationSummary = () => {
        const summary = form.querySelector('[data-warehouse-confirmation-summary]');
        clearElement(summary);
        if (!item || !verified || !quantityInput.value) {
            appendLine(summary, 'span', 'Ringkasan akan tampil setelah barang dan karyawan verifikator valid.', 'warehouse-muted');
            return;
        }
        const projectionData = getProjection();
        const stockBefore = projectionData
            ? `${formatQuantity(projectionData.before.toFixed(3))} ${item.unit || ''}`.trim()
            : `${formatQuantity(item.current_stock)} ${item.unit || ''}`.trim();
        const stockAfter = projectionData
            ? `${formatQuantity(projectionData.after.toFixed(3))} ${item.unit || ''}`.trim()
            : summaryEmpty;
        appendPreview(summary, 'Ringkasan transaksi', [
            ['Tipe', typeInput.value === 'IN' ? 'Stock In' : 'Stock Out'],
            ['Barang', item.item_name || 'Barang tanpa nama'],
            ['Jumlah', `${formatQuantity(quantityInput.value)} ${item.unit || ''}`.trim() || summaryEmpty],
            ['Lokasi', currentLocation() || 'Belum diatur'],
            ['Stok', `${stockBefore} → ${stockAfter}`],
            ['Karyawan verifikator', verified.name || 'Karyawan tanpa nama'],
            ['NPK', verified.npk || 'NPK tidak tersedia'],
            ['Bagian', verified.section || 'Bagian tidak tersedia'],
        ], 'warehouse-confirmation-preview');
    };

    const detailState = () => {
        const quantity = Number(quantityInput?.value);
        if (!item) return { valid: false, field: 'item', message: 'Pindai barang terlebih dahulu.' };
        if (!Number.isFinite(quantity) || quantity <= 0) return { valid: false, field: 'quantity', message: 'Isi jumlah lebih besar dari nol.' };
        if (!item.allow_fraction && !Number.isInteger(quantity)) return { valid: false, field: 'quantity', message: 'Barang ini hanya menerima jumlah bilangan bulat.' };
        const projected = getProjection();
        if (typeInput.value === 'OUT' && projected && projected.after < 0) return { valid: false, field: 'quantity', message: 'Jumlah melebihi stok tersedia.' };
        if (typeInput.value === 'IN' && requirements.storageLocationForIn && !cleanScan(storageLocationInput?.value)) {
            return { valid: false, field: 'storage_location', message: 'Isi lokasi penyimpanan Stock In.' };
        }
        return { valid: true, field: null, message: '' };
    };

    const syncVerifierAvailability = () => {
        const state = detailState();
        const locked = !state.valid;
        if (userInput) userInput.disabled = locked;
        if (verifierButton) verifierButton.disabled = locked;
        verifierPanel?.classList.toggle('is-locked', locked);
        verifierPanel?.setAttribute('aria-disabled', locked ? 'true' : 'false');
        if (verifierLockMessage) verifierLockMessage.textContent = state.message;

        if (locked && (verified || userInput?.value)) {
            verified = null;
            if (userInput) userInput.value = '';
            if (confirmationCheck) confirmationCheck.checked = false;
            renderUser();
            renderConfirmationSummary();
        }
        if (confirmationCheck) confirmationCheck.disabled = locked || !verified;
        return state;
    };

    const updateTypeLabels = () => {
        const isIn = typeInput.value === 'IN';
        text('[data-warehouse-type-caption]', isIn ? 'Penambahan stok' : 'Pengeluaran stok');
        text('[data-warehouse-submit-label]', `Konfirmasi Stock ${isIn ? 'In' : 'Out'}`);
        const locationRequired = isIn && requirements.storageLocationForIn;
        if (storageLocationInput) {
            storageLocationInput.required = locationRequired;
            storageLocationInput.disabled = !isIn;
        }
        if (storageLocationField) storageLocationField.hidden = !isIn;
        transactionFields?.classList.toggle('is-single-field', !isIn);
        text('[data-warehouse-storage-location-required]', locationRequired ? '*' : '');
        renderLocationSummary();
        typeButtons.forEach((button) => button.setAttribute('aria-pressed', button.dataset.warehouseType === typeInput.value ? 'true' : 'false'));
        renderProjection();
        renderSummary();
        renderConfirmationSummary();
        syncVerifierAvailability();
    };

    const updateStep = (nextStep, focusSelector = null) => {
        currentStep = nextStep;
        steps.forEach((step) => {
            const active = Number(step.dataset.warehouseStep) === currentStep;
            step.hidden = !active;
            step.classList.toggle('is-active', active);
        });
        indicators.forEach((indicator) => {
            const number = Number(indicator.dataset.warehouseStepIndicator);
            indicator.classList.toggle('is-active', number === currentStep);
            indicator.classList.toggle('is-complete', number < currentStep);
        });
        if (focusSelector) window.setTimeout(() => form.querySelector(focusSelector)?.focus(), 0);
    };

    const resetDownstream = () => {
        item = null;
        verified = null;
        itemInput.value = '';
        userInput.value = '';
        quantityInput.value = '';
        if (storageLocationInput) storageLocationInput.value = '';
        if (confirmationCheck) confirmationCheck.checked = false;
        setError('[data-warehouse-item-error]');
        setError('[data-warehouse-user-error]');
        setError('[data-warehouse-submit-error]');
        renderItem();
        renderUser();
        renderProjection();
        renderSummary();
        renderConfirmationSummary();
        syncVerifierAvailability();
        updateStep(1, '[data-warehouse-item-input]');
    };

    const applyType = (nextType) => {
        pendingType = null;
        warning.hidden = true;
        typeInput.value = nextType;
        resetDownstream();
        updateTypeLabels();
    };

    const hasDownstreamData = () => Boolean(item || verified || quantityInput.value || storageLocationInput?.value);

    const lookupItem = (force = false) => {
        const code = cleanScan(itemInput.value);
        itemInput.value = code;
        if (!code || (!force && duplicate(`item:${code}`))) return;
        setError('[data-warehouse-item-error]');
        setBusy(form.querySelector('[data-warehouse-scan-item]'), '[data-warehouse-spinner]', '[data-warehouse-lookup-label]', true);
        requestJson('/warehouse/scans/item', { code })
            .then((body) => { item = body.data; renderItem(); renderProjection(); renderSummary(); renderConfirmationSummary(); syncVerifierAvailability(); })
            .catch((error) => { item = null; renderItem(); renderProjection(); renderSummary(); syncVerifierAvailability(); setError('[data-warehouse-item-error]', error.message); itemInput.focus(); })
            .finally(() => setBusy(form.querySelector('[data-warehouse-scan-item]'), '[data-warehouse-spinner]', '[data-warehouse-lookup-label]', false));
    };

    const lookupUser = (force = false) => {
        const state = syncVerifierAvailability();
        if (!state.valid) {
            if (state.field === 'storage_location') storageLocationInput?.focus();
            else quantityInput?.focus();
            return;
        }
        const code = cleanScan(userInput.value);
        userInput.value = code;
        if (!code || (!force && duplicate(`user:${code}`))) return;
        setError('[data-warehouse-user-error]');
        setBusy(form.querySelector('[data-warehouse-scan-user]'), '[data-warehouse-user-spinner]', '[data-warehouse-user-lookup-label]', true);
        requestJson('/warehouse/scans/user', { code, type: typeInput.value })
            .then((body) => { verified = body.data; renderUser(); renderSummary(); renderConfirmationSummary(); syncVerifierAvailability(); })
            .catch((error) => { verified = null; renderUser(); renderSummary(); syncVerifierAvailability(); setError('[data-warehouse-user-error]', error.message); userInput.focus(); })
            .finally(() => { setBusy(verifierButton, '[data-warehouse-user-spinner]', '[data-warehouse-user-lookup-label]', false); syncVerifierAvailability(); });
    };

    const validateDetail = () => {
        setError('[data-warehouse-quantity-error]');
        setError('[data-warehouse-storage-location-error]');
        const state = detailState();
        if (state.field === 'item') {
            setError('[data-warehouse-item-error]', 'Barcode barang harus diverifikasi terlebih dahulu.');
            updateStep(1, '[data-warehouse-item-input]');
        } else if (state.field === 'quantity') {
            setError('[data-warehouse-quantity-error]', state.message);
            quantityInput?.focus();
        } else if (state.field === 'storage_location') {
            setError('[data-warehouse-storage-location-error]', 'Lokasi penyimpanan wajib untuk Stock In.');
            storageLocationInput?.focus();
        }
        renderProjection();
        renderSummary();
        syncVerifierAvailability();
        return state.valid;
    };

    const renderReceipt = (receipt) => {
        const details = form.querySelector('[data-warehouse-receipt-details]');
        clearElement(details);
        const rows = [
            ['Nomor transaksi', receipt.transaction_number],
            ['Tipe', displayTransactionType(receipt.transaction_type || typeInput.value)],
            ['Barang', receipt.item],
            ['Jumlah', formatQuantity(receipt.quantity)],
            ['Lokasi penyimpanan', receipt.storage_location || '—'],
            ['Stok sebelum → sesudah', `${formatQuantity(receipt.stock_before)} → ${formatQuantity(receipt.stock_after)}`],
            ['Karyawan verifikator', `${receipt.verified_user_name || '—'} (${receipt.verified_user_section || '—'})`],
            ['Waktu transaksi', receipt.transaction_at ? new Date(receipt.transaction_at).toLocaleString('id-ID') : '—'],
        ];
        rows.forEach(([label, value]) => { appendLine(details, 'dt', label); appendLine(details, 'dd', value, label === 'Nomor transaksi' ? 'font-monospace' : ''); });
        form.classList.add('is-complete');
        const layout = form.closest('.warehouse-transaction-layout');
        layout?.classList.add('is-receipt');
        const summary = layout?.querySelector('[data-warehouse-summary]');
        if (summary) summary.hidden = true;
        updateStep(3);
    };

    typeButtons.forEach((button) => button.addEventListener('click', () => {
        const nextType = button.dataset.warehouseType;
        if (nextType === typeInput.value) return;
        if (hasDownstreamData()) {
            pendingType = nextType;
            warning.hidden = false;
            form.querySelector('[data-warehouse-type-confirm]')?.focus();
            return;
        }
        applyType(nextType);
    }));
    form.querySelector('[data-warehouse-type-confirm]')?.addEventListener('click', () => { if (pendingType) applyType(pendingType); });
    form.querySelector('[data-warehouse-type-cancel]')?.addEventListener('click', () => { pendingType = null; warning.hidden = true; });
    form.querySelector('[data-warehouse-scan-item]')?.addEventListener('click', () => lookupItem(true));
    verifierButton?.addEventListener('click', () => lookupUser(true));
    itemInput?.addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); lookupItem(); } });
    userInput?.addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); lookupUser(); } });
    quantityInput?.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        const state = syncVerifierAvailability();
        if (state.field === 'quantity') { validateDetail(); return; }
        if (state.field === 'storage_location') { storageLocationInput?.focus(); return; }
        if (state.valid) userInput?.focus();
    });
    storageLocationInput?.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        const state = syncVerifierAvailability();
        if (state.valid) userInput?.focus();
        else validateDetail();
    });
    itemInput?.addEventListener('input', () => {
        if (!item) return;
        item = null;
        verified = null;
        userInput.value = '';
        quantityInput.value = '';
        if (storageLocationInput) storageLocationInput.value = '';
        if (confirmationCheck) confirmationCheck.checked = false;
        renderItem();
        renderUser();
        renderProjection();
        renderConfirmationSummary();
        syncVerifierAvailability();
        if (currentStep > 1) updateStep(1);
    });
    userInput?.addEventListener('input', () => {
        if (!verified) return;
        verified = null;
        if (confirmationCheck) confirmationCheck.checked = false;
        renderUser();
        renderSummary();
        renderConfirmationSummary();
        syncVerifierAvailability();
    });
    itemInput?.addEventListener('blur', () => { if (cleanScan(itemInput.value) && !item) lookupItem(); });
    userInput?.addEventListener('blur', () => { if (cleanScan(userInput.value) && !verified) lookupUser(); });
    quantityInput?.addEventListener('input', () => {
        if (confirmationCheck) confirmationCheck.checked = false;
        renderProjection();
        renderSummary();
        syncVerifierAvailability();
        renderConfirmationSummary();
    });
    const handleStorageLocationChange = () => {
        if (confirmationCheck) confirmationCheck.checked = false;
        renderLocationSummary();
        renderSummary();
        syncVerifierAvailability();
        renderConfirmationSummary();
    };
    storageLocationInput?.addEventListener('input', handleStorageLocationChange);
    storageLocationInput?.addEventListener('change', handleStorageLocationChange);
    form.querySelectorAll('[data-warehouse-quantity-step]').forEach((button) => button.addEventListener('click', () => {
        const step = item?.allow_fraction ? .001 : 1;
        const next = Math.max(Number(quantityInput.min || step), (Number(quantityInput.value) || 0) + Number(button.dataset.warehouseQuantityStep) * step);
        quantityInput.value = item?.allow_fraction ? next.toFixed(3) : String(Math.round(next));
        quantityInput.dispatchEvent(new Event('input', { bubbles: true }));
    }));
    form.querySelector('[data-warehouse-next-item]')?.addEventListener('click', () => {
        if (!item) { lookupItem(); return; }
        updateStep(2, '[data-warehouse-quantity]');
    });
    form.querySelectorAll('[data-warehouse-back-step]').forEach((button) => button.addEventListener('click', () => updateStep(Number(button.dataset.warehouseBackStep))));

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (submitted || currentStep !== 2) return;
        setError('[data-warehouse-submit-error]');
        if (!validateDetail()) return;
        if (!verified) { setError('[data-warehouse-submit-error]', 'Barcode NPK karyawan wajib diverifikasi terlebih dahulu.'); userInput.focus(); return; }
        if (!confirmationCheck?.checked) { setError('[data-warehouse-submit-error]', 'Centang konfirmasi eksplisit sebelum menyimpan transaksi.'); confirmationCheck?.focus(); return; }
        submitted = true;
        form.querySelector('[data-warehouse-idempotency-key]').value = uuid();
        setBusy(submitButton, '[data-warehouse-submit-spinner]', '[data-warehouse-submit-label]', true);
        const payload = Object.fromEntries(new FormData(form).entries());
        requestJson(form.action, payload)
            .then((body) => renderReceipt(body.data || {}))
            .catch((error) => {
                submitted = false;
                setError('[data-warehouse-submit-error]', error.message);
                let firstErrorSelector = null;
                Object.entries(error.details || {}).forEach(([key, messages]) => {
                    const message = Array.isArray(messages) ? messages[0] : messages;
                    const selector = ({ quantity: '[data-warehouse-quantity-error]', storage_location: '[data-warehouse-storage-location-error]' })[key];
                    if (selector) {
                        setError(selector, message);
                        firstErrorSelector ||= selector.replace('-error', '');
                    }
                });
                const focusTarget = firstErrorSelector || (error.message.toLowerCase().includes('karyawan') ? '[data-warehouse-user-input]' : '[data-warehouse-item-input]');
                window.setTimeout(() => form.querySelector(focusTarget)?.focus(), 0);
                setBusy(submitButton, '[data-warehouse-submit-spinner]', '[data-warehouse-submit-label]', false);
            });
    });

    typeInput.value = form.dataset.warehouseInitialType || typeInput.value;
    updateTypeLabels();
    renderItem();
    renderUser();
    renderProjection();
    renderSummary();
    syncVerifierAvailability();
    if (form.dataset.warehouseInitialBarcode) window.setTimeout(lookupItem, 0);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-warehouse-transaction-form]').forEach(initWarehouseTransactionForm);
});
