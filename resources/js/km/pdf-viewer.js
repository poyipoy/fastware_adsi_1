import * as pdfjsLib from 'pdfjs-dist/legacy/build/pdf';
import pdfWorkerUrl from 'pdfjs-dist/legacy/build/pdf.worker.min.js?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorkerUrl;

const SCALE_MIN = 0.5;
const SCALE_MAX = 3;
const SCALE_STEP = 0.25;
const ACTIVE_INACTIVITY_SECONDS = Math.max(
    1,
    Number(window.kmConfig?.reading?.inactiveTimeoutSeconds ?? 60),
);
const PROGRESS_FLUSH_SECONDS = Math.max(
    1,
    Number(window.kmConfig?.reading?.progressFlushSeconds ?? 12),
);
const READING_LEASE_SECONDS = 5;
const TAB_ID = globalThis.crypto?.randomUUID?.()
    ?? `km-tab-${Date.now()}-${Math.random().toString(16).slice(2)}`;

let modalElement = null;
let modal = null;
let pdfDocument = null;
let loadingTask = null;
let renderTask = null;
let currentPage = 1;
let totalPages = 0;
let scale = 1;
let fitMode = 'page';
let renderVersion = 0;
let currentDocumentId = null;
let currentDocumentVersionId = null;
let currentProgressUrl = null;
let triggeringElement = null;
let progressTimer = null;
let activeTimer = null;
let progressInFlight = false;
let activeSecondsStored = 0;
let activeSecondsPending = 0;
let uniquePagesCount = 0;
let progressPercent = 0;
let completionEligible = false;
let sessionActiveSeconds = 0;
let lastInteractionAt = Date.now();
let readingLeaseDocumentId = null;
const pendingPages = new Set();

function deviceToken() {
    const key = 'km-reading-device-token';
    try {
        let token = window.localStorage.getItem(key);
        if (!token) {
            token = globalThis.crypto?.randomUUID?.()
                ?? `km-device-${Date.now()}-${Math.random().toString(16).slice(2)}`;
            window.localStorage.setItem(key, token);
        }
        return token;
    } catch (_) {
        return 'storage-unavailable';
    }
}

function element(id) {
    return document.getElementById(id);
}

function actionButton(action) {
    return modalElement?.querySelector(`[data-km-viewer-action="${action}"]`) ?? null;
}

function csrfToken() {
    return window.kmConfig?.csrfToken
        ?? document.querySelector('meta[name="csrf-token"]')?.content
        ?? '';
}

function setState(state, message = '') {
    const loading = element('km-viewer-loading');
    const error = element('km-viewer-error');
    const fallback = element('km-viewer-fallback');
    const canvas = element('km-viewer-canvas');

    loading?.classList.toggle('d-none', state !== 'loading');
    error?.classList.toggle('d-none', state !== 'error');
    fallback?.classList.toggle('d-none', state !== 'fallback');
    if (canvas) {
        canvas.hidden = state !== 'ready';
    }

    if (state === 'error') {
        element('km-viewer-error-msg').textContent = message || 'Gagal memuat dokumen.';
    }
    if (state === 'fallback') {
        element('km-viewer-fallback-msg').textContent = message || 'Preview belum tersedia.';
    }
}

function updateControls() {
    element('km-viewer-page-info').textContent = totalPages > 0 ? `${currentPage} / ${totalPages}` : '- / -';
    element('km-viewer-zoom-label').textContent = `${Math.round(scale * 100)}%`;

    actionButton('previous').disabled = currentPage <= 1 || totalPages === 0;
    actionButton('next').disabled = currentPage >= totalPages || totalPages === 0;
    actionButton('zoom-out').disabled = scale <= SCALE_MIN || totalPages === 0;
    actionButton('zoom-in').disabled = scale >= SCALE_MAX || totalPages === 0;
    actionButton('fit-width').classList.toggle('active', fitMode === 'width');
    actionButton('fit-page').classList.toggle('active', fitMode === 'page');
}

