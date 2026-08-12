import { notify } from './ui-feedback.js';


const config = window.kmConfig ?? {};
const reactionLabels = {
    helpful: 'Membantu',
    insightful: 'Mencerahkan',
    agree: 'Setuju',
};
const configuredMaximumMentions = Number.parseInt(config.maximumMentions ?? '10', 10);
const maximumMentions = Number.isInteger(configuredMaximumMentions) && configuredMaximumMentions >= 0
    ? configuredMaximumMentions
    : 10;

let modalElement = null;
let modal = null;
let currentDocumentId = null;
let currentPage = 1;
let lastPage = 1;
let editingInsightId = null;
let triggeringElement = null;
let mentionSearchTimer = null;
let mentionRequestController = null;
let mentionResults = [];
let mentionResultMessage = '';
const selectedMentions = new Map();
const lockedMentionIds = new Set();


function csrfToken() {
    return config.csrfToken ?? document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function endpoint(template, placeholder, value) {
    return String(template ?? '').replace(placeholder, String(value));
}

function documentEndpoint(template) {
    return endpoint(template, '__KM_DOCUMENT__', currentDocumentId);
}

function insightEndpoint(template, insightId) {
    return endpoint(template, '__KM_INSIGHT__', insightId);
}

async function jsonResponse(response) {
    const payload = await response.json().catch(() => ({}));
    if (! response.ok || payload.success === false) {
        const validationMessage = Object.values(payload.errors ?? {}).flat().find(Boolean);
        throw new Error(validationMessage ?? payload.message ?? 'Permintaan insight belum dapat diproses.');
    }

    return payload;
}

async function request(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...options.headers,
        },
    });

    return jsonResponse(response);
}

function node(tag, className = '', text = '') {
    const created = document.createElement(tag);
    if (className) {
        created.className = className;
    }
    if (text) {
        created.textContent = text;
    }

    return created;
}

function actionButton(label, action, insightId, className = 'btn btn-link btn-sm') {
    const button = node('button', className, label);
    button.type = 'button';
    button.dataset.kmInsightAction = action;
    button.dataset.insightId = String(insightId);

    return button;
}

