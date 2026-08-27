# EXECUTION CONTEXT — OUTSTANDING MATERIAL V2

Repository: `poyipoy/fastware_adsi_1`  
Framework: Laravel 10  
Frontend: Bootstrap 5.3.2, Blade, jQuery, DataTables  
Database: MySQL

## Final Capability Policy

| Capability | Rule |
|---|---|
| View | Legacy Outstanding Material viewer OR active Sales job user |
| Manage | Existing manager rule, including role_id 1 and legacy manager names |
| Upload PL/MTC | Only `ILYAS NOOR FIRDAUS` |
| Download PL/MTC | Only active Sales job user |

Legacy viewers such as Jessica Paune, Fajar Bagaskara, and Vivian Angelika retain module view access but do not receive PL/MTC download unless they are also active Sales users.

## Security Invariants

- Mandatory invoice scope comes from backend route-model binding.
- Never trust invoice number or material IDs from the client.
- Validate every bulk-update material ID belongs to the submitted invoice.
- Do not delete files before database commit.
- Do not delete files still referenced by another row.
- No frontend-only authorization.
- Use testing database for mutating tests.
- Read current code before patching; line numbers in the mission are orientation only.
- Stop after completing this mission.

---

# MISSION 06
## Sticky Header, Sticky Filter & Reusable Frontend Components

## DEPENDENCY

MISSION 05 sudah selesai.

Semua business behavior sudah bekerja sebelum frontend refactor dilakukan.

## OBJECTIVE

Memperbaiki scrolling table dan mengurangi duplikasi yang aman tanpa melakukan redesign besar.

Target halaman:

```text
Outstanding Material Index
Invoice Detail Workspace
Grouped Invoice Page
```

## CONSTRAINTS

- Pertahankan design token `.om-*`.
- Jangan menambah CDN.
- Jangan mengganti DataTables.
- Jangan mengubah visual branding.
- Jangan mengubah business logic controller.
- Jangan memaksa refactor besar jika meningkatkan risiko regresi.

## STICKY BEHAVIOR

Saat vertical scroll di table container:

- Header nama kolom tetap terlihat.
- Filter row tetap terlihat di bawah header.
- Tidak overlap.
- Tidak ada gap.
- Background tidak transparan.
- Z-index benar.

Saat horizontal scroll:

- Header dan filter tetap sinkron dengan body.

Saat resize:

- Offset filter dihitung ulang.

## REMOVE HARDCODED OFFSET

Jangan bergantung pada:

```css
.om-filter-row th {
    top: 40px;
}
```

karena header dapat memiliki teks dua baris.

Gunakan dynamic measurement.

Contoh:

```js
function syncStickyOffsets(tableSelector) {
    const table = document.querySelector(tableSelector);

    if (!table) {
        return;
    }

    const firstHeaderRow = table.querySelector(
        'thead tr:first-child'
    );

    if (!firstHeaderRow) {
        return;
    }

    const headerHeight =
        Math.ceil(firstHeaderRow.getBoundingClientRect().height);

    table.style.setProperty(
        '--om-table-header-height',
        `${headerHeight}px`
    );
}
```

CSS:

```css
.om-table thead tr:first-child th {
    position: sticky;
    top: 0;
    z-index: 4;
    background: var(--om-gray-50);
}

.om-table thead .om-filter-row th {
    position: sticky;
    top: var(--om-table-header-height);
    z-index: 3;
    background: #fff;
}
```

Gunakan `ResizeObserver` jika tersedia.

Fallback:

```js
window.addEventListener('resize', ...);
```

Panggil setelah:

```text
DataTables init
DataTables draw
window resize
font/layout change yang relevan
```

## TABLE CONTAINER

Pastikan container memiliki:

```css
max-height: ...
overflow: auto;
overscroll-behavior: contain;
position: relative;
```

Jangan membuat page-wide sticky yang menutupi navbar utama.

## FILTER PANEL

Jika halaman memiliki toolbar/filter panel di luar `<thead>`:

- Pertahankan tetap terlihat hanya jika tidak menutupi application header.
- Gunakan offset berdasarkan layout aktual.
- Jangan hardcode tinggi navbar tanpa inspeksi.

Jika filter menggunakan inline filter row, requirement fixed filter dianggap terpenuhi oleh sticky filter row.

## FRONTEND REUSE

Ekstrak reusable component hanya jika behavior sudah stabil.

Prioritas:

```text
shared table styles
shared column markup
shared filter controls
shared DataTables initialization helper
shared sticky offset helper
```

Contoh lokasi:

```text
resources/views/outstanding_materials/partials/
public/assets/js/outstanding-materials/
public/assets/css/
```

Jangan memindahkan seluruh file besar sekaligus tanpa test.

Pendekatan aman:

1. Ekstrak sticky helper.
2. Ekstrak shared CSS.
3. Ekstrak table/filter markup yang benar-benar identik.
4. Ekstrak DataTables common options.
5. Biarkan page-specific options di masing-masing Blade.

## DATATABLES CONSIDERATIONS

Periksa:

- `scrollX`.
- Wrapper cloning header.
- `.dataTables_scrollHead`.
- Existing `.om-table-wrap`.
- `drawCallback`.
- Tooltip initialization.
- Filter event listener.
- Date range popover.
- Search delay.
- Export URL sync.

Jangan menghasilkan dua scrollbar horizontal yang saling bertumpuk.

## RESPONSIVE QA

Validasi minimal:

```text
Desktop 1920px
Laptop 1366px
Tablet width
Mobile width
Browser zoom 80%
Browser zoom 125%
```

Periksa:

- Two-line header.
- Date filter.
- Dropdown filter.
- Tooltip.
- Modal.
- Long invoice.
- Long filename.
- Pagination.

## TESTS & QA

Automated test tidak dapat membuktikan seluruh sticky behavior.

Lakukan:

- Blade render test.
- Asset reference check.
- Browser/manual QA.
- Console error check.
- DataTables AJAX check.

Pastikan tidak ada:

```text
undefined selector
duplicate event handler
double DataTables initialization
JS syntax error
missing asset
```

## ACCEPTANCE CRITERIA

- Header sticky bekerja.
- Filter row sticky bekerja.
- Offset mengikuti actual header height.
- Resize tidak merusak posisi.
- Horizontal scroll tetap sinkron.
- Tidak ada overlap.
- Tidak ada double scrollbar yang mengganggu.
- Index dan detail berbagi helper yang aman.
- Tidak ada CDN/framework baru.
- Tidak ada business behavior yang berubah.
- Manual QA terdokumentasi.

## FINAL REPORT

```text
1. Shared Components Created
2. Sticky Strategy
3. DataTables Integration
4. Responsive QA Result
5. Console/AJAX Validation
6. Files Changed
7. Remaining UI Risks
```

## STOP CONDITION

Berhenti setelah UI stabilization selesai.

Jangan mengerjakan MISSION 07.

---
