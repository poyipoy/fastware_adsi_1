# UX Redesign Knowledge Management — Enterprise Blueprint

**Status:** prototype desain read-only

**Tanggal:** 25 Juli 2026

**Stack target:** Laravel 10, Blade, Bootstrap 5.3.2, aset lokal

**Prototype:** `km-docs/prototypes/km-enterprise-redesign/index.html`

Dokumen ini adalah design handoff, bukan mission implementasi produksi. Prototype mencakup 30 layar yang diminta, tetapi tidak menambah route, status, role, tabel, policy, notification, SLA, versioning, archiving, atau workflow baru. Elemen yang bergantung pada keputusan stakeholder diberi label **Konsep** atau **Parsial**.

## Batas Produk yang Digunakan

### Kemampuan produksi yang terbukti tersedia

- Status dokumen aktif hanya `Tidak Aktif`, `Draf`, `Menunggu Persetujuan`, dan `Terbit`.
- Alur aktif adalah `Draf -> Menunggu Persetujuan -> Terbit`; penolakan mengembalikan dokumen ke `Draf` dan menyimpan alasan pada approval event.
- Contributor yang sah dapat membuat draf, mengubah draf miliknya, autosave metadata, menambah tag/co-author, mengganti file, mengirim, dan menonaktifkan sesuai policy.
- Approver yang sah dapat menyetujui atau menolak satu dokumen dan menjalankan bulk approval atomik pada workflow satu tahap.
- Employee dapat menelusuri knowledge terbit sesuai posisi/akses, mencari pada judul dan sinopsis dengan filter tag, membuka private preview/download, bookmark, like, insight, dan completion.
- Analytics yang sah saat ini adalah **Materi Populer — data operasional, bukan KPI**, tanpa identitas pembaca individual.
- Route dan view legacy `dsKnowlege`, `pengajuanKM`, `persetujuanKM`, serta folder `knowlege_management` adalah compatibility surface.

### Future-state yang tidak boleh dianggap telah disetujui

| Area desain | Gate keputusan | Perlakuan pada prototype |
|---|---|---|
| Versioning, versi current/published, compare version | KM-DEC-001 | Ditampilkan sebagai konsep; tidak ada model atau status baru |
| Reviewer terpisah, multi-stage approval, SLA, delegation, review cycle | KM-DEC-003 | Layar dan indikator diberi label konsep |
| Targeting berdasarkan master organisasi | KM-DEC-005 | Department hanya data dummy, bukan business key |
| Notification center, preference, mention delivery | KM-DEC-006 | Prototype interaksi tanpa pengiriman |
| Thread, inline comment, mention, moderation | KM-DEC-007 | Prototype review tanpa persistence |
| KPI, search analytics, drill-down individu | KM-DEC-009 | Hanya aggregate operational; KPI diberi label belum tersedia |
| Assignment/compliance dan due date resmi | KM-DEC-010 | Tidak diaktifkan sebagai workflow |
| Retention, archive otomatis, legal hold | KM-DEC-014 | Archived/expiry hanya future-state IA |

---

## 1. UX Audit dan Asumsi Masalah Utama

### Temuan utama

1. **Arsitektur navigasi belum berorientasi tugas KM.** Layout aktif adalah navbar global yang sangat panjang dengan nested dropdown dan aturan visibilitas tersebar. Dashboard, authoring, approval, dan analytics KM tidak terasa sebagai satu workspace.
2. **Terminologi dan hierarki tidak konsisten.** “Dashboard Knowledge Management”, “Pengajuan Knowledge Management”, “Halaman Persetujuan KM”, “Publish”, dan “Tampilan Data” menggunakan pola judul berbeda dan tidak selalu menjelaskan next action.
3. **Login belum memenuhi form UX dasar.** Field hanya memakai placeholder tanpa label terlihat, copy bercampur Inggris/Indonesia, atribut `lang` masih `en`, tidak ada recovery/error state yang jelas, dan dependency halaman terlalu berat untuk sebuah form login.
4. **Library memiliki filter kuat tetapi terlalu terbuka sekaligus.** Multi-select tag native dengan `size=3`, tanggal, status baca, sort, page size, dan bookmark berada pada satu bar. Fungsi tersedia, tetapi scanability dan progressive disclosure rendah.
5. **Card knowledge belum cukup mendukung keputusan sebelum membuka.** Owner utama, confidentiality, version, last updated, format, dan quick preview belum konsisten ditampilkan. Action icon masih kecil dan beberapa bergantung pada `title`.
6. **Tidak ada halaman detail knowledge yang stabil.** Pengguna berinteraksi melalui card, synopsis modal, dan PDF viewer modal. Konten panjang, metadata, related knowledge, attachment, approval history, dan activity tidak memiliki information hierarchy terpadu.
7. **Authoring berada di modal.** Pembuatan dan edit draf dipadatkan ke dialog. Ini cocok untuk draf minimum, tetapi tidak untuk progressive disclosure, autosave yang terlihat, review summary, duplicate warning, atau field kompleks.
8. **Daftar pengajuan belum menjawab “siapa memegang tindakan berikutnya”.** Tabel hanya menampilkan PIC, judul, status, dan icon action. Tidak ada ID submission, last activity, next action, atau status timeline.
9. **Approval memaksa approver berpindah konteks.** Daftar, form keputusan, private preview, kategori, target posisi, alasan, dan riwayat berada pada tabel/modal terpisah. Decision brief belum diprioritaskan.
10. **Reviewer dan approver belum merupakan persona terpisah.** Akses produksi saat ini menggabungkan kemampuan create/approve pada kelompok yang sama. UI tidak boleh mengklaim reviewer workflow terpisah sebelum RACI disahkan.
11. **Status masih mengandalkan warna dan istilah teknis.** Badge perlu ikon/teks, bukan warna saja. “Tidak Aktif” harus dibedakan dari archive/expired yang belum tersedia.
12. **Aksesibilitas lintas shell belum konsisten.** Skip link, heading hierarchy, focus management pada route change, touch target 44 px, error summary ber-anchor, dan reduced motion belum menjadi kontrak komponen global.
13. **Visual language terfragmentasi.** Cambria, Open Sans, Nunito, Poppins, Font Awesome, Bootstrap Icons, Boxicons, Remixicon, dan berbagai radius/shadow tampil bersama. Hal ini menurunkan konsistensi dan memperbesar beban asset.
14. **Dashboard consumer memuat leaderboard poin.** Governance poin historis masih decision-gated; leaderboard tidak semestinya menjadi hierarchy utama sampai ledger dan aturan resmi disahkan.

