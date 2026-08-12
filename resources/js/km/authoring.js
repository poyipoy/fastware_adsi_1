import '../../css/km/authoring.css';
import { initDraftAutosave, stopAutosave } from './draft-autosave.js';
import { confirmAction, initSubmitProtection, notify } from './ui-feedback.js';

const config = window.kmAuthoringConfig ?? {};
const tagPickers = new Map();
const coAuthorPickers = new Map();

function routeFor(template, id) {
    return template.replace('__KM_ID__', encodeURIComponent(String(id)));
}

async function requestJson(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            ...options.headers,
        },
    });
    const payload = await response.json().catch(() => ({}));

    if (! response.ok) {
        throw new Error(Object.values(payload.errors ?? {}).flat()[0] ?? payload.message ?? 'Permintaan tidak dapat diproses.');
    }

    return payload;
}

function initializeTagPicker(input, hidden) {
    let tags = [];
    const container = input.closest('[data-km-tag-picker]');
    const feedback = container.parentElement.querySelector('[data-km-tag-feedback]');

    function normalize(value) {
        return value.trim().replace(/\s+/g, ' ');
    }

    function setFeedback(message = '') {
        if (! feedback) {
            return;
        }

        feedback.textContent = message;
        feedback.hidden = message === '';
        container.classList.toggle('km-tag-container--invalid', message !== '');
        if (message) {
            input.setAttribute('aria-invalid', 'true');
        } else {
            input.removeAttribute('aria-invalid');
        }
    }

    function render() {
        container.querySelectorAll('.km-tag-chip').forEach((chip) => chip.remove());

        tags.forEach((tag) => {
            const chip = document.createElement('span');
            chip.className = 'km-tag-chip badge text-bg-secondary';
            chip.textContent = tag;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn-close btn-close-white';
            remove.setAttribute('aria-label', `Hapus tag ${tag}`);
            remove.addEventListener('click', () => {
                tags = tags.filter((value) => value !== tag);
                setFeedback();
                render();
            });

            chip.appendChild(remove);
            container.insertBefore(chip, input);
        });

        hidden.value = tags.join(',');
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function commit(rawValue = input.value) {
        const candidates = String(rawValue)
            .split(/[,\r\n]+/)
            .map(normalize)
            .filter(Boolean);
        let changed = false;
        let duplicateTag = '';

        candidates.forEach((tag) => {
            const exists = tags.some((value) => value.toLocaleLowerCase('id-ID') === tag.toLocaleLowerCase('id-ID'));
            if (exists) {
                duplicateTag ||= tag;
            } else if (tag.length <= 50 && tags.length < 10) {
                tags.push(tag);
                changed = true;
            }
        });

        if (candidates.length > 0) {
            input.value = '';
        }
        if (changed) {
            render();
        }
        setFeedback(duplicateTag ? `Tag "${duplicateTag}" sudah ditambahkan. Gunakan tag yang berbeda.` : '');
    }

    input.addEventListener('keydown', (event) => {
        const isSeparator = event.key === 'Enter'
            || event.key === ','
            || (event.code === 'Comma' && ! event.shiftKey);

        if (isSeparator && ! event.isComposing) {
            event.preventDefault();
            commit();
        } else if (event.key === 'Backspace' && input.value === '' && tags.length > 0) {
            tags.pop();
            render();
        }
    });
    input.addEventListener('input', () => {
        if (/[,\r\n]/.test(input.value)) {
            commit();
        } else {
            setFeedback();
        }
    });
    input.closest('form').addEventListener('submit', () => commit());

    return {
        set(values) {
            tags = String(values ?? '')
                .split(',')
                .map(normalize)
                .filter(Boolean)
                .slice(0, 10);
            input.value = '';
            setFeedback();
            render();
        },
    };
}

function initializeCoAuthorPicker(root) {
    const search = root.querySelector('input[type="search"]');
    const results = root.querySelector('[data-km-coauthor-results]');
    const selectedContainer = root.querySelector('[data-km-coauthor-selected]');
    const inputs = root.querySelector('[data-km-coauthor-inputs]');
    const selected = new Map();
    let documentId = null;
    let searchTimer = null;
    let requestController = null;

    function renderSelected() {
        selectedContainer.replaceChildren();
        inputs.replaceChildren();

        selected.forEach((user) => {
            const chip = document.createElement('span');
            chip.className = 'badge text-bg-primary d-inline-flex align-items-center gap-1';
            chip.textContent = user.name;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn-close btn-close-white';
            remove.setAttribute('aria-label', `Hapus co-author ${user.name}`);
            remove.addEventListener('click', () => {
                selected.delete(Number(user.id));
                renderSelected();
                inputs.dispatchEvent(new Event('change', { bubbles: true }));
            });
            chip.appendChild(remove);
            selectedContainer.appendChild(chip);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'co_author_ids[]';
            hidden.value = String(user.id);
            inputs.appendChild(hidden);
        });
    }

    function renderResults(users) {
        results.replaceChildren();
        const available = users.filter((user) => ! selected.has(Number(user.id)));
        results.classList.toggle('d-none', available.length === 0);

        available.forEach((user) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'list-group-item list-group-item-action py-2';
            button.textContent = user.name;
            button.addEventListener('click', () => {
                if (selected.size >= 10) {
                    return;
                }
                selected.set(Number(user.id), user);
                renderSelected();
                results.classList.add('d-none');
                search.value = '';
                inputs.dispatchEvent(new Event('change', { bubbles: true }));
            });
            results.appendChild(button);
        });
    }

    async function searchUsers() {
        requestController?.abort();
        requestController = new AbortController();
        const url = new URL(config.coAuthorOptionsUrl, window.location.origin);
        if (search.value.trim()) {
            url.searchParams.set('q', search.value.trim());
        }
        if (documentId) {
            url.searchParams.set('document_id', String(documentId));
        }

        try {
            const response = await requestJson(url.toString(), { signal: requestController.signal });
            renderResults(response.data ?? []);
        } catch (error) {
            if (error.name !== 'AbortError') {
                results.classList.remove('d-none');
                results.textContent = error.message;
            }
        }
    }

    search.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => void searchUsers(), 250);
    });
    search.addEventListener('focus', () => void searchUsers());

    return {
        set(nextDocumentId, users = []) {
            requestController?.abort();
            documentId = nextDocumentId;
            selected.clear();
            users.slice(0, 10).forEach((user) => selected.set(Number(user.id), user));
            search.value = '';
            results.classList.add('d-none');
            renderSelected();
        },
    };
}

