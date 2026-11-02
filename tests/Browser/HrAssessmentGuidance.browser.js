// Isolated Chromium test: actual page accordion/summary markup and bundled Bootstrap,
// synthetic metadata and mocked fetch. No application navigation or DB endpoint calls.
// Set window.hrGuidanceOnly=true when testing multiple copies of Bootstrap.
(async () => {
    const checks = [];
    const check = (name, condition) => { if (!condition) throw new Error(name); checks.push(name); };
    const settle = async () => { for (let n = 0; n < 16; n++) await Promise.resolve(); };
    const event = (element, name) => new Promise((resolve, reject) => {
        const timeout = setTimeout(() => reject(new Error('Missing ' + name)), 1500);
        element.addEventListener(name, () => { clearTimeout(timeout); resolve(); }, { once: true });
    });
    const fixture = window.hrAssessmentFixture;
    document.body.innerHTML = fixture.accordion + '<form id="fixture-form"><input name="nilai_tc[]" value="3"><input name="year" value="2026">'
        + '<select id="jobPositionSelect"><option value="1">One</option><option value="2">Two</option><option value="3">Three</option></select>'
        + fixture.button + '</form>' + fixture.modal;
    const config = document.createElement('script');
    config.type = 'application/json';
    config.dataset.assessmentSummary = '';
    config.dataset.endpoint = '/fixture/__POSITION__';
    document.body.append(config);
    let calls = 0;
    const deferred = {};
    let fail = false;
    const rubric = { id: 1, judul_keterangan: 'Fixture category', deskripsi_1: 'One', deskripsi_2: 'Two', deskripsi_3: 'Three', deskripsi_4: 'Four' };
    const data = {
        tcs: [{ id: 1, keterangan_tc: 'Same name', deskripsi_tc: '<img src=x onerror="window.hrXss=true">', nilai: 3, poin_kategori: rubric },
            { id: 2, keterangan_tc: 'Same name', deskripsi_tc: 'Different definition', nilai: 4, poin_kategori: rubric }],
        softSkills: [{ id: 1, keterangan_sk: 'Soft fixture', deskripsi_sk: 'Soft description', nilai: 2, poin_kategori: rubric }],
        additionals: [{ id: 1, keterangan_ad: 'Additional fixture', deskripsi_ad: null, nilai: 3, poin_kategori: rubric }],
    };
    window.fetch = (url) => {
        calls++;
        const id = url.split('/').pop();
        if (id === '2' || id === '3') return new Promise((resolve) => { deferred[id] = resolve; });
        return Promise.resolve({ ok: !fail, json: async () => data });
    };
    document.dispatchEvent(new Event('DOMContentLoaded'));
    check('guidance initially visible', document.getElementById('collapseKeterangan').classList.contains('show')
        && document.getElementById('collapseKeterangan').getBoundingClientRect().height > 0);
    check('three existing rubrics remain', document.querySelectorAll('#collapseKeterangan .card-header').length === 3
        && document.querySelectorAll('#collapseKeterangan tbody tr').length === 12);
    check('extra information UI is absent', !document.querySelector('dialog,[data-competency-information],.hr-ci-header-button,[data-information-list]'));
    check('no automatic metadata request', calls === 0);
    let trigger = document.querySelector('[aria-controls="collapseKeterangan"]');
    let panel = document.getElementById('collapseKeterangan');
    trigger.click();
    check('guidance closes with correct aria', panel.hidden && !panel.classList.contains('show') && trigger.getAttribute('aria-expanded') === 'false');
    trigger.click();
    check('guidance reopens with correct aria', !panel.hidden && panel.classList.contains('show') && trigger.getAttribute('aria-expanded') === 'true');
    for (let i = 0; i < 6; i++) trigger.click();
    check('repeated toggles stay synchronized', !panel.hidden && panel.classList.contains('show') && trigger.getAttribute('aria-expanded') === 'true');
    trigger.click();
    await new Promise((resolve) => setTimeout(resolve, 500));
    check('duplicate Bootstrap handlers cannot reopen guidance', panel.hidden && !panel.classList.contains('show') && trigger.getAttribute('aria-expanded') === 'false');
    document.getElementById('accordionKeteranganPenilaian').outerHTML = fixture.accordion;
    check('fresh page markup always opens guidance', document.getElementById('collapseKeterangan').classList.contains('show')
        && document.querySelector('[aria-controls="collapseKeterangan"]').getAttribute('aria-expanded') === 'true');
    const summary = document.getElementById('openModalButton');
    if (summary && !window.hrGuidanceOnly) {
        const modal = document.getElementById('jobPositionModal');
        const details = document.getElementById('details');
        let done = event(modal, 'shown.bs.modal');
        summary.click();
        await done;
        await settle();
        check('original Summary modal opens', modal.classList.contains('show'));
        check('Summary tables restored', details.querySelectorAll('table').length === 3 && details.querySelectorAll('tbody tr').length === 4);
        check('same-name masters stay separate', details.textContent.includes('Different definition') && details.querySelector('tbody').children.length === 2);
        check('Summary description and rubric remain safe text', details.textContent.includes('<img') && !details.querySelector('img,svg,script')
            && !window.hrXss && details.textContent.includes('One') && details.textContent.includes('Four'));
        done = event(modal, 'hidden.bs.modal');
        modal.querySelector('[data-bs-dismiss="modal"]').click();
        await done;
        check('Summary closes and clears data', !modal.classList.contains('show') && !details.children.length);
        const select = document.getElementById('jobPositionSelect');
        const choose = (id) => { select.value = id; select.dispatchEvent(new Event('change')); };
        const loadOnly = () => summary.dispatchEvent(new MouseEvent('click'));
        choose('2'); loadOnly();
        choose('3'); loadOnly();
        deferred['3']({ ok: true, json: async () => ({ tcs: [{ id: 3, keterangan_tc: 'Latest definition' }] }) });
        await settle();
        deferred['2']({ ok: true, json: async () => data });
        await settle();
        check('late response cannot replace new Summary context', details.textContent.includes('Latest definition') && !details.textContent.includes('Different definition'));
        fail = true;
        choose('1'); loadOnly();
        await settle();
        check('Summary error clears old data', details.textContent === 'Gagal mengambil data. Coba lagi.');
        fail = false; loadOnly();
        await settle();
        check('Summary can retry', details.querySelectorAll('table').length === 3);
    } else if (!summary) {
        check('preview preserves original absence of Summary', !document.getElementById('jobPositionModal'));
    }
    const form = document.getElementById('fixture-form');
    check('score and year unchanged', new FormData(form).get('nilai_tc[]') === '3' && new FormData(form).get('year') === '2026');
    return { form: fixture.name, count: checks.length, summaryChecksSkipped: !!summary && !!window.hrGuidanceOnly, checks };
})
