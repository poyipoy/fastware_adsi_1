const CONFIRM_TONES = new Set(['primary', 'success', 'danger', 'warning']);
const TOAST_TONES = new Set(['info', 'success', 'warning', 'danger']);

let activeConfirmation = null;

function bootstrapApi() {
    return window.bootstrap ?? null;
}

function restoreFocus(trigger) {
    if (trigger instanceof HTMLElement && trigger.isConnected) {
        window.requestAnimationFrame(() => trigger.focus());
    }
}

export function confirmAction({
    title = 'Konfirmasi tindakan',
    message = 'Apakah Anda yakin ingin melanjutkan?',
    confirmLabel = 'Lanjutkan',
    cancelLabel = 'Batal',
    tone = 'primary',
    trigger = document.activeElement,
} = {}) {
    const modalElement = document.getElementById('km-feedback-modal');
    const bootstrap = bootstrapApi();

    if (!modalElement || !bootstrap?.Modal) {
        return Promise.resolve(false);
    }

    activeConfirmation?.resolve(false);
    activeConfirmation?.cleanup();

    const titleElement = modalElement.querySelector('#km-feedback-modal-title');
    const messageElement = modalElement.querySelector('#km-feedback-modal-message');
    const acceptButton = modalElement.querySelector('[data-km-confirm-accept]');
    const cancelButton = modalElement.querySelector('[data-km-confirm-cancel]');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement, {
        backdrop: 'static',
        keyboard: true,
    });

    titleElement.textContent = title;
    messageElement.textContent = message;
    acceptButton.textContent = confirmLabel;
    cancelButton.textContent = cancelLabel;

    const safeTone = CONFIRM_TONES.has(tone) ? tone : 'primary';
    acceptButton.className = `btn btn-${safeTone}`;

    return new Promise((resolve) => {
        let settled = false;

        const settle = (value) => {
            if (settled) {
                return;
            }
            settled = true;
            resolve(value);
            modal.hide();
        };
        const accept = () => settle(true);
        const cancel = () => settle(false);
        const hidden = () => {
            if (!settled) {
                settled = true;
                resolve(false);
            }
            cleanup();
            restoreFocus(trigger);
        };
        const cleanup = () => {
            acceptButton.removeEventListener('click', accept);
            cancelButton.removeEventListener('click', cancel);
            modalElement.removeEventListener('hidden.bs.modal', hidden);
            if (activeConfirmation?.cleanup === cleanup) {
                activeConfirmation = null;
            }
        };

        acceptButton.addEventListener('click', accept);
        cancelButton.addEventListener('click', cancel);
        modalElement.addEventListener('hidden.bs.modal', hidden);
        activeConfirmation = { resolve, cleanup };
        modal.show();
    });
}

export function notify({
    title = 'Informasi',
    message = '',
    tone = 'info',
    delay = 5000,
} = {}) {
    const region = document.getElementById('km-toast-region');
    if (!region) {
        return;
    }

    const safeTone = TOAST_TONES.has(tone) ? tone : 'info';
    const toast = document.createElement('div');
    toast.className = 'toast km-toast';
    toast.dataset.tone = safeTone;
    toast.setAttribute('role', safeTone === 'danger' ? 'alert' : 'status');
    toast.setAttribute('aria-atomic', 'true');

    const header = document.createElement('div');
    header.className = 'toast-header';
    const icon = document.createElement('i');
    const iconByTone = {
        info: 'bi-info-circle',
        success: 'bi-check-circle',
        warning: 'bi-exclamation-triangle',
        danger: 'bi-x-octagon',
    };
    icon.className = `bi ${iconByTone[safeTone]} me-2`;
    icon.setAttribute('aria-hidden', 'true');
    const heading = document.createElement('strong');
    heading.className = 'me-auto';
    heading.textContent = title;
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close';
    close.dataset.bsDismiss = 'toast';
    close.setAttribute('aria-label', 'Tutup notifikasi');
    header.append(icon, heading, close);

    const body = document.createElement('div');
    body.className = 'toast-body';
    body.textContent = message;
    toast.append(header, body);
    region.appendChild(toast);

    const bootstrap = bootstrapApi();
    if (!bootstrap?.Toast) {
        window.setTimeout(() => toast.remove(), Math.max(delay, 1000));
        return;
    }

    toast.addEventListener('hidden.bs.toast', () => toast.remove(), { once: true });
    bootstrap.Toast.getOrCreateInstance(toast, {
        autohide: true,
        delay,
    }).show();
}

export function setFormBusy(form, submitter = null, busyLabel = 'Memproses…') {
    if (!form || form.getAttribute('aria-busy') === 'true') {
        return false;
    }

    form.setAttribute('aria-busy', 'true');
    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((control) => {
        control.disabled = true;
    });

    if (submitter instanceof HTMLButtonElement) {
        submitter.dataset.kmOriginalLabel = submitter.textContent.trim();
        submitter.textContent = busyLabel;
    }

    return true;
}

export function initSubmitProtection(root = document) {
    root.querySelectorAll('[data-km-submit-protection]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!setFormBusy(form, event.submitter)) {
                event.preventDefault();
            }
        });
    });
}

