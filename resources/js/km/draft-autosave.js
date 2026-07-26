const AUTOSAVE_DELAY_MS = 4_000;

let session = null;

function setStatus(type, message) {
    if (! session?.statusElement) {
        return;
    }

    session.statusElement.textContent = message;
    session.statusElement.className = `km-autosave-status km-autosave-${type} mb-3`;
}

function payloadFrom(form) {
    const data = new FormData(form);
    const minutes = data.get('reading_minutes');

    return {
        judul: String(data.get('judul') ?? ''),
        keterangan: String(data.get('keterangan') ?? ''),
        reading_minutes: minutes === null || minutes === '' ? null : Number.parseInt(minutes, 10),
        tags: String(data.get('tags_csv') ?? '')
            .split(',')
            .map((tag) => tag.trim())
            .filter(Boolean),
        co_author_ids: data.getAll('co_author_ids[]')
            .map((id) => Number.parseInt(id, 10))
            .filter(Number.isInteger),
    };
}

function schedule() {
    if (! session || session.stopped) {
        return;
    }

    window.clearTimeout(session.timer);
    const delay = Math.max(0, session.dueAt - Date.now());
    session.timer = window.setTimeout(() => void performAutosave(), delay);
}

async function performAutosave() {
    if (! session || session.stopped || session.inFlight || session.changeVersion === session.savedVersion) {
        return;
    }

    const activeSession = session;
    const snapshotVersion = activeSession.changeVersion;
    const controller = new AbortController();
    activeSession.inFlight = true;
    activeSession.controller = controller;
    setStatus('saving', 'Menyimpan draf...');

    try {
        const response = await fetch(activeSession.url, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': activeSession.csrf,
            },
            body: JSON.stringify({
                ...payloadFrom(activeSession.form),
                revision: activeSession.revision,
            }),
            signal: controller.signal,
        });
        const data = await response.json().catch(() => ({}));

        if (response.status === 409) {
            activeSession.revision = data.draft_revision ?? activeSession.revision;
            activeSession.stopped = true;
            setStatus('conflict', 'Konflik draf terdeteksi. Muat ulang dokumen sebelum melanjutkan.');
            return;
        }

        if (! response.ok) {
            const validationMessage = Object.values(data.errors ?? {}).flat()[0];
            throw new Error(validationMessage ?? data.message ?? 'Autosave gagal.');
        }

        activeSession.revision = data.draft_revision ?? activeSession.revision;
        activeSession.savedVersion = snapshotVersion;
        const savedAt = data.autosaved_at ? new Date(data.autosaved_at) : new Date();
        setStatus('saved', `Tersimpan ${savedAt.toLocaleTimeString('id-ID')}`);
    } catch (error) {
        if (error.name !== 'AbortError') {
            setStatus('error', error.message ?? 'Autosave gagal.');
            activeSession.dueAt = Date.now() + AUTOSAVE_DELAY_MS;
        }
    } finally {
        if (session === activeSession) {
            activeSession.inFlight = false;
            activeSession.controller = null;

            if (! activeSession.stopped && activeSession.changeVersion !== activeSession.savedVersion) {
                schedule();
            }
        }
    }
}

export function initDraftAutosave({ url, csrf, revision, formId, statusElId }) {
    stopAutosave(true);

    const form = document.getElementById(formId);
    if (! form || ! url) {
        return;
    }

    const changeHandler = () => {
        if (! session || session.form !== form || session.stopped) {
            return;
        }

        session.changeVersion += 1;
        session.dueAt = Date.now() + AUTOSAVE_DELAY_MS;
        schedule();
    };

    session = {
        url,
        csrf,
        revision: Number.parseInt(revision, 10) || 0,
        form,
        statusElement: document.getElementById(statusElId),
        changeHandler,
        changeVersion: 0,
        savedVersion: 0,
        dueAt: 0,
        timer: null,
        inFlight: false,
        controller: null,
        stopped: false,
    };

    form.addEventListener('input', changeHandler);
    form.addEventListener('change', changeHandler);
    setStatus('saved', 'Draf siap disimpan otomatis.');
}

export function stopAutosave(abortRequest = true) {
    if (! session) {
        return;
    }

    window.clearTimeout(session.timer);
    session.form.removeEventListener('input', session.changeHandler);
    session.form.removeEventListener('change', session.changeHandler);

    if (abortRequest) {
        session.controller?.abort();
    }

    session = null;
}