function updateProgressUi(message = '') {
    const progress = modalElement?.querySelector('[data-km-viewer-progress]');
    const progressBar = progress?.querySelector('.progress-bar');
    const completionButton = element('km-viewer-complete');
    const isCompleted = triggeringElement?.dataset.completed === 'true';
    const isPdf = triggeringElement?.dataset.isPdf === 'true';
    const visibleSeconds = activeSecondsStored + activeSecondsPending;
    const requiredPages = totalPages > 0
        ? Math.ceil(
            Math.min(1, Math.max(0, Number(window.kmConfig?.reading?.uniquePageRatio ?? 0.9)))
                * totalPages,
        )
        : 0;
    const minimumSeconds = Math.max(
        0,
        Number(window.kmConfig?.reading?.minimumActiveSeconds ?? 60),
    );
    const secondsPerPage = Math.max(
        0,
        Number(window.kmConfig?.reading?.secondsPerPage ?? 20),
    );
    const maximumSeconds = Math.max(
        minimumSeconds,
        Number(window.kmConfig?.reading?.maximumRequiredSeconds ?? 900),
    );
    const requiredSeconds = totalPages > 0
        ? Math.max(minimumSeconds, Math.min(secondsPerPage * totalPages, maximumSeconds))
        : minimumSeconds;

    const progressLabel = modalElement?.querySelector('[data-km-viewer-progress-label]');
    const activeLabel = modalElement?.querySelector('[data-km-viewer-active-label]');
    if (progressLabel) {
        progressLabel.textContent = `Progres ${progressPercent}%`;
    }
    if (activeLabel) {
        activeLabel.textContent = `${visibleSeconds} detik aktif`;
    }
    if (progress) {
        progress.setAttribute('aria-valuenow', String(progressPercent));
    }
    if (progressBar) {
        progressBar.style.width = `${progressPercent}%`;
    }

    if (completionButton) {
        completionButton.hidden = isCompleted;
        completionButton.disabled = ! completionEligible;
        completionButton.setAttribute('aria-disabled', String(! completionEligible));
    }

    const hint = modalElement?.querySelector('[data-km-viewer-completion-hint]');
    if (! hint) {
        return;
    }
    if (message) {
        hint.textContent = message;
    } else if (isCompleted) {
        hint.textContent = 'Materi ini sudah selesai dibaca.';
    } else if (completionEligible) {
        hint.textContent = 'Syarat terpenuhi. Anda dapat menandai materi selesai.';
    } else if (isPdf && totalPages > 0) {
        const remainingPages = Math.max(0, requiredPages - uniquePagesCount);
        const remainingSeconds = Math.max(0, requiredSeconds - visibleSeconds);
        hint.textContent = `Buka ${remainingPages} halaman unik lagi dan baca aktif ${remainingSeconds} detik lagi.`;
    } else if (isPdf) {
        hint.textContent = 'Memuat syarat halaman dan waktu baca aktif...';
    } else {
        hint.textContent = 'Preview format ini belum tersedia dan unduhan dinonaktifkan.';
    }
}

function resetProgress(trigger) {
    pendingPages.clear();
    activeSecondsStored = Number.parseInt(trigger.dataset.activeSeconds ?? '0', 10) || 0;
    activeSecondsPending = 0;
    sessionActiveSeconds = 0;
    uniquePagesCount = Number.parseInt(trigger.dataset.uniquePagesCount ?? '0', 10) || 0;
    progressPercent = Number.parseInt(trigger.dataset.progressPercent ?? '0', 10) || 0;
    completionEligible = trigger.dataset.canComplete === 'true';
    currentProgressUrl = trigger.dataset.progressUrl || null;
    lastInteractionAt = Date.now();
    updateProgressUi();
}

function recordInteraction() {
    lastInteractionAt = Date.now();
}

function readingLeaseKey(documentId, documentVersionId = null) {
    return `km-reading-active:${documentId}:${documentVersionId ?? 'legacy'}`;
}

function releaseReadingLease() {
    if (readingLeaseDocumentId === null) {
        return;
    }

    try {
        const key = readingLeaseKey(readingLeaseDocumentId, currentDocumentVersionId);
        const lease = JSON.parse(window.localStorage.getItem(key) ?? 'null');
        if (lease?.owner === TAB_ID) {
            window.localStorage.removeItem(key);
        }
    } catch (_) {
        // Storage may be unavailable; the server-side delta cap remains active.
    }
    readingLeaseDocumentId = null;
}