### Asumsi desain

- Bahasa utama UI adalah Bahasa Indonesia dengan istilah enterprise yang sudah umum, misalnya *review*, *approval*, dan *workflow* bila membantu presisi.
- Desktop 1280–1440 px adalah workspace utama; tablet 768–1024 px tetap fully operable.
- `Bootstrap Icons` lokal menjadi satu-satunya icon family pada prototype dan implementasi awal.
- Light theme adalah prioritas awal. Dark theme baru masuk mission setelah token semantic dan QA kontras disetujui.
- Sidebar baru adalah pola KM-scoped. Migrasi shell global aplikasi bukan bagian prototype ini.
- Akses tetap diputuskan oleh policy/Gate server-side. Role switcher pada prototype hanya alat review desain.

---

## 2. User Roles dan Kebutuhan Setiap Role

| Persona UX | Kebutuhan utama | Tugas prioritas | Pemetaan produksi saat ini |
|---|---|---|---|
| Employee / Knowledge Consumer | Menemukan materi sah dengan cepat; mengetahui relevansi dan kemutakhiran | Search, filter, preview, baca, bookmark, like, insight | Semua user terautentikasi, dibatasi published visibility/posisi |
| Knowledge Contributor | Membuat draf tanpa kehilangan data; memahami next action | Create/edit draf, autosave, tag/co-author, submit, lacak, perbaiki setelah penolakan | User yang lolos `canCreate()`; hanya draf milik sendiri kecuali full access |
| Reviewer | Memeriksa kualitas konten secara konsisten | Checklist, inline/general feedback, version compare, rekomendasi | **Belum terpisah**; future-state KM-DEC-003/007 |
| Approver | Mengambil keputusan formal dengan bukti cukup | Review decision brief, private preview, approve/reject, bulk action | User yang lolos `canApprove()`; workflow satu tahap |
| Knowledge Manager | Menjaga taxonomy, ownership, kualitas inventory, laporan agregat | Inventory health, taxonomy, operational analytics, governance | **Belum menjadi role authoritative** |
| Administrator | Menjaga akses, konfigurasi, dan auditability | User/access, permission matrix, config, audit | Full-access role aplikasi; konfigurasi KM dinamis belum tersedia |

### Prinsip pengalaman berbasis peran

- Dashboard pertama setelah login mengikuti pekerjaan utama role.
- Sidebar hanya menampilkan destinasi yang mempunyai ability; mode desain prototype dapat menampilkan semua layar untuk review.
- Primary CTA hanya satu per layar.
- Tugas yang menunggu user selalu menyebut knowledge, actor sebelumnya, waktu, dan next action.
- Destinasi yang belum tersedia tidak boleh silently disappear pada konfigurasi admin; berikan alasan atau label “Belum tersedia”.

---

## 3. Information Architecture

### IA target

