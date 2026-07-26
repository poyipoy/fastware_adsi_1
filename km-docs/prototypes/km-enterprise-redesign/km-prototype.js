(() => {
    'use strict';

    const ROLE_LABELS = {
        employee: 'Employee / Knowledge Consumer',
        contributor: 'Knowledge Contributor',
        reviewer: 'Reviewer (konsep)',
        approver: 'Approver',
        manager: 'Knowledge Manager (konsep)',
        admin: 'Administrator',
    };

    const ROLE_DASHBOARDS = {
        employee: 'employee-dashboard',
        contributor: 'contributor-dashboard',
        reviewer: 'reviewer-dashboard',
        approver: 'approver-dashboard',
        manager: 'admin-dashboard',
        admin: 'admin-dashboard',
    };

    const SCREEN_DEFINITIONS = [
        { id: 'login', group: 'Akses', nav: 'Login', title: 'Masuk ke Knowledge Management', icon: 'bi-shield-lock', availability: 'current', roles: ['employee', 'contributor', 'reviewer', 'approver', 'manager', 'admin'] },
        { id: 'employee-dashboard', group: 'Dashboard', nav: 'Dashboard Employee', title: 'Knowledge untuk pekerjaan Anda', icon: 'bi-grid-1x2', availability: 'current', roles: ['employee', 'admin'] },
        { id: 'contributor-dashboard', group: 'Dashboard', nav: 'Dashboard Contributor', title: 'Ringkasan kontribusi knowledge', icon: 'bi-pencil-square', availability: 'partial', decisions: ['KM-DEC-003'], roles: ['contributor', 'manager', 'admin'] },
        { id: 'reviewer-dashboard', group: 'Dashboard', nav: 'Dashboard Reviewer', title: 'Workspace reviewer', icon: 'bi-clipboard2-check', availability: 'concept', decisions: ['KM-DEC-003'], roles: ['reviewer', 'manager', 'admin'] },
        { id: 'approver-dashboard', group: 'Dashboard', nav: 'Dashboard Approver', title: 'Keputusan yang menunggu Anda', icon: 'bi-check2-square', availability: 'partial', decisions: ['KM-DEC-003'], roles: ['approver', 'manager', 'admin'] },
        { id: 'admin-dashboard', group: 'Dashboard', nav: 'Dashboard Admin', title: 'Kesehatan operasional Knowledge Management', icon: 'bi-speedometer2', availability: 'partial', decisions: ['KM-DEC-005', 'KM-DEC-009'], roles: ['manager', 'admin'] },
        { id: 'knowledge-library', group: 'Temukan Knowledge', nav: 'Knowledge Library', title: 'Knowledge Library', icon: 'bi-collection', availability: 'current', roles: ['employee', 'contributor', 'reviewer', 'approver', 'manager', 'admin'] },
        { id: 'search-results', group: 'Temukan Knowledge', nav: 'Hasil Pencarian', title: 'Hasil pencarian knowledge', icon: 'bi-search', availability: 'current', roles: ['employee', 'contributor', 'reviewer', 'approver', 'manager', 'admin'] },
        { id: 'knowledge-detail', group: 'Temukan Knowledge', nav: 'Detail Knowledge', title: 'Standar Penanganan Material Tool Steel', icon: 'bi-file-earmark-text', availability: 'partial', decisions: ['KM-DEC-001', 'KM-DEC-014'], roles: ['employee', 'contributor', 'reviewer', 'approver', 'manager', 'admin'] },
        { id: 'create-knowledge', group: 'Kontribusi Saya', nav: 'Buat Knowledge', title: 'Buat knowledge baru', icon: 'bi-file-earmark-plus', availability: 'partial', decisions: ['KM-DEC-003', 'KM-DEC-014'], roles: ['contributor', 'manager', 'admin'] },
        { id: 'review-submit', group: 'Kontribusi Saya', nav: 'Review & Submit', title: 'Tinjau sebelum mengirim', icon: 'bi-send-check', availability: 'partial', decisions: ['KM-DEC-003'], roles: ['contributor', 'manager', 'admin'] },
        { id: 'submission-success', group: 'Kontribusi Saya', nav: 'Submission Success', title: 'Pengajuan berhasil dikirim', icon: 'bi-check-circle', availability: 'current', roles: ['contributor', 'manager', 'admin'] },
        { id: 'my-drafts', group: 'Kontribusi Saya', nav: 'My Drafts', title: 'Draf saya', icon: 'bi-file-earmark', availability: 'current', roles: ['contributor', 'manager', 'admin'] },
        { id: 'my-submissions', group: 'Kontribusi Saya', nav: 'My Submissions', title: 'Pengajuan saya', icon: 'bi-inboxes', availability: 'partial', decisions: ['KM-DEC-003', 'KM-DEC-001'], roles: ['contributor', 'manager', 'admin'] },
        { id: 'submission-detail', group: 'Kontribusi Saya', nav: 'Submission Detail', title: 'Detail pengajuan KM-2026-0148', icon: 'bi-card-checklist', availability: 'partial', decisions: ['KM-DEC-003'], roles: ['contributor', 'reviewer', 'approver', 'manager', 'admin'] },
        { id: 'revision-detail', group: 'Kontribusi Saya', nav: 'Revision Detail', title: 'Revisi yang perlu diselesaikan', icon: 'bi-arrow-repeat', availability: 'partial', decisions: ['KM-DEC-001', 'KM-DEC-003'], roles: ['contributor', 'reviewer', 'manager', 'admin'] },
        { id: 'review-queue', group: 'Tugas & Keputusan', nav: 'Review Queue', title: 'Review Queue', icon: 'bi-list-check', availability: 'concept', decisions: ['KM-DEC-003'], roles: ['reviewer', 'manager', 'admin'] },
        { id: 'review-workspace', group: 'Tugas & Keputusan', nav: 'Review Workspace', title: 'Review: Instruksi Inspeksi Incoming Material', icon: 'bi-layout-three-columns', availability: 'concept', decisions: ['KM-DEC-001', 'KM-DEC-003', 'KM-DEC-007'], roles: ['reviewer', 'manager', 'admin'] },
        { id: 'approval-queue', group: 'Tugas & Keputusan', nav: 'Approval Queue', title: 'Approval Queue', icon: 'bi-check2-all', availability: 'partial', decisions: ['KM-DEC-003'], roles: ['approver', 'manager', 'admin'] },
        { id: 'approval-workspace', group: 'Tugas & Keputusan', nav: 'Approval Workspace', title: 'Persetujuan: Prosedur Heat Treatment Vacuum', icon: 'bi-person-check', availability: 'partial', decisions: ['KM-DEC-003'], roles: ['approver', 'manager', 'admin'] },
        { id: 'version-comparison', group: 'Tugas & Keputusan', nav: 'Version Comparison', title: 'Perbandingan versi', icon: 'bi-files', availability: 'concept', decisions: ['KM-DEC-001'], roles: ['contributor', 'reviewer', 'approver', 'manager', 'admin'] },
        { id: 'notifications', group: 'Monitor', nav: 'Notifications', title: 'Pusat notifikasi', icon: 'bi-bell', availability: 'concept', decisions: ['KM-DEC-006'], roles: ['employee', 'contributor', 'reviewer', 'approver', 'manager', 'admin'] },
        { id: 'analytics', group: 'Monitor', nav: 'Analytics & Reports', title: 'Analytics dan laporan operasional', icon: 'bi-bar-chart-line', availability: 'partial', decisions: ['KM-DEC-009'], roles: ['approver', 'manager', 'admin'] },
        { id: 'user-management', group: 'Administrasi', nav: 'User Management', title: 'Pengguna dan akses KM', icon: 'bi-people', availability: 'concept', decisions: ['KM-DEC-005'], roles: ['admin'] },
        { id: 'role-permissions', group: 'Administrasi', nav: 'Role & Permission', title: 'Role dan permission matrix', icon: 'bi-person-lock', availability: 'concept', decisions: ['KM-DEC-003', 'KM-DEC-005'], roles: ['admin'] },
        { id: 'category-management', group: 'Administrasi', nav: 'Category Management', title: 'Kategori dan taxonomy', icon: 'bi-diagram-3', availability: 'concept', decisions: ['KM-DEC-005', 'KM-DEC-014'], roles: ['manager', 'admin'] },
        { id: 'workflow-config', group: 'Administrasi', nav: 'Workflow Configuration', title: 'Konfigurasi approval workflow', icon: 'bi-bezier2', availability: 'concept', decisions: ['KM-DEC-003'], roles: ['admin'] },
        { id: 'audit-log', group: 'Administrasi', nav: 'Audit Log', title: 'Audit log', icon: 'bi-journal-check', availability: 'partial', decisions: ['KM-DEC-014'], roles: ['manager', 'admin'] },
        { id: 'empty-states', group: 'System States', nav: 'Empty & Loading States', title: 'Empty, loading, dan success states', icon: 'bi-box', availability: 'current', roles: ['employee', 'contributor', 'reviewer', 'approver', 'manager', 'admin'] },
        { id: 'error-states', group: 'System States', nav: 'Error & Permission', title: 'Error dan permission-denied states', icon: 'bi-exclamation-octagon', availability: 'current', roles: ['employee', 'contributor', 'reviewer', 'approver', 'manager', 'admin'] },
    ];

    const state = {
        role: 'approver',
        designMode: true,
        activeScreen: 'approver-dashboard',
        lastSearch: 'prosedur heat treatment',
    };

    const root = document.getElementById('screen-root');
    const navRoot = document.getElementById('prototype-nav');
    const main = document.getElementById('prototype-main');
    const dialog = document.getElementById('prototype-dialog');
    const dialogTitle = document.getElementById('prototype-dialog-title');
    const dialogDescription = document.getElementById('prototype-dialog-description');
    const dialogBody = document.getElementById('prototype-dialog-body');
    const dialogConfirm = document.getElementById('prototype-dialog-confirm');
    const toastRegion = document.getElementById('toast-region');

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function icon(name, label = '') {
        return `<i class="bi ${name}" aria-hidden="true"></i>${label ? `<span class="km-visually-hidden">${escapeHtml(label)}</span>` : ''}`;
    }

    function status(label, kind = 'neutral') {
        return `<span class="km-status km-status-${kind}">${escapeHtml(label)}</span>`;
    }

    function tag(label) {
        return `<span class="km-tag">${escapeHtml(label)}</span>`;
    }

    function button(label, options = {}) {
        const variant = options.variant || 'secondary';
        const size = options.size === 'sm' ? ' km-btn-sm' : '';
        const iconHtml = options.icon ? icon(options.icon) : '';
        const attrs = [
            options.target ? `data-screen-target="${options.target}"` : '',
            options.action ? `data-demo-action="${escapeHtml(options.action)}"` : '',
            options.confirm ? `data-confirm="${escapeHtml(options.confirm)}"` : '',
            options.confirmTitle ? `data-confirm-title="${escapeHtml(options.confirmTitle)}"` : '',
            options.disabled ? 'disabled aria-disabled="true"' : '',
        ].filter(Boolean).join(' ');
        const type = options.submit ? 'submit' : 'button';
        return `<button class="km-btn km-btn-${variant}${size}" type="${type}" ${attrs}>${iconHtml}<span>${escapeHtml(label)}</span></button>`;
    }

    function pageHeading(meta, description, actions = '') {
        return `
            <header class="km-page-heading">
                <div>
                    <ol class="km-breadcrumb" aria-label="Breadcrumb">
                        <li>Knowledge Management</li>
                        <li>${escapeHtml(meta.group)}</li>
                    </ol>
                    <p class="km-eyebrow">${meta.availability === 'current' ? 'Pola siap saat ini' : meta.availability === 'partial' ? 'Pola transisi' : 'Future-state concept'}</p>
                    <h1>${escapeHtml(meta.title)}</h1>
                    <p class="km-page-description">${escapeHtml(description)}</p>
                </div>
                ${actions ? `<div class="km-page-actions">${actions}</div>` : ''}
            </header>`;
    }

    function conceptBanner(meta) {
        if (meta.availability === 'current') {
            return '';
        }
        const decisionText = (meta.decisions || []).join(', ');
        const lead = meta.availability === 'partial'
            ? 'Sebagian pola pada layar ini dapat memakai kemampuan sekarang; elemen lanjutan tetap konseptual.'
            : 'Layar ini adalah prototype konseptual dan belum boleh menjadi workflow produksi.';
        return `
            <div class="km-concept-banner" role="note">
                ${icon('bi-lock')}
                <div>
                    <strong>${lead}</strong>
                    <p>Gate keputusan terkait: ${escapeHtml(decisionText || 'mission lanjutan yang disetujui stakeholder')}.</p>
                </div>
            </div>`;
    }

    function card(title, body, options = {}) {
        const description = options.description ? `<p class="km-card-description">${options.description}</p>` : '';
        const headerAction = options.headerAction || '';
        const footer = options.footer ? `<div class="km-card-footer">${options.footer}</div>` : '';
        const extraClass = options.className ? ` ${options.className}` : '';
        return `
            <section class="km-card${extraClass}">
                <div class="km-card-header">
                    <div><h2 class="km-card-title">${title}</h2>${description}</div>
                    ${headerAction}
                </div>
                <div class="km-card-body">${body}</div>
                ${footer}
            </section>`;
    }

    function kpi(label, value, meta, options = {}) {
        const tone = options.tone ? ` is-${options.tone}` : '';
        return `
            <article class="km-card km-kpi-card${tone}">
                <div>
                    <p class="km-kpi-label">${escapeHtml(label)}</p>
                    <strong class="km-kpi-value">${escapeHtml(value)}</strong>
                    <span class="km-kpi-meta">${options.metaIcon ? icon(options.metaIcon) : ''}${escapeHtml(meta)}</span>
                </div>
                <span class="km-kpi-icon">${icon(options.icon || 'bi-graph-up')}</span>
            </article>`;
    }

    function sectionHeading(title, description = '', action = '') {
        return `
            <div class="km-section-heading">
                <div><h2>${title}</h2>${description ? `<p>${description}</p>` : ''}</div>
                ${action}
            </div>`;
    }

    function table(headers, rows, caption) {
        const head = headers.map((item) => `<th scope="col">${item}</th>`).join('');
        return `
            <div class="km-table-shell">
                <table class="km-table">
                    <caption>${caption}</caption>
                    <thead><tr>${head}</tr></thead>
                    <tbody>${rows.join('')}</tbody>
                </table>
            </div>`;
    }

    function cellTitle(title, meta) {
        return `<span class="km-cell-title"><strong>${title}</strong><span>${meta}</span></span>`;
    }

    function timeline(items) {
        return `<ol class="km-timeline">${items.map((item) => `
            <li class="km-timeline-item ${item.state ? `is-${item.state}` : ''}">
                <span class="km-timeline-marker">${icon(item.state === 'complete' ? 'bi-check-lg' : item.state === 'current' ? 'bi-circle-fill' : 'bi-circle')}</span>
                <span class="km-timeline-copy"><strong>${item.title}</strong><span>${item.meta}</span></span>
            </li>`).join('')}</ol>`;
    }

    function trendChart(id, label, summary) {
        return `
            <div class="km-chart-wrap">
                <svg class="km-chart" viewBox="0 0 640 190" role="img" aria-labelledby="${id}-title ${id}-desc">
                    <title id="${id}-title">${label}</title>
                    <desc id="${id}-desc">${summary}</desc>
                    <path class="km-chart-grid" d="M30 30H620M30 80H620M30 130H620M30 180H620"></path>
                    <path class="km-chart-area" d="M30 154 L115 142 L200 126 L285 133 L370 93 L455 104 L540 58 L620 46 L620 180 L30 180 Z"></path>
                    <path class="km-chart-line" d="M30 154 L115 142 L200 126 L285 133 L370 93 L455 104 L540 58 L620 46"></path>
                    <circle class="km-chart-point" cx="620" cy="46" r="5"></circle>
                </svg>
                <p class="km-helper">${summary}</p>
            </div>`;
    }

    function barList(items) {
        return `<div class="km-bar-list">${items.map((item) => `
            <div class="km-bar-row">
                <span>${item.label}</span>
                <span class="km-bar-track" aria-hidden="true"><span style="width:${item.value}%"></span></span>
                <strong>${item.display || `${item.value}%`}</strong>
            </div>`).join('')}</div>`;
    }

    function knowledgeCard(item) {
        return `
            <article class="km-card km-knowledge-card">
                <div class="km-document-cover">${icon(item.icon || 'bi-file-earmark-pdf')}</div>
                <div class="km-card-body">
                    <div class="km-tag-row">${status(item.status || 'Terbit', item.statusKind || 'success')}${tag(item.category)}</div>
                    <h3 style="margin-top:var(--km-space-3)">${item.title}</h3>
                    <p>${item.summary}</p>
                    <div class="km-meta-row"><span>${icon('bi-person')} ${item.owner}</span><span>${icon('bi-clock')} ${item.updated}</span></div>
                </div>
                <div class="km-card-footer">
                    <span class="km-meta-row" style="margin:0">${icon('bi-eye')} ${item.views} dilihat · v${item.version || '1.0'}</span>
                    ${button('Preview', { size: 'sm', target: 'knowledge-detail' })}
                </div>
            </article>`;
    }

    function taskList(items) {
        return `<ul class="km-list">${items.map((item) => `
            <li class="km-list-item">
                <span class="km-list-icon">${icon(item.icon || 'bi-file-earmark-text')}</span>
                <span class="km-list-copy"><strong>${item.title}</strong><span>${item.meta}</span></span>
                ${item.status ? status(item.status, item.kind || 'neutral') : ''}
            </li>`).join('')}</ul>`;
    }

    function searchHero() {
        return `
            <section class="km-search-hero">
                <h2>Temukan panduan yang tepat sebelum memulai pekerjaan</h2>
                <p>Cari pada judul dan sinopsis knowledge terbit yang sesuai dengan hak akses Anda.</p>
                <form class="km-global-search" data-inline-search role="search">
                    <label class="km-visually-hidden" for="employee-search">Cari knowledge</label>
                    ${icon('bi-search')}
                    <input id="employee-search" type="search" value="" placeholder="Contoh: inspeksi material SKD11" maxlength="100">
                </form>
            </section>`;
    }

    const renderers = {
        login() {
            return `
                <section class="km-login-stage" aria-labelledby="login-title">
                    <div class="km-login-panel">
                        <form class="km-login-form" data-demo-login>
                            <div class="km-login-logo">
                                <img src="../../../public/assets/img/logo-adasi.png" alt="Logo PT Astra Daido Steel Indonesia">
                                <div><strong>Fastware ADSI</strong><span>Knowledge Management</span></div>
                            </div>
                            <h1 id="login-title">Selamat datang kembali</h1>
                            <p>Masuk menggunakan akun perusahaan untuk mengakses knowledge sesuai kewenangan Anda.</p>
                            <div class="km-field">
                                <label for="prototype-username">Username</label>
                                <input class="km-input" id="prototype-username" name="username" autocomplete="username" required value="maya.santoso">
                            </div>
                            <div class="km-field">
                                <label for="prototype-password">Password</label>
                                <input class="km-input" id="prototype-password" name="password" type="password" autocomplete="current-password" required value="prototype">
                                <p class="km-helper">Gunakan kredensial Fastware yang aktif.</p>
                            </div>
                            ${button('Masuk ke Knowledge Management', { variant: 'primary', icon: 'bi-box-arrow-in-right', submit: true })}
                            <div class="km-login-assurance">${icon('bi-shield-check')} Koneksi aman · Akses dicatat untuk kebutuhan audit</div>
                        </form>
                    </div>
                    <div class="km-login-visual">
                        <div class="km-login-visual-content">
                            <p class="km-eyebrow" style="color:var(--km-navy-100)">Enterprise Knowledge Management</p>
                            <h2>Knowledge terpercaya untuk keputusan kerja yang lebih konsisten.</h2>
                            <p>Satu tempat untuk menemukan, menyusun, menyetujui, dan menjaga materi operasional perusahaan.</p>
                            <div class="km-login-points">
                                <div class="km-login-point"><strong>128</strong><span>Knowledge aktif</span></div>
                                <div class="km-login-point"><strong>96%</strong><span>File private terverifikasi</span></div>
                                <div class="km-login-point"><strong>1 tahap</strong><span>Workflow approval aktif</span></div>
                            </div>
                        </div>
                    </div>
                </section>`;
        },

        'employee-dashboard'() {
            const meta = getScreen('employee-dashboard');
            return pageHeading(meta, 'Akses cepat ke knowledge terbit yang relevan dengan pekerjaan dan posisi Anda.', button('Buka Library', { variant: 'primary', target: 'knowledge-library', icon: 'bi-collection' }))
                + searchHero()
                + `<section class="km-section">${sectionHeading('Direkomendasikan untuk Anda', 'Berdasarkan posisi dan kategori yang dapat Anda akses.', `<button class="km-btn km-btn-ghost km-btn-sm" type="button" data-screen-target="knowledge-library">Lihat semua</button>`)}
                    <div class="km-knowledge-grid">
                        ${knowledgeCard({ title: 'Panduan Identifikasi Material Tool Steel', summary: 'Langkah verifikasi grade, heat number, dan dokumen incoming material.', category: 'Quality', owner: 'Dimas Pratama', updated: '18 Jul 2026', views: 348, version: '1.0' })}
                        ${knowledgeCard({ title: 'Standar Keselamatan Operasi Vacuum Furnace', summary: 'Pemeriksaan wajib sebelum, selama, dan setelah proses heat treatment.', category: 'Safety', owner: 'Rina Kusuma', updated: '16 Jul 2026', views: 291, version: '1.2' })}
                        ${knowledgeCard({ title: 'Checklist Persiapan Proses Machining', summary: 'Kontrol kesiapan drawing, material, tooling, dan mesin sebelum produksi.', category: 'Machining', owner: 'Arif Wibowo', updated: '12 Jul 2026', views: 217, version: '1.0' })}
                    </div>
                </section>
                <section class="km-section km-grid km-grid-2">
                    ${card('Baru diterbitkan', taskList([
                        { title: 'Metode Pengukuran Kekerasan HRC', meta: 'Quality · Terbit 24 Jul 2026', status: 'Baru', kind: 'info' },
                        { title: 'Penanganan Abnormality Proses EDM', meta: 'Machining · Terbit 22 Jul 2026', status: '6 menit', kind: 'neutral' },
                        { title: 'Panduan 5S Area Warehouse', meta: 'General · Terbit 19 Jul 2026', status: '4 menit', kind: 'neutral' },
                    ]), { headerAction: button('Lihat semua', { size: 'sm', target: 'knowledge-library' }) })}
                    ${card('Aktivitas Anda', taskList([
                        { title: 'Prosedur Heat Treatment Vacuum', meta: 'Terakhir dibuka hari ini, 09.42 WIB', status: 'Sedang dibaca', kind: 'warning', icon: 'bi-book' },
                        { title: 'Checklist Incoming Material', meta: 'Disimpan ke Baca Nanti', status: 'Tersimpan', kind: 'info', icon: 'bi-bookmark' },
                        { title: 'Standar Packaging Finished Goods', meta: 'Selesai dibaca 21 Jul 2026', status: 'Selesai', kind: 'success', icon: 'bi-check2-circle' },
                    ]))}
                </section>`;
        },

        'contributor-dashboard'() {
            const meta = getScreen('contributor-dashboard');
            return pageHeading(meta, 'Pantau seluruh draf dan pengajuan yang menjadi tanggung jawab Anda.', button('Buat Knowledge', { variant: 'primary', target: 'create-knowledge', icon: 'bi-plus-lg' }))
                + conceptBanner(meta)
                + `<section class="km-grid km-grid-4">
                    ${kpi('Draf', '4', '2 diperbarui minggu ini', { icon: 'bi-file-earmark', metaIcon: 'bi-arrow-up-right' })}
                    ${kpi('Menunggu Persetujuan', '3', 'Workflow aktif satu tahap', { icon: 'bi-hourglass-split', tone: 'warning' })}
                    ${kpi('Revisi Diperlukan', '1', 'Diturunkan dari event penolakan', { icon: 'bi-arrow-repeat', tone: 'danger' })}
                    ${kpi('Terbit', '18', '3 knowledge dalam 30 hari', { icon: 'bi-check2-circle', tone: 'success' })}
                </section>
                <section class="km-section km-grid km-grid-3">
                    <div class="km-span-2">${card('Membutuhkan tindakan Anda', table(
                        ['Knowledge', 'Status', 'Aktivitas terakhir', 'Tindakan berikutnya'],
                        [
                            `<tr><td>${cellTitle('Checklist Verifikasi Dies', 'KM-2026-0148 · Tooling')}</td><td>${status('Draf — revisi diminta', 'danger')}</td><td>24 Jul 2026, 15.20</td><td>${button('Perbaiki', { size: 'sm', target: 'revision-detail' })}</td></tr>`,
                            `<tr><td>${cellTitle('Panduan Setup Mesin MCT', 'KM-2026-0141 · Machining')}</td><td>${status('Draf', 'neutral')}</td><td>23 Jul 2026, 10.08</td><td>${button('Lanjutkan', { size: 'sm', target: 'create-knowledge' })}</td></tr>`,
                            `<tr><td>${cellTitle('Penanganan Material Retur', 'KM-2026-0136 · Warehouse')}</td><td>${status('Menunggu Persetujuan', 'warning')}</td><td>22 Jul 2026, 14.12</td><td>${button('Lacak', { size: 'sm', target: 'submission-detail' })}</td></tr>`,
                        ],
                        'Daftar pengajuan contributor yang membutuhkan tindakan.'
                    ))}</div>
                    ${card('Aktivitas terbaru', timeline([
                        { title: 'Draf tersimpan otomatis', meta: 'Checklist Verifikasi Dies · 10.32 WIB', state: 'complete' },
                        { title: 'Revisi diminta', meta: 'Catatan approver tersedia · Kemarin', state: 'current' },
                        { title: 'Knowledge diterbitkan', meta: 'Standar Packaging · 21 Jul 2026', state: 'complete' },
                    ]))}
                </section>`;
        },

        'reviewer-dashboard'() {
            const meta = getScreen('reviewer-dashboard');
            return pageHeading(meta, 'Prioritaskan submission berdasarkan risiko, tenggat, dan kesiapan konten.', button('Buka Review Queue', { variant: 'primary', target: 'review-queue', icon: 'bi-list-check' }))
                + conceptBanner(meta)
                + `<section class="km-grid km-grid-4">
                    ${kpi('Menunggu Review', '12', '7 ditugaskan kepada Anda', { icon: 'bi-inbox' })}
                    ${kpi('Mendekati SLA', '4', 'Jatuh tempo ≤ 2 hari', { icon: 'bi-clock-history', tone: 'warning' })}
                    ${kpi('Overdue', '2', 'Butuh eskalasi proses', { icon: 'bi-exclamation-triangle', tone: 'danger' })}
                    ${kpi('Selesai Bulan Ini', '27', 'Rata-rata 2,8 hari', { icon: 'bi-check2-all', tone: 'success' })}
                </section>
                <section class="km-section km-grid km-grid-3">
                    <div class="km-span-2">${card('Prioritas hari ini', table(
                        ['Priority', 'Submission', 'Contributor', 'Due date', 'Status'],
                        [
                            `<tr><td>${status('Tinggi', 'danger')}</td><td>${cellTitle('Instruksi Inspeksi Incoming Material', 'KM-2026-0154 · Quality')}</td><td>Dimas Pratama</td><td>Hari ini, 16.00</td><td>${button('Mulai review', { size: 'sm', variant: 'primary', target: 'review-workspace' })}</td></tr>`,
                            `<tr><td>${status('Sedang', 'warning')}</td><td>${cellTitle('Panduan Setup Wire Cut', 'KM-2026-0150 · Machining')}</td><td>Nur Aini</td><td>Besok</td><td>${button('Buka', { size: 'sm', target: 'review-workspace' })}</td></tr>`,
                            `<tr><td>${status('Normal', 'neutral')}</td><td>${cellTitle('Checklist Serah Terima Material', 'KM-2026-0147 · Warehouse')}</td><td>Agus Setiawan</td><td>28 Jul</td><td>${button('Buka', { size: 'sm', target: 'review-workspace' })}</td></tr>`,
                        ],
                        'Submission reviewer yang diurutkan berdasarkan prioritas dan tenggat konseptual.'
                    ))}</div>
                    ${card('Workload', barList([
                        { label: 'Baru', value: 68, display: '7' },
                        { label: 'Dalam review', value: 49, display: '5' },
                        { label: 'Menunggu revisi', value: 29, display: '3' },
                        { label: 'Selesai', value: 87, display: '27' },
                    ]), { description: 'Ringkasan bulan berjalan.' })}
                </section>`;
        },

        'approver-dashboard'() {
            const meta = getScreen('approver-dashboard');
            return pageHeading(meta, 'Ambil keputusan dengan ringkasan risiko, rekomendasi, dan histori yang mudah ditelusuri.', button('Buka Approval Queue', { variant: 'primary', target: 'approval-queue', icon: 'bi-check2-all' }))
                + conceptBanner(meta)
                + `<section class="km-grid km-grid-4">
                    ${kpi('Menunggu Persetujuan', '8', 'Workflow produksi: satu tahap', { icon: 'bi-hourglass-split' })}
                    ${kpi('Prioritas Tinggi', '2', 'Perlu keputusan hari ini', { icon: 'bi-flag', tone: 'danger' })}
                    ${kpi('Disetujui Bulan Ini', '31', 'Tanpa bobot KPI resmi', { icon: 'bi-check2-circle', tone: 'success' })}
                    ${kpi('Rata-rata Proses', '1,8 hari', 'Konsep SLA; belum metrik resmi', { icon: 'bi-stopwatch', tone: 'warning' })}
                </section>
                <section class="km-section km-grid km-grid-3">
                    <div class="km-span-2">${card('Keputusan prioritas', taskList([
                        { title: 'Prosedur Heat Treatment Vacuum', meta: 'Risk: Tinggi · Reviewer: Rina Kusuma · Due hari ini', status: 'Tinjau', kind: 'danger', icon: 'bi-fire' },
                        { title: 'Standar Pemeriksaan Kekerasan HRC', meta: 'Risk: Sedang · Reviewer: Budi Hartono · Due besok', status: 'Siap', kind: 'warning', icon: 'bi-clipboard2-check' },
                        { title: 'Panduan Packaging Ekspor', meta: 'Risk: Rendah · Reviewer: Ayu Lestari · Due 29 Jul', status: 'Siap', kind: 'success', icon: 'bi-box-seam' },
                    ]), { footer: `<span class="km-helper">8 pengajuan menunggu keputusan.</span>${button('Lihat queue', { size: 'sm', target: 'approval-queue' })}` })}</div>
                    ${card('Tren keputusan', trendChart('approval-trend', 'Tren keputusan approval selama delapan minggu', 'Jumlah keputusan naik stabil dari 18 menjadi 31 per minggu; data pada prototype bukan KPI resmi.'), { description: '8 minggu terakhir · data dummy' })}
                </section>`;
        },

        'admin-dashboard'() {
            const meta = getScreen('admin-dashboard');
            return pageHeading(meta, 'Pantau inventory, partisipasi, kualitas metadata, dan kesehatan operasional tanpa mengekspos aktivitas individu.', button('Lihat Analytics', { variant: 'primary', target: 'analytics', icon: 'bi-bar-chart-line' }))
                + conceptBanner(meta)
                + `<section class="km-grid km-grid-4">
                    ${kpi('Total Knowledge', '184', '128 terbit · 24 draf', { icon: 'bi-collection' })}
                    ${kpi('Menunggu Persetujuan', '8', 'Satu tahap aktif', { icon: 'bi-hourglass-split', tone: 'warning' })}
                    ${kpi('Knowledge Perlu Ditinjau', '17', 'Konsep review cycle', { icon: 'bi-arrow-clockwise', tone: 'danger' })}
                    ${kpi('Kontributor Aktif', '46', '30 hari terakhir · agregat', { icon: 'bi-people', tone: 'success' })}
                </section>
                <section class="km-section km-grid km-grid-2">
                    ${card('Pertumbuhan knowledge terbit', trendChart('publish-trend', 'Tren jumlah knowledge terbit', 'Jumlah knowledge terbit meningkat dari 9 ke 22 per bulan pada enam bulan terakhir.'), { description: 'Data agregat operasional.' })}
                    ${card('Kualitas inventory', barList([
                        { label: 'Metadata lengkap', value: 92 },
                        { label: 'Memiliki tag', value: 86 },
                        { label: 'File private valid', value: 100 },
                        { label: 'Memiliki owner', value: 96 },
                    ]), { description: 'Indikator kelengkapan, bukan penilaian individu.' })}
                </section>
                <section class="km-section km-grid km-grid-2">
                    ${card('Perlu perhatian', taskList([
                        { title: '17 knowledge mendekati tanggal tinjau', meta: 'Konsep review cycle — belum dijadwalkan produksi', status: 'Periksa', kind: 'warning', icon: 'bi-calendar-event' },
                        { title: '5 knowledge tanpa taxonomy yang konsisten', meta: 'Perlu konsolidasi kategori dan tag', status: 'Metadata', kind: 'info', icon: 'bi-tags' },
                        { title: 'Queue masih menggunakan driver sync', meta: 'Operasional WARN dari km:health', status: 'WARN', kind: 'warning', icon: 'bi-hdd-network' },
                    ]))}
                    ${card('Kesehatan sistem', taskList([
                        { title: 'Private storage', meta: 'Path canonical berada di luar public', status: 'PASS', kind: 'success', icon: 'bi-shield-lock' },
                        { title: 'Schema dan constraint KM', meta: '10 readiness check wajib lulus', status: 'PASS', kind: 'success', icon: 'bi-database-check' },
                        { title: 'Worker dan scheduler', meta: 'Harus diverifikasi operator saat deployment', status: 'WARN', kind: 'warning', icon: 'bi-clock-history' },
                    ]))}
                </section>`;
        },

        'knowledge-library'() {
            const meta = getScreen('knowledge-library');
            return pageHeading(meta, 'Telusuri materi terbit berdasarkan metadata dan tag dengan hasil yang dibatasi hak akses.', button('Buat Knowledge', { variant: 'primary', target: 'create-knowledge', icon: 'bi-plus-lg' }))
                + `<section class="km-filter-bar" aria-label="Filter Knowledge Library">
                    <div class="km-field"><label for="library-search">Pencarian</label><input class="km-input" id="library-search" type="search" value="tool steel" placeholder="Judul atau sinopsis"></div>
                    <div class="km-field"><label for="library-category">Kategori</label><select class="km-select" id="library-category"><option>Semua kategori</option><option selected>Quality</option><option>Machining</option><option>Safety</option></select></div>
                    <div class="km-field"><label for="library-tag">Tag</label><select class="km-select" id="library-tag"><option>Semua tag</option><option selected>Material</option><option>Inspection</option></select></div>
                    <div class="km-field"><label for="library-sort">Urutkan</label><select class="km-select" id="library-sort"><option>Relevansi</option><option>Terbaru</option><option>Terpopuler</option></select></div>
                    ${button('Terapkan', { variant: 'primary', target: 'search-results', icon: 'bi-funnel' })}
                </section>
                <section class="km-section">
                    ${sectionHeading('24 knowledge ditemukan', 'Filter aktif: Quality dan tag Material.', `<div class="km-row-actions"><button class="km-icon-button" type="button" aria-label="Tampilan grid" aria-pressed="true">${icon('bi-grid-3x3-gap')}</button><button class="km-icon-button" type="button" aria-label="Tampilan daftar" aria-pressed="false">${icon('bi-list-ul')}</button></div>`)}
                    <div class="km-knowledge-grid">
                        ${knowledgeCard({ title: 'Panduan Identifikasi Material Tool Steel', summary: 'Verifikasi grade, heat number, sertifikat, dan kondisi material incoming.', category: 'Quality', owner: 'Dimas Pratama', updated: '18 Jul 2026', views: 348, version: '1.0' })}
                        ${knowledgeCard({ title: 'Matriks Kesetaraan Grade JIS–AISI', summary: 'Referensi praktis pemetaan grade utama untuk proses quotation dan engineering.', category: 'Engineering', owner: 'Lina Permata', updated: '15 Jul 2026', views: 276, version: '1.1', icon: 'bi-table' })}
                        ${knowledgeCard({ title: 'Checklist Incoming Material Tool Steel', summary: 'Daftar pemeriksaan visual, dimensi, label, dan dokumen pendukung.', category: 'Warehouse', owner: 'Agus Setiawan', updated: '11 Jul 2026', views: 229, version: '1.0', icon: 'bi-clipboard2-check' })}
                        ${knowledgeCard({ title: 'Penanganan Material dengan Karat Permukaan', summary: 'Kriteria penerimaan dan langkah eskalasi untuk material berkarat.', category: 'Quality', owner: 'Rina Kusuma', updated: '08 Jul 2026', views: 184, version: '1.0' })}
                        ${knowledgeCard({ title: 'Panduan Penyimpanan Material SKD11', summary: 'Persyaratan rack, label, perlindungan, serta FIFO material SKD11.', category: 'Warehouse', owner: 'Bambang Irawan', updated: '02 Jul 2026', views: 163, version: '1.0', icon: 'bi-box-seam' })}
                        ${knowledgeCard({ title: 'Verifikasi Sertifikat Material', summary: 'Cara membaca mill certificate dan memeriksa konsistensi data material.', category: 'Quality', owner: 'Nadia Putri', updated: '29 Jun 2026', views: 152, version: '1.0', icon: 'bi-patch-check' })}
                    </div>
                </section>`;
        },

        'search-results'() {
            const meta = getScreen('search-results');
            const query = escapeHtml(state.lastSearch);
            return pageHeading(meta, `Menampilkan hasil yang cocok dengan “${state.lastSearch}” pada judul dan sinopsis.`, button('Ubah Filter', { variant: 'secondary', action: 'Panel filter dibuka.', icon: 'bi-sliders' }))
                + `<section class="km-filter-bar">
                    <div class="km-field"><label for="result-search">Kata kunci</label><input class="km-input" id="result-search" type="search" value="${query}" maxlength="100"></div>
                    <div class="km-field"><label for="result-category">Kategori</label><select class="km-select" id="result-category"><option>Semua kategori</option><option selected>Heat Treatment</option></select></div>
                    <div class="km-field"><label for="result-tag">Tag</label><select class="km-select" id="result-tag"><option>Semua tag</option><option selected>Vacuum</option></select></div>
                    <div class="km-field"><label for="result-sort">Urutkan</label><select class="km-select" id="result-sort"><option selected>Relevansi</option><option>Terbaru</option></select></div>
                    ${button('Cari', { variant: 'primary', action: 'Hasil pencarian diperbarui.', icon: 'bi-search' })}
                </section>
                <section class="km-section">
                    ${sectionHeading('8 hasil', 'Pencarian metadata MySQL FULLTEXT; isi file tidak diindeks.')}
                    ${card('Hasil paling relevan', taskList([
                        { title: 'Prosedur Heat Treatment Vacuum untuk SKD11', meta: 'Heat Treatment · Diperbarui 18 Jul 2026 · 421 dilihat', status: 'Relevansi tinggi', kind: 'success', icon: 'bi-file-earmark-pdf' },
                        { title: 'Checklist Persiapan Vacuum Furnace', meta: 'Safety · Diperbarui 12 Jul 2026 · 308 dilihat', status: 'Relevansi tinggi', kind: 'success', icon: 'bi-clipboard2-check' },
                        { title: 'Penanganan Abnormality Heat Treatment', meta: 'Quality · Diperbarui 08 Jul 2026 · 264 dilihat', status: 'Relevansi sedang', kind: 'info', icon: 'bi-exclamation-diamond' },
                        { title: 'Panduan Tempering Tool Steel', meta: 'Heat Treatment · Diperbarui 01 Jul 2026 · 197 dilihat', status: 'Relevansi sedang', kind: 'info', icon: 'bi-thermometer-high' },
                    ]), { footer: `<span class="km-helper">Hasil 1–4 dari 8</span>${button('Buka hasil pertama', { size: 'sm', target: 'knowledge-detail' })}` })}
                </section>`;
        },

        'knowledge-detail'() {
            const meta = getScreen('knowledge-detail');
            return pageHeading(meta, 'Dokumen terbit · Quality · Terakhir diperbarui 18 Juli 2026.', `${button('Simpan', { variant: 'secondary', action: 'Knowledge disimpan ke Baca Nanti.', icon: 'bi-bookmark' })}${button('Buka Dokumen', { variant: 'primary', action: 'Private preview dibuka setelah pemeriksaan policy.', icon: 'bi-file-earmark-pdf' })}`)
                + conceptBanner(meta)
                + `<section class="km-detail-layout">
                    <article class="km-card">
                        <div class="km-card-body km-readable-copy" style="padding:var(--km-space-8)">
                            <div class="km-tag-row">${status('Terbit', 'success')}${tag('Quality')}${tag('Material')}${tag('Inspection')}</div>
                            <h2 style="font-size:var(--km-text-3xl);margin-bottom:var(--km-space-2)">Standar Penanganan Material Tool Steel</h2>
                            <p style="font-size:var(--km-text-lg)">Panduan verifikasi identitas, kondisi, penyimpanan, dan eskalasi abnormality material tool steel sebelum diproses.</p>
                            <div class="km-grid km-grid-3" style="margin:var(--km-space-6) 0">
                                <div><span class="km-helper">Owner</span><strong style="display:block">Dimas Pratama</strong></div>
                                <div><span class="km-helper">Target pembaca</span><strong style="display:block">All Employee</strong></div>
                                <div><span class="km-helper">Estimasi baca</span><strong style="display:block">8 menit</strong></div>
                            </div>
                            <h3 id="detail-purpose">1. Tujuan</h3>
                            <p>Memastikan setiap material yang diterima dapat ditelusuri dan memenuhi persyaratan teknis sebelum masuk ke proses machining atau heat treatment.</p>
                            <h3 id="detail-scope">2. Ruang lingkup</h3>
                            <p>Panduan berlaku untuk material tool steel yang diterima oleh Warehouse dan diperiksa bersama Quality Control.</p>
                            <h3 id="detail-procedure">3. Langkah pemeriksaan</h3>
                            <ol>
                                <li>Bandingkan label material dengan purchase order dan mill certificate.</li>
                                <li>Periksa heat number, grade, dimensi, dan kondisi permukaan.</li>
                                <li>Catat setiap ketidaksesuaian dan tahan material sampai keputusan diberikan.</li>
                            </ol>
                            <div class="km-alert km-alert-info" role="note">${icon('bi-info-circle')}<div><h3>Catatan traceability</h3><p>Jangan mengubah label asli pemasok. Tambahkan label internal tanpa menutupi heat number.</p></div></div>
                            <h3 id="detail-attachments">Lampiran</h3>
                            ${taskList([
                                { title: 'Checklist Incoming Material.pdf', meta: 'PDF · 284 KB · Private file', status: 'Unduh', kind: 'info', icon: 'bi-paperclip' },
                                { title: 'Contoh Mill Certificate.pdf', meta: 'PDF · 612 KB · Private file', status: 'Preview', kind: 'info', icon: 'bi-paperclip' },
                            ])}
                        </div>
                    </article>
                    <aside class="km-detail-aside">
                        ${card('Pada halaman ini', `<nav aria-label="Daftar isi"><ul class="km-toc"><li><a href="#detail-purpose" aria-current="true">Tujuan</a></li><li><a href="#detail-scope">Ruang lingkup</a></li><li><a href="#detail-procedure">Langkah pemeriksaan</a></li><li><a href="#detail-attachments">Lampiran</a></li></ul></nav>`)}
                        <div style="height:var(--km-space-4)"></div>
                        ${card('Traceability', timeline([
                            { title: 'Disetujui', meta: 'Maya Santoso · 18 Jul 2026', state: 'complete' },
                            { title: 'Dikirim', meta: 'Dimas Pratama · 17 Jul 2026', state: 'complete' },
                            { title: 'Draf dibuat', meta: '15 Jul 2026', state: 'complete' },
                        ]))}
                    </aside>
                </section>`;
        },

        'create-knowledge'() {
            const meta = getScreen('create-knowledge');
            return pageHeading(meta, 'Progressive disclosure menjaga form tetap singkat; hanya field relevan yang ditampilkan.', `${button('Simpan Draf', { variant: 'secondary', action: 'Perubahan disimpan sebagai draf.' })}${button('Lanjut ke Konten', { variant: 'primary', action: 'Membuka langkah Konten.', icon: 'bi-arrow-right' })}`)
                + conceptBanner(meta)
                + `<ol class="km-stepper" aria-label="Progres pengajuan knowledge">
                    <li class="km-step is-current" aria-current="step"><span class="km-step-marker">1</span><span>Informasi Dasar</span></li>
                    <li class="km-step"><span class="km-step-marker">2</span><span>Konten</span></li>
                    <li class="km-step"><span class="km-step-marker">3</span><span>Klasifikasi</span></li>
                    <li class="km-step"><span class="km-step-marker">4</span><span>Persetujuan</span></li>
                    <li class="km-step"><span class="km-step-marker">5</span><span>Review & Submit</span></li>
                </ol>
                <section class="km-form-layout">
                    ${card('Informasi dasar', `
                        <form class="km-form-grid" data-demo-form>
                            <div class="km-field is-full"><label for="create-title">Judul knowledge <span aria-hidden="true">*</span></label><input class="km-input" id="create-title" value="Panduan Verifikasi Dies Sebelum Produksi" maxlength="255" required><p class="km-helper">46 dari 255 karakter</p></div>
                            <div class="km-field is-full"><label for="create-summary">Sinopsis <span aria-hidden="true">*</span></label><textarea class="km-textarea" id="create-summary" maxlength="3000" required>Panduan pemeriksaan kondisi, drawing, dan kesiapan dies sebelum dipasang ke mesin produksi.</textarea><p class="km-helper">Gunakan 1–3 kalimat yang membantu pencarian.</p></div>
                            <div class="km-field"><label for="create-type">Jenis knowledge</label><select class="km-select" id="create-type"><option>Work Instruction</option><option>Best Practice</option><option>Reference</option></select></div>
                            <div class="km-field"><label for="create-category">Kategori</label><select class="km-select" id="create-category"><option>Tooling</option><option>Machining</option><option>Quality</option></select></div>
                            <div class="km-field"><label for="create-tags">Tag</label><input class="km-input" id="create-tags" value="dies, verification, production"><p class="km-helper">Tekan Enter untuk menambahkan tag.</p></div>
                            <div class="km-field"><label for="create-minutes">Estimasi waktu baca</label><input class="km-input" id="create-minutes" type="number" value="8" min="1" max="1440"></div>
                            <div class="km-field is-full"><label for="create-file">File utama <span aria-hidden="true">*</span></label><input class="km-input" id="create-file" type="file" accept=".pdf,.ppt,.pptx"><p class="km-helper">PDF mendukung private preview. PPT/PPTX saat ini hanya dapat diunduh.</p></div>
                            <div class="km-field is-full"><label for="create-coauthors">Co-author</label><input class="km-input" id="create-coauthors" type="search" placeholder="Cari nama, email, atau NPK" autocomplete="off"></div>
                        </form>`, {
                            description: 'Field yang ditandai wajib harus lengkap sebelum pengajuan dikirim.',
                            footer: `<span class="km-helper" aria-live="polite">${icon('bi-cloud-check')} Tersimpan otomatis 10.32 WIB</span>${button('Simpan Draf', { size: 'sm', action: 'Draf tersimpan.' })}`,
                        })}
                    <aside class="km-sticky-summary">
                        ${card('Progres pengajuan', `<div class="km-progress" role="progressbar" aria-label="Progres pengajuan" aria-valuemin="0" aria-valuemax="100" aria-valuenow="20"><span style="width:20%"></span></div><p class="km-card-description" style="margin-top:var(--km-space-3)">1 dari 5 langkah · 20% lengkap</p>`)}
                        <div style="height:var(--km-space-4)"></div>
                        ${card('Rekomendasi serupa', taskList([
                            { title: 'Checklist Pemasangan Dies', meta: 'Tooling · 78% kemiripan', status: 'Preview', kind: 'info' },
                            { title: 'Standar Preventive Dies', meta: 'Tooling · 62% kemiripan', status: 'Preview', kind: 'info' },
                        ]), { description: 'Deteksi duplikasi adalah konsep UX; sumber similarity harus ditetapkan.' })}
                    </aside>
                </section>`;
        },

        'review-submit'() {
            const meta = getScreen('review-submit');
            return pageHeading(meta, 'Pastikan informasi, file, target pembaca, dan jalur persetujuan sudah benar.', `${button('Kembali', { variant: 'secondary', target: 'create-knowledge', icon: 'bi-arrow-left' })}${button('Kirim untuk Persetujuan', { variant: 'primary', confirmTitle: 'Kirim pengajuan?', confirm: 'Setelah dikirim, draf tidak dapat diedit sampai diproses approver.', icon: 'bi-send' })}`)
                + conceptBanner(meta)
                + `<ol class="km-stepper" aria-label="Progres pengajuan knowledge">
                    <li class="km-step is-complete"><span class="km-step-marker">${icon('bi-check-lg')}</span><span>Informasi Dasar</span></li>
                    <li class="km-step is-complete"><span class="km-step-marker">${icon('bi-check-lg')}</span><span>Konten</span></li>
                    <li class="km-step is-complete"><span class="km-step-marker">${icon('bi-check-lg')}</span><span>Klasifikasi</span></li>
                    <li class="km-step is-complete"><span class="km-step-marker">${icon('bi-check-lg')}</span><span>Persetujuan</span></li>
                    <li class="km-step is-current" aria-current="step"><span class="km-step-marker">5</span><span>Review & Submit</span></li>
                </ol>
                <section class="km-grid km-grid-3">
                    <div class="km-span-2 km-grid">
                        ${card('Ringkasan knowledge', `<dl class="km-grid km-grid-2"><div><dt class="km-helper">Judul</dt><dd>Panduan Verifikasi Dies Sebelum Produksi</dd></div><div><dt class="km-helper">Kategori</dt><dd>Tooling</dd></div><div><dt class="km-helper">Owner</dt><dd>Dimas Pratama</dd></div><div><dt class="km-helper">Target pembaca</dt><dd>All Employee</dd></div></dl>`)}
                        ${card('File dan konten', taskList([
                            { title: 'Panduan-Verifikasi-Dies.pdf', meta: 'PDF · 1,8 MB · Checksum akan disimpan saat upload', status: 'Siap', kind: 'success', icon: 'bi-file-earmark-pdf' },
                            { title: 'Struktur heading', meta: '1 judul, 5 bagian, 2 checklist', status: 'Valid', kind: 'success', icon: 'bi-list-ol' },
                        ]))}
                        ${card('Deklarasi', `<label class="km-checkbox-row"><input type="checkbox" checked><span>Saya memastikan informasi ini akurat dan tidak memuat data rahasia di luar klasifikasinya.</span></label>`)}
                    </div>
                    <aside>
                        ${card('Hasil validasi', taskList([
                            { title: 'Informasi wajib', meta: 'Semua field wajib lengkap', status: 'Lulus', kind: 'success', icon: 'bi-check-circle' },
                            { title: 'File utama', meta: 'PDF valid dan dapat diproses', status: 'Lulus', kind: 'success', icon: 'bi-file-check' },
                            { title: 'Kemiripan', meta: 'Tidak ada duplikat pasti', status: 'Periksa', kind: 'warning', icon: 'bi-files' },
                        ]))}
                        <div style="height:var(--km-space-4)"></div>
                        ${card('Jalur aktif', timeline([
                            { title: 'Contributor', meta: 'Dimas Pratama', state: 'complete' },
                            { title: 'Approver', meta: 'Satu tahap · sesuai workflow aktif', state: 'current' },
                            { title: 'Terbit', meta: 'Otomatis setelah disetujui' },
                        ]))}
                    </aside>
                </section>`;
        },

        'submission-success'() {
            const meta = getScreen('submission-success');
            return pageHeading(meta, 'Konfirmasi yang jelas setelah pengajuan berhasil masuk ke workflow aktif.')
                + `<section class="km-card km-empty-state">
                    <div class="km-empty-state-inner">
                        <span class="km-empty-icon" style="color:var(--km-color-success);background:var(--km-color-success-soft)">${icon('bi-check-lg')}</span>
                        <h2>Knowledge berhasil dikirim untuk persetujuan</h2>
                        <p>Nomor pengajuan <strong>KM-2026-0158</strong>. Status berubah menjadi <strong>Menunggu Persetujuan</strong>.</p>
                        <div class="km-page-actions" style="justify-content:center">
                            ${button('Lihat Pengajuan', { variant: 'primary', target: 'submission-detail' })}
                            ${button('Kembali ke Draf Saya', { target: 'my-drafts' })}
                        </div>
                        <div class="km-alert km-alert-info" style="margin-top:var(--km-space-6);text-align:left">${icon('bi-info-circle')}<div><h3>Langkah berikutnya</h3><p>Approver akan memeriksa knowledge. Riwayat keputusan dapat dilihat pada detail pengajuan.</p></div></div>
                    </div>
                </section>`;
        },

        'my-drafts'() {
            const meta = getScreen('my-drafts');
            return pageHeading(meta, 'Semua knowledge berstatus Draf yang masih dapat Anda edit.', button('Buat Knowledge', { variant: 'primary', target: 'create-knowledge', icon: 'bi-plus-lg' }))
                + `<section class="km-filter-bar">
                    <div class="km-field"><label for="draft-search">Cari draf</label><input class="km-input" id="draft-search" type="search" placeholder="Judul draf"></div>
                    <div class="km-field"><label for="draft-category">Kategori</label><select class="km-select" id="draft-category"><option>Semua kategori</option><option>Tooling</option></select></div>
                    <div class="km-field"><label for="draft-updated">Diperbarui</label><select class="km-select" id="draft-updated"><option>Kapan saja</option><option>7 hari terakhir</option></select></div>
                    <div class="km-field"><label for="draft-sort">Urutkan</label><select class="km-select" id="draft-sort"><option>Terbaru</option><option>Judul A–Z</option></select></div>
                    ${button('Terapkan', { variant: 'primary', action: 'Filter draf diterapkan.' })}
                </section>
                <section class="km-section">${table(
                    ['Knowledge', 'Kelengkapan', 'Terakhir disimpan', 'Co-author', 'Aksi'],
                    [
                        `<tr><td>${cellTitle('Panduan Verifikasi Dies Sebelum Produksi', 'KM-2026-0158 · Tooling')}</td><td><div class="km-progress" aria-label="60 persen lengkap"><span style="width:60%"></span></div><span class="km-helper">60% lengkap</span></td><td>Hari ini, 10.32</td><td>Nur Aini</td><td class="is-action">${button('Lanjutkan', { size: 'sm', variant: 'primary', target: 'create-knowledge' })}</td></tr>`,
                        `<tr><td>${cellTitle('Checklist Pembersihan MCT', 'KM-2026-0151 · Machining')}</td><td><div class="km-progress" aria-label="35 persen lengkap"><span style="width:35%"></span></div><span class="km-helper">35% lengkap</span></td><td>Kemarin, 16.04</td><td>—</td><td class="is-action">${button('Lanjutkan', { size: 'sm', target: 'create-knowledge' })}</td></tr>`,
                        `<tr><td>${cellTitle('Referensi Toleransi Grinding', 'KM-2026-0149 · Quality')}</td><td><div class="km-progress" aria-label="80 persen lengkap"><span style="width:80%"></span></div><span class="km-helper">80% lengkap</span></td><td>22 Jul 2026</td><td>Fajar Hadi</td><td class="is-action">${button('Lanjutkan', { size: 'sm', target: 'create-knowledge' })}</td></tr>`,
                    ],
                    'Daftar draf milik contributor.'
                )}</section>`;
        },

        'my-submissions'() {
            const meta = getScreen('my-submissions');
            return pageHeading(meta, 'Lacak status, pemilik tindakan saat ini, dan histori pengajuan dalam satu daftar.', button('Buat Knowledge', { variant: 'primary', target: 'create-knowledge', icon: 'bi-plus-lg' }))
                + conceptBanner(meta)
                + `<section class="km-filter-bar">
                    <div class="km-field"><label for="submission-search">Cari pengajuan</label><input class="km-input" id="submission-search" type="search" placeholder="ID atau judul"></div>
                    <div class="km-field"><label for="submission-status">Status</label><select class="km-select" id="submission-status"><option>Semua status</option><option>Menunggu Persetujuan</option><option>Terbit</option><option>Draf</option></select></div>
                    <div class="km-field"><label for="submission-category">Kategori</label><select class="km-select" id="submission-category"><option>Semua kategori</option><option>Quality</option></select></div>
                    <div class="km-field"><label for="submission-sort">Urutkan</label><select class="km-select" id="submission-sort"><option>Aktivitas terbaru</option><option>Tanggal diajukan</option></select></div>
                    ${button('Terapkan', { variant: 'primary', action: 'Filter pengajuan diterapkan.' })}
                </section>
                <section class="km-section">${table(
                    ['Submission', 'Tanggal', 'Tahap saat ini', 'Penanggung jawab', 'Status', 'Next action'],
                    [
                        `<tr><td>${cellTitle('KM-2026-0156 · Prosedur Heat Treatment Vacuum', 'Heat Treatment')}</td><td>24 Jul 2026</td><td>Persetujuan satu tahap</td><td>Maya Santoso</td><td>${status('Menunggu Persetujuan', 'warning')}</td><td>${button('Lacak', { size: 'sm', target: 'submission-detail' })}</td></tr>`,
                        `<tr><td>${cellTitle('KM-2026-0148 · Checklist Verifikasi Dies', 'Tooling')}</td><td>20 Jul 2026</td><td>Kembali ke contributor</td><td>Anda</td><td>${status('Draf — revisi diminta', 'danger')}</td><td>${button('Perbaiki', { size: 'sm', target: 'revision-detail' })}</td></tr>`,
                        `<tr><td>${cellTitle('KM-2026-0139 · Standar Packaging Ekspor', 'Warehouse')}</td><td>12 Jul 2026</td><td>Selesai</td><td>—</td><td>${status('Terbit', 'success')}</td><td>${button('Lihat', { size: 'sm', target: 'knowledge-detail' })}</td></tr>`,
                    ],
                    'Daftar pengajuan dan tindakan berikutnya.'
                )}</section>`;
        },

        'submission-detail'() {
            const meta = getScreen('submission-detail');
            return pageHeading(meta, 'Checklist Verifikasi Dies · Tooling · Diajukan 20 Juli 2026.', `${button('Unduh File', { variant: 'secondary', action: 'File private diunduh setelah policy check.', icon: 'bi-download' })}${button('Buka Revisi', { variant: 'primary', target: 'revision-detail', icon: 'bi-pencil' })}`)
                + conceptBanner(meta)
                + `<section class="km-grid km-grid-3">
                    <div class="km-span-2 km-grid">
                        ${card('Ringkasan', `<dl class="km-grid km-grid-2"><div><dt class="km-helper">Status</dt><dd>${status('Draf — revisi diminta', 'danger')}</dd></div><div><dt class="km-helper">Owner</dt><dd>Dimas Pratama</dd></div><div><dt class="km-helper">Kategori</dt><dd>Tooling</dd></div><div><dt class="km-helper">Target pembaca</dt><dd>All Employee</dd></div></dl><p>Checklist pemeriksaan drawing, kondisi dies, dan kesiapan pemasangan sebelum produksi.</p>`)}
                        ${card('Catatan keputusan', `<div class="km-comment"><span class="km-avatar">MS</span><div><strong>Maya Santoso · Approver</strong><p>Tambahkan kriteria penerimaan untuk kondisi permukaan dan jelaskan siapa yang berwenang menghentikan pemasangan.</p><div class="km-tag-row" style="margin-top:var(--km-space-2)">${status('Belum diselesaikan', 'danger')}</div></div></div>`)}
                        ${card('File pengajuan', taskList([{ title: 'Checklist-Verifikasi-Dies.pdf', meta: 'PDF · 1,4 MB · Private storage · checksum tersedia', status: 'Preview', kind: 'info', icon: 'bi-file-earmark-pdf' }]))}
                    </div>
                    <aside>
                        ${card('Progress', timeline([
                            { title: 'Draf dibuat', meta: 'Dimas Pratama · 18 Jul', state: 'complete' },
                            { title: 'Dikirim', meta: '20 Jul, 09.18', state: 'complete' },
                            { title: 'Revisi diminta', meta: 'Maya Santoso · 20 Jul, 15.20', state: 'current' },
                            { title: 'Kirim ulang', meta: 'Menunggu tindakan contributor' },
                        ]))}
                        <div style="height:var(--km-space-4)"></div>
                        ${card('Tindakan berikutnya', `<p class="km-card-description">Perbarui draf berdasarkan catatan keputusan, lalu kirim ulang.</p>${button('Buka Revisi', { variant: 'primary', target: 'revision-detail', icon: 'bi-arrow-repeat' })}`)}
                    </aside>
                </section>`;
        },

        'revision-detail'() {
            const meta = getScreen('revision-detail');
            return pageHeading(meta, 'Selesaikan feedback approver tanpa kehilangan histori keputusan.', `${button('Simpan Draf', { variant: 'secondary', action: 'Perubahan revisi disimpan.' })}${button('Kirim Ulang', { variant: 'primary', confirmTitle: 'Kirim ulang pengajuan?', confirm: 'Pastikan semua feedback wajib sudah ditanggapi.', icon: 'bi-send' })}`)
                + conceptBanner(meta)
                + `<section class="km-grid km-grid-3">
                    <div class="km-span-2 km-grid">
                        ${card('Feedback yang perlu ditangani', `
                            <div class="km-comment"><span class="km-avatar">MS</span><div><strong>Kriteria penerimaan belum lengkap</strong><p>Tambahkan kondisi permukaan yang diterima dan contoh abnormality yang harus dieskalasikan.</p><div class="km-tag-row" style="margin-top:var(--km-space-2)">${status('Belum selesai', 'danger')}${tag('Bagian 3')}</div></div></div>
                            <div class="km-comment"><span class="km-avatar">MS</span><div><strong>Perjelas otoritas penghentian</strong><p>Sebutkan jabatan yang dapat menghentikan pemasangan ketika kondisi dies tidak sesuai.</p><div class="km-tag-row" style="margin-top:var(--km-space-2)">${status('Selesai', 'success')}${tag('Bagian 4')}</div></div></div>`)}
                        ${card('Checklist perubahan', `<label class="km-checkbox-row"><input type="checkbox" checked><span>Tambahkan otoritas penghentian pemasangan.</span></label><label class="km-checkbox-row"><input type="checkbox"><span>Lengkapi kriteria kondisi permukaan.</span></label><label class="km-checkbox-row"><input type="checkbox"><span>Tinjau ulang file PDF dan ringkasan.</span></label>`)}
                        ${card('Ringkasan perubahan', `<div class="km-field"><label for="revision-summary">Perubahan yang dilakukan</label><textarea class="km-textarea" id="revision-summary">Menambahkan tanggung jawab Sec. Head pada bagian 4. Kriteria kondisi permukaan masih dilengkapi.</textarea><p class="km-helper">Ringkasan ini disertakan ketika pengajuan dikirim ulang.</p></div>`)}
                    </div>
                    <aside>
                        ${card('Status feedback', `<div class="km-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="50"><span style="width:50%"></span></div><p class="km-card-description" style="margin-top:var(--km-space-3)">1 dari 2 feedback selesai.</p>`)}
                        <div style="height:var(--km-space-4)"></div>
                        ${card('Histori', timeline([
                            { title: 'Revisi diminta', meta: '20 Jul 2026 · Maya Santoso', state: 'complete' },
                            { title: 'Draf revisi', meta: 'Terakhir disimpan hari ini', state: 'current' },
                            { title: 'Kirim ulang', meta: 'Belum dilakukan' },
                        ]))}
                    </aside>
                </section>`;
        },

        'review-queue'() {
            const meta = getScreen('review-queue');
            return pageHeading(meta, 'Kelola assignment review, prioritas, dan deadline yang masih menunggu RACI/SLA resmi.', button('Buka Workspace', { variant: 'primary', target: 'review-workspace', icon: 'bi-layout-three-columns' }))
                + conceptBanner(meta)
                + `<section class="km-filter-bar">
                    <div class="km-field"><label for="review-search">Cari submission</label><input class="km-input" id="review-search" type="search" placeholder="ID, judul, atau contributor"></div>
                    <div class="km-field"><label for="review-assignment">Assignment</label><select class="km-select" id="review-assignment"><option>Ditugaskan kepada saya</option><option>Belum ditugaskan</option></select></div>
                    <div class="km-field"><label for="review-priority">Priority</label><select class="km-select" id="review-priority"><option>Semua</option><option>Tinggi</option></select></div>
                    <div class="km-field"><label for="review-due">Due date</label><select class="km-select" id="review-due"><option>Semua</option><option>≤ 2 hari</option><option>Overdue</option></select></div>
                    ${button('Terapkan', { variant: 'primary', action: 'Filter review queue diterapkan.' })}
                </section>
                <section class="km-section">${table(
                    ['Priority', 'Submission', 'Contributor', 'Department', 'Due date', 'Revisi', 'Status', 'Aksi'],
                    [
                        `<tr><td>${status('Tinggi', 'danger')}</td><td>${cellTitle('KM-2026-0154 · Instruksi Inspeksi Incoming Material', 'Quality')}</td><td>Dimas Pratama</td><td>Quality</td><td>Hari ini</td><td class="is-numeric">0</td><td>${status('Baru', 'info')}</td><td>${button('Review', { size: 'sm', variant: 'primary', target: 'review-workspace' })}</td></tr>`,
                        `<tr><td>${status('Sedang', 'warning')}</td><td>${cellTitle('KM-2026-0150 · Panduan Setup Wire Cut', 'Machining')}</td><td>Nur Aini</td><td>Production</td><td>Besok</td><td class="is-numeric">1</td><td>${status('Revisi dikirim', 'warning')}</td><td>${button('Lanjutkan', { size: 'sm', target: 'review-workspace' })}</td></tr>`,
                        `<tr><td>${status('Normal', 'neutral')}</td><td>${cellTitle('KM-2026-0147 · Checklist Serah Terima Material', 'Warehouse')}</td><td>Agus Setiawan</td><td>Logistics</td><td>28 Jul</td><td class="is-numeric">0</td><td>${status('Dalam review', 'info')}</td><td>${button('Lanjutkan', { size: 'sm', target: 'review-workspace' })}</td></tr>`,
                    ],
                    'Review queue konseptual dengan assignment dan SLA.'
                )}</section>`;
        },

        'review-workspace'() {
            const meta = getScreen('review-workspace');
            return pageHeading(meta, 'Split-view menjaga daftar, konten, metadata, feedback, dan tindakan tetap dalam satu konteks.', `${button('Simpan Review', { variant: 'secondary', action: 'Review disimpan sebagai draf.' })}${button('Rekomendasikan Approval', { variant: 'primary', confirmTitle: 'Rekomendasikan approval?', confirm: 'Checklist review akan dikunci pada versi ini.', icon: 'bi-check2-circle' })}`)
                + conceptBanner(meta)
                + `<section class="km-workspace" aria-label="Review workspace">
                    <aside class="km-workspace-panel">
                        <div class="km-workspace-header"><h2>Review Queue</h2><p class="km-helper">7 ditugaskan kepada Anda</p></div>
                        <div class="km-workspace-body">${taskList([
                            { title: 'Incoming Material', meta: 'Due hari ini · High', status: 'Aktif', kind: 'danger' },
                            { title: 'Setup Wire Cut', meta: 'Due besok · Medium', status: 'Revisi', kind: 'warning' },
                            { title: 'Serah Terima Material', meta: 'Due 28 Jul · Normal', status: 'Baru', kind: 'info' },
                        ])}</div>
                    </aside>
                    <article class="km-workspace-panel" aria-label="Konten submission">
                        <div class="km-workspace-header"><h2>Instruksi Inspeksi Incoming Material</h2><p class="km-helper">KM-2026-0154 · Dimas Pratama</p></div>
                        <div class="km-workspace-body"><div class="km-document-page"><h2>Instruksi Inspeksi Incoming Material</h2><p>Dokumen ini menjelaskan pemeriksaan awal material tool steel saat diterima.</p><h3>1. Pemeriksaan dokumen</h3><p>Bandingkan purchase order, surat jalan, dan mill certificate. Pastikan <span class="km-inline-comment">heat number pada semua dokumen konsisten</span>.</p><h3>2. Pemeriksaan fisik</h3><p>Periksa grade, dimensi, kondisi permukaan, serta label pemasok. Pisahkan material bila ditemukan ketidaksesuaian.</p><h3>3. Pencatatan</h3><p>Catat hasil pada form incoming inspection dan unggah evidence yang diperlukan.</p></div></div>
                    </article>
                    <aside class="km-workspace-panel">
                        <div class="km-workspace-header"><h2>Review Panel</h2><p class="km-helper">2 dari 4 checklist selesai</p></div>
                        <div class="km-workspace-body">
                            <div class="km-tabs" role="tablist"><button class="km-tab" role="tab" aria-selected="true">Checklist</button><button class="km-tab" role="tab" aria-selected="false">Komentar (2)</button><button class="km-tab" role="tab" aria-selected="false">Metadata</button></div>
                            <fieldset class="km-fieldset"><legend>Review checklist</legend><label class="km-checkbox-row"><input type="checkbox" checked><span>Judul dan sinopsis jelas</span></label><label class="km-checkbox-row"><input type="checkbox" checked><span>Struktur heading benar</span></label><label class="km-checkbox-row"><input type="checkbox"><span>Referensi teknis valid</span></label><label class="km-checkbox-row"><input type="checkbox"><span>Tidak ada data sensitif</span></label></fieldset>
                            <div style="height:var(--km-space-4)"></div>
                            <div class="km-field"><label for="review-feedback">General feedback</label><textarea class="km-textarea" id="review-feedback">Mohon tambahkan kriteria penerimaan untuk kondisi permukaan.</textarea></div>
                            <div class="km-page-actions" style="margin-top:var(--km-space-4)">${button('Minta Revisi', { variant: 'danger', confirmTitle: 'Minta revisi?', confirm: 'Alasan dan feedback wajib akan dikirim kepada contributor.' })}</div>
                        </div>
                    </aside>
                </section>`;
        },

        'approval-queue'() {
            const meta = getScreen('approval-queue');
            return pageHeading(meta, 'Queue produksi tetap satu tahap; indikator SLA dan assignment pada prototype menunggu keputusan proses.', button('Buka Workspace', { variant: 'primary', target: 'approval-workspace', icon: 'bi-person-check' }))
                + conceptBanner(meta)
                + `<section class="km-filter-bar">
                    <div class="km-field"><label for="approval-search">Cari submission</label><input class="km-input" id="approval-search" type="search" placeholder="ID, judul, atau contributor"></div>
                    <div class="km-field"><label for="approval-priority">Priority</label><select class="km-select" id="approval-priority"><option>Semua priority</option><option>Tinggi</option></select></div>
                    <div class="km-field"><label for="approval-dept">Department</label><select class="km-select" id="approval-dept"><option>Semua department</option><option>Quality</option></select></div>
                    <div class="km-field"><label for="approval-due">Due date</label><select class="km-select" id="approval-due"><option>Semua</option><option>Hari ini</option></select></div>
                    ${button('Terapkan', { variant: 'primary', action: 'Filter approval queue diterapkan.' })}
                </section>
                <section class="km-section">${table(
                    ['Pilih', 'Submission', 'Contributor', 'Kategori', 'Rekomendasi', 'Risiko', 'Due', 'Aksi'],
                    [
                        `<tr><td><input type="checkbox" aria-label="Pilih Prosedur Heat Treatment Vacuum"></td><td>${cellTitle('KM-2026-0156 · Prosedur Heat Treatment Vacuum', 'Dikirim 24 Jul 2026')}</td><td>Rina Kusuma</td><td>Heat Treatment</td><td>${status('Siap disetujui', 'success')}</td><td>${status('Tinggi', 'danger')}</td><td>Hari ini</td><td>${button('Tinjau', { size: 'sm', variant: 'primary', target: 'approval-workspace' })}</td></tr>`,
                        `<tr><td><input type="checkbox" aria-label="Pilih Standar Pemeriksaan HRC"></td><td>${cellTitle('KM-2026-0153 · Standar Pemeriksaan Kekerasan HRC', 'Dikirim 23 Jul 2026')}</td><td>Budi Hartono</td><td>Quality</td><td>${status('Siap disetujui', 'success')}</td><td>${status('Sedang', 'warning')}</td><td>Besok</td><td>${button('Tinjau', { size: 'sm', target: 'approval-workspace' })}</td></tr>`,
                        `<tr><td><input type="checkbox" aria-label="Pilih Panduan Packaging Ekspor"></td><td>${cellTitle('KM-2026-0149 · Panduan Packaging Ekspor', 'Dikirim 22 Jul 2026')}</td><td>Ayu Lestari</td><td>Warehouse</td><td>${status('Perlu perhatian', 'warning')}</td><td>${status('Rendah', 'neutral')}</td><td>29 Jul</td><td>${button('Tinjau', { size: 'sm', target: 'approval-workspace' })}</td></tr>`,
                    ],
                    'Approval queue satu tahap dengan informasi keputusan yang diprioritaskan.'
                )}<div class="km-card-footer" style="margin-top:var(--km-space-3);border:1px solid var(--km-color-border-subtle);border-radius:var(--km-radius-md);background:var(--km-color-surface)"><span class="km-helper">0 submission dipilih</span>${button('Proses Batch', { variant: 'primary', disabled: true })}</div></section>`;
        },

        'approval-workspace'() {
            const meta = getScreen('approval-workspace');
            return pageHeading(meta, 'Ringkasan keputusan menempatkan risiko, rekomendasi, histori, dan dokumen pada konteks yang sama.', `${button('Minta Revisi', { variant: 'secondary', confirmTitle: 'Minta revisi?', confirm: 'Alasan wajib akan disimpan pada approval event.' })}${button('Setujui Knowledge', { variant: 'primary', confirmTitle: 'Setujui knowledge?', confirm: 'Knowledge akan langsung berstatus Terbit pada workflow satu tahap.', icon: 'bi-check2' })}`)
                + conceptBanner(meta)
                + `<section class="km-grid km-grid-3">
                    <article class="km-span-2 km-grid">
                        ${card('Decision brief', `<div class="km-grid km-grid-3"><div><span class="km-helper">Contributor</span><strong style="display:block">Rina Kusuma</strong></div><div><span class="km-helper">Kategori</span><strong style="display:block">Heat Treatment</strong></div><div><span class="km-helper">Risk</span><strong style="display:block">${status('Tinggi', 'danger')}</strong></div><div><span class="km-helper">Target pembaca</span><strong style="display:block">Sec. Head, All Employee</strong></div><div><span class="km-helper">Rekomendasi reviewer</span><strong style="display:block">Siap disetujui</strong></div><div><span class="km-helper">Due date</span><strong style="display:block">Hari ini, 17.00</strong></div></div>`)}
                        ${card('Ringkasan knowledge', `<div class="km-document-page" style="min-height:auto;box-shadow:none"><h2>Prosedur Heat Treatment Vacuum</h2><p>Prosedur pengaturan parameter, pemantauan, dan verifikasi hasil proses vacuum furnace untuk material tool steel.</p><h3>Kontrol kritis</h3><p>Operator wajib memverifikasi recipe, thermocouple, vacuum level, dan cooling pressure sebelum siklus dimulai.</p></div>`, { headerAction: button('Private Preview', { size: 'sm', action: 'Preview file private dibuka.' }) })}
                        ${card('Outstanding comment', `<div class="km-alert km-alert-success">${icon('bi-check-circle')}<div><h3>Tidak ada komentar terbuka</h3><p>Semua feedback reviewer telah ditanggapi contributor.</p></div></div>`)}
                    </article>
                    <aside>
                        ${card('Approval timeline', timeline([
                            { title: 'Draf dibuat', meta: 'Rina Kusuma · 19 Jul', state: 'complete' },
                            { title: 'Review selesai', meta: 'Budi Hartono · 23 Jul', state: 'complete' },
                            { title: 'Persetujuan aktif', meta: 'Maya Santoso · saat ini', state: 'current' },
                            { title: 'Terbit', meta: 'Setelah disetujui' },
                        ]))}
                        <div style="height:var(--km-space-4)"></div>
                        ${card('Keputusan', `<div class="km-field"><label for="decision-note">Catatan keputusan</label><textarea class="km-textarea" id="decision-note" placeholder="Tambahkan catatan bila diperlukan"></textarea></div><div class="km-page-actions" style="margin-top:var(--km-space-4)">${button('Tolak', { variant: 'danger', confirmTitle: 'Tolak pengajuan?', confirm: 'Alasan penolakan wajib diisi dan akan disimpan pada audit event.' })}</div>`)}
                    </aside>
                </section>`;
        },

        'version-comparison'() {
            const meta = getScreen('version-comparison');
            return pageHeading(meta, 'Bandingkan perubahan konten dan metadata tanpa menghilangkan versi yang pernah disetujui.', button('Kembali ke Review', { variant: 'primary', target: 'review-workspace', icon: 'bi-arrow-left' }))
                + conceptBanner(meta)
                + `<section class="km-card">
                    <div class="km-card-header"><div><h2 class="km-card-title">Prosedur Heat Treatment Vacuum</h2><p class="km-card-description">Versi 1.2 (usulan) dibanding versi 1.1 (terbit)</p></div><div class="km-tag-row">${status('12 perubahan', 'info')}${status('3 komentar', 'warning')}</div></div>
                    <div class="km-card-body">
                        <div class="km-tabs" role="tablist"><button class="km-tab" role="tab" aria-selected="true">Side by side</button><button class="km-tab" role="tab" aria-selected="false">Inline changes</button><button class="km-tab" role="tab" aria-selected="false">Metadata</button></div>
                        <div class="km-grid km-grid-2">
                            <article class="km-document-page"><p class="km-eyebrow">Versi 1.1 · Terbit</p><h2>Kontrol Proses</h2><p>Operator memeriksa recipe dan thermocouple sebelum proses dimulai.</p><h3>Cooling</h3><p>Gunakan nitrogen sesuai parameter standar.</p></article>
                            <article class="km-document-page"><p class="km-eyebrow">Versi 1.2 · Usulan</p><h2>Kontrol Proses</h2><p>Operator dan Sec. Head memeriksa recipe, thermocouple, <span class="km-inline-comment">vacuum leak test, dan alarm history</span> sebelum proses dimulai.</p><h3>Cooling</h3><p>Gunakan nitrogen <span class="km-inline-comment">dengan pressure 6–8 bar</span> sesuai parameter tervalidasi.</p></article>
                        </div>
                    </div>
                </section>`;
        },

        notifications() {
            const meta = getScreen('notifications');
            return pageHeading(meta, 'Setiap notifikasi menjelaskan kejadian, knowledge terkait, actor, waktu, dan tindakan berikutnya.', button('Tandai Semua Dibaca', { variant: 'primary', action: 'Semua notifikasi ditandai dibaca.', icon: 'bi-check2-all' }))
                + conceptBanner(meta)
                + `<section class="km-grid km-grid-3">
                    <aside>${card('Kategori', `<nav><ul class="km-list"><li class="km-list-item"><span class="km-list-icon">${icon('bi-inbox')}</span><span class="km-list-copy"><strong>Semua</strong><span>12 notifikasi</span></span>${status('3 baru', 'info')}</li><li class="km-list-item"><span class="km-list-icon">${icon('bi-send')}</span><span class="km-list-copy"><strong>Submission</strong><span>2 notifikasi</span></span></li><li class="km-list-item"><span class="km-list-icon">${icon('bi-check2-square')}</span><span class="km-list-copy"><strong>Approval</strong><span>4 notifikasi</span></span></li><li class="km-list-item"><span class="km-list-icon">${icon('bi-arrow-repeat')}</span><span class="km-list-copy"><strong>Revision</strong><span>3 notifikasi</span></span></li></ul></nav>`)}</aside>
                    <div class="km-span-2">${card('Terbaru', taskList([
                        { title: 'Revisi diperlukan untuk “Checklist Verifikasi Dies”', meta: 'Maya Santoso meminta revisi · 24 Jul 2026, 15.20 · Tindakan: perbaiki draf', status: 'Belum dibaca', kind: 'info', icon: 'bi-arrow-repeat' },
                        { title: '“Standar Packaging Ekspor” telah diterbitkan', meta: 'Disetujui oleh Maya Santoso · 21 Jul 2026, 10.08', status: 'Terbit', kind: 'success', icon: 'bi-check2-circle' },
                        { title: 'Persetujuan “Heat Treatment Vacuum” menunggu Anda', meta: 'Dikirim oleh Rina Kusuma · 24 Jul 2026, 09.10 · Tindakan: ambil keputusan', status: 'Perlu tindakan', kind: 'warning', icon: 'bi-hourglass-split' },
                        { title: 'Anda disebut pada feedback review', meta: 'Budi Hartono · 23 Jul 2026, 16.44', status: 'Mention', kind: 'info', icon: 'bi-at' },
                    ]), { footer: `<span class="km-helper">Menampilkan 4 dari 12</span>${button('Preferensi', { size: 'sm', action: 'Preferensi notifikasi dibuka.' })}` })}</div>
                </section>`;
        },

        analytics() {
            const meta = getScreen('analytics');
            return pageHeading(meta, 'Data agregat operasional untuk memahami penggunaan knowledge; bukan KPI resmi dan tanpa identitas pembaca.', `${button('Export XLSX', { variant: 'secondary', action: 'Export data agregat dibuat.', icon: 'bi-file-earmark-spreadsheet' })}${button('Export PDF', { variant: 'primary', action: 'Export PDF operasional dibuat.', icon: 'bi-file-earmark-pdf' })}`)
                + conceptBanner(meta)
                + `<div class="km-alert" role="note">${icon('bi-info-circle')}<div><h2>Materi Populer — data operasional, bukan KPI</h2><p>Counter historis sebelum hardening mungkin memiliki keterbatasan. Jangan gunakan laporan ini untuk menilai individu.</p></div></div>
                <section class="km-section km-filter-bar">
                    <div class="km-field"><label for="analytics-date">Periode</label><select class="km-select" id="analytics-date"><option>30 hari terakhir</option><option>90 hari terakhir</option></select></div>
                    <div class="km-field"><label for="analytics-category">Kategori</label><select class="km-select" id="analytics-category"><option>Semua kategori</option><option>Quality</option></select></div>
                    <div class="km-field"><label for="analytics-owner">Owner</label><select class="km-select" id="analytics-owner"><option>Semua owner</option></select></div>
                    <div class="km-field"><label for="analytics-status">Status</label><select class="km-select" id="analytics-status"><option>Terbit</option></select></div>
                    ${button('Terapkan', { variant: 'primary', action: 'Filter analytics diterapkan.' })}
                </section>
                <section class="km-section km-grid km-grid-4">
                    ${kpi('Total Dilihat', '12.480', 'Agregat seluruh materi', { icon: 'bi-eye' })}
                    ${kpi('Pembaca Selesai', '2.164', 'Distinct user agregat', { icon: 'bi-check2-circle', tone: 'success' })}
                    ${kpi('Knowledge Terpopuler', 'Heat Treatment', '842 total views', { icon: 'bi-trophy' })}
                    ${kpi('Pencarian Tanpa Hasil', '—', 'Belum tersedia; perlu event analytics', { icon: 'bi-search', tone: 'warning' })}
                </section>
                <section class="km-section km-grid km-grid-2">
                    ${card('Tren penggunaan', trendChart('usage-trend', 'Tren total view knowledge', 'Total view mingguan meningkat 28 persen selama delapan minggu terakhir; angka adalah data dummy prototype.'), { description: 'Total view agregat per minggu.' })}
                    ${card('Kategori teratas', barList([
                        { label: 'Quality', value: 88, display: '3.421' },
                        { label: 'Heat Treatment', value: 74, display: '2.876' },
                        { label: 'Machining', value: 61, display: '2.362' },
                        { label: 'Warehouse', value: 39, display: '1.508' },
                    ]), { description: 'Jumlah view; tabel menjadi alternatif aksesibilitas.' })}
                </section>
                <section class="km-section">${table(
                    ['Peringkat', 'Knowledge', 'Kategori', 'Total view', 'Pembaca selesai', 'Like'],
                    [
                        `<tr><td>1</td><td>${cellTitle('Prosedur Heat Treatment Vacuum', 'Rina Kusuma')}</td><td>Heat Treatment</td><td class="is-numeric">842</td><td class="is-numeric">164</td><td class="is-numeric">73</td></tr>`,
                        `<tr><td>2</td><td>${cellTitle('Panduan Identifikasi Tool Steel', 'Dimas Pratama')}</td><td>Quality</td><td class="is-numeric">731</td><td class="is-numeric">149</td><td class="is-numeric">61</td></tr>`,
                        `<tr><td>3</td><td>${cellTitle('Checklist Setup MCT', 'Nur Aini')}</td><td>Machining</td><td class="is-numeric">654</td><td class="is-numeric">132</td><td class="is-numeric">58</td></tr>`,
                    ],
                    'Materi populer dengan urutan operasional deterministik.'
                )}</section>`;
        },

        'user-management'() {
            const meta = getScreen('user-management');
            return pageHeading(meta, 'Kelola keterkaitan user aktif dengan persona dan akses KM tanpa mengganti sumber organisasi authoritative.', button('Tambah Akses', { variant: 'primary', action: 'Dialog assignment akses dibuka.', icon: 'bi-person-plus' }))
                + conceptBanner(meta)
                + `<section class="km-filter-bar">
                    <div class="km-field"><label for="user-search">Cari pengguna</label><input class="km-input" id="user-search" type="search" placeholder="Nama, email, atau NPK"></div>
                    <div class="km-field"><label for="user-role">Peran KM</label><select class="km-select" id="user-role"><option>Semua peran</option><option>Contributor</option><option>Approver</option></select></div>
                    <div class="km-field"><label for="user-dept">Department</label><select class="km-select" id="user-dept"><option>Semua department</option><option>Quality</option></select></div>
                    <div class="km-field"><label for="user-status">Status akun</label><select class="km-select" id="user-status"><option>Aktif</option><option>Nonaktif</option></select></div>
                    ${button('Terapkan', { variant: 'primary', action: 'Filter pengguna diterapkan.' })}
                </section>
                <section class="km-section">${table(
                    ['Pengguna', 'NPK', 'Department', 'Peran aplikasi', 'Akses KM', 'Status', 'Aksi'],
                    [
                        `<tr><td>${cellTitle('Maya Santoso', 'maya.santoso@adasi.co.id')}</td><td>001284</td><td>Human Resources</td><td>Ka. Dept</td><td>Contributor, Approver</td><td>${status('Aktif', 'success')}</td><td>${button('Kelola', { size: 'sm', action: 'Detail akses Maya Santoso dibuka.' })}</td></tr>`,
                        `<tr><td>${cellTitle('Dimas Pratama', 'dimas.pratama@adasi.co.id')}</td><td>001452</td><td>Quality</td><td>Staff</td><td>Contributor</td><td>${status('Aktif', 'success')}</td><td>${button('Kelola', { size: 'sm', action: 'Detail akses Dimas Pratama dibuka.' })}</td></tr>`,
                        `<tr><td>${cellTitle('Rina Kusuma', 'rina.kusuma@adasi.co.id')}</td><td>001317</td><td>Heat Treatment</td><td>Sec. Head</td><td>Contributor, Approver</td><td>${status('Aktif', 'success')}</td><td>${button('Kelola', { size: 'sm', action: 'Detail akses Rina Kusuma dibuka.' })}</td></tr>`,
                    ],
                    'Pengguna aktif dan akses Knowledge Management konseptual.'
                )}</section>`;
        },

        'role-permissions'() {
            const meta = getScreen('role-permissions');
            const allow = `<span class="km-permission-value is-allowed" aria-label="Diizinkan">${icon('bi-check-lg')}</span>`;
            const deny = `<span class="km-permission-value" aria-label="Tidak diizinkan">${icon('bi-dash')}</span>`;
            return pageHeading(meta, 'Matriks transparan untuk menyelaraskan menu dengan policy server-side.', button('Simpan Perubahan', { variant: 'primary', confirmTitle: 'Simpan permission?', confirm: 'Perubahan permission harus menghasilkan audit event dan regression test.' }))
                + conceptBanner(meta)
                + `<section class="km-alert km-alert-info" role="note">${icon('bi-shield-check')}<div><h2>Menu visibility bukan authorization</h2><p>Setiap endpoint read dan mutation tetap harus memeriksa policy atau Gate pada server.</p></div></section>
                <section class="km-section">${table(
                    ['Kemampuan', 'Employee', 'Contributor', 'Reviewer', 'Approver', 'KM Manager', 'Admin'],
                    [
                        `<tr><td>Lihat knowledge terbit</td><td>${allow}</td><td>${allow}</td><td>${allow}</td><td>${allow}</td><td>${allow}</td><td>${allow}</td></tr>`,
                        `<tr><td>Buat dan edit draf milik sendiri</td><td>${deny}</td><td>${allow}</td><td>${deny}</td><td>${deny}</td><td>${allow}</td><td>${allow}</td></tr>`,
                        `<tr><td>Review konten</td><td>${deny}</td><td>${deny}</td><td>${allow}</td><td>${deny}</td><td>${allow}</td><td>${allow}</td></tr>`,
                        `<tr><td>Setujui / tolak</td><td>${deny}</td><td>${deny}</td><td>${deny}</td><td>${allow}</td><td>${allow}</td><td>${allow}</td></tr>`,
                        `<tr><td>Lihat analytics operasional</td><td>${deny}</td><td>${deny}</td><td>${deny}</td><td>${allow}</td><td>${allow}</td><td>${allow}</td></tr>`,
                        `<tr><td>Kelola konfigurasi</td><td>${deny}</td><td>${deny}</td><td>${deny}</td><td>${deny}</td><td>${deny}</td><td>${allow}</td></tr>`,
                    ],
                    'Matriks future-state; role Reviewer dan KM Manager belum menjadi role authoritative pada produksi.'
                ).replace('class="km-table"', 'class="km-table km-permission-matrix"')}</section>`;
        },

        'category-management'() {
            const meta = getScreen('category-management');
            return pageHeading(meta, 'Jaga taxonomy ringkas, tidak tumpang tindih, dan memiliki owner yang jelas.', button('Tambah Kategori', { variant: 'primary', action: 'Form kategori baru dibuka.', icon: 'bi-plus-lg' }))
                + conceptBanner(meta)
                + `<section class="km-grid km-grid-3">
                    <div class="km-span-2">${table(
                        ['Kategori', 'Deskripsi', 'Knowledge', 'Owner', 'Status', 'Aksi'],
                        [
                            `<tr><td>${cellTitle('Quality', 'QLT')}</td><td>Inspeksi, standar kualitas, dan penanganan nonconformity.</td><td class="is-numeric">38</td><td>Quality Assurance</td><td>${status('Aktif', 'success')}</td><td>${button('Edit', { size: 'sm', action: 'Kategori Quality dibuka.' })}</td></tr>`,
                            `<tr><td>${cellTitle('Heat Treatment', 'HT')}</td><td>Proses furnace, parameter, dan verifikasi hasil.</td><td class="is-numeric">31</td><td>Heat Treatment</td><td>${status('Aktif', 'success')}</td><td>${button('Edit', { size: 'sm', action: 'Kategori Heat Treatment dibuka.' })}</td></tr>`,
                            `<tr><td>${cellTitle('Machining', 'MC')}</td><td>Setup, proses, tooling, dan abnormality machining.</td><td class="is-numeric">29</td><td>Production</td><td>${status('Aktif', 'success')}</td><td>${button('Edit', { size: 'sm', action: 'Kategori Machining dibuka.' })}</td></tr>`,
                            `<tr><td>${cellTitle('General', 'GEN')}</td><td>Materi umum yang belum dipetakan secara spesifik.</td><td class="is-numeric">42</td><td>Knowledge Manager</td><td>${status('Perlu ditinjau', 'warning')}</td><td>${button('Tinjau', { size: 'sm', action: 'Kategori General dibuka.' })}</td></tr>`,
                        ],
                        'Kategori KM dan jumlah knowledge terkait.'
                    )}</div>
                    ${card('Taxonomy health', barList([
                        { label: 'Memiliki owner', value: 92 },
                        { label: 'Deskripsi lengkap', value: 84 },
                        { label: 'Tanpa duplikat tag', value: 76 },
                    ]), { description: 'Data dummy untuk review taxonomy.' })}
                </section>`;
        },

        'workflow-config'() {
            const meta = getScreen('workflow-config');
            return pageHeading(meta, 'Visual builder hanya boleh diaktifkan setelah RACI, state matrix, SLA, delegation, dan exception disahkan.', button('Simpan sebagai Draf', { variant: 'primary', disabled: true, icon: 'bi-lock' }))
                + conceptBanner(meta)
                + `<section class="km-grid km-grid-3">
                    <div class="km-span-2">${card('Workflow: Knowledge Operasional', `<div class="km-workflow-canvas" aria-label="Diagram workflow konseptual"><div class="km-workflow-node"><strong>Contributor</strong><span>Membuat dan mengirim draf</span></div><div class="km-workflow-node"><strong>Reviewer</strong><span>Review konten dan checklist</span></div><div class="km-workflow-node"><strong>Approver</strong><span>Keputusan formal</span></div><div class="km-workflow-node"><strong>Terbit</strong><span>Publikasi ke audience</span></div></div>`, { description: 'Workflow produksi saat ini hanya Contributor → Approver → Terbit.' })}</div>
                    <aside>${card('Konfigurasi', `<div class="km-field"><label for="workflow-name">Nama workflow</label><input class="km-input" id="workflow-name" value="Knowledge Operasional"></div><div class="km-field" style="margin-top:var(--km-space-4)"><label for="workflow-mode">Mode approval</label><select class="km-select" id="workflow-mode"><option>Sequential</option><option>Parallel</option><option>Single approval</option></select></div><div class="km-field" style="margin-top:var(--km-space-4)"><label for="workflow-sla">SLA tahap</label><input class="km-input" id="workflow-sla" value="3 hari kerja" disabled><p class="km-helper">Menunggu definisi kalender kerja.</p></div>`)}</aside>
                </section>`;
        },

        'audit-log'() {
            const meta = getScreen('audit-log');
            return pageHeading(meta, 'Telusuri actor, tindakan, perubahan status, alasan, dan waktu tanpa mengekspos credential.', button('Export Audit', { variant: 'primary', action: 'Export audit read-only dibuat.', icon: 'bi-download' }))
                + conceptBanner(meta)
                + `<section class="km-filter-bar">
                    <div class="km-field"><label for="audit-search">Cari event</label><input class="km-input" id="audit-search" type="search" placeholder="ID knowledge atau actor"></div>
                    <div class="km-field"><label for="audit-action">Tindakan</label><select class="km-select" id="audit-action"><option>Semua tindakan</option><option>Submitted</option><option>Approved</option><option>Rejected</option></select></div>
                    <div class="km-field"><label for="audit-actor">Actor</label><select class="km-select" id="audit-actor"><option>Semua actor</option></select></div>
                    <div class="km-field"><label for="audit-date">Tanggal</label><input class="km-input" id="audit-date" type="date" value="2026-07-25"></div>
                    ${button('Terapkan', { variant: 'primary', action: 'Filter audit diterapkan.' })}
                </section>
                <section class="km-section">${card('Event terbaru', `<ol class="km-audit-list">
                    <li class="km-audit-event"><time datetime="2026-07-25T09:44:00+07:00">25 Jul · 09.44</time><div><strong>Knowledge disetujui</strong><p class="km-card-description">KM-2026-0153 · Menunggu Persetujuan → Terbit</p></div><div><strong>Maya Santoso</strong><small>Approver · IP disamarkan</small></div></li>
                    <li class="km-audit-event"><time datetime="2026-07-25T09:10:00+07:00">25 Jul · 09.10</time><div><strong>Knowledge dikirim</strong><p class="km-card-description">KM-2026-0156 · Draf → Menunggu Persetujuan</p></div><div><strong>Rina Kusuma</strong><small>Contributor</small></div></li>
                    <li class="km-audit-event"><time datetime="2026-07-24T15:20:00+07:00">24 Jul · 15.20</time><div><strong>Revisi diminta</strong><p class="km-card-description">KM-2026-0148 · Menunggu Persetujuan → Draf · Alasan tersimpan</p></div><div><strong>Maya Santoso</strong><small>Approver</small></div></li>
                    <li class="km-audit-event"><time datetime="2026-07-24T10:32:00+07:00">24 Jul · 10.32</time><div><strong>Draf diperbarui</strong><p class="km-card-description">KM-2026-0158 · revision counter 5 → 6</p></div><div><strong>Dimas Pratama</strong><small>Contributor</small></div></li>
                </ol>`, { description: 'Approval event tersedia saat ini; audit lintas konfigurasi tetap future-state.' })}</section>`;
        },

        'empty-states'() {
            const meta = getScreen('empty-states');
            return pageHeading(meta, 'Setiap state menjelaskan kondisi, dampak, dan tindakan pemulihan yang relevan.')
                + `<section class="km-state-gallery">
                    <article class="km-card km-empty-state"><div class="km-empty-state-inner"><span class="km-empty-icon">${icon('bi-journal-x')}</span><h2>Belum ada knowledge</h2><p>Knowledge yang dapat Anda akses akan tampil di sini setelah diterbitkan.</p>${button('Buka Panduan', { variant: 'primary', action: 'Panduan contributor dibuka.' })}</div></article>
                    <article class="km-card km-empty-state"><div class="km-empty-state-inner"><span class="km-empty-icon">${icon('bi-search')}</span><h2>Tidak ada hasil</h2><p>Coba gunakan kata kunci yang lebih umum atau hapus beberapa filter.</p>${button('Reset Filter', { variant: 'primary', target: 'knowledge-library' })}</div></article>
                    <article class="km-card km-empty-state"><div class="km-empty-state-inner"><span class="km-empty-icon" style="color:var(--km-color-success);background:var(--km-color-success-soft)">${icon('bi-check2-all')}</span><h2>Semua tugas selesai</h2><p>Tidak ada submission yang menunggu tindakan Anda saat ini.</p>${button('Lihat Aktivitas', { variant: 'primary', target: 'audit-log' })}</div></article>
                    <article class="km-card"><div class="km-card-header"><div><h2 class="km-card-title">Loading — card</h2><p class="km-card-description">Skeleton mempertahankan ruang untuk mencegah layout shift.</p></div></div><div class="km-card-body"><div class="km-skeleton" style="height:8rem"></div><div class="km-skeleton" style="height:1rem;width:76%;margin-top:var(--km-space-4)"></div><div class="km-skeleton" style="height:1rem;width:52%;margin-top:var(--km-space-2)"></div></div></article>
                    <article class="km-card"><div class="km-card-header"><div><h2 class="km-card-title">Loading — table</h2><p class="km-card-description">Header dan row height tetap stabil.</p></div></div><div class="km-card-body"><div class="km-skeleton" style="height:2.5rem"></div><div class="km-skeleton" style="height:3rem;margin-top:var(--km-space-2)"></div><div class="km-skeleton" style="height:3rem;margin-top:var(--km-space-2)"></div></div></article>
                    <article class="km-card km-empty-state"><div class="km-empty-state-inner"><span class="km-empty-icon" style="color:var(--km-color-success);background:var(--km-color-success-soft)">${icon('bi-cloud-check')}</span><h2>Perubahan tersimpan</h2><p>Draf terakhir disimpan otomatis pada 10.32 WIB.</p>${button('Lanjutkan', { variant: 'primary', target: 'create-knowledge' })}</div></article>
                </section>`;
        },

        'error-states'() {
            const meta = getScreen('error-states');
            return pageHeading(meta, 'Error message selalu menjelaskan penyebab, dampak, dan cara pemulihan.')
                + `<section class="km-state-gallery">
                    <article class="km-card km-empty-state"><div class="km-empty-state-inner"><span class="km-empty-icon" style="color:var(--km-color-danger);background:var(--km-color-danger-soft)">${icon('bi-shield-lock')}</span><h2>Akses ditolak</h2><p>Anda tidak memiliki izin untuk membuka knowledge ini. Hubungi administrator bila akses diperlukan untuk pekerjaan.</p>${button('Kembali ke Library', { variant: 'primary', target: 'knowledge-library' })}</div></article>
                    <article class="km-card km-empty-state"><div class="km-empty-state-inner"><span class="km-empty-icon" style="color:var(--km-color-danger);background:var(--km-color-danger-soft)">${icon('bi-wifi-off')}</span><h2>Data gagal dimuat</h2><p>Koneksi ke server terputus. Perubahan yang belum disimpan tetap berada di halaman ini.</p>${button('Coba Lagi', { variant: 'primary', action: 'Permintaan data dicoba kembali.' })}</div></article>
                    <article class="km-card km-empty-state"><div class="km-empty-state-inner"><span class="km-empty-icon" style="color:var(--km-color-warning);background:var(--km-color-warning-soft)">${icon('bi-hourglass-split')}</span><h2>Sesi berakhir</h2><p>Demi keamanan, sesi Anda telah berakhir. Masuk kembali untuk melanjutkan.</p>${button('Masuk Kembali', { variant: 'primary', target: 'login' })}</div></article>
                    <article class="km-card"><div class="km-card-header"><div><h2 class="km-card-title">Validation error</h2><p class="km-card-description">Fokus berpindah ke ringkasan error pertama.</p></div></div><div class="km-card-body"><div class="km-alert km-alert-danger" role="alert">${icon('bi-exclamation-circle')}<div><h3>Pengajuan belum dapat dikirim</h3><p>Perbaiki 2 field berikut: kategori dan file utama.</p></div></div><div class="km-field" style="margin-top:var(--km-space-4)"><label for="error-category">Kategori <span aria-hidden="true">*</span></label><select class="km-select" id="error-category" aria-invalid="true" aria-describedby="error-category-message"><option value="">Pilih kategori</option></select><p class="km-error-message" id="error-category-message" role="alert">Pilih kategori knowledge.</p></div></div></article>
                    <article class="km-card"><div class="km-card-header"><div><h2 class="km-card-title">Conflict error</h2><p class="km-card-description">Mencegah autosave menimpa perubahan yang lebih baru.</p></div></div><div class="km-card-body"><div class="km-alert km-alert-danger" role="alert">${icon('bi-files')}<div><h3>Draf telah diperbarui di sesi lain</h3><p>Muat versi terbaru, lalu gabungkan perubahan Anda sebelum menyimpan kembali.</p></div></div><div class="km-page-actions" style="margin-top:var(--km-space-4)">${button('Muat Versi Terbaru', { variant: 'primary', action: 'Versi terbaru dimuat.' })}</div></div></article>
                    <article class="km-card"><div class="km-card-header"><div><h2 class="km-card-title">File preview error</h2><p class="km-card-description">Download private tetap tersedia bila policy mengizinkan.</p></div></div><div class="km-card-body"><div class="km-alert km-alert-danger" role="alert">${icon('bi-file-earmark-x')}<div><h3>Preview tidak tersedia</h3><p>Format file tidak dapat ditampilkan. Unduh file untuk membukanya dengan aplikasi yang sesuai.</p></div></div><div class="km-page-actions" style="margin-top:var(--km-space-4)">${button('Unduh File', { variant: 'primary', action: 'File private diunduh.' })}</div></div></article>
                </section>`;
        },
    };

    function getScreen(id) {
        return SCREEN_DEFINITIONS.find((screen) => screen.id === id) || SCREEN_DEFINITIONS[0];
    }

    function isRoleVisible(screen) {
        return state.designMode || screen.roles.includes(state.role);
    }

    function renderNavigation() {
        const groups = [...new Set(SCREEN_DEFINITIONS.map((screen) => screen.group))];
        navRoot.innerHTML = groups.map((group) => {
            const screens = SCREEN_DEFINITIONS.filter((screen) => screen.group === group && isRoleVisible(screen));
            if (!screens.length) {
                return '';
            }
            return `<section class="km-nav-group" aria-labelledby="nav-${group.replaceAll(' ', '-').toLowerCase()}">
                <h2 class="km-nav-label" id="nav-${group.replaceAll(' ', '-').toLowerCase()}">${group}</h2>
                <ul class="km-nav-list">${screens.map((screen) => `
                    <li class="km-nav-item">
                        <button class="km-nav-button" type="button" data-screen-target="${screen.id}"
                            ${state.activeScreen === screen.id ? 'aria-current="page"' : ''}
                            title="${screen.nav}">
                            ${icon(screen.icon)}
                            <span class="km-nav-text">${screen.nav}</span>
                            ${screen.availability === 'concept' ? '<span class="km-nav-flag">Konsep</span>' : screen.availability === 'partial' ? '<span class="km-nav-flag">Parsial</span>' : ''}
                        </button>
                    </li>`).join('')}</ul>
            </section>`;
        }).join('');
    }

    function renderScreen(id, options = {}) {
        const meta = getScreen(id);
        state.activeScreen = meta.id;
        document.body.classList.toggle('km-auth-screen', meta.id === 'login');
        const renderer = renderers[meta.id] || renderers['error-states'];
        root.innerHTML = renderer();
        renderNavigation();
        document.title = `${meta.nav} — Prototype Fastware KM`;
        if (options.updateHash !== false) {
            history.replaceState(null, '', `#${meta.id}`);
        }
        document.body.classList.remove('km-sidebar-open');
        document.getElementById('mobile-menu').setAttribute('aria-expanded', 'false');
        if (options.focus !== false) {
            requestAnimationFrame(() => main.focus());
        }
    }

    function showToast(title, message = 'Aksi prototype berhasil dijalankan.') {
        const toast = document.createElement('div');
        toast.className = 'km-toast';
        toast.setAttribute('role', 'status');
        toast.innerHTML = `${icon('bi-check-circle')}<div><strong>${escapeHtml(title)}</strong><p>${escapeHtml(message)}</p></div><button class="km-icon-button" type="button" aria-label="Tutup notifikasi" data-toast-close>${icon('bi-x-lg')}</button>`;
        toastRegion.append(toast);
        window.setTimeout(() => toast.remove(), 4500);
    }

    function openConfirmation(trigger) {
        dialogTitle.textContent = trigger.dataset.confirmTitle || 'Konfirmasi tindakan';
        dialogDescription.textContent = 'Tinjau dampak tindakan sebelum melanjutkan.';
        dialogBody.innerHTML = `<div class="km-alert km-alert-info">${icon('bi-info-circle')}<div><h3>Informasi keputusan</h3><p>${escapeHtml(trigger.dataset.confirm || 'Pastikan data sudah benar sebelum melanjutkan.')}</p></div></div>`;
        dialogConfirm.textContent = trigger.dataset.confirmTitle?.toLowerCase().includes('tolak') ? 'Tolak Pengajuan' : 'Konfirmasi';
        dialogConfirm.onclick = () => {
            dialog.close();
            showToast('Keputusan dicatat pada prototype', 'Pada implementasi, server wajib memvalidasi policy dan status asal.');
        };
        dialog.showModal();
    }

    document.addEventListener('click', (event) => {
        const targetButton = event.target.closest('[data-screen-target]');
        if (targetButton) {
            renderScreen(targetButton.dataset.screenTarget);
            return;
        }

        const actionButton = event.target.closest('[data-demo-action]');
        if (actionButton) {
            showToast('Aksi prototype', actionButton.dataset.demoAction);
            return;
        }

        const confirmButton = event.target.closest('[data-confirm]');
        if (confirmButton) {
            openConfirmation(confirmButton);
            return;
        }

        const closeDialog = event.target.closest('[data-dialog-close]');
        if (closeDialog) {
            dialog.close();
            return;
        }

        const closeToast = event.target.closest('[data-toast-close]');
        if (closeToast) {
            closeToast.closest('.km-toast')?.remove();
            return;
        }

        const tab = event.target.closest('.km-tab');
        if (tab) {
            tab.parentElement.querySelectorAll('.km-tab').forEach((item) => item.setAttribute('aria-selected', String(item === tab)));
            showToast('Tab dipilih', `${tab.textContent.trim()} aktif pada prototype.`);
        }
    });

    document.getElementById('sidebar-collapse').addEventListener('click', (event) => {
        const collapsed = document.body.classList.toggle('km-sidebar-collapsed');
        event.currentTarget.setAttribute('aria-expanded', String(!collapsed));
        event.currentTarget.setAttribute('aria-label', collapsed ? 'Perluas sidebar' : 'Ciutkan sidebar');
    });

    document.getElementById('mobile-menu').addEventListener('click', (event) => {
        const open = document.body.classList.toggle('km-sidebar-open');
        event.currentTarget.setAttribute('aria-expanded', String(open));
    });

    document.getElementById('sidebar-scrim').addEventListener('click', () => {
        document.body.classList.remove('km-sidebar-open');
        document.getElementById('mobile-menu').setAttribute('aria-expanded', 'false');
    });

    document.getElementById('role-switcher').addEventListener('change', (event) => {
        state.role = event.target.value;
        renderNavigation();
        if (!state.designMode) {
            renderScreen(ROLE_DASHBOARDS[state.role]);
        } else {
            showToast('Peran prototype diubah', ROLE_LABELS[state.role]);
        }
    });

    document.getElementById('design-mode-toggle').addEventListener('change', (event) => {
        state.designMode = event.target.checked;
        if (!state.designMode && !getScreen(state.activeScreen).roles.includes(state.role)) {
            renderScreen(ROLE_DASHBOARDS[state.role]);
            return;
        }
        renderNavigation();
        showToast(state.designMode ? 'Mode desain aktif' : 'Navigasi berbasis peran aktif', state.designMode ? 'Seluruh 30 layar ditampilkan.' : `Menu disesuaikan untuk ${ROLE_LABELS[state.role]}.`);
    });

    document.getElementById('global-search-form').addEventListener('submit', (event) => {
        event.preventDefault();
        const query = new FormData(event.currentTarget).get('q');
        state.lastSearch = String(query || '').trim() || 'knowledge';
        renderScreen('search-results');
    });

    document.addEventListener('submit', (event) => {
        if (event.target.matches('[data-inline-search]')) {
            event.preventDefault();
            const input = event.target.querySelector('input[type="search"]');
            state.lastSearch = input?.value.trim() || 'knowledge';
            renderScreen('search-results');
        }
        if (event.target.matches('[data-demo-login]')) {
            event.preventDefault();
            renderScreen(ROLE_DASHBOARDS[state.role]);
            showToast('Berhasil masuk', `Dashboard ${ROLE_LABELS[state.role]} dibuka.`);
        }
    });

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            document.getElementById('global-search').focus();
        }
        if (event.key === 'Escape' && document.body.classList.contains('km-sidebar-open')) {
            document.body.classList.remove('km-sidebar-open');
            document.getElementById('mobile-menu').setAttribute('aria-expanded', 'false');
            document.getElementById('mobile-menu').focus();
        }
    });

    dialog.addEventListener('click', (event) => {
        const bounds = dialog.getBoundingClientRect();
        const outside = event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom;
        if (outside) {
            dialog.close();
        }
    });

    window.addEventListener('hashchange', () => {
        const id = window.location.hash.slice(1);
        if (SCREEN_DEFINITIONS.some((screen) => screen.id === id)) {
            renderScreen(id, { updateHash: false });
        }
    });

    const initialId = window.location.hash.slice(1);
    renderScreen(SCREEN_DEFINITIONS.some((screen) => screen.id === initialId) ? initialId : state.activeScreen, { updateHash: !initialId, focus: false });
})();
