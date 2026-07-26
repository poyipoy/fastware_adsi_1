import { initBookmarkButtons } from './bookmarks.js';
import { initPdfViewer } from './pdf-viewer.js';
import { confirmAction, initSubmitProtection, notify } from './ui-feedback.js';

const config = window.kmConfig ?? {};
const csrfToken = config.csrfToken ?? document.querySelector('meta[name="csrf-token"]')?.content ?? '';

async function jsonResponse(response) {
    const payload = await response.json().catch(() => ({}));
    if (! response.ok || payload.success === false) {
        throw new Error(payload.message ?? 'Permintaan tidak dapat diproses.');
    }

    return payload;
}

async function markAsRead(documentId) {
    if (! config.markAsReadUrl) {
        return;
    }

    await fetch(config.markAsReadUrl, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ id_km_pengajuan: documentId }),
    });
}

async function completeReading(button) {
    if (! config.completionUrl || button.disabled) {
        return;
    }

    const confirmed = await confirmAction({
        title: 'Tandai selesai dibaca?',
        message: 'Status dokumen akan dicatat sebagai selesai dibaca dan poin hanya diberikan sesuai aturan yang berlaku.',
        confirmLabel: 'Tandai Selesai',
        tone: 'success',
        trigger: button,
    });
    if (!confirmed) {
        return;
    }

    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(config.completionUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ id_km_pengajuan: Number.parseInt(button.dataset.documentId, 10) }),
        });
        const payload = await jsonResponse(response);
        notify({
            title: payload.already_completed ? 'Status tidak berubah' : 'Bacaan selesai',
            message: payload.already_completed
                ? 'Dokumen ini sudah pernah diselesaikan.'
                : 'Dokumen ditandai selesai dibaca.',
            tone: payload.already_completed ? 'info' : 'success',
        });
        window.setTimeout(() => window.location.reload(), 900);
    } catch (error) {
        button.disabled = false;
        button.removeAttribute('aria-busy');
        notify({
            title: 'Status belum tersimpan',
            message: error.message ?? 'Status selesai tidak dapat disimpan.',
            tone: 'danger',
        });
    }
}

async function toggleLike(button) {
    if (button.disabled) {
        return;
    }

    const liked = button.getAttribute('aria-pressed') === 'true';
    button.disabled = true;

    try {
        const response = await fetch(liked ? config.unlikeUrl : config.likeUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ document_id: button.dataset.documentId, id_km_pengajuan: button.dataset.documentId }),
        });
        const payload = await jsonResponse(response);
        const nextState = ! liked;
        button.classList.toggle('liked', nextState);
        button.setAttribute('aria-pressed', String(nextState));
        button.querySelector('[data-km-like-count]').textContent = payload.like_count;
    } catch (error) {
        notify({
            title: 'Like belum diperbarui',
            message: error.message ?? 'Status suka tidak dapat diperbarui.',
            tone: 'danger',
        });
    } finally {
        button.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initBookmarkButtons();
    initPdfViewer();
    initSubmitProtection();

    document.addEventListener('click', (event) => {
        const documentTrigger = event.target.closest('.km-open-document');
        if (documentTrigger && ! documentTrigger.disabled) {
            void markAsRead(Number.parseInt(documentTrigger.dataset.documentId, 10));
            return;
        }

        const synopsisButton = event.target.closest('.km-show-synopsis');
        if (synopsisButton) {
            document.getElementById('globalSynopsisTitle').textContent = `Sinopsis: ${synopsisButton.dataset.title}`;
            document.getElementById('globalSynopsisContent').textContent = synopsisButton.dataset.synopsis || '-';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('globalSynopsisModal')).show();
            return;
        }

        const likeButton = event.target.closest('[data-km-like]');
        if (likeButton) {
            void toggleLike(likeButton);
            return;
        }

        const completeButton = event.target.closest('#km-viewer-complete');
        if (completeButton) {
            void completeReading(completeButton);
        }
    });
});