function hasReadingLease() {
    if (currentDocumentId === null) {
        return false;
    }

    try {
        const key = readingLeaseKey(currentDocumentId, currentDocumentVersionId);
        const now = Date.now();
        const lease = JSON.parse(window.localStorage.getItem(key) ?? 'null');
        if (lease?.owner !== TAB_ID && Number(lease?.expiresAt ?? 0) > now) {
            return false;
        }

        window.localStorage.setItem(key, JSON.stringify({
            owner: TAB_ID,
            expiresAt: now + READING_LEASE_SECONDS * 1000,
        }));
        const confirmed = JSON.parse(window.localStorage.getItem(key) ?? 'null');
        if (confirmed?.owner !== TAB_ID) {
            return false;
        }

        readingLeaseDocumentId = currentDocumentId;

        return true;
    } catch (_) {
        return true;
    }
}

function isReadingActive() {
    return modalElement?.classList.contains('show')
        && pdfDocument !== null
        && document.visibilityState === 'visible'
        && Date.now() - lastInteractionAt <= ACTIVE_INACTIVITY_SECONDS * 1000
        && hasReadingLease();
}

function startProgressTracking() {
    stopProgressTracking();
    activeTimer = window.setInterval(() => {
        if (isReadingActive()) {
            activeSecondsPending += 1;
            sessionActiveSeconds += 1;
            updateProgressUi();
        }
    }, 1000);
    progressTimer = window.setInterval(() => {
        void flushProgress();
    }, PROGRESS_FLUSH_SECONDS * 1000);
}

function stopProgressTracking() {
    if (activeTimer !== null) {
        window.clearInterval(activeTimer);
        activeTimer = null;
    }
    if (progressTimer !== null) {
        window.clearInterval(progressTimer);
        progressTimer = null;
    }
}

async function parseJsonResponse(response) {
    const payload = await response.json().catch(() => ({}));
    if (! response.ok || payload.success === false) {
        const validationMessage = Object.values(payload.errors ?? {}).flat().find(Boolean);
        throw new Error(validationMessage ?? payload.message ?? 'Progres baca belum dapat disimpan.');
    }

    return payload;
}

async function flushProgress({ keepalive = false } = {}) {
    if (progressInFlight || ! currentProgressUrl || totalPages <= 0 || currentDocumentId === null) {
        return;
    }
    if (pendingPages.size === 0 && activeSecondsPending === 0) {
        return;
    }

    progressInFlight = true;
    const pageSnapshot = [...pendingPages].slice(0, 200);
    const activeSnapshot = activeSecondsPending;
    pageSnapshot.forEach((page) => pendingPages.delete(page));
    activeSecondsPending = Math.max(0, activeSecondsPending - activeSnapshot);

    try {
        const response = await fetch(currentProgressUrl, {
            method: 'PATCH',
            keepalive,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                id_km_pengajuan: currentDocumentId,
                document_version_id: currentDocumentVersionId,
                last_page: currentPage,
                pages_total: totalPages,
                pages: pageSnapshot.length > 0 ? pageSnapshot : [currentPage],
                active_delta: activeSnapshot,
                session_token: TAB_ID,
                device_token: deviceToken(),
                session_active_seconds: sessionActiveSeconds,
            }),
        });
        const payload = await parseJsonResponse(response);
        activeSecondsStored = Number(payload.active_seconds ?? activeSecondsStored + activeSnapshot);
        uniquePagesCount = Number(payload.unique_pages_count ?? uniquePagesCount);
        progressPercent = Number(payload.progress_percent ?? progressPercent);
        completionEligible = payload.completion_eligible === true;
        updateTriggerProgress(payload);
        updateProgressUi();
    } catch (error) {
        pageSnapshot.forEach((page) => pendingPages.add(page));
        activeSecondsPending += activeSnapshot;
        updateProgressUi(error.message ?? 'Progres baca belum dapat disimpan.');
    } finally {
        progressInFlight = false;
    }
}

