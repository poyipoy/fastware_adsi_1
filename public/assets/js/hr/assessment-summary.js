(() => {
    'use strict';

    const escapeText = (input) => String(input ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[char]));
    const node = (tag, content) => {
        const element = document.createElement(tag);
        if (content !== undefined) element.textContent = content;
        return element;
    };

    function render(container, payload) {
        container.replaceChildren();
        [
            ['tcs', 'Technical Competency', 'Keterangan Competency', 'keterangan_tc', 'deskripsi_tc'],
            ['softSkills', 'Soft Skills', 'Keterangan Soft Skills', 'keterangan_sk', 'deskripsi_sk'],
            ['additionals', 'Additionals', 'Keterangan Additional', 'keterangan_ad', 'deskripsi_ad'],
        ].forEach(([key, title, nameLabel, nameField, descriptionField]) => {
            const masters = Array.isArray(payload[key]) ? payload[key] : [];
            if (!masters.length) return;
            container.append(node('h3', title));
            const table = node('table');
            table.className = 'styled-table';
            table.style.width = '100%';
            table.style.borderCollapse = 'collapse';
            const head = node('thead');
            const main = node('tr');
            [nameLabel, 'Deskripsi'].forEach((label, index) => {
                const cell = node('th', label);
                cell.rowSpan = 2;
                cell.style.width = index === 0 ? '15%' : '20%';
                cell.style.textAlign = 'left';
                cell.style.padding = '8px';
                cell.style.verticalAlign = 'middle';
                main.append(cell);
            });
            const category = node('th', 'Judul Keterangan Kategori');
            category.colSpan = 4;
            category.style.textAlign = 'center';
            category.style.padding = '8px';
            main.append(category);
            const standard = node('th', 'Nilai Standar');
            standard.rowSpan = 2;
            standard.style.width = '5%';
            standard.style.textAlign = 'left';
            standard.style.padding = '8px';
            standard.style.verticalAlign = 'middle';
            main.append(standard);
            const scores = node('tr');
            for (let score = 1; score <= 4; score++) {
                const cell = node('th', 'Nilai ' + score);
                cell.style.width = '15%';
                cell.style.textAlign = 'left';
                cell.style.padding = '8px';
                scores.append(cell);
            }
            head.append(main, scores);
            const body = node('tbody');
            masters.forEach((master) => {
                const rubric = master.poin_kategori || {};
                const row = node('tr');
                const name = node('td', master[nameField] ?? '-');
                const badge = node('span', '(' + (rubric.judul_keterangan ?? '-') + ')');
                badge.style.fontSize = '0.85em';
                badge.style.padding = '2px 4px';
                badge.style.borderRadius = '4px';
                const color = { 1: 'blue', 2: 'green', 3: 'orange' }[rubric.id];
                if (color) {
                    badge.style.backgroundColor = color;
                    badge.style.color = 'white';
                }
                name.append(node('br'), badge);
                row.append(name, node('td', master[descriptionField] ?? '-'));
                for (let score = 1; score <= 4; score++) row.append(node('td', rubric['deskripsi_' + score] ?? '-'));
                row.append(node('td', master.nilai ?? '-'));
                body.append(row);
            });
            table.append(head, body);
            container.append(table);
        });
        if (!container.children.length) container.textContent = 'Belum ada data kompetensi untuk posisi ini.';
    }

    window.HrAssessmentSummary = { escapeText, render };
    document.addEventListener('DOMContentLoaded', () => {
        // The legacy shell loads Bootstrap more than once. Keep this panel out
        // of its delegated collapse handlers so one click changes state once.
        const guidanceToggle = document.querySelector('[data-assessment-guidance-toggle]');
        const guidance = guidanceToggle && document.getElementById(guidanceToggle.getAttribute('aria-controls'));
        if (guidanceToggle && guidance) {
            guidanceToggle.addEventListener('click', () => {
                const expanded = !guidance.classList.contains('show');
                guidance.classList.toggle('show', expanded);
                guidance.hidden = !expanded;
                guidanceToggle.classList.toggle('collapsed', !expanded);
                guidanceToggle.setAttribute('aria-expanded', String(expanded));
            });
        }

        const button = document.getElementById('openModalButton');
        const container = document.getElementById('details');
        const script = document.querySelector('script[data-assessment-summary]');
        if (!button || !container || !script) return;
        const select = document.getElementById('jobPositionSelect');
        const position = () => document.getElementById('jobPositionSelectId')?.value || select?.value || '';
        let request;
        let version = 0;
        const clear = () => {
            version++;
            request?.abort();
            container.replaceChildren();
        };
        select?.addEventListener('change', clear);
        document.getElementById('jobPositionModal')?.addEventListener('hidden.bs.modal', clear);
        button.addEventListener('click', async () => {
            clear();
            const current = version;
            const id = position();
            if (!id) {
                container.textContent = 'Pilih posisi terlebih dahulu!';
                return;
            }
            container.textContent = 'Memuat data…';
            request = new AbortController();
            try {
                const response = await fetch(script.dataset.endpoint.replace('__POSITION__', encodeURIComponent(id)), {
                    headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: request.signal,
                });
                if (!response.ok) throw new Error('Summary request failed');
                const data = await response.json();
                if (current !== version || position() !== id) return;
                render(container, data);
            } catch (error) {
                if (current === version && error.name !== 'AbortError') {
                    container.textContent = 'Gagal mengambil data. Coba lagi.';
                }
            }
        });
    });
})();