```text
Knowledge Management
├── Dashboard
│   ├── Employee
│   ├── Contributor
│   ├── Reviewer [Konsep]
│   ├── Approver
│   └── Knowledge Manager / Admin [Parsial]
├── Temukan Knowledge
│   ├── Knowledge Library
│   ├── Explore Categories
│   ├── Search Results
│   └── Knowledge Detail [Parsial]
├── Kontribusi Saya
│   ├── Buat Knowledge
│   ├── Draf Saya
│   ├── Pengajuan Saya
│   ├── Detail Pengajuan
│   └── Revisi [Parsial]
├── Tugas & Keputusan
│   ├── Review Queue [Konsep]
│   ├── Review Workspace [Konsep]
│   ├── Approval Queue
│   ├── Approval Workspace
│   └── Version Comparison [Konsep]
├── Monitor
│   ├── Notifications [Konsep]
│   └── Analytics & Reports [Parsial: materi populer non-KPI]
├── Administrasi
│   ├── User & Access [Konsep KM]
│   ├── Role & Permission [Konsep]
│   ├── Category & Taxonomy [Konsep management]
│   ├── Workflow Configuration [Konsep]
│   └── Audit Log [Parsial: approval events tersedia]
└── Help & Guidelines
```

### Navigasi global

- **Sidebar collapsible:** top-level destinations dikelompokkan berdasarkan pekerjaan, bukan struktur database.
- **Topbar:** global search, quick create, notification, help, role/workspace switcher, profile.
- **Breadcrumb:** dipakai mulai kedalaman dua; nama route legacy tidak terlihat pada microcopy.
- **Contextual actions:** ditempatkan di kanan page heading, satu primary CTA maksimal.
- **Deep link:** setiap screen produksi harus mempunyai URL stabil; modal tidak menjadi primary navigation flow.

---

## 4. End-to-End User Flow

### A. Pencarian dan konsumsi knowledge — siap dengan kemampuan sekarang

```text
Dashboard/Library
  -> masukkan kata kunci
  -> server menerapkan published visibility
  -> FULLTEXT judul + sinopsis
  -> filter tag/kategori/status baca
  -> hasil + empty/no-result recovery
  -> detail/quick preview
  -> policy view
  -> private preview/download
  -> bookmark/like/insight/completion
```

Guardrail: UI tidak menyebut pencarian isi PDF/PPT karena backend hanya mengindeks metadata.

### B. Pengajuan knowledge — alur produksi satu tahap

```text
Contributor membuat draf
  -> upload file private
  -> tambah metadata/tag/co-author
  -> autosave/edit selama status Draf
  -> review summary
  -> submit
  -> status Menunggu Persetujuan + approval event
  -> approver memeriksa
     ├── approve -> status Terbit + approval event
     └── reject  -> status Draf + alasan event -> contributor memperbaiki -> submit ulang
```

Guardrail: label “Draf — revisi diminta” adalah presentasi turunan dari status Draf + approval event terakhir, bukan status baru.

### C. Review terpisah — future-state

```text
Submit
  -> assignment reviewer
  -> review checklist + feedback
  -> contributor revision loop
  -> reviewer recommendation
  -> approver decision
  -> publish
```

Prasyarat: RACI, transition matrix, assignment source, SLA, delegation, dan exception disahkan pada KM-DEC-003.

### D. Version comparison — future-state

```text
Published v1.1 tetap aktif
  -> contributor membuat candidate v1.2
  -> compare metadata/content/file checksum
  -> review/approval candidate
  -> v1.2 menjadi published current
  -> v1.1 tetap read-only dalam histori
```

Prasyarat: KM-DEC-001 menentukan numbering, current/published semantics, dan trigger approval ulang.

### E. Notification — future-state

```text
Domain event
  -> recipient matrix
  -> redact payload berdasarkan confidentiality
  -> queue + retry/dead-letter
  -> in-app center
  -> direct action dengan policy check ulang
```

Prasyarat: KM-DEC-006.

### F. Archive/retention — future-state

```text
Review/expiry signal
  -> owner decision
  -> archive visibility rule
  -> retention/legal-hold check
  -> eventual disposition with audit evidence
```

Prasyarat: KM-DEC-014. `Tidak Aktif` tidak boleh otomatis diganti label “Archived”.

---

## 5. Design System Lengkap

### Visual direction

- Gaya: **Swiss enterprise + flat functional**, formal, tenang, terstruktur, medium density.
- Primary: navy untuk trust/authority; red logo ADASI hanya brand accent, bukan warna CTA umum.
- Surface: white/off-white dengan border halus; shadow hanya untuk hierarchy/modal.
- Radius: 4–8 px untuk control/card; pill hanya badge/status.
- Motion: 150–220 ms, transform/opacity/color, dan menghormati `prefers-reduced-motion`.
- Icon: Bootstrap Icons lokal, outline konsisten, tidak memakai emoji.
- Typography: Inter/IBM Plex Sans/Source Sans 3 dengan fallback Segoe UI; tidak menambah CDN.

### Token architecture

```text
Primitive
  navy/slate/blue/red/green/amber, 4/8 spacing, type scale, radius, elevation
    ↓
Semantic
  page, surface, text, border, primary, focus, success, warning, danger, info
    ↓
Component
  button, input, card, table, status, sidebar, topbar, dialog, toast
```

### Color tokens