document.addEventListener('DOMContentLoaded', () => {
    tagPickers.set('create', initializeTagPicker(
        document.getElementById('km-tags-input'),
        document.getElementById('km-tags-csv'),
    ));
    tagPickers.set('edit', initializeTagPicker(
        document.getElementById('edit-km-tags-input'),
        document.getElementById('edit-km-tags-csv'),
    ));

    document.querySelectorAll('[data-km-coauthor-picker]').forEach((root) => {
        coAuthorPickers.set(root.dataset.kmCoauthorPicker, initializeCoAuthorPicker(root));
    });
    coAuthorPickers.get('create')?.set(null, []);

    const editModalElement = document.getElementById('editKmModal');
    const revisionModalElement = document.getElementById('kmRevisionModal');
    const revisionForm = document.getElementById('km-revision-form');

    document.addEventListener('click', async (event) => {
        const editButton = event.target.closest('[data-km-edit]');
        const deactivateButton = event.target.closest('[data-km-deactivate]');
        const submitButton = event.target.closest('[data-km-submit]');
        const revisionButton = event.target.closest('[data-km-revise]');

        if (revisionButton && revisionModalElement && revisionForm) {
            revisionForm.action = routeFor(config.majorRevisionUrl, revisionButton.dataset.kmRevise);
            document.getElementById('km-revision-document-title').textContent = revisionButton.dataset.kmDocumentTitle ?? '';
            document.getElementById('km-revision-note').value = '';
            bootstrap.Modal.getOrCreateInstance(revisionModalElement).show();
            return;
        }

        try {
            if (editButton) {
                const id = editButton.dataset.kmEdit;
                editButton.disabled = true;
                editButton.setAttribute('aria-busy', 'true');
                const data = await requestJson(routeFor(config.editUrl, id));
                document.getElementById('editId').value = data.id;
                document.getElementById('editJudul').value = data.judul ?? '';
                document.getElementById('editKeterangan').value = data.keterangan ?? '';
                document.getElementById('editReadingMinutes').value = data.reading_minutes ?? '';
                tagPickers.get('edit')?.set(data.tags_csv ?? '');
                coAuthorPickers.get('edit')?.set(data.id, data.co_authors ?? []);

                const fileInput = document.getElementById('editFile');
                const fileLink = document.getElementById('editFileLink');
                const fileName = document.getElementById('editFileName');
                const fileState = document.getElementById('editFileState');
                fileInput.value = '';
                fileInput.required = ! data.has_file;
                fileLink.hidden = ! data.has_file;
                fileName.textContent = data.file_name || 'File tersimpan';
                fileState.textContent = data.processing_state === 'pending_processing'
                    ? 'File Office tersimpan privat dan masih menunggu konversi. Draf belum dapat dikirim.'
                    : data.has_file
                        ? 'File tersimpan di penyimpanan privat. Unduhan dinonaktifkan.'
                        : 'Unggah PDF, PPT, atau PPTX untuk menyimpan draf.';

                bootstrap.Modal.getOrCreateInstance(editModalElement).show();
                initDraftAutosave({
                    url: routeFor(config.autosaveUrl, data.id),
                    csrf: config.csrfToken,
                    revision: data.draft_revision,
                    formId: 'km-draft-form',
                    statusElId: 'km-autosave-status',
                });
                editButton.disabled = false;
                editButton.removeAttribute('aria-busy');
            } else if (deactivateButton) {
                const confirmed = await confirmAction({
                    title: 'Nonaktifkan dokumen?',
                    message: 'Dokumen tidak lagi aktif setelah tindakan ini. Lanjutkan hanya jika dokumen memang harus dinonaktifkan.',
                    confirmLabel: 'Nonaktifkan',
                    tone: 'danger',
                    trigger: deactivateButton,
                });
                if (!confirmed) {
                    return;
                }
                deactivateButton.disabled = true;
                deactivateButton.setAttribute('aria-busy', 'true');
                await requestJson(routeFor(config.deactivateUrl, deactivateButton.dataset.kmDeactivate), {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': config.csrfToken },
                });
                notify({
                    title: 'Dokumen dinonaktifkan',
                    message: 'Status dokumen berhasil diperbarui.',
                    tone: 'success',
                });
                window.setTimeout(() => window.location.reload(), 900);
            } else if (submitButton) {
                const confirmed = await confirmAction({
                    title: 'Kirim untuk persetujuan?',
                    message: 'Draf akan masuk ke antrean persetujuan dan tidak dapat diedit selama menunggu keputusan.',
                    confirmLabel: 'Kirim Draf',
                    tone: 'primary',
                    trigger: submitButton,
                });
                if (!confirmed) {
                    return;
                }
                submitButton.disabled = true;
                submitButton.setAttribute('aria-busy', 'true');
                await requestJson(routeFor(config.submitUrl, submitButton.dataset.kmSubmit), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': config.csrfToken },
                });
                notify({
                    title: 'Draf terkirim',
                    message: 'Dokumen masuk ke antrean persetujuan.',
                    tone: 'success',
                });
                window.setTimeout(() => window.location.reload(), 900);
            }
        } catch (error) {
            [editButton, deactivateButton, submitButton].filter(Boolean).forEach((button) => {
                button.disabled = false;
                button.removeAttribute('aria-busy');
            });
            notify({
                title: 'Tindakan belum berhasil',
                message: error.message ?? 'Permintaan tidak dapat diproses.',
                tone: 'danger',
            });
        }
    });

    editModalElement.addEventListener('hidden.bs.modal', () => {
        stopAutosave(true);
        coAuthorPickers.get('edit')?.set(null, []);
        document.getElementById('km-autosave-status').textContent = '';
    });

    document.getElementById('km-draft-form').addEventListener('submit', () => stopAutosave(true));
    initSubmitProtection();
    window.addEventListener('beforeunload', () => stopAutosave(true));
});