async function finishProgressBeforeClose() {
    let attempts = 0;
    while (progressInFlight && attempts < 40) {
        await new Promise((resolve) => window.setTimeout(resolve, 50));
        attempts += 1;
    }
    await flushProgress({ keepalive: true });
}

function updateTriggerProgress(payload) {
    const versionSelector = currentDocumentVersionId === null
        ? ':not([data-document-version-id])'
        : `[data-document-version-id="${currentDocumentVersionId}"]`;
    document.querySelectorAll(
        `.km-open-document[data-document-id="${currentDocumentId}"]${versionSelector}`,
    ).forEach((trigger) => {
        trigger.dataset.resumePage = String(payload.last_page ?? currentPage);
        trigger.dataset.progressPercent = String(payload.progress_percent ?? progressPercent);
        trigger.dataset.activeSeconds = String(payload.active_seconds ?? activeSecondsStored);
        trigger.dataset.uniquePagesCount = String(payload.unique_pages_count ?? uniquePagesCount);
        trigger.dataset.canComplete = payload.completion_eligible === true ? 'true' : 'false';

        const card = trigger.closest('.km-document-card, .km-continue-card');
        const progress = card?.querySelector('[role="progressbar"]');
        const progressBar = progress?.querySelector('.progress-bar');
        const progressLabel = progress?.nextElementSibling;
        if (progress) {
            progress.setAttribute('aria-valuenow', String(progressPercent));
        }
        if (progressBar) {
            progressBar.style.width = `${progressPercent}%`;
        }
        if (progressLabel) {
            progressLabel.textContent = `${progressPercent}% halaman dibuka`;
        }
    });
}

async function cancelDocument() {
    renderVersion += 1;

    if (renderTask) {
        try {
            renderTask.cancel();
        } catch (_) {
            // The render may already be complete.
        }
        renderTask = null;
    }

    if (loadingTask) {
        try {
            await loadingTask.destroy();
        } catch (_) {
            // The loading task may already be complete.
        }
        loadingTask = null;
    }

    if (pdfDocument) {
        try {
            await pdfDocument.destroy();
        } catch (_) {
            // The document may already be destroyed.
        }
        pdfDocument = null;
    }
}

async function renderPage(pageNumber, version) {
    if (! pdfDocument || version !== renderVersion) {
        return;
    }

    if (renderTask) {
        try {
            renderTask.cancel();
        } catch (_) {
            // The previous render may already be complete.
        }
    }

    setState('loading');

    try {
        const page = await pdfDocument.getPage(pageNumber);
        if (version !== renderVersion) {
            return;
        }

        const container = element('km-viewer-container');
        const sourceCanvas = element('km-viewer-canvas');
        const baseViewport = page.getViewport({ scale: 1 });
        const availableWidth = Math.max((container?.clientWidth ?? window.innerWidth) - 32, 240);
        const availableHeight = Math.max((container?.clientHeight ?? window.innerHeight) - 32, 240);
        const automaticScale = fitMode === 'width'
            ? availableWidth / baseViewport.width
            : Math.min(availableWidth / baseViewport.width, availableHeight / baseViewport.height);
        const viewport = page.getViewport({ scale: automaticScale * scale });
        const nextCanvas = document.createElement('canvas');
        const context = nextCanvas.getContext('2d');

        if (! context) {
            throw new Error('Canvas 2D tidak tersedia.');
        }

        nextCanvas.id = 'km-viewer-canvas';
        nextCanvas.width = Math.ceil(viewport.width);
        nextCanvas.height = Math.ceil(viewport.height);
        nextCanvas.hidden = true;

        renderTask = page.render({ canvasContext: context, viewport });
        await renderTask.promise;

        if (version !== renderVersion) {
            return;
        }

        sourceCanvas.replaceWith(nextCanvas);
        currentPage = pageNumber;
        pendingPages.add(pageNumber);
        recordInteraction();
        setState('ready');
        updateControls();
        updateProgressUi();
    } catch (error) {
        if (error?.name === 'RenderingCancelledException' || version !== renderVersion) {
            return;
        }
        setState('error', 'Halaman PDF tidak dapat dirender.');
    } finally {
        renderTask = null;
    }
}