| Semantic token | Nilai | Fungsi |
|---|---:|---|
| `--km-color-primary` | `#163A5F` | CTA, active navigation, key hierarchy |
| `--km-color-primary-hover` | `#102A43` | Hover/pressed primary |
| `--km-color-link` | `#175CD3` | Link dan affordance informasional |
| `--km-color-focus` | `#2563EB` | Focus ring yang jelas |
| `--km-color-brand-accent` | `#C4322B` | Accent merek terbatas |
| `--km-color-page` | `#F4F7FA` | Page canvas |
| `--km-color-surface` | `#FFFFFF` | Card/control/modal |
| `--km-color-text` | `#101828` | Teks utama |
| `--km-color-text-secondary` | `#475467` | Teks sekunder |
| `--km-color-border` | `#D0D5DD` | Border control |
| `--km-color-success` | `#18794E` | Terbit/sukses + teks/ikon |
| `--km-color-warning` | `#854A0E` | Pending/attention + teks/ikon |
| `--km-color-danger` | `#B42318` | Error/reject/destructive + teks/ikon |

Status tidak pernah disampaikan dengan warna saja; semua badge menyertakan label dan marker.

### Typography scale

| Role | Size | Weight | Line-height |
|---|---:|---:|---:|
| Page title | 30 px responsif | 700 | 1.25 |
| Section title | 20 px | 600 | 1.25 |
| Card title | 18 px | 600 | 1.25 |
| Body | 16 px | 400 | 1.5–1.65 |
| Label/table | 14 px | 500–600 | 1.5 |
| Caption/helper | 12 px | 400–600 | 1.5 |

Body mobile tidak lebih kecil dari 16 px untuk input; data table desktop boleh 14 px dengan row 48–56 px.

### Spacing, grid, border, radius, elevation

- Base unit: 4 px; hierarchy utama memakai 8, 16, 24, 32, 48, 64 px.
- Desktop shell: sidebar 272 px, topbar 72 px, page gutter 32 px.
- Content max width: 1600 px agar dashboard besar tidak melebar tanpa batas.
- Grid: 12-column concept melalui CSS grid; KPI 4/2/1 kolom pada desktop/tablet/mobile.
- Control height: 44 px; small control hanya untuk desktop data toolbar dan tetap memiliki hit area memadai.
- Border: 1 px neutral; selected/active memakai border atau indicator selain warna background.
- Card radius 8 px, dialog 8 px, input 6 px.
- Shadow: `xs` untuk card, `md` untuk modal/toast; tidak ada shadow dekoratif tebal.

### Status vocabulary

| Status production | Label UI | Marker |
|---|---|---|
| `INACTIVE` | Tidak Aktif | danger marker + teks |
| `DRAFT` | Draf | neutral marker + teks |
| `DRAFT` + rejection event terakhir | Draf — revisi diminta | danger marker + teks, bukan enum baru |
| `PENDING_APPROVAL` | Menunggu Persetujuan | warning marker + teks |
| `PUBLISHED` | Terbit | success marker + teks |

`Under Review`, `Pending Approval` multi-stage, `Approved`, `Archived`, dan `Expired` pada brief tetap terminology future-state sampai lifecycle disahkan.

---

## 6. Daftar Komponen Reusable

| Komponen | Variants/state wajib |
|---|---|
| App shell | Sidebar expanded/collapsed/drawer; topbar desktop/tablet |
| Global search | Default, active, suggestion, recent, no-result |
| Page heading | Breadcrumb, title, description, satu primary CTA |
| Button | Primary, secondary, ghost, danger; default/hover/active/focus/disabled/loading |
| Icon button | 44×44 px, `aria-label`, tooltip opsional |
| Form field | Label, helper, required, valid, invalid, read-only, disabled |
| Stepper | Complete, current, upcoming; horizontal/vertical |
| Autosave indicator | Saving, saved, error, conflict, offline |
| File upload | Empty, selected, validating, uploaded, error, private label |
| Tag/co-author picker | Search, selected chip, duplicate, empty result |
| Filter bar | Inline desktop, drawer tablet, applied chips, reset |
| Knowledge card/list item | Status, category, tags, owner, updated, view, version, privacy |
| KPI card | Neutral, success, warning, danger; no count-up animation wajib |
| Data table | Sortable header, selected row, bulk toolbar, empty/loading/error |
| Status badge | Marker + label; semantic token; no color-only meaning |
| Timeline | Complete/current/upcoming/rejected; actor/time/reason |
| Approval decision brief | Risk, contributor, recommendation, history, outstanding comment |
| Split workspace | Queue, document, review/approval panel; tablet tabs |
| Comment | Actor, timestamp, target section, resolved/unresolved, response |
| Rich content reader | Readable measure 65–75 chars, sticky TOC, attachments |
| Modal/dialog | Focus trap, Escape, return focus, confirmation copy |
| Drawer | Filter/sidebar tablet; clear close affordance |
| Toast | `aria-live=polite`, manual dismiss, 3–5 second timeout |
| Alert | Info/success/warning/error; cause + recovery action |
| Skeleton | Reserve final dimensions; reduced motion |
| Empty state | Context, explanation, one recovery CTA |
| Error/permission state | Cause, impact, safe next step, reference ID bila perlu |
| Chart | Legend, direct labels, keyboard/tap tooltip, text summary, data table |

