import * as pdfjsLib from 'pdfjs-dist/legacy/build/pdf';
import pdfWorkerUrl from 'pdfjs-dist/legacy/build/pdf.worker.min.js?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorkerUrl;

const SCALE_MIN = 0.5;
const SCALE_MAX = 3;
const SCALE_STEP = 0.25;

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
let triggeringElement = null;

function element(id) {
    return document.getElementById(id);
}

function actionButton(action) {
    return modalElement?.querySelector(`[data-km-viewer-action="${action}"]`) ?? null;
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
        setState('ready');
        updateControls();
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
    await cancelDocument();

    currentDocumentId = Number.parseInt(trigger.dataset.documentId, 10);
    currentPage = 1;
    totalPages = 0;
    scale = 1;
    fitMode = 'page';

    element('km-viewer-modal-title').textContent = trigger.dataset.title || 'Pratinjau Dokumen';

    const downloadLink = element('km-viewer-download-link');
    downloadLink.href = trigger.dataset.downloadUrl || '#';
    downloadLink.hidden = ! trigger.dataset.downloadUrl;
    downloadLink.target = '_blank';
    downloadLink.rel = 'noopener';

    const completeButton = element('km-viewer-complete');
    completeButton.hidden = trigger.dataset.canComplete !== 'true';
    completeButton.dataset.documentId = String(currentDocumentId);

    modal.show();
    updateControls();

    if (trigger.dataset.isPdf !== 'true') {
        setState('fallback', 'Preview belum tersedia untuk format ini. Gunakan tombol Unduh.');
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
        await renderPage(1, version);
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
        const button = event.target.closest('[data-km-viewer-action]');
        if (! button || button.disabled || ! pdfDocument) {
            return;
        }

        const action = button.dataset.kmViewerAction;
        if (action === 'previous' && currentPage > 1) {
            void renderPage(currentPage - 1, renderVersion);
        } else if (action === 'next' && currentPage < totalPages) {
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

    modalElement.addEventListener('hidden.bs.modal', () => {
        void cancelDocument();
        currentDocumentId = null;
        triggeringElement?.focus();
        triggeringElement = null;
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
