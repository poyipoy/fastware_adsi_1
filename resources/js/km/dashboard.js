import { initBookmarkButtons } from './bookmarks.js';
import { initInsights } from './insights.js';
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

async function markAsRead(documentId, documentVersionId = null) {
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
        body: JSON.stringify({
            id_km_pengajuan: documentId,
            document_version_id: documentVersionId,
        }),
    });
}

async function completeReading(button) {
    if (! config.completionUrl || button.disabled) {
        return;
    }

    const confirmed = await confirmAction({
        title: 'Tandai selesai dibaca?',
        message: 'Saya telah membaca dan memahami dokumen ini.',
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
            body: JSON.stringify({
                id_km_pengajuan: Number.parseInt(button.dataset.documentId, 10),
                document_version_id: Number.parseInt(button.dataset.documentVersionId ?? '', 10) || null,
                acknowledged: true,
            }),
        });
        const payload = await jsonResponse(response);
        notify({
            title: payload.already_completed ? 'Status tidak berubah' : 'Bacaan selesai',
            message: payload.already_completed
                ? 'Dokumen ini sudah pernah diselesaikan.'
                : 'Dokumen ditandai selesai dibaca.',
            tone: payload.already_completed ? 'info' : 'success',
        });
        markDocumentCompleted(button.dataset.documentId, button.dataset.documentVersionId ?? null);
        button.hidden = true;
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

function markDocumentCompleted(documentId, documentVersionId = null) {
    const versionSelector = documentVersionId === null
        ? ':not([data-document-version-id])'
        : `[data-document-version-id="${documentVersionId}"]`;
    document.querySelectorAll(
        `.km-open-document[data-document-id="${documentId}"]${versionSelector}`,
    ).forEach((trigger) => {
        trigger.dataset.completed = 'true';
        trigger.dataset.canComplete = 'false';

        const card = trigger.closest('.km-document-card, .km-continue-card');
        const status = card?.querySelector('[data-km-reading-status]');
        if (status) {
            status.textContent = 'Selesai';
        }
    });
}

function initAdvancedTagFilter() {
    document.querySelectorAll('[data-km-tag-filter]').forEach((filter) => {
        const summary = filter.querySelector('[data-km-tag-summary]');
        const searchContainer = filter.querySelector('[data-km-tag-search]');
        const search = searchContainer?.querySelector('input[type="search"]');
        const options = Array.from(filter.querySelectorAll('[data-km-tag-option]'));
        const empty = filter.querySelector('[data-km-tag-empty]');

        if (! summary) {
            return;
        }

        const updateSummary = () => {
            const selectedCount = options.filter((option) => option.querySelector('input[type="checkbox"]')?.checked).length;
            summary.textContent = selectedCount > 0 ? `${selectedCount} tag dipilih` : 'Semua tag';
        };

        const filterOptions = () => {
            const term = search?.value.trim().toLocaleLowerCase('id-ID') ?? '';
            let visibleCount = 0;

            options.forEach((option) => {
                const matches = option.textContent.trim().toLocaleLowerCase('id-ID').includes(term);
                option.hidden = ! matches;
                if (matches) {
                    visibleCount += 1;
                }
            });

            if (empty) {
                empty.hidden = options.length === 0 || visibleCount > 0;
            }
        };

        searchContainer?.removeAttribute('hidden');
        updateSummary();

        options.forEach((option) => {
            option.querySelector('input[type="checkbox"]')?.addEventListener('change', updateSummary);
        });
        search?.addEventListener('input', filterOptions);
        filter.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || ! filter.open) {
                return;
            }

            event.preventDefault();
            filter.open = false;
            filter.querySelector(':scope > summary')?.focus();
        });

        document.addEventListener('click', (event) => {
            if (filter.open && ! filter.contains(event.target)) {
                filter.open = false;
            }
        });
    });
}

function initLeaderboardToggle() {
    const leaderboard = document.querySelector('[data-km-leaderboard]');
    if (! leaderboard) {
        return;
    }

    leaderboard.addEventListener('change', (event) => {
        const toggle = event.target.closest('[data-km-leaderboard-toggle]');
        if (! toggle) {
            return;
        }

        leaderboard.querySelectorAll('[data-km-leaderboard-panel]').forEach((panel) => {
            panel.hidden = panel.dataset.kmLeaderboardPanel !== toggle.value;
        });
        const status = leaderboard.querySelector('[data-km-leaderboard-status]');
        if (status) {
            status.textContent = toggle.value === 'department'
                ? 'Leaderboard departemen ditampilkan.'
                : 'Leaderboard global ditampilkan.';
        }
    });
}

function openNotificationTarget() {
    const parameters = new URLSearchParams(window.location.search);
    const documentId = Number.parseInt(parameters.get('document') ?? '', 10);
    if (! Number.isInteger(documentId) || documentId <= 0) {
        return;
    }

    const selector = parameters.has('insight') ? '[data-km-insights-open]' : '.km-open-document';
    const target = document.querySelector(`${selector}[data-document-id="${documentId}"]`);
    if (target && ! target.disabled) {
        window.setTimeout(() => target.click(), 150);
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
    initInsights();
    initPdfViewer();
    initLeaderboardToggle();
    initAdvancedTagFilter();
    initSubmitProtection();

    document.addEventListener('click', (event) => {
        const documentTrigger = event.target.closest('.km-open-document');
        if (documentTrigger && ! documentTrigger.disabled) {
            void markAsRead(
                Number.parseInt(documentTrigger.dataset.documentId, 10),
                Number.parseInt(documentTrigger.dataset.documentVersionId ?? '', 10) || null,
            );
            const resumePage = Number.parseInt(documentTrigger.dataset.resumePage ?? '1', 10);
            if (resumePage > 1) {
                notify({
                    title: 'Melanjutkan bacaan',
                    message: `Melanjutkan dari halaman ${resumePage}.`,
                    tone: 'info',
                });
            }
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

    openNotificationTarget();
});