---

## 7. Struktur Setiap Halaman

| # | Layar | Struktur utama | Primary action | Status desain |
|---:|---|---|---|---|
| 1 | Login | Brand, title, labeled credentials, assurance, enterprise value panel | Masuk | Siap |
| 2 | Employee dashboard | Dominant search, recommended, new, recent/saved | Buka Library | Siap |
| 3 | Contributor dashboard | Status KPIs, action-required table, activity | Buat Knowledge | Parsial |
| 4 | Reviewer dashboard | Review KPIs, priority queue, workload | Buka Review Queue | Konsep KM-DEC-003 |
| 5 | Approver dashboard | Pending/priority KPIs, decision list, trend | Buka Approval Queue | Parsial |
| 6 | Admin dashboard | Inventory KPIs, usage, health, attention | Lihat Analytics | Parsial |
| 7 | Knowledge Library | Search/filter/sort, grid/list, result cards | Buat Knowledge | Siap |
| 8 | Search results | Query summary, refinements, ranked results | Buka result | Siap |
| 9 | Knowledge detail | Metadata, readable content, sticky TOC, attachments, history | Buka Dokumen | Parsial |
| 10 | Create knowledge | 5-step concept, form, autosave, similarity aside | Lanjut ke Konten | Parsial |
| 11 | Review and submit | Completed stepper, summary, validation, route preview | Kirim untuk Persetujuan | Parsial |
| 12 | Submission success | Confirmation, ID, next step, track/back | Lihat Pengajuan | Siap |
| 13 | My drafts | Search/filter, completeness, last saved, action | Lanjutkan | Siap |
| 14 | My submissions | Stage/owner/status/next-action table | Lacak | Parsial |
| 15 | Submission detail | Summary, file, decision note, timeline | Buka Revisi | Parsial |
| 16 | Revision detail | Structured feedback, checklist, response, history | Kirim Ulang | Parsial |
| 17 | Review queue | Assignment/priority/SLA filters, submission table | Review | Konsep |
| 18 | Review workspace | Queue + document + checklist/comments/actions | Rekomendasikan Approval | Konsep |
| 19 | Approval queue | Decision filters, risk/recommendation, bulk selection | Tinjau | Parsial |
| 20 | Approval workspace | Decision brief, document, history, reason/actions | Setujui Knowledge | Parsial |
| 21 | Version comparison | Side-by-side/inline/metadata tabs | Kembali ke Review | Konsep KM-DEC-001 |
| 22 | Notifications | Category rail, event list, direct action, preference | Tandai Semua Dibaca | Konsep KM-DEC-006 |
| 23 | Analytics | Non-KPI notice, filter, KPI, selective chart, source table | Export | Parsial |
| 24 | User management | Search/filter, org snapshot, access table | Tambah Akses | Konsep |
| 25 | Role & permission | Safety notice, capability matrix, audit warning | Simpan | Konsep |
| 26 | Category management | Taxonomy table, owner, usage, health | Tambah Kategori | Konsep management |
| 27 | Workflow configuration | Locked visual builder, settings, gate notice | Simpan sebagai Draf | Konsep KM-DEC-003 |
| 28 | Audit log | Filter, event timeline, actor/status/reason | Export Audit | Parsial |
| 29 | Empty states | No-data, no-result, all-done, loading, saved | Contextual recovery | Siap |
| 30 | Error/permission | 403, network, session, validation, conflict, preview error | Safe recovery | Siap |

---

## 8. Low-Fidelity Wireframe

### A. Dashboard

```text
┌──────── Sidebar ────────┬──────────── Topbar: Search | Create | Role ───────┐
│ Dashboard               │ Breadcrumb                                         │
│ Library                 │ Page title                         [Primary action]  │
│ My Knowledge            ├─────────────────────────────────────────────────────┤
│ Tasks                   │ [KPI] [KPI] [KPI] [KPI]                            │
│ Analytics               │                                                     │
│ Admin                   │ [Main task / content list      ] [Secondary panel]  │
│ Help                    │ [Trend/table                    ] [Activity       ]  │
└─────────────────────────┴─────────────────────────────────────────────────────┘
```

### B. Library/search

```text
┌──────────────────────── Page title + Create ────────────────────────────────┐
│ [Dominant search........................................] [Search]           │
│ [Category] [Tag] [Owner] [Date] [More filters] [Sort]                      │
├──────────────────────────────────────────────────────────────────────────────┤
│ Result count / applied chips                                  [Grid] [List] │
│ [Knowledge card] [Knowledge card] [Knowledge card]                         │
│ [Knowledge card] [Knowledge card] [Knowledge card]                         │
└──────────────────────────────────────────────────────────────────────────────┘
```

### C. Multi-step authoring