function formatDate(value) {
    if (! value) {
        return '-';
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

function setFeedback(message = '', tone = 'info') {
    const feedback = modalElement?.querySelector('[data-km-insight-feedback]');
    if (! feedback) {
        return;
    }
    feedback.textContent = message;
    feedback.dataset.tone = tone;
}

function renderLoading() {
    const list = modalElement.querySelector('[data-km-insight-list]');
    list.replaceChildren();
    list.setAttribute('aria-busy', 'true');
    const loading = node('div', 'km-loading-state');
    const spinner = node('span', 'spinner-border spinner-border-sm');
    spinner.setAttribute('aria-hidden', 'true');
    loading.append(spinner, node('span', '', 'Memuat diskusi...'));
    list.append(loading);
}

function renderEmpty() {
    const empty = node('div', 'km-empty-state');
    const icon = node('i', 'bi bi-chat-square-text km-empty-state__icon');
    icon.setAttribute('aria-hidden', 'true');
    empty.append(
        icon,
        node('h3', 'km-empty-state__title', 'Belum ada insight'),
        node('p', 'km-empty-state__description', 'Jadilah yang pertama membagikan pembelajaran dari materi ini.'),
    );

    return empty;
}

function renderDeletePanel(insightId) {
    const panel = node('div', 'km-insight-delete-panel mt-3');
    panel.dataset.kmInsightDeletePanel = String(insightId);
    const id = `km-insight-delete-reason-${insightId}`;
    const label = node('label', 'form-label', 'Alasan penghapusan (wajib untuk moderator)');
    label.htmlFor = id;
    const input = node('textarea', 'form-control');
    input.id = id;
    input.rows = 2;
    input.maxLength = 500;
    input.dataset.kmInsightDeleteReason = '';
    const controls = node('div', 'd-flex justify-content-end gap-2 mt-2');
    controls.append(
        actionButton('Batal', 'cancel-delete', insightId, 'btn btn-outline-secondary btn-sm'),
        actionButton('Hapus insight', 'confirm-delete', insightId, 'btn btn-danger btn-sm'),
    );
    panel.append(label, input, controls);

    return panel;
}

function renderInsight(insight, isReply = false) {
    const card = node('article', 'km-insight-card');
    card.dataset.insightId = String(insight.id);
    card.dataset.reply = String(isReply);
    card.dataset.featured = String(insight.featured === true);

    const header = node('div', 'km-insight-card__header');
    const identity = node('div');
    const author = node('strong', '', insight.author?.name ?? 'Pengguna tidak tersedia');
    const meta = node(
        'span',
        'km-insight-card__meta ms-2',
        `${formatDate(insight.created_at)}${insight.edited_at ? ' \xB7 diedit' : ''}`,
    );
    identity.append(author, meta);
    header.append(identity);
    if (insight.featured) {
        const featuredDetails = [
            insight.featured_by ? `oleh ${insight.featured_by}` : '',
            insight.featured_at ? formatDate(insight.featured_at) : '',
        ].filter(Boolean).join(' \xB7 ');
        const featured = node(
            'span',
            'km-status km-status--pending',
            `Insight Pilihan${featuredDetails ? ` \xB7 ${featuredDetails}` : ''}`,
        );
        header.append(featured);
    }
    card.append(header);

    const content = node('p', 'km-insight-card__content', insight.content ?? '');
    card.append(content);
    if (insight.delete_reason) {
        card.append(node('p', 'km-insight-card__meta', `Alasan penghapusan: ${insight.delete_reason}`));
    }
    if ((insight.mentions ?? []).length > 0) {
        const mentions = insight.mentions.map((mention) => `@${mention.name}`).join(', ');
        card.append(node('p', 'km-insight-mentions', `Mention: ${mentions}`));
    }

    if (! insight.deleted) {
        const reactions = node('div', 'km-insight-reactions');
        (config.reactionTypes ?? []).forEach((reaction) => {
            const count = Number(insight.reactions?.[reaction] ?? 0);
            const button = actionButton(
                `${reactionLabels[reaction] ?? reaction} (${count})`,
                'reaction',
                insight.id,
                'btn btn-outline-secondary btn-sm km-insight-reaction',
            );
            button.dataset.reaction = reaction;
            button.setAttribute('aria-pressed', String(insight.viewer_reaction === reaction));
            reactions.append(button);
        });
        card.append(reactions);

        const actions = node('div', 'km-insight-card__actions');
        if (insight.permissions?.reply) {
            const reply = actionButton('Balas', 'reply', insight.id);
            reply.dataset.authorName = insight.author?.name ?? 'pengguna';
            actions.append(reply);
        }
        if (insight.permissions?.edit) {
            const edit = actionButton('Edit', 'edit', insight.id);
            edit.dataset.content = insight.content ?? '';
            edit.dataset.mentionIds = JSON.stringify(
                (insight.mentions ?? []).map((mention) => Number(mention.id)),
            );
            edit.dataset.mentions = JSON.stringify(
                (insight.mentions ?? []).map((mention) => ({
                    id: Number(mention.id),
                    name: mention.name,
                    email: mention.email ?? null,
                })),
            );
            actions.append(edit);
        }
        if (insight.permissions?.feature) {
            actions.append(actionButton(
                insight.featured ? 'Batalkan pilihan' : 'Jadikan pilihan',
                insight.featured ? 'unfeature' : 'feature',
                insight.id,
            ));
        }
        if (insight.permissions?.delete) {
            actions.append(actionButton('Hapus', 'delete', insight.id, 'btn btn-link btn-sm text-danger'));
        }
        card.append(actions);
    }

    (insight.replies ?? []).forEach((reply) => card.append(renderInsight(reply, true)));

    return card;
}

async function loadInsights({ append = false } = {}) {
    if (! currentDocumentId) {
        return;
    }
    if (! append) {
        currentPage = 1;
        renderLoading();
    }

    const requestedDocumentId = currentDocumentId;
    const list = modalElement.querySelector('[data-km-insight-list]');
    const more = modalElement.querySelector('[data-km-insight-more]');
    more.disabled = true;
    try {
        const url = new URL(documentEndpoint(config.insightIndexUrlTemplate), window.location.origin);
        url.searchParams.set('page', String(currentPage));
        url.searchParams.set('per_page', '10');
        const focusInsightId = Number.parseInt(
            new URLSearchParams(window.location.search).get('insight') ?? '',
            10,
        );
        if (Number.isInteger(focusInsightId)) {
            url.searchParams.set('focus_id', String(focusInsightId));
        }
        const payload = await request(url.toString());
        if (currentDocumentId !== requestedDocumentId) {
            return;
        }
        if (! append) {
            list.replaceChildren();
        }
        if (! append && payload.data.length === 0) {
            list.append(renderEmpty());
        } else {
            payload.data.forEach((insight) => list.append(renderInsight(insight)));
        }
        const insightTotal = Number.parseInt(payload.meta?.total ?? '', 10);
        if (Number.isInteger(insightTotal)) {
            document
                .querySelectorAll(`[data-km-insights-open][data-document-id="${currentDocumentId}"] [data-km-insight-count]`)
                .forEach((counter) => {
                    counter.textContent = String(insightTotal);
                });
        }
        lastPage = Number(payload.meta?.last_page ?? 1);
        more.hidden = currentPage >= lastPage;
        list.setAttribute('aria-busy', 'false');
        setFeedback();
        if (! append && Number.isInteger(focusInsightId)) {
            const target = list.querySelector(`[data-insight-id="${focusInsightId}"]`);
            if (target) {
                target.tabIndex = -1;
                target.scrollIntoView({ block: 'center' });
                target.focus({ preventScroll: true });
            }
        }
    } catch (error) {
        if (currentDocumentId !== requestedDocumentId) {
            return;
        }
        if (! append) {
            list.replaceChildren();
        }
        setFeedback(error.message ?? 'Insight belum dapat dimuat.', 'danger');
        more.hidden = true;
        list.setAttribute('aria-busy', 'false');
    } finally {
        more.disabled = false;
    }
}

function normalizeMentionUser(user) {
    const id = Number.parseInt(user?.id, 10);
    if (! Number.isInteger(id) || id <= 0) {
        return null;
    }

    return {
        id,
        name: String(user?.name ?? 'Pengguna tidak tersedia'),
        email: user?.email ? String(user.email) : '',
    };
}

function mentionLabel(user) {
    return user.name;
}

function setMentionStatus(message = '', tone = 'info') {
    const status = modalElement?.querySelector('[data-km-insight-mention-status]');
    if (! status) {
        return;
    }

    status.textContent = message;
    status.dataset.tone = tone;
}

function selectionSummary() {
    const count = selectedMentions.size;

    return count > 0 ? `${count} dari ${maximumMentions} pengguna dipilih.` : '';
}

function refreshMentionStatus(tone = 'info') {
    setMentionStatus(
        [mentionResultMessage, selectionSummary()].filter(Boolean).join(' '),
        tone,
    );
}

function renderMentionOptions() {
    const select = modalElement?.querySelector('[data-km-insight-mentions]');
    if (! select) {
        return;
    }

    const users = new Map();
    selectedMentions.forEach((user, id) => users.set(id, user));
    mentionResults.forEach((user) => {
        if (! users.has(user.id)) {
            users.set(user.id, user);
        }
    });

    select.replaceChildren();
    if (users.size === 0) {
        const empty = node('option', '', 'Tidak ada pengguna untuk ditampilkan.');
        empty.disabled = true;
        select.append(empty);
        return;
    }

    users.forEach((user, id) => {
        const option = node('option');
        const locked = lockedMentionIds.has(id);
        option.value = String(id);
        option.textContent = `${mentionLabel(user)}${locked ? ' - mention tersimpan' : ''}`;
        option.dataset.name = user.name;
        option.dataset.email = user.email;
        option.dataset.locked = String(locked);
        option.selected = selectedMentions.has(id);
        select.append(option);
    });
}

function syncMentionSelection(select) {
    select.querySelectorAll('option').forEach((option) => {
        const id = Number.parseInt(option.value, 10);
        if (lockedMentionIds.has(id)) {
            option.selected = true;
        }
    });

    let options = [...select.selectedOptions]
        .filter((option) => Number.isInteger(Number.parseInt(option.value, 10)));
    const exceededLimit = options.length > maximumMentions;
    if (exceededLimit) {
        const additions = options
            .filter((option) => ! selectedMentions.has(Number.parseInt(option.value, 10)))
            .reverse();
        while (options.length > maximumMentions && additions.length > 0) {
            additions.shift().selected = false;
            options = [...select.selectedOptions]
                .filter((option) => Number.isInteger(Number.parseInt(option.value, 10)));
        }
    }

    selectedMentions.clear();
    options.forEach((option) => {
        const user = normalizeMentionUser({
            id: option.value,
            name: option.dataset.name,
            email: option.dataset.email,
        });
        if (user) {
            selectedMentions.set(user.id, user);
        }
    });

    if (exceededLimit) {
        setMentionStatus(
            `Maksimal ${maximumMentions} pengguna dapat di-mention. ${selectionSummary()}`,
            'danger',
        );
    } else {
        refreshMentionStatus();
    }
}

async function loadMentions(query = '') {
    if (! currentDocumentId) {
        return;
    }

    mentionRequestController?.abort();
    const controller = new AbortController();
    mentionRequestController = controller;
    const requestedDocumentId = currentDocumentId;
    const select = modalElement.querySelector('[data-km-insight-mentions]');
    const search = modalElement.querySelector('[data-km-insight-mention-search]');
    const normalizedQuery = String(query).trim();
    select.disabled = true;
    search.setAttribute('aria-busy', 'true');
    mentionResultMessage = 'Memuat pengguna...';
    refreshMentionStatus();
    try {
        const url = new URL(documentEndpoint(config.insightMentionUrlTemplate), window.location.origin);
        if (normalizedQuery) {
            url.searchParams.set('q', normalizedQuery);
        }
        const payload = await request(url.toString(), { signal: controller.signal });
        if (currentDocumentId !== requestedDocumentId || mentionRequestController !== controller) {
            return;
        }

        mentionResults = (Array.isArray(payload.data) ? payload.data : [])
            .map(normalizeMentionUser)
            .filter(Boolean);
        renderMentionOptions();
        mentionResultMessage = mentionResults.length === 0
            ? 'Tidak ada pengguna ditemukan.'
            : `${mentionResults.length} pengguna ditampilkan.`;
        refreshMentionStatus();
    } catch (error) {
        if (error?.name === 'AbortError') {
            return;
        }
        mentionResultMessage = error.message ?? 'Daftar pengguna belum dapat dimuat.';
        refreshMentionStatus('danger');
    } finally {
        if (currentDocumentId === requestedDocumentId && mentionRequestController === controller) {
            select.disabled = false;
            search.removeAttribute('aria-busy');
        }
    }
}

function resetMentionPicker() {
    window.clearTimeout(mentionSearchTimer);
    mentionSearchTimer = null;
    mentionRequestController?.abort();
    mentionRequestController = null;
    mentionResults = [];
    mentionResultMessage = '';
    selectedMentions.clear();
    lockedMentionIds.clear();

    const search = modalElement?.querySelector('[data-km-insight-mention-search]');
    if (search) {
        search.value = '';
        search.removeAttribute('aria-busy');
    }
    renderMentionOptions();
    setMentionStatus();
}

function resetComposer() {
    const form = modalElement.querySelector('[data-km-insight-form]');
    form.reset();
    resetMentionPicker();
    editingInsightId = null;
    form.querySelector('[data-km-insight-parent]').value = '';
    form.querySelector('[data-km-insight-reply-context]').hidden = true;
    form.querySelector('[data-km-insight-submit]').textContent = 'Kirim insight';
    form.querySelector('[data-km-insight-mention-panel]').hidden = true;
    form.querySelector('[data-km-insight-mention-toggle]').setAttribute('aria-expanded', 'false');
}

function prepareReply(button) {
    resetComposer();
    const form = modalElement.querySelector('[data-km-insight-form]');
    form.querySelector('[data-km-insight-parent]').value = button.dataset.insightId;
    form.querySelector('[data-km-insight-reply-label]').textContent = `Membalas ${button.dataset.authorName}.`;
    form.querySelector('[data-km-insight-reply-context]').hidden = false;
    form.querySelector('[name="content"]').focus();
    void loadMentions();
}

function prepareEdit(button) {
    resetComposer();
    editingInsightId = button.dataset.insightId;
    const form = modalElement.querySelector('[data-km-insight-form]');
    form.querySelector('[data-km-insight-reply-label]').textContent = 'Mengedit insight Anda.';
    form.querySelector('[data-km-insight-reply-context]').hidden = false;
    form.querySelector('[data-km-insight-submit]').textContent = 'Simpan perubahan';
    const content = form.querySelector('[name="content"]');
    content.value = button.dataset.content ?? '';
    const mentions = JSON.parse(button.dataset.mentions ?? '[]')
        .map(normalizeMentionUser)
        .filter(Boolean);
    mentions.forEach((user) => {
        selectedMentions.set(user.id, user);
        lockedMentionIds.add(user.id);
    });
    renderMentionOptions();
    refreshMentionStatus();
    if (mentions.length > 0) {
        form.querySelector('[data-km-insight-mention-panel]').hidden = false;
        form.querySelector('[data-km-insight-mention-toggle]').setAttribute('aria-expanded', 'true');
    }
    void loadMentions();
    content.focus();
}

async function submitComposer(form) {
    const submit = form.querySelector('[data-km-insight-submit]');
    submit.disabled = true;
    submit.setAttribute('aria-busy', 'true');
    const mentionIds = [...selectedMentions.keys()];
    const body = {
        content: form.querySelector('[name="content"]').value,
        mention_ids: mentionIds,
    };
    const parentId = Number.parseInt(form.querySelector('[data-km-insight-parent]').value, 10);
    if (Number.isInteger(parentId)) {
        body.parent_id = parentId;
    }

    try {
        const payload = await request(
            editingInsightId
                ? insightEndpoint(config.insightUpdateUrlTemplate, editingInsightId)
                : documentEndpoint(config.insightStoreUrlTemplate),
            {
                method: editingInsightId ? 'PATCH' : 'POST',
                body: JSON.stringify(body),
            },
        );
        notify({ title: 'Insight tersimpan', message: payload.message, tone: 'success' });
        resetComposer();
        await loadInsights();
    } catch (error) {
        setFeedback(error.message ?? 'Insight belum dapat disimpan.', 'danger');
    } finally {
        submit.disabled = false;
        submit.removeAttribute('aria-busy');
    }
}

async function mutateInsight(button) {
    const insightId = button.dataset.insightId;
    const action = button.dataset.kmInsightAction;
    button.disabled = true;
    try {
        let payload;
        if (action === 'reaction') {
            const isActive = button.getAttribute('aria-pressed') === 'true';
            payload = await request(insightEndpoint(config.insightReactionUrlTemplate, insightId), {
                method: isActive ? 'DELETE' : 'PUT',
                body: isActive ? undefined : JSON.stringify({ reaction: button.dataset.reaction }),
            });
        } else if (action === 'feature' || action === 'unfeature') {
            payload = await request(insightEndpoint(config.insightFeatureUrlTemplate, insightId), {
                method: action === 'feature' ? 'POST' : 'DELETE',
                body: JSON.stringify({}),
            });
            notify({ title: 'Status insight diperbarui', message: payload.message, tone: 'success' });
        } else if (action === 'confirm-delete') {
            const panel = button.closest('[data-km-insight-delete-panel]');
            const reason = panel.querySelector('[data-km-insight-delete-reason]').value.trim();
            payload = await request(insightEndpoint(config.insightDeleteUrlTemplate, insightId), {
                method: 'DELETE',
                body: JSON.stringify({ reason: reason || null }),
            });
            notify({ title: 'Insight dihapus', message: payload.message, tone: 'success' });
        }
        await loadInsights();
    } catch (error) {
        setFeedback(error.message ?? 'Perubahan insight belum dapat disimpan.', 'danger');
    } finally {
        button.disabled = false;
    }
}

function openDeletePanel(button) {
    const card = button.closest('.km-insight-card');
    if (! card || card.querySelector('[data-km-insight-delete-panel]')) {
        return;
    }
    card.append(renderDeletePanel(button.dataset.insightId));
    card.querySelector('[data-km-insight-delete-reason]').focus();
}

function initEventHandlers() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-km-insights-open]');
        if (trigger) {
            triggeringElement = trigger;
            currentDocumentId = Number.parseInt(trigger.dataset.documentId, 10);
            modalElement.querySelector('#km-insight-modal-title').textContent = `Insight \u2014 ${trigger.dataset.title}`;
            resetComposer();
            setFeedback();
            modal.show();
            renderLoading();
            void loadMentions();
            void loadInsights();
            return;
        }

        const action = event.target.closest('[data-km-insight-action]');
        if (! action || ! modalElement.contains(action)) {
            return;
        }
        const type = action.dataset.kmInsightAction;
        if (type === 'reply') {
            prepareReply(action);
        } else if (type === 'edit') {
            prepareEdit(action);
        } else if (type === 'delete') {
            openDeletePanel(action);
        } else if (type === 'cancel-delete') {
            action.closest('[data-km-insight-delete-panel]')?.remove();
        } else {
            void mutateInsight(action);
        }
    });

    modalElement.querySelector('[data-km-insight-cancel-reply]').addEventListener('click', resetComposer);
    modalElement.querySelector('[data-km-insight-mention-toggle]').addEventListener('click', (event) => {
        const panel = modalElement.querySelector('[data-km-insight-mention-panel]');
        const expanded = event.currentTarget.getAttribute('aria-expanded') === 'true';
        event.currentTarget.setAttribute('aria-expanded', String(! expanded));
        panel.hidden = expanded;
        if (! expanded) {
            panel.querySelector('[data-km-insight-mention-search]')?.focus();
            if (mentionResults.length === 0) {
                void loadMentions();
            }
        }
    });
    modalElement.querySelector('[data-km-insight-mention-search]').addEventListener('input', (event) => {
        const query = event.currentTarget.value;
        window.clearTimeout(mentionSearchTimer);
        mentionResultMessage = 'Menyiapkan pencarian...';
        refreshMentionStatus();
        mentionSearchTimer = window.setTimeout(() => {
            void loadMentions(query);
        }, 300);
    });
    modalElement.querySelector('[data-km-insight-mentions]').addEventListener('change', (event) => {
        syncMentionSelection(event.currentTarget);
    });
    modalElement.querySelector('[data-km-insight-form]').addEventListener('submit', (event) => {
        event.preventDefault();
        void submitComposer(event.currentTarget);
    });
    modalElement.querySelector('[data-km-insight-more]').addEventListener('click', () => {
        if (currentPage < lastPage) {
            currentPage += 1;
            void loadInsights({ append: true });
        }
    });
    modalElement.addEventListener('hidden.bs.modal', () => {
        currentDocumentId = null;
        resetComposer();
        triggeringElement?.focus();
        triggeringElement = null;
    });
}

export function initInsights() {
    modalElement = document.getElementById('km-insight-modal');
    if (! modalElement) {
        return;
    }
    modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    initEventHandlers();
}
