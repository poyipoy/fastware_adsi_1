(function () {
    'use strict';

    const page = document.querySelector('[data-training-history-page]');
    const configElement = document.getElementById('trainingHistoryConfig');

    if (!page || !configElement) {
        return;
    }

    let config;

    try {
        config = JSON.parse(configElement.textContent);
    } catch (error) {
        console.error('Konfigurasi History Development tidak valid.', error);
        return;
    }

    const elements = {
        form: document.getElementById('historyFilterForm'),
        department: document.getElementById('department_id'),
        year: document.getElementById('year'),
        search: document.getElementById('searchInput'),
        filterButton: document.getElementById('btnFilter'),
        resetButton: document.getElementById('btnReset'),
        activeFilters: document.getElementById('activeFilters'),
        exportCsv: document.getElementById('exportCsv'),
        exportXlsx: document.getElementById('exportXlsx'),
        resultCount: document.getElementById('resultCount'),
        tableBody: document.getElementById('peopleDevTabel'),
        tableViewport: document.getElementById('historyTableViewport'),
        tableRange: document.getElementById('tableRange'),
        pageSize: document.getElementById('pageSize'),
        sortOrder: document.getElementById('sortOrder'),
        pagination: document.getElementById('historyPagination'),
        paginationSummary: document.getElementById('paginationSummary'),
        status: document.getElementById('historyStatus'),
    };

    if (Object.values(elements).some((element) => !element)) {
        console.error('Elemen History Development belum lengkap.');
        return;
    }

    const numberFormatter = new Intl.NumberFormat('id-ID');
    const state = {
        rows: [],
        total: 0,
        page: 1,
        pageSize: Number(config.page_size) || 25,
        sort: 'newest',
        filters: normalizeFilters(config.filters || {}),
        requestController: null,
    };

    function text(value, fallback) {
        const normalized = String(value === null || value === undefined ? '' : value).trim();

        return normalized || (fallback === undefined ? '-' : fallback);
    }

    function normalizeFilters(filters) {
        return {
            department_id: text(filters.department_id, ''),
            year: text(filters.year, ''),
            search: text(filters.search, ''),
        };
    }

    function normalizePayload(payload) {
        const data = Array.isArray(payload && payload.data) ? payload.data : [];
        const rows = data.map((row) => ({
            id: Number(row.id) || 0,
            npk: text(row.npk),
            employee_name: text(row.employee_name),
            program: text(row.program),
            category: text(row.category),
            competency: text(row.competency),
            institution: text(row.institution),
            period: text(row.period),
            year: Number(row.year) || 0,
            department_id: row.department_id === null || row.department_id === undefined
                ? ''
                : String(row.department_id),
            department_name: text(row.department_name),
            has_file: Boolean(row.has_file),
            can_download: Boolean(row.can_download),
            download_url: text(row.download_url, ''),
        }));

        return {
            data: rows,
            meta: {
                total: Number(payload && payload.meta && payload.meta.total) || rows.length,
            },
        };
    }

    function createElement(tagName, className, content) {
        const element = document.createElement(tagName);

        if (className) {
            element.className = className;
        }

        if (content !== undefined && content !== null) {
            element.textContent = content;
        }

        return element;
    }

    function appendIcon(parent, className) {
        const icon = createElement('i', className);
        icon.setAttribute('aria-hidden', 'true');
        parent.appendChild(icon);

        return icon;
    }

    function initialFor(name) {
        const normalized = text(name, '-');

        return normalized === '-' ? '-' : normalized.charAt(0).toUpperCase();
    }

    function categoryPresentation(category) {
        const original = text(category);
        const key = original.toLowerCase().replace(/[\s_-]+/g, '');

        if (key === 'technical') {
            return { label: 'Technical', tone: 'technical' };
        }

        if (['softskill', 'nontechnical', 'nonteknis'].includes(key)) {
            return { label: 'Soft Skill', tone: 'soft-skill' };
        }

        if (key === 'additional') {
            return { label: 'Additional', tone: 'additional' };
        }

        return { label: original, tone: 'neutral' };
    }

    function safeDownloadUrl(value) {
        if (!value) {
            return null;
        }

        try {
            const url = new URL(value, window.location.origin);

            return url.origin === window.location.origin ? url.href : null;
        } catch (error) {
            return null;
        }
    }

    function createEmployeeCell(row) {
        const cell = createElement('td');
        const wrapper = createElement('span', 'training-history-employee');
        const avatar = createElement('span', 'training-history-avatar', initialFor(row.employee_name));
        const name = createElement('span', '', row.employee_name);

        avatar.setAttribute('aria-hidden', 'true');
        wrapper.append(avatar, name);
        cell.appendChild(wrapper);

        return cell;
    }

    function createCategoryCell(category) {
        const presentation = categoryPresentation(category);
        const cell = createElement('td');
        const badge = createElement(
            'span',
            `training-history-category training-history-category--${presentation.tone}`,
            presentation.label,
        );

        cell.appendChild(badge);

        return cell;
    }

    function createFileCell(row) {
        const cell = createElement('td', 'text-center');

        if (!row.has_file) {
            const unavailable = createElement('span', 'training-history-file-state', 'Belum ada');
            appendIcon(unavailable, 'bi bi-file-earmark-x');
            unavailable.insertBefore(unavailable.lastChild, unavailable.firstChild);
            cell.appendChild(unavailable);

            return cell;
        }

        const downloadUrl = row.can_download ? safeDownloadUrl(row.download_url) : null;

        if (!downloadUrl) {
            const restricted = createElement('span', 'training-history-file-state', 'Akses terbatas');
            appendIcon(restricted, 'bi bi-lock');
            restricted.insertBefore(restricted.lastChild, restricted.firstChild);
            restricted.title = 'Anda tidak memiliki akses untuk mengunduh bukti ini.';
            cell.appendChild(restricted);

            return cell;
        }

        const link = createElement('a', 'btn btn-sm btn-outline-primary training-history-file-action');
        link.href = downloadUrl;
        link.setAttribute('aria-label', `Unduh bukti training ${row.employee_name}`);
        appendIcon(link, 'bi bi-download');
        link.appendChild(document.createTextNode('Unduh'));
        cell.appendChild(link);

        return cell;
    }

    function createRow(row) {
        const tableRow = document.createElement('tr');

        tableRow.append(
            createElement('td', '', row.npk),
            createEmployeeCell(row),
            createElement('td', '', row.program),
            createCategoryCell(row.category),
            createElement('td', '', row.competency),
            createElement('td', '', row.institution),
            createElement('td', '', row.period),
            createFileCell(row),
        );

        return tableRow;
    }

    function sortedRows() {
        const rows = [...state.rows];
        const collator = new Intl.Collator('id-ID', { sensitivity: 'base', numeric: true });

        rows.sort((left, right) => {
            switch (state.sort) {
                case 'oldest':
                    return (left.year - right.year) || (left.id - right.id);
                case 'name_asc':
                    return collator.compare(left.employee_name, right.employee_name)
                        || (right.year - left.year)
                        || (right.id - left.id);
                case 'program_asc':
                    return collator.compare(left.program, right.program)
                        || (right.year - left.year)
                        || (right.id - left.id);
                case 'newest':
                default:
                    return (right.year - left.year) || (right.id - left.id);
            }
        });

        return rows;
    }

    function pageSequence(currentPage, totalPages) {
        if (totalPages <= 7) {
            return Array.from({ length: totalPages }, (_, index) => index + 1);
        }

        const pages = new Set([1, totalPages, currentPage - 1, currentPage, currentPage + 1]);
        const sorted = [...pages]
            .filter((pageNumber) => pageNumber >= 1 && pageNumber <= totalPages)
            .sort((left, right) => left - right);
        const sequence = [];

        sorted.forEach((pageNumber, index) => {
            if (index > 0 && pageNumber - sorted[index - 1] > 1) {
                sequence.push('ellipsis');
            }

            sequence.push(pageNumber);
        });

        return sequence;
    }

    function createPaginationItem(label, options) {
        const item = createElement('li', 'page-item');
        const button = createElement('button', 'page-link', label);

        button.type = 'button';
        button.setAttribute('aria-label', options.ariaLabel || label);

        if (options.active) {
            item.classList.add('active');
            button.setAttribute('aria-current', 'page');
        }

        if (options.disabled) {
            item.classList.add('disabled');
            button.disabled = true;
        } else if (typeof options.onClick === 'function') {
            button.addEventListener('click', options.onClick);
        }

        item.appendChild(button);

        return item;
    }

    function goToPage(pageNumber) {
        const totalPages = Math.max(1, Math.ceil(state.rows.length / state.pageSize));
        state.page = Math.min(Math.max(1, pageNumber), totalPages);
        renderData();
        elements.tableViewport.scrollTop = 0;
        elements.tableViewport.focus({ preventScroll: true });
    }

    function renderPagination(totalPages) {
        elements.pagination.replaceChildren();

        if (state.rows.length === 0 || totalPages <= 1) {
            return;
        }

        elements.pagination.appendChild(createPaginationItem('‹', {
            ariaLabel: 'Halaman sebelumnya',
            disabled: state.page === 1,
            onClick: () => goToPage(state.page - 1),
        }));

        pageSequence(state.page, totalPages).forEach((pageNumber) => {
            if (pageNumber === 'ellipsis') {
                elements.pagination.appendChild(createPaginationItem('…', {
                    ariaLabel: 'Halaman lainnya',
                    disabled: true,
                }));
                return;
            }

            elements.pagination.appendChild(createPaginationItem(String(pageNumber), {
                active: pageNumber === state.page,
                ariaLabel: `Halaman ${pageNumber}`,
                onClick: () => goToPage(pageNumber),
            }));
        });

        elements.pagination.appendChild(createPaginationItem('›', {
            ariaLabel: 'Halaman berikutnya',
            disabled: state.page === totalPages,
            onClick: () => goToPage(state.page + 1),
        }));
    }

    function setStateRow(type, message) {
        const row = document.createElement('tr');
        const cell = createElement('td', 'training-history-state');
        const content = createElement('div');

        cell.colSpan = 8;

        if (type === 'loading') {
            const spinner = createElement('span', 'spinner-border spinner-border-sm text-primary');
            spinner.setAttribute('role', 'status');
            spinner.setAttribute('aria-hidden', 'true');
            content.appendChild(spinner);
        } else {
            appendIcon(content, type === 'error' ? 'bi bi-exclamation-circle fs-4' : 'bi bi-inbox fs-4');
        }

        content.appendChild(createElement('span', '', message));

        if (type === 'error') {
            const retry = createElement('button', 'btn btn-outline-primary btn-sm', 'Coba Lagi');
            retry.type = 'button';
            retry.addEventListener('click', loadFilteredData);
            content.appendChild(retry);
        }

        cell.appendChild(content);
        row.appendChild(cell);
        elements.tableBody.replaceChildren(row);
    }

    function renderData() {
        const rows = sortedRows();
        const totalRows = rows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / state.pageSize));
        state.page = Math.min(state.page, totalPages);

        elements.resultCount.textContent = `${numberFormatter.format(state.total)} Data`;

        if (totalRows === 0) {
            setStateRow('empty', 'Tidak ada data yang sesuai dengan filter.');
            elements.tableRange.textContent = 'Tidak ada data untuk ditampilkan.';
            elements.paginationSummary.textContent = '0 data';
            renderPagination(0);
            setExportAvailability(false);
            announce('Tidak ada data yang sesuai dengan filter.');
            return;
        }

        const startIndex = (state.page - 1) * state.pageSize;
        const endIndex = Math.min(startIndex + state.pageSize, totalRows);
        const fragment = document.createDocumentFragment();

        rows.slice(startIndex, endIndex).forEach((row) => {
            fragment.appendChild(createRow(row));
        });

        elements.tableBody.replaceChildren(fragment);
        elements.tableRange.textContent = `Menampilkan ${numberFormatter.format(startIndex + 1)}–${numberFormatter.format(endIndex)} dari ${numberFormatter.format(state.total)} data`;
        elements.paginationSummary.textContent = `Halaman ${numberFormatter.format(state.page)} dari ${numberFormatter.format(totalPages)}`;
        renderPagination(totalPages);
        setExportAvailability(true);
    }

    function announce(message) {
        elements.status.textContent = '';
        window.setTimeout(() => {
            elements.status.textContent = message;
        }, 20);
    }

    function readFilters() {
        return normalizeFilters({
            department_id: elements.department.value,
            year: elements.year.value,
            search: elements.search.value,
        });
    }

    function queryFromFilters(filters) {
        const query = new URLSearchParams();

        Object.entries(filters).forEach(([key, value]) => {
            if (value) {
                query.set(key, value);
            }
        });

        return query;
    }

    function updateBrowserUrl() {
        const url = new URL(window.location.href);

        ['department_id', 'year', 'search'].forEach((key) => url.searchParams.delete(key));
        queryFromFilters(state.filters).forEach((value, key) => url.searchParams.set(key, value));
        window.history.replaceState({}, '', url);
    }

    function endpointWithFilters(endpoint) {
        const url = new URL(endpoint, window.location.origin);
        queryFromFilters(state.filters).forEach((value, key) => url.searchParams.set(key, value));

        return url.href;
    }

    function setExportAvailability(available) {
        [
            [elements.exportCsv, config.endpoints.export_csv],
            [elements.exportXlsx, config.endpoints.export_xlsx],
        ].forEach(([link, endpoint]) => {
            link.href = endpointWithFilters(endpoint);
            link.classList.toggle('disabled', !available);
            link.setAttribute('aria-disabled', available ? 'false' : 'true');
            link.tabIndex = available ? 0 : -1;
        });
    }

    function departmentLabel(departmentId) {
        const option = [...elements.department.options]
            .find((candidate) => candidate.value === String(departmentId));

        return option ? option.textContent.trim() : departmentId;
    }

    function createFilterChip(key, label) {
        const chip = createElement('span', 'training-history-filter-chip');
        const remove = createElement('button');

        chip.appendChild(document.createTextNode(label));
        remove.type = 'button';
        remove.setAttribute('aria-label', `Hapus filter ${label}`);
        appendIcon(remove, 'bi bi-x-lg');
        remove.addEventListener('click', () => {
            if (key === 'department_id') {
                elements.department.value = '';
            } else if (key === 'year') {
                elements.year.value = '';
            } else if (key === 'search') {
                elements.search.value = '';
            }

            loadFilteredData();
        });
        chip.appendChild(remove);

        return chip;
    }

    function renderActiveFilters() {
        const fragment = document.createDocumentFragment();

        if (state.filters.department_id) {
            fragment.appendChild(createFilterChip(
                'department_id',
                `Departemen: ${departmentLabel(state.filters.department_id)}`,
            ));
        }

        if (state.filters.year) {
            fragment.appendChild(createFilterChip('year', `Tahun: ${state.filters.year}`));
        }

        if (state.filters.search) {
            fragment.appendChild(createFilterChip('search', `Pencarian: ${state.filters.search}`));
        }

        elements.activeFilters.replaceChildren(fragment);
    }

    function applyPayload(payload) {
        const normalized = normalizePayload(payload);
        state.rows = normalized.data;
        state.total = normalized.meta.total;
        state.page = 1;
        renderData();
        renderActiveFilters();
        setExportAvailability(state.total > 0);
    }

    function setLoading(loading) {
        elements.filterButton.disabled = loading;
        elements.resetButton.disabled = loading;
        elements.form.setAttribute('aria-busy', loading ? 'true' : 'false');

        const icon = elements.filterButton.querySelector('i');
        const label = elements.filterButton.querySelector('span');

        if (icon) {
            icon.className = loading
                ? 'spinner-border spinner-border-sm'
                : 'bi bi-funnel';
        }

        if (label) {
            label.textContent = loading ? 'Memuat...' : 'Terapkan Filter';
        }
    }

    async function loadFilteredData() {
        const filters = readFilters();

        if (state.requestController) {
            state.requestController.abort();
        }

        const controller = new AbortController();
        state.requestController = controller;
        setLoading(true);
        setStateRow('loading', 'Memuat data History Development...');
        setExportAvailability(false);
        announce('Memuat data History Development.');

        try {
            const url = new URL(config.endpoints.filter, window.location.origin);
            queryFromFilters(filters).forEach((value, key) => url.searchParams.set(key, value));
            const response = await window.fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error(response.status === 422
                    ? 'Filter yang dimasukkan belum valid.'
                    : 'Data belum dapat dimuat.');
            }

            const payload = await response.json();
            state.filters = filters;
            applyPayload(payload);
            updateBrowserUrl();
            announce(`${numberFormatter.format(state.total)} data berhasil dimuat.`);
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            console.error(error);
            state.rows = [];
            state.total = 0;
            state.page = 1;
            setStateRow('error', `${error.message} Silakan coba kembali.`);
            elements.resultCount.textContent = '– Data';
            elements.tableRange.textContent = 'Data belum dapat dimuat.';
            elements.paginationSummary.textContent = 'Data belum tersedia';
            elements.pagination.replaceChildren();
            setExportAvailability(false);
            announce('Data History Development gagal dimuat.');
        } finally {
            if (state.requestController === controller) {
                state.requestController = null;
                setLoading(false);
            }
        }
    }

    elements.form.addEventListener('submit', (event) => {
        event.preventDefault();
        loadFilteredData();
    });

    elements.resetButton.addEventListener('click', () => {
        elements.department.value = '';
        elements.year.value = '';
        elements.search.value = '';
        loadFilteredData();
    });

    elements.pageSize.addEventListener('change', () => {
        state.pageSize = Number(elements.pageSize.value) || 25;
        state.page = 1;
        renderData();
    });

    elements.sortOrder.addEventListener('change', () => {
        state.sort = elements.sortOrder.value;
        state.page = 1;
        renderData();
    });

    [elements.exportCsv, elements.exportXlsx].forEach((link) => {
        link.addEventListener('click', (event) => {
            if (link.getAttribute('aria-disabled') === 'true') {
                event.preventDefault();
            }
        });
    });

    elements.pageSize.value = String(state.pageSize);
    applyPayload(config.initial || { data: [], meta: { total: 0 } });
}());