```text
┌─ Step 1 ─── Step 2 ─── Step 3 ─── Step 4 ─── Step 5 ───────────────────────┐
│ ┌──────────────────────── Form area ──────────────────┐ ┌─ Progress ─────┐ │
│ │ Label                                               │ │ 20% complete   │ │
│ │ [Input............................................] │ │ Validation     │ │
│ │ Label                                               │ │ Similar items  │ │
│ │ [Textarea.........................................] │ │ Help           │ │
│ └─────────────────────────────────────────────────────┘ └────────────────┘ │
│ Autosaved 10.32                               [Save Draft] [Next step]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

### D. Knowledge detail

```text
┌──────────────── Title / status / owner ─────────────── [Save] [Open file] ┐
│ ┌──────────────── Readable content 65–75ch ─────────┐ ┌─ Sticky TOC ──┐ │
│ │ Summary, metadata, headings, body, attachments    │ │ Sections      │ │
│ │ Related knowledge, usefulness, feedback           │ │ Traceability  │ │
│ └────────────────────────────────────────────────────┘ └───────────────┘ │
└───────────────────────────────────────────────────────────────────────────┘
```

### E. Review/approval workspace

```text
┌──── Queue 19rem ────┬──────── Document ─────────┬──── Decision 20rem ────┐
│ Search/filter       │ Private preview/content   │ Metadata/risk          │
│ Active item         │ Inline markers            │ Checklist/comments     │
│ Other task          │ Compare version           │ Timeline               │
│ Other task          │                           │ [Secondary] [Primary] │
└─────────────────────┴───────────────────────────┴────────────────────────┘
Tablet: Queue | Document | Decision menjadi tabs/stack, bukan 3 kolom sempit.
```

### F. Administration

```text
┌──────────────── Page title + contextual create ──────────────────────────┐
│ [Search] [Role] [Department] [Status] [Apply]                            │
├───────────────────────────────────────────────────────────────────────────┤
│ Table / permission matrix / workflow canvas                              │
│ Sticky identity column · audited actions · explicit disabled states      │
└───────────────────────────────────────────────────────────────────────────┘
```

---

## 9. High-Fidelity UI

Prototype high-fidelity berada di:

```text
km-docs/prototypes/km-enterprise-redesign/index.html
```

Karakteristik prototype:

- 30 layar dapat dipilih dari sidebar.
- Role switcher mengubah navigasi ketika “Tampilkan semua 30 layar” dimatikan.
- Semua layar yang bergantung keputusan memiliki banner gate.
- Global search, hash navigation, sidebar collapse/drawer, dialog konfirmasi, toast, tabs, dan state demo dapat diinteraksikan.
- Seluruh visual menggunakan token pada `tokens.css` dan komponen pada `km-prototype.css`.
- Aset icon/logo berasal dari repository; tidak ada dependency CDN baru.
- Konten dummy memakai contoh proses perusahaan seperti tool steel, machining, heat treatment, quality, warehouse, dan safety.

Prototype tidak terhubung ke controller/database dan tidak boleh dipakai untuk UAT business rule.

---

## 10. Responsive Behavior

| Breakpoint | Perilaku |
|---:|---|
| ≥1440 px | Sidebar 272 px, 4 KPI, 3-column knowledge grid, split workspace 3 panel |
| 1280–1439 px | Gutter 24 px, 2–4 KPI, content max 1600 px, label quick-create dapat diringkas |
| 1024–1279 px | Sidebar menjadi off-canvas drawer; workspace 2 panel + panel metadata di bawah |
| 768–1023 px | Table horizontal scroll dengan sticky context; form aside turun; grid 2 kolom |
| <768 px | Prototype tetap usable: 1 kolom, filter stacked/drawer, stepper vertikal, workspace stacked |

Aturan tambahan:

- Informasi penting tidak dihilangkan; visual density yang berubah, bukan data kritis.
- Primary action muncul setelah heading dan tetap berada dalam alur baca; sticky bottom CTA baru dipakai bila tidak menutup konten.
- Table boleh horizontal scroll bila struktur kolom penting, tetapi summary card/list dipertimbangkan untuk task sederhana.
- Touch target minimum 44×44 px dan jarak antar action minimum 8 px.
- Tidak ada nested scroll utama; panel workspace memiliki scroll terkontrol hanya pada desktop.
- Zoom browser 200% tidak boleh memutus navigation, dialog, atau form.

---

## 11. Interaction dan State Specification

### Global search

- Shortcut `Ctrl/Cmd + K` memfokuskan input, tidak mengambil alih shortcut assistive technology.
- Submit memuat screen result dan fokus pindah ke `main`/heading.
- Recent search dan suggestion hanya ditampilkan setelah user berinteraksi.
- Query maksimal 100 karakter mengikuti validasi aktif.
- Loading >300 ms memakai skeleton result; error mempertahankan query/filter.

### Form dan autosave

- Validate pada blur/submit, bukan setiap keystroke.
- Error tampil dekat field dan ringkasan di atas dengan anchor ke field.
- Autosave state: `Menyimpan…`, `Tersimpan HH.mm`, `Gagal menyimpan — Coba lagi`, `Konflik versi`.
- Tombol submit disabled/loading setelah activation untuk mencegah double submit.
- Unsaved-change dialog hanya muncul bila ada perubahan yang belum tersimpan.
- File upload menyebut format, size, privacy, progress, failure, dan recovery.

### Approval/reject

- `Setujui Knowledge` adalah primary action; `Minta Revisi`/`Tolak` dipisahkan secara visual.
- Semua keputusan penting memakai confirmation dialog.
- Reject meminta alasan wajib; server tetap memvalidasi whitespace-only.
- Confirmation menyebut perubahan status yang benar: satu-stage approve langsung menjadi `Terbit`.
- Bulk toolbar baru aktif ketika selection valid; category tetap per dokumen.
- Partial failure tidak ditawarkan karena service bersifat all-or-nothing.

### Timeline dan audit

- Setiap event memuat action, actor snapshot, from/to status, timestamp, dan reason bila ada.
- Current step mempunyai label “Saat ini”, bukan sekadar highlight.
- Future step memakai garis/marker netral dan tidak disajikan sebagai komitmen SLA.

### Dialog/drawer/toast

- Dialog trap focus, Escape menutup, dan focus kembali ke trigger.
- Drawer memiliki scrim 56%, close button, Escape, dan tidak menjadi primary navigation pada desktop.
- Toast menggunakan `aria-live=polite`, tidak mencuri focus, dapat ditutup, dan hilang dalam 4,5 detik.

### Loading, empty, error, permission

- Skeleton mempertahankan ukuran final untuk menghindari CLS.
- Empty state menjelaskan kondisi dan satu recovery CTA.
- Error menyebut sebab, dampak, dan recovery; tidak hanya “Terjadi kesalahan”.
- 403 tidak menawarkan cara bypass; hanya kembali atau meminta akses melalui proses resmi.

---

## 12. Accessibility Review — WCAG 2.2 AA

### Checklist yang diterapkan pada prototype

- `lang="id"`, semantic `header/nav/main/aside/section/article/table/dialog`.
- Skip link ke main content.
- Heading hierarchy dimulai dari satu `h1` per screen.
- Label terlihat untuk semua input; placeholder bukan label.
- `aria-label` pada icon-only control dan caption pada table.
- Status mempunyai label/marker, tidak bergantung warna saja.
- Focus ring 2 px + offset dan kontras jelas.
- Route/screen change memindahkan focus ke main.
- Control utama minimum 44 px.
- Error menggunakan `aria-invalid`, `aria-describedby`, dan `role=alert`.
- Dialog menggunakan native `<dialog>` dan close affordance.
- Toast memakai polite live region.
- Chart memiliki title, description, text summary, dan table alternatif.
- `prefers-reduced-motion` mematikan shimmer/transisi non-esensial.
- Text body 16 px; readable content 65–75 karakter per baris.
- Breakpoint tidak menghapus informasi kritis.

### Verifikasi yang masih wajib dilakukan sebelum produksi

- Automated axe/Lighthouse pada route aktual.
- Keyboard-only: Tab, Shift+Tab, Enter, Space, Escape, arrow keys pada tab/menu.
- NVDA + Chrome/Edge untuk form error, dialog, status, dan chart summary.
- Zoom 200% pada 1280 dan 1024 px.
- Kontras seluruh token/state dengan tool terukur, termasuk disabled/focus/border.
- Manual QA 768, 1024, 1280, 1440 dan tablet landscape.
- Browser QA belum dijalankan pada sesi desain ini karena browser runtime tidak tersedia.

---

## 13. UX Heuristic Review

| Heuristic | Temuan/penerapan desain | Status |
|---|---|---|
| Visibility of system status | Autosave, progress, loading, decision state, current owner terlihat | Baik pada prototype |
| Match with real world | Istilah “Draf”, “Menunggu Persetujuan”, “Terbit” mengikuti lifecycle aktual | Baik |
| User control and freedom | Save draft, cancel, back, close, confirmation, retry tersedia | Baik; undo future |
| Consistency and standards | Satu shell, token, icon family, status pattern, page heading | Baik |
| Error prevention | Validation summary, confirmation, disabled invalid bulk, unsaved warning | Baik pada spec |
| Recognition over recall | Search suggestion, related items, decision brief, sticky context | Baik |
| Flexibility and efficiency | Global search, quick create, saved views concept, bulk approval | Parsial |
| Aesthetic/minimal design | Medium density, limited color, restrained radius/shadow | Baik |
| Error recovery | Cause + recovery pada network, validation, conflict, preview, permission | Baik |
| Help and documentation | Contextual helper, format/privacy notice, Help & Guidelines | Perlu content owner |

### Risiko terbesar sebelum implementasi

1. Menjadikan prototype future-state sebagai backlog tanpa keputusan tertulis.
2. Membuat role reviewer/manager hanya di menu tanpa policy dan state model.
3. Mengganti status legacy dengan label baru yang mengubah arti lifecycle.
4. Mengubah layout global sekaligus sehingga regression surface terlalu luas.
5. Membuat dashboard penuh chart tanpa definisi metrik dan privacy rule.

---

## 14. Rekomendasi Implementasi

### Mission A — KM Design Foundation (aman tanpa keputusan bisnis baru)

Scope yang disarankan:

- Tambah token semantic KM dan komponen Blade reusable.
- Terapkan KM-scoped shell/sidebar tanpa menghapus route/view legacy.
- Redesign login hanya bila disetujui sebagai perubahan global terpisah.
- Perbaiki page heading, search/filter disclosure, knowledge card, table, status badge, empty/loading/error, focus/keyboard.
- Pertahankan Bootstrap 5.3.2 dan Bootstrap Icons lokal; jangan menambah React/Tailwind/shadcn ke aplikasi legacy.
- Tambah component/Blade accessibility tests dan screenshot regression bila browser tersedia.

Acceptance criteria minimum:

- Tidak ada route, enum status, request/response contract, atau policy berubah.
- `dsKnowlege`, `pengajuanKM`, `persetujuanKM` tetap berfungsi.
- Pencarian tetap hanya judul/sinopsis + tag.
- Semua interactive target ≥44 px pada tablet dan focus terlihat.
- Targeted KM tests tetap hijau dan browser QA 768/1024/1280/1440 selesai.

### Mission B — Authoring Page dan Submission Tracking

Scope setelah foundation:

- Pindahkan authoring dari modal ke route/page canonical sambil mempertahankan modal/route alias minimal satu release.
- Stepper dibatasi field yang benar-benar tersedia: basic metadata, file, tag/co-author, review/submit.
- Tampilkan “Draf — revisi diminta” sebagai derived presentation dari event, bukan status baru.
- Tambah submission detail/timeline dari `km_approval_events`.

Tidak termasuk: rich text canonical, classification governance, SLA, reviewer terpisah, versioning.

### Mission C — Approval Decision Workspace

Scope:

- Ubah approval list/modal menjadi queue + detail workspace untuk workflow satu tahap.
- Decision brief memakai data yang sudah tersedia.
- Bulk approval tetap atomik dan category tetap per dokumen.
- Alasan reject tetap wajib dan tercatat pada event.

Tidak termasuk: delegate, sequential/parallel approval, reviewer assignment, due date resmi.

### Mission D dan seterusnya — hanya setelah pending decisions siap

- KM-DEC-001 -> document versioning + compare.
- KM-DEC-003 -> reviewer, multi-stage, SLA, delegation, review cycle, workflow builder.
- KM-DEC-006 -> notification center dan channel.
- KM-DEC-009 -> KPI/search analytics dengan privacy matrix.
- KM-DEC-014 -> archive/retention/legal hold.

### Urutan engineering per mission

```text
preflight data
-> migration bila diperlukan
-> model/enum/service/Form Request
-> controller
-> route + authorization
-> view/menu/assets
-> tests
-> accessibility + responsive QA
-> rollout/rollback note
```

### Handoff file

```text
km-docs/UX-REDESIGN-KM-ENTERPRISE.md
km-docs/prototypes/km-enterprise-redesign/index.html
km-docs/prototypes/km-enterprise-redesign/tokens.css
km-docs/prototypes/km-enterprise-redesign/km-prototype.css
km-docs/prototypes/km-enterprise-redesign/km-prototype.js
```

## Lampiran A — Verifikasi Artifact

- Registry dan renderer mencakup tepat 30 layar sesuai brief.
- `km-prototype.js` lolos pemeriksaan sintaks Node.
- `tokens.css` dan `km-prototype.css` lolos parsing PostCSS.
- HTML utama berhasil diparse; peringatan libxml hanya berasal dari elemen semantic HTML5 yang tidak dikenali parser HTML4 bawaan (`aside`, `nav`, `header`, `main`, dan `dialog`).
- HTML, CSS, JavaScript, dua logo, Bootstrap Icons CSS, dan font lokal seluruhnya merespons HTTP 200 pada server statis lokal.
- Rasio kontras pasangan teks utama berada pada 5,13:1–17,75:1 dan melewati batas WCAG AA untuk teks normal.
- Pemeriksaan sumber memastikan `lang="id"`, skip link, visible focus, label/ARIA penting, caption tabel, alternative text untuk chart, error association, live region, dan reduced-motion tersedia.
- Tidak ada CDN, inline event handler, emoji dekoratif, trailing whitespace, atau warna literal pada layer komponen.
- Pemeriksaan visual interaktif desktop/tablet dan screenshot belum dapat dilakukan karena in-app Browser tidak tersedia pada sesi ini; status tersebut bukan pengganti sign-off visual manusia.

Baseline aplikasi pada saat audit: `km:readiness --json` menghasilkan 10 PASS, 2 WARN, 0 FAIL. Dua WARN operasional berkaitan dengan queue sync/worker dan scheduler, bukan kegagalan wajib KM.