async function openDocument(trigger) {
    triggeringElement = trigger;
    releaseReadingLease();
    await cancelDocument();

    currentDocumentId = Number.parseInt(trigger.dataset.documentId, 10);
    currentDocumentVersionId = Number.parseInt(trigger.dataset.documentVersionId ?? '', 10) || null;
    currentPage = Math.max(1, Number.parseInt(trigger.dataset.resumePage ?? '1', 10) || 1);
    totalPages = 0;
    scale = 1;
    fitMode = 'page';
    resetProgress(trigger);

    element('km-viewer-modal-title').textContent = trigger.dataset.title || 'Pratinjau Dokumen';

    const completeButton = element('km-viewer-complete');
    completeButton.dataset.documentId = String(currentDocumentId);
    if (currentDocumentVersionId === null) {
        delete completeButton.dataset.documentVersionId;
    } else {
        completeButton.dataset.documentVersionId = String(currentDocumentVersionId);
    }

    modal.show();
    updateControls();

    if (trigger.dataset.isPdf !== 'true') {
        setState('fallback', 'Preview belum tersedia untuk format ini. Unduhan dinonaktifkan.');
        updateProgressUi();
        return;
    }

    setState('loading');
    const version = renderVersion;

    try {
        loadingTask = pdfjsLib.getDocument({
            url: trigger.dataset.previewUrl,
            withCredentials: true,
        });
        pdfDocument = await loadingTask.promise;
        loadingTask = null;

        if (version !== renderVersion) {
            return;
        }

        totalPages = pdfDocument.numPages;
        currentPage = Math.min(currentPage, totalPages);
        startProgressTracking();
        await renderPage(currentPage, version);
    } catch (error) {
        if (version !== renderVersion) {
            return;
        }
        setState('error', 'PDF tidak dapat dimuat atau akses Anda telah berakhir.');
    }
}

export function initPdfViewer() {
    modalElement = element('km-viewer-modal');
    if (! modalElement) {
        return;
    }

    modal = bootstrap.Modal.getOrCreateInstance(modalElement);

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.km-open-document');
        if (trigger && ! trigger.disabled) {
            void openDocument(trigger);
        }
    });

    modalElement.addEventListener('click', (event) => {
        recordInteraction();
        const button = event.target.closest('[data-km-viewer-action]');
        if (! button || button.disabled || ! pdfDocument) {
            return;
        }

        const action = button.dataset.kmViewerAction;
        if (action === 'previous' && currentPage > 1) {
            void flushProgress();
            void renderPage(currentPage - 1, renderVersion);
        } else if (action === 'next' && currentPage < totalPages) {
            void flushProgress();
            void renderPage(currentPage + 1, renderVersion);
        } else if (action === 'zoom-in' && scale < SCALE_MAX) {
            scale = Math.min(SCALE_MAX, scale + SCALE_STEP);
            void renderPage(currentPage, renderVersion);
        } else if (action === 'zoom-out' && scale > SCALE_MIN) {
            scale = Math.max(SCALE_MIN, scale - SCALE_STEP);
            void renderPage(currentPage, renderVersion);
        } else if (action === 'fit-width' || action === 'fit-page') {
            fitMode = action === 'fit-width' ? 'width' : 'page';
            scale = 1;
            void renderPage(currentPage, renderVersion);
        }
    });

    ['keydown', 'pointermove', 'wheel', 'touchstart'].forEach((eventName) => {
        modalElement.addEventListener(eventName, recordInteraction, { passive: true });
    });

    modalElement.addEventListener('hidden.bs.modal', async () => {
        stopProgressTracking();
        releaseReadingLease();
        await finishProgressBeforeClose();
        await cancelDocument();
        currentDocumentId = null;
        currentDocumentVersionId = null;
        currentProgressUrl = null;
        triggeringElement?.focus();
        triggeringElement = null;
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            releaseReadingLease();
            void flushProgress({ keepalive: true });
        } else {
            recordInteraction();
        }
    });

    window.addEventListener('pagehide', () => {
        releaseReadingLease();
        void flushProgress({ keepalive: true });
    });

    let resizeTimer = null;
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => {
            if (pdfDocument) {
                void renderPage(currentPage, renderVersion);
            }
        }, 150);
    });
}
