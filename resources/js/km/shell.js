const config = window.kmShellConfig;
const trigger = document.getElementById('km-notification-trigger');
const menu = document.getElementById('km-notification-menu');
const list = document.querySelector('[data-km-notification-list]');
const badge = document.querySelector('[data-km-notification-badge]');
const summary = document.querySelector('[data-km-notification-summary]');
const readAllButton = document.querySelector('[data-km-notification-read-all]');

if (config && trigger && menu && list && badge && summary && readAllButton) {
    let lastFetchedAt = 0;
    let controller = null;

    const notificationLabels = {
        document_submitted: 'Dokumen menunggu persetujuan',
        document_approved: 'Dokumen disetujui',
        document_rejected: 'Dokumen ditolak',
        insight_mention: 'Anda disebut dalam insight',
        insight_reply: 'Insight Anda mendapat balasan',
        insight_reaction: 'Insight Anda mendapat reaction',
        insight_featured: 'Insight Anda dipilih',
        new_material: 'Materi baru tersedia',
        assignment_created: 'Materi wajib baru',
        assignment_reminder: 'Deadline materi wajib mendekat',
        assignment_overdue: 'Materi wajib melewati deadline',
        assignment_exempted: 'Pengecualian materi wajib dicatat',
        completion_overridden: 'Completion aksesibilitas dicatat',
        approval_reminder: 'Pengingat persetujuan',
        approval_overdue: 'Persetujuan terlambat',
    };

    const setUnreadCount = (count) => {
        const unread = Math.max(0, Number(count) || 0);
        badge.hidden = unread === 0;
        badge.textContent = unread > 99 ? '99+' : String(unread);
        trigger.setAttribute(
            'aria-label',
            unread > 0
                ? `Buka notifikasi Knowledge Management, ${unread} belum dibaca`
                : 'Buka notifikasi Knowledge Management, tidak ada yang belum dibaca',
        );
        summary.textContent = unread > 0 ? `${unread} belum dibaca` : 'Semua sudah dibaca';
        readAllButton.disabled = unread === 0;
    };

    const destinationFor = (notification) => {
        const documentId = notification.data?.document_id;
        if (!documentId) {
            return config.documentUrlTemplate.replace('__KM_ID__', '');
        }

        const base = config.documentUrlTemplate.replace('__KM_ID__', encodeURIComponent(String(documentId)));
        const url = new URL(base, window.location.origin);
        if (notification.data?.assignment_id) {
            url.searchParams.set('assignment', String(notification.data.assignment_id));
        }
        if (notification.data?.insight_id) {
            url.searchParams.set('insight', String(notification.data.insight_id));
        }

        return `${url.pathname}${url.search}${url.hash}`;
    };

    const formattedTime = (value) => {
        if (!value) {
            return '';
        }
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '';
        }
        return new Intl.DateTimeFormat('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(date);
    };

    const markRead = (notificationId) => {
        const url = config.readUrlTemplate.replace(
            '__KM_NOTIFICATION__',
            encodeURIComponent(String(notificationId)),
        );
        void fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
    };

    const renderEmpty = () => {
        const empty = document.createElement('div');
        empty.className = 'km-notification-empty';

        const icon = document.createElement('i');
        icon.className = 'bi bi-bell-slash';
        icon.setAttribute('aria-hidden', 'true');

        const title = document.createElement('strong');
        title.textContent = 'Belum ada notifikasi';

        const description = document.createElement('span');
        description.textContent = 'Aktivitas KM yang perlu Anda ketahui akan tampil di sini.';

        empty.append(icon, title, description);
        list.replaceChildren(empty);
    };

    const renderError = (message) => {
        const error = document.createElement('div');
        error.className = 'km-notification-error';
        error.setAttribute('role', 'alert');

        const text = document.createElement('span');
        text.textContent = message || 'Notifikasi belum dapat dimuat.';

        const retry = document.createElement('button');
        retry.type = 'button';
        retry.className = 'btn btn-outline-primary btn-sm';
        retry.textContent = 'Coba lagi';
        retry.addEventListener('click', () => loadNotifications(true));

        error.append(text, retry);
        list.replaceChildren(error);
    };

    const renderNotifications = (notifications) => {
        if (!Array.isArray(notifications) || notifications.length === 0) {
            renderEmpty();
            return;
        }

        const fragment = document.createDocumentFragment();
        notifications.forEach((notification) => {
            const link = document.createElement('a');
            link.className = 'km-notification-item';
            link.href = destinationFor(notification);
            link.dataset.unread = notification.read_at ? 'false' : 'true';

            const body = document.createElement('span');
            body.className = 'km-notification-item__body';

            const title = document.createElement('strong');
            title.textContent = notificationLabels[notification.type] || 'Aktivitas Knowledge Management';

            const documentTitle = document.createElement('span');
            documentTitle.className = 'km-notification-document';
            documentTitle.textContent = notification.data?.title || 'Buka detail aktivitas';

            const meta = document.createElement('span');
            meta.className = 'km-notification-item__meta';
            meta.textContent = formattedTime(notification.created_at);

            if (!notification.read_at) {
                const unreadText = document.createElement('span');
                unreadText.className = 'visually-hidden';
                unreadText.textContent = 'Belum dibaca. ';
                body.append(unreadText);
            }
            body.append(title, documentTitle, meta);
            link.append(body);
            link.addEventListener('click', () => markRead(notification.id));
            fragment.append(link);
        });
        list.replaceChildren(fragment);
    };

    async function loadNotifications(force = false) {
        if (!force && Date.now() - lastFetchedAt < 60_000) {
            return;
        }

        controller?.abort();
        controller = new AbortController();
        list.setAttribute('aria-busy', 'true');
        if (lastFetchedAt === 0) {
            const loading = document.createElement('div');
            loading.className = 'km-notification-loading';
            loading.textContent = 'Memuat notifikasi...';
            list.replaceChildren(loading);
        }

        try {
            const response = await fetch(config.indexUrl, {
                credentials: 'same-origin',
                signal: controller.signal,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload.message || 'Notifikasi belum dapat dimuat.');
            }

            renderNotifications(payload.data);
            setUnreadCount(payload.unread_count);
            lastFetchedAt = Date.now();
        } catch (error) {
            if (error.name !== 'AbortError') {
                renderError(error.message);
            }
        } finally {
            list.setAttribute('aria-busy', 'false');
        }
    }

    readAllButton.addEventListener('click', async () => {
        readAllButton.disabled = true;
        readAllButton.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(config.readAllUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: '{}',
            });
            if (!response.ok) {
                throw new Error('Notifikasi belum dapat ditandai sebagai sudah dibaca.');
            }

            list.querySelectorAll('.km-notification-item').forEach((item) => {
                item.dataset.unread = 'false';
                item.querySelector('.visually-hidden')?.remove();
            });
            setUnreadCount(0);
        } catch (error) {
            renderError(error.message);
        } finally {
            readAllButton.removeAttribute('aria-busy');
            readAllButton.disabled = badge.hidden;
        }
    });

    trigger.addEventListener('shown.bs.dropdown', () => loadNotifications());
    void loadNotifications(true);
}
