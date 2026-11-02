# Gate 1 — Revisi HR Fastware: PR 1, PR 2 Kompetensi/Training 2025, dan PR 3

Tanggal audit dan revisi final Q13–Q15: **16 Oktober 2026**. Status: **DOCUMENTATION / PLANNING ONLY**.

**GATE 1 FINAL BUSINESS SCOPE RECORDED — PR 2 DATA ACTIONS PENDING VALIDATION AND SEPARATE APPROVAL**

**USER-CONFIRMED — Q13=A, Q14=C, Q15=C dan keputusan periode telah final; tidak perlu interview ulang B1/B2.** Seluruh 281 assessment pada immutable legacy cohort adalah periode bisnis 2025 menurut pengguna, dengan `period_evidence_type=USER_ATTESTATION`; ini bukan independent audit-year proof. Seluruh 934 target training 2025 dicurigai dummy/tidak relevan oleh pengguna, tetapi belum terbukti. Tidak tersedia sumber pengganti training 2025 yang autentik: rencana **0 legacy training inserts**. Mapping/lineage, keaslian record dan kelayakan cleansing tetap memerlukan review teknis dan approval terpisah.

**Approval data saat revisi ini: 0 INSERT_APPROVED / 0 UPDATE_APPROVED / 0 DELETE_APPROVED.** Gate 2 code, staging write, dan eksekusi produksi masing-masing tetap memerlukan izin terpisah. Dokumen ini tidak menyatakan Gate 1 migration-ready.

**NO SQL EXECUTED; NO DATA WRITES; NO PRODUCTION SQL EXECUTED; NO APPLICATION CODE MODIFIED; GATE 2 NOT AUTHORIZED.** Tidak ada koneksi database, migration, seeder, importer aplikasi, delete/update database, commit, push, atau deploy yang dijalankan. Satu-satunya perubahan yang dibuat untuk tugas ini adalah dokumen ini.

## 1. Keputusan dan batas scope yang berlaku

| PR | Keputusan final | Batas penting |
| --- | --- | --- |
| PR 1 | HR/Admin tetap full manage Mapping Karyawan dan Working Experience. Section, Department, Division Head berhak membaca bawahan langsung/tidak langsung melalui hierarki aktif, termasuk gabungan multiple active positions. | Backend wajib melindungi list, detail/modal, AJAX, CRUD, import dan template. Staff biasa tidak memperoleh akses baru. Izin baca terpisah dari izin kelola. |
| PR 2 | **Q13 Option A — APPROVED BUSINESS SCOPE:** migrasi/rekonsiliasi assessment business period 2025 (281 source year accepted melalui USER_ATTESTATION), minimum linked TC/Soft Skills/Additional yang diperlukan transaksi approved, serta investigasi/rekonsiliasi target assessment dan Training Development 2025. Q15 menetapkan **0 legacy training inserts**. | Struktur target `(12)` dipertahankan. Hanya exact target records 2025 yang terbukti dummy/duplikat/superseded/tidak relevan boleh diusulkan untuk dihapus, setelah validasi, signed HR record-level review, dependency check, backup, rollback rehearsal dan izin eksekusi terpisah. NPK unik menjadi penghubung employee; seluruh posisi/master/transaksi melalui crosswalk, bukan PK legacy. **0 data actions approved.** |
| PR 3 | Form penilaian menampilkan informasi spesifik competency bersama panduan skor 1–4 yang sudah ada, secara kontekstual dan accessible; tahap awal memakai data existing dengan empty fallback. | Tidak mengubah skor, standar, perhitungan, approval, locking, atau kategori. Pemulihan deskripsi legacy adalah keputusan data tersendiri dan tidak menjadi syarat UI existing-data-only. |
| PR 4 | **DEFERRED / DITUNDA.** | Dilarang mengubah struktur Sales, assignment, jalur atau level approval. Membaca dependensi Sales untuk menjaga regresi bukan izin mengubahnya. |

### 1.1 Keputusan final yang menggantikan asumsi sebelumnya

| Keputusan | USER-CONFIRMED business fact | Batas teknis / approval |
| --- | --- | --- |
| Q13=A | PR 2 hanya business-period-2025 assessment, minimum linked TC/Soft Skills/Additional dan investigasi/cleansing terkontrol target 2025; target `(12)` adalah schema authority. | Bukan full HR migration, overwrite, code approval atau data approval. |
| Periode / B1 | **CLOSED FOR BUSINESS-YEAR DECISION** untuk tepat **281** row `trs_penilaian_tcs` dalam legacy dump ber-hash §2.2. Gunakan `tahun_penilaian=2025`, `business_year=2025`, `period_evidence_type=USER_ATTESTATION`. | Attestation diterima sebagai keputusan bisnis, bukan bukti tahun independen. Literal `created_at` 10/2025 dan 271/2026 dipertahankan; tidak dibackdate. NPK, posisi, master/category, score/status dan natural-key checks belum selesai; unresolved tetap QUARANTINE. Input/cohort berbeda memerlukan review baru. |
| Q14=C | Pengguna mencurigai **seluruh 934** target training 2025 dummy/tidak relevan. | Suspicion bukan proof. T25-A/B/C/D tetap 490/433/10/1, QUARANTINE / INVESTIGATION; **DELETE_APPROVED=0**. Target 320 assessment 2025 juga harus direkonsiliasi berbasis evidence, bukan dinyatakan dummy. |
| Q15=C / B2 | **CLOSED AS NO REPLACEMENT SOURCE**. Tidak tersedia authentic replacement Training Development 2025; **0 legacy training inserts planned**. | Bukan bukti target invalid dan bukan 934 approved deletes. Tidak mencari sumber baru sebagai prerequisite. Dilarang membuat sejarah dari Faker, demo/fixture, generator importer, assessment gap atau guessed logs. Jika exact records kelak terbukti obsolete dan delete-approved, periode terkait boleh tanpa training; jangan mensintesis pengganti. |

Prioritas legacy adalah **preferensi bisnis terhadap kebenaran historis 2025 yang sudah diverifikasi**, bukan izin overwrite seluruh current rows. Setiap konflik dan setiap usulan delete memerlukan signed review manifest sendiri dengan exact record/field, evidence dan before/after. Tidak boleh menghapus, menulis ulang, atau mereset sejarah 2025 secara diam-diam; historical evidence tetap dilindungi. Izin Gate 2 menulis kode tidak sekaligus mengizinkan insert/update/delete, staging write atau go-live.

Dilindungi dari PR 2: `users` dan seluruh credential/account fields; role dan access rules; `mst_job_positions`, `user_job_positions`, `mst_departments`, `mst_sections`, `mst_position_approvals`; Working Experience; konfigurasi tahun aktif; seluruh transaksi di luar 2025, termasuk 2026/2027; modul lain dan file pendukungnya. Master kompetensi bersama tidak boleh ditimpa/dihapus hanya karena dibutuhkan periode 2025.

## 2. Baseline repository, bahan, dan metode bukti

### 2.1 Baseline yang benar-benar diperiksa

- Checkout lokal: `C:\laragon\www\fastware_adsi_1`.
- Origin terkonfigurasi: `https://github.com/poyipoy/fastware_adsi_1.git`.
- **STATIC VERIFIED — diperiksa kembali saat revisi final Q13–Q15**, bukan diasumsikan dari pasted report: branch `main`; HEAD aktual **`6161d682e1eeaf81c9e83acad523af7c5b688b45`**; origin sesuai repository di atas. Tidak ditemukan drift baseline checkout.
- Kesamaan dengan HEAD GitHub terbaru: **NOT RUN**; tidak fetch/pull. Audit merujuk checkout lokal ini dan worktree yang diperiksa, bukan klaim remote terkini.
- Sebelum revisi final Q13–Q15: **17 tracked files berubah dan 14 untracked files**, termasuk dokumen Gate 1 ini yang sudah ada; daftar status sama dengan akhir revisi Q13. Audit awal mencatat 17 tracked + 13 untracked sebelum dokumen pertama dibuat. Tiga puluh file lainnya terkait Outstanding Material/Warehouse beserta dokumentasi/test dipertahankan. Hunk tujuh tambahan route Outstanding Material tetap existing dirty work; tidak disentuh/distage/direvert.
- `AGENTS.md` dibaca ulang sebelum revisi; `CLAUDE.md` dan `claudes-cognitive-framework-for-laravel-development.md` sudah dibaca lengkap pada audit awal dan fingerprint keduanya diperiksa tetap sama. Pencarian termasuk hidden paths kembali menemukan hanya root `AGENTS.md`. Tiga belas fingerprint instruksi/core HR yang dicatat audit awal tetap sama; bukti source tersebut dipakai ulang tanpa broad re-audit.
- Dokumentasi HR lama #03/#04 dan implementation plan-nya dibaca sebagai konteks, bukan fakta runtime: controller/view kini sudah mempunyai reminder, import, dan Working Experience read-only dalam modal edit.
- Discrepancy dokumentasi: `composer.json` menetapkan PHP `^8.1`, sementara `CLAUDE.md` menyebut `^8.2`; runtime belum diperiksa. Klaim `CLAUDE.md` bahwa Markdown baru selalu diabaikan tidak berlaku pada `docs/**`: `.gitignore` kini mengizinkannya. Tidak perlu perubahan `.gitignore`.

Skill yang digunakan: `boost`, `ecc:mysql-patterns`, `sql-sentinel`, `sql-pro`, `security-audit`, `ecc:laravel-patterns`, `accessibility`, `plan-writing`. Penerapannya terbatas pada audit teks, risiko, dan rancangan. Security skill digunakan untuk review boundary HR terfokus, bukan klaim audit keamanan menyeluruh. Engine sql-sentinel tidak diunduh/dijalankan; rule warehouse/cost-nya tidak dijadikan skor MySQL fiktif. Ketentuan lokasi/panjang plan generik tunduk pada lokasi dan isi wajib yang diminta pengguna.

### 2.2 Identitas bahan immutable

| Bahan yang dibaca | Ukuran | SHA-256 |
| --- | ---: | --- |
| Root `dms_adasi_rev1.sql` — legacy | 2,069,117 byte | `e697a99f80994ff8987b0a325f6b54ed7dc0f9767a2226f617afaeb7a1e9c3d8` |
| Root `dms_adasi_rev1 (12).sql` — target/skema acuan | 4,313,503 byte | `996ef43a253969d992f29d917cdc021df353c6c89f8081a005b4f8787b1ea3db` |
| Dokumen pendamping awal: `C:\Users\BAHRIALGI\Downloads\GATE-1-HR-2025-MIGRATION-REVISED-PLAN.md` | Dibaca penuh pada audit awal | `9d6ecc4476b17480ad7f84039304119d1953b32e26959014083c5c34cb77505f` |
| Pendamping revisi Q13: root `GATE-1-HR-2025-MIGRATION-REVISED-PLAN (1).md` | **29,898 byte; dibaca penuh saat revisi Q13** | `f935474303958c20ee7fc5a207bd7de5dceaf67395ad490dfa75adc9bd51f308` |
| **Addendum final:** `C:\Users\BAHRIALGI\Downloads\GATE-1-HR-2025-FINAL-DECISIONS-Q15.md` | **17,388 byte; dibaca penuh saat revisi final** | `1d7e564860b2eac4abd0653ce82d14367c333d3e7f2d7b3a438a00780b9fd8b1` |

**VERIFIED STATIC saat revisi final:** ukuran/SHA-256 kedua SQL root diperiksa kembali dan cocok persis dengan baseline §2.2; plan sebelum edit identik dengan hasil revisi Q13. Tidak ada mismatch yang memerlukan re-audit. Bukti count/schema/parser Q13 dipakai ulang; **tidak mengulang large parser, recount seluruh dump, atau menjalankan SQL**. Jika input kelak mismatch, hentikan material changes dan laporkan drift; jangan menjalankan audit/migrasi ulang secara diam-diam.

Pada audit awal, copy SQL Downloads dan dokumen pendamping awal root/Downloads sudah dibandingkan sama. Revisi Q13 memakai file `(1).md`; **keputusan pengguna pada tugas final dan addendum Q15 kini menggantikan hanya asumsi lama tentang periode seluruh 281 assessment dan ketersediaan sumber training, serta menambahkan suspicion Q14**. B1/B2 ditutup sebatas §1.1; safeguards dan B3–B9 tetap berlaku. Bagian lama yang memakai `INSERT`/`UPDATE_APPROVED` sebagai klasifikasi tidak menjadi approval baru. Dump lain `(11)`, `(4)`, dan sebagainya tetap bukan input audit ini, walaupun importer existing menggunakan default dump `(4)`.

Header legacy menyatakan **Feb 02, 2026 at 12:20 PM**; target **Oct 16, 2026 at 06:39 AM**. Keduanya menyebut MySQL **8.0.30**, PHP **8.2.30**, dan nama database `dms_adasi_rev1`. Ini metadata dump, bukan verifikasi versi atau keadaan server saat ini. Zona waktu waktu pembuatan header tidak diasumsikan.

### 2.3 Metode dan tingkat keyakinan

Analisis menggunakan JavaScript buatan untuk tugas ini di memori: membaca byte file, memisah statement dengan kesadaran quoted literal/comment, mendekode tuple literal INSERT, dan membaca CREATE/ALTER sebagai teks. Tidak menggunakan SQL engine, `eval`, PDO, Laravel bootstrap, atau importer repository. Nilai personal hanya digunakan di memori untuk pencocokan; keluaran dibatasi agregat/schema, tanpa row dump, NPK asli, nama employee, credential, token atau hash password.

- Legacy: **56 CREATE TABLE, 76 INSERT statements, 13,153 tuple** seluruh dump.
- Target: **113 CREATE TABLE, 159 INSERT statements, 18,530 tuple** seluruh dump.
- Final parsing selesai tanpa unsupported INSERT/column-count mismatch; tidak ditemukan data statement UPDATE/DELETE/REPLACE/LOAD DATA yang belum dihitung.
- **Audit awal: 28 pemeriksaan offline lulus**, mencakup 3 fixture sintetis untuk quoted semicolon/comma, escaped/doubled quote dan newline; PK unik pada tabel HR berisi data; penjumlahan cohort tahun/link/dependensi. Revisi Q13 pernah menghitung ulang finding wajib dari hasil parse identik. Semua itu bukti historis, bukan test/recount baru pada M1 final Q13–Q15 ini; final update hanya memeriksa checksum/bytes, baseline/source fingerprints, before/after dokumen dan konsistensi keputusan. Tidak membuat fixture file baru.
- Normalisasi kandidat teks hanya trim, whitespace collapse, uppercase; bukan simulasi lengkap collation MySQL dan bukan persetujuan kesetaraan bisnis. ID angka dibandingkan secara kanonik, termasuk kemungkinan ZEROFILL Additional.

Label bukti: **USER-CONFIRMED** = scope Q13, business-year attestation, suspicion Q14 dan no-replacement constraint Q15; **VERIFIED STATIC** (sinonim `STATIC VERIFIED` pada audit Q13) = kode/literal dump/fingerprint diperiksa; **PENDING HR/DBA** = remaining technical identity, lineage, score/status, mapping, governance/schema dan exact-action review B3–B9; **NOT RUN** = eksekusi aplikasi, SQL, test, staging atau production tidak dilakukan. Hipotesis bukan temuan runtime. Counts/schema §3–§4 adalah bukti statis snapshot; **tahun bisnis 2025 bagi 281 source berasal dari USER_ATTESTATION, bukan disimpulkan dari dump**. Tidak satu pun label tersebut merupakan data-write approval.

## 3. Audit berangka kedua SQL

### 3.1 Inventaris inti

| Tabel | Legacy | Target `(12)` | Perlakuan |
| --- | ---: | ---: | --- |
| `users` | 110 | 114 | Lookup identitas NPK saja; tidak dimigrasikan. |
| `tc_job_positions` | 99 | ABSENT | Referensi label/posisi legacy, bukan PK target. |
| `mst_job_positions` | ABSENT | 56 | Hierarki target dilindungi. |
| `user_job_positions` | ABSENT | 104 | Assignment target dilindungi. |
| `mst_position_approvals` | ABSENT | 123 | Dibaca untuk scope; tidak diubah. |
| `mst_tcs` | 193 | 207 | Hanya dependency closure yang disetujui. |
| `mst_soft_skills` | 144 | 205 | Sama. |
| `mst_additionals` | 75 | 203 | Sama. |
| `tc_poin_kategoris` | 3 | 3 | Mapping semantik/rubric, bukan overwrite. |
| `trs_penilaian_tcs` | 281 | 327 | Target: 320 periode 2025, 7 periode 2026. |
| `detail_penilaian_tcs` | 1 | 723 | Tidak memiliki parent assessment FK/tahun eksplisit. |
| `mst_pd_pengajuans` | **0** | 952 | Target: 934 `tahun_aktual=2025`, 18 `2027`. |
| `mst_pd_pengajuan_participants` | ABSENT | 1 | Parent satu row ini training **2027**, dilindungi. |
| `mst_pd_active_years` | ABSENT | 2 | Konfigurasi di luar migrasi. |
| `working_experiences` | ABSENT | 0 | PR 1 fitur akses; bukan migrasi data PR 2. |

ABSENT berarti CREATE table tidak ada; 0 berarti tabel ada tetapi tidak memiliki tuple INSERT. Kedua keadaan tidak boleh disamakan.

### 3.2 Periode, status, identitas transaksi

| Pemeriksaan | Hasil STATIC VERIFIED | Implikasi |
| --- | --- | --- |
| Periode legacy assessment | VERIFIED STATIC: tidak ada `tahun_penilaian`; literal `created_at` 2025: **10**, 2026: **271**. | USER-CONFIRMED: **seluruh 281 adalah business period 2025** untuk immutable cohort ini, `period_evidence_type=USER_ATTESTATION`; B1 ditutup untuk keputusan tahun. Timestamp tidak diubah, independent year proof tidak diklaim; eligibility teknis/approval tetap belum diberikan. |
| Status legacy | `1`: **7**, `2`: **32**, `3`: **242**. | Arti/mapping historis perlu HR; jangan menyamakan `3` dengan final target. |
| Target assessment 2025 | **320 locked**, status `4`: **312**, `2`: **8**. | Lock/status bukan bukti dummy atau alasan reset approval. |
| Benturan PK assessment | Semua **281** ID legacy ada pada target; **0** pasangan sama `id_user`; **0** pasangan sama NPK employee. | Tidak ada identitas transaksi yang dapat dibuktikan dari PK yang sama. |
| Bentuk row assessment | Legacy: **139 TC saja**, **94 TC+SK**, **48 TC+SK+AD**. Target 2025: **320 TC+SK+AD**. | Jangan menyamakan satu row dengan satu butir kompetensi, atau memecah/menggabungkannya tanpa keputusan. |
| Score diagnostik | Legacy mempunyai **5 nilai TC nol**; tidak ada nilai terisi di luar 0–4 pada kedua cohort. Legacy total sama dengan penjumlahan komponen; **320** target 2025 total berbeda dari penjumlahan itu. | Perbedaan representasi, **bukan vonis corruption**. Semantik `total_nilai` harus dikonfirmasi; jangan recompute/coerce nol menjadi satu. |
| History/detail | Legacy **1 created_at 2025**; target **723 created_at 2026**. | Timestamp bukan period discriminator history. |

### 3.3 Training 2025: partisi investigasi yang tidak tumpang tindih

**STATIC VERIFIED — dihitung kembali saat revisi Q13 dari dump ber-checksum sama.** Seluruh cohort berarti QUARANTINE / REVIEW dan dipertahankan in place; bukan daftar delete approved dan bukan kesimpulan bahwa 933 record adalah junk.

**USER-CONFIRMED Q14=C:** seluruh 934 row dicurigai dummy/tidak relevan; suspicion ini tidak mengubah classification atau counts di bawah. **USER-CONFIRMED Q15=C:** tidak ada authentic replacement source; tidak mengisi kekosongan dengan data sintetis jika penghapusan spesifik kelak disetujui.

Semua **934** target training 2025 memiliki `created_at` tanggal **2026-07-07**, `status_1=3`, `status_2=Done`, dan `tahun_usulan=NULL`; kategori technical **299**, softskill **326**, additional **309**. Semuanya mempunyai user dan posisi numerik yang ditemukan pada target, field evaluasi terisi, dan **0** field `file` terisi. Pola bulk ingest, status seragam, atau tidak ada file **tidak membuktikan dummy**.

| Cohort | Jumlah row | Fakta link | Keputusan saat ini |
| --- | ---: | --- | --- |
| T25-A | **490** | `id_trs` tidak ada pada assessment target; mencakup **317 distinct missing IDs**, bukan 490 ID unik. Tidak ada NULL `id_trs`. | QUARANTINE dalam manifest; simpan row existing. |
| T25-B | **433** | ID assessment ada, tetapi employee target pada parent berbeda. | QUARANTINE; tidak relink/delete otomatis. |
| T25-C | **10** | Employee/posisi/periode cocok, tetapi ID master kategori yang dipakai training berbeda dari master pada parent. | QUARANTINE. |
| T25-D | **1** | Employee, posisi, periode 2025, dan master untuk kategori training cocok. | Masih QUARANTINE untuk provenance; kecocokan relasi tidak membuktikan kejadian training. |
| Total | **934** | `490 + 433 + 10 + 1` | **0 INSERT_APPROVED / 0 UPDATE_APPROVED / 0 DELETE_APPROVED.** |

Dari **444** row yang menemukan ID parent, **432** mengarah ke assessment 2025 dan **12** ke assessment 2026; **416** berbeda posisi. Angka ini overlap dengan cohort di atas dan tidak boleh dijumlahkan sebagai tambahan row. **Dua belas cross-year links tetap dilindungi dari efek tidak langsung: tidak otomatis relink, cascade, atau mengubah parent 2026.** Tujuh assessment 2026, 18 training 2027 dan pivot 2027 tetap OUT_OF_SCOPE. Hanya **1/444** cocok dengan master kategori training, dan row tersebut juga termasuk T25-D. Master reference training sendiri ditemukan; masalahnya bukan sekadar master hilang.

VERIFIED STATIC: tidak ada legacy training yang bisa diekstrak dari input wajib ini. USER-CONFIRMED Q15=C menerima tidak adanya authentic replacement sebagai constraint final: **rencana 0 source training inserts**, bukan blocker untuk mencari sumber baru. Tidak boleh menyatakan 934 target berasal dari legacy dump ini, membuat pengganti dari assessment gap/Faker/demo/generator/guessed logs, atau menghapus target karena sumber kosong. Penghapusan exact obsolete records yang kelak sah dapat meninggalkan nol training pada periode terkait. Jika factual source baru muncul di masa depan, itu memerlukan scope/review/approval baru, bukan perubahan Q15 diam-diam.

### 3.4 NPK, posisi, dan kandidat crosswalk awal

| Pemeriksaan | Legacy | Target |
| --- | ---: | ---: |
| NPK NULL/kosong | 0 | 0 |
| NPK seluruhnya nol | 3 row | 7 row |
| Grup NPK berulang, termasuk grup nol | 2 | 2 |
| Row dalam seluruh grup berulang | 5 | 9 |
| Row dengan NPK nonzero unik | 105 | 105 |

**105** employee source mempunyai pasangan NPK unik pada target; **0** unmatched/target-ambiguous untuk subset itu. Numeric user ID kebetulan sama pada 105 pasangan ini, tetapi kesamaan ini **tidak boleh menjadi aturan matching**. Dari 281 assessment legacy, **264** user dapat di-resolve lewat NPK unik, **17** tidak. Tahun bisnis seluruh cohort sudah diterima melalui USER_ATTESTATION; identity exceptions dan technical mapping tetap pending, bukan B1 year ambiguity. Nama-only fallback pada importer Working Experience existing tidak boleh dipakai untuk PR 2.

Posisi legacy: **46** label berbeda dalam 99 row. Target: **56** label unik, seluruhnya `is_active=1`: staff **40**, sec_head **9**, dept_head **6**, div_head **1**. Hanya **5** label legacy mempunyai satu pasangan target melalui normalisasi sederhana (mencakup **8** row legacy position dan **25** row assessment). Ini kandidat, bukan mapping yang disahkan; label berbeda tidak otomatis berarti posisi berbeda.

Target mempunyai **104** assignment dengan `is_active=1`, seluruhnya memiliki `effective_from`; **7 user** memiliki lebih dari satu distinct posisi aktif. Efektivitas tanggal harus tetap diperiksa pada waktu akses. Graph approval mempunyai level `0`: **17**, `1`: **40**, `2`: **49**, `3`: **17**; tidak ditemukan dangling position edge, self edge, atau siklus dalam graph snapshot. Komentar skema hanya menyebut level 1–3, sedangkan kode memakai 0–1: makna level 0 dan hubungan approval versus reporting perlu dikonfirmasi HR.

### 3.5 Master dan deskripsi

| Master | Deskripsi nonblank legacy | Deskripsi nonblank target | Distinct master legacy direferensikan assessment: ditemukan / hilang |
| --- | ---: | ---: | ---: |
| TC | **193/193** | **7/207** | **133 / 1**; missing ID dipakai oleh **5** assessment |
| Soft Skills | **144/144** | **5/205** | **75 / 0** |
| Additional | **36/75** | **3/203** | **21 / 4**; missing IDs dipakai oleh **4** assessment |

Union assessment legacy dengan reference master hilang: **9 row**. Master legacy yang ada dan direferensikan seluruh 281 assessment berjumlah **229**; ini batas inventaris dependency sebelum technical eligibility/crosswalk selesai, **bukan** kuota insert. Tahun bisnis cohort sudah diterima dari pengguna; tidak mengimpor seluruh 412 master lama.

Referensi posisi legacy pada master tidak ditemukan di `tc_job_positions`: TC **9**, SK **6**, Additional **4**. Dengan kandidat posisi exact-normalized, **23 TC / 26 SK / 5 Additional** dapat dicoba ke posisi target; pencocokan komposit konservatif `(jenis, posisi kandidat, kategori ID, label normalized, standar)` menghasilkan **0 pasangan master exact** untuk ketiganya. Ini batas bukti pencocokan sederhana; bukan bukti tidak ada padanan bisnis. Kategori sendiri masih memerlukan validasi semantik.

Panduan `deskripsi_1..4` terisi **12/12** pada masing-masing dump dan sama setelah normalisasi untuk pasangan category ID yang sama. Ketiga `judul_keterangan` berubah. Jangan menganggap ID kategori stabil secara semantik tanpa review HR; tidak ada kebutuhan terbukti untuk menulis ulang rubric.

## 4. Dependensi, constraint, dan discrepancy schema

### 4.1 Dampak yang sudah terukur

| Dependency | Hasil snapshot | Konsekuensi sebelum perubahan |
| --- | --- | --- |
| Assessment 2025 → training via `id_trs` | **432** reference training 2025 terhadap 320 parent. Parent dengan 0/1/2/3/4 child: **53/132/106/28/1**. | Nol child bukan bukti parent dummy. Hitung ulang per candidate, bukan berdasarkan total cohort. |
| Assessment 2025 → training non-2025 | **0** reference dalam snapshot. | Tetap precondition live; drift dapat menambah dependensi. |
| Training 2025 → assessment 2026 | **12** row. | Relink/cleansing dapat menyentuh sejarah lintas tahun; parent 2026 tidak boleh berubah. |
| Training → participants | Satu participant pada training **2027**; **0** pada training 2025. FK delete cascade tersedia. | Parent 2027 dan pivot tetap dilindungi; tidak mengandalkan cascade untuk cleansing. |
| Master dipakai 2025 dan luar 2025 | **3 TC / 2 SK / 4 Additional** distinct master dibagi lintas cohort assessment/training. | Tidak boleh overwrite deskripsi/standar master bersama. Angka dihitung dari reference eksplisit, belum seluruh consumer. |
| Master → assessment 2026 | **7 TC / 6 SK / 5 Additional** distinct master. Training 2027 tidak mempunyai reference master eksplisit. | Data 2027 tetap dapat memakai competency teks; nol numeric reference bukan bukti tidak terdampak tampilan. |
| Detail/history → posisi | **723** memakai posisi target yang ada; **617** pada posisi yang juga mempunyai assessment 2025, **10** pada posisi yang juga mempunyai assessment 2026. Dua angka dapat overlap. | Tidak ada `id_trs`, `id_user`, atau kolom tahun pada detail. Association berdasarkan posisi bukan bukti parent/period, sehingga bukan child-delete set. |

Selain FK, telusuri logical references, JSON/text snapshots, history, export, dashboard, file bukti, cache dan event/notification. Pencarian kolom eksplisit dump menemukan `id_trs` hanya pada training dan master IDs pada assessment/training; ini tidak membuktikan nihil dependensi melalui nama/JSON/kode lain.

### 4.2 Constraint nyata pada dump target

- `trs_penilaian_tcs` mempunyai tiga UNIQUE: `(id_user,id_job_position,tahun_penilaian,id_tc)`, analog SK dan AD, serta index `(tahun_penilaian,is_locked)`. Tidak ditemukan grup duplikat nonnull pada masing-masing key untuk 320 row 2025. Uniqueness tidak membuktikan record asli; nullable key juga bukan pengaman semua bentuk duplikasi.
- `mst_tcs`, `mst_soft_skills`, `mst_additionals`, dan `mst_pd_pengajuans` hanya mempunyai PK pada ALTER index yang diperiksa; tidak ada unique business key yang boleh diasumsikan melindungi check-then-insert.
- Tidak ada FK dump untuk assessment → user/position/master, training → assessment/master/position, atau detail → assessment. `id_trs` bertipe int sementara parent assessment bigint unsigned. Relasi Eloquent tidak menggantikan constraint.
- Participants mempunyai FK `people_development_id` → training dengan DELETE CASCADE. Assignment dan approval berhubungan dengan posisi; modul KM juga mengacu posisi/assignment. Karena itu posisi aktif bukan target cleansing.
- Tidak merancang penambahan index/FK atau normalisasi tipe sebagai perubahan otomatis. Ukuran/query plan/ledger live perlu diverifikasi dahulu; schema target adalah acuan tugas ini.

### 4.3 Discrepancy yang melarang menjalankan seluruh migration begitu saja

| Bukti repository vs dump | Status dan rencana |
| --- | --- |
| Ketiga model master memasukkan `deskripsi_level_1..4`; migration `2026_06_29_000003_add_deskripsi_bertingkat_fields.php` membuatnya. Kedua dump tidak memiliki kolom tersebut; target hanya sudah memiliki `mst_tcs.sub_kategori`. | STATIC VERIFIED. Ledger target tidak mempunyai migration `2026_06_29_*`. Asal perubahan parsial PENDING HR/DBA. PR 3 memakai kolom tersedia dan rubric kategori; jangan menambah migration atau mengeksekusi migration lama untuk memaksa kesamaan. |
| Migration `2026_06_29_000002_add_penilaian_yearly_and_history_fields.php` tidak tercatat di ledger target, tetapi kolom tahun/lock/history ada. | Schema dan ledger berbeda; perlu rekonsiliasi staging yang disetujui, bukan menambah ulang kolom. |
| `2026_06_29_000004_rename_tc_poin_kategoris.php` menulis `nama_kategori`; kedua dump/model menggunakan `judul_keterangan`. | Tidak boleh dijalankan sebagai solusi label. Usulan perubahan bisnis tetap terpisah. |
| Ledger target mencatat migration unique assessment `2026_08_03_163700...`, batch 59. Implementasinya memanggil `cleanDuplicates()` dan menghapus selain MIN(id) sebelum menambah UNIQUE. | Bahaya: berpotensi membuang parent tanpa remap training. **Hipotesis** penyebab orphan, belum terbukti tanpa backup/log before-after. Jangan rerun, rollback/reapply, atau menjadikannya cleaner PR 2. |
| Model training mengizinkan `sharing_knowledge`; migration `2026_07_08_100005...` tercatat batch 35, tetapi kolom itu tidak ada dalam CREATE target. | Runtime NOT RUN / PENDING HR/DBA; pertahankan schema target, jangan memasukkan field yang tidak ada atau menjalankan migration massal. |
| `TrsPenilaianTc::detailPenilaian()` dan `DetailTcPenilaian::tcPenilaian()` menggunakan `id_job_position` sebagai foreign key tanpa explicit owner/local position key; detail `user()` mengacu kolom `id_user` yang tidak ada dalam dump. Controller history query berdasarkan posisi. | Tidak boleh memakai relasi ini sebagai otoritas lineage untuk delete/remap detail. Temuan dicatat; refactor relasi tidak termasuk implementasi diam-diam. |

## 5. Audit jalur repository dan rencana PR 1

### 5.1 Flow aktif dan celah yang harus ditutup

`routes/web.php:91` membuka group `web,auth`. Group Mapping Karyawan di `1059–1075` mengarah ke **`UserJobPositionController` tanpa prefix tanggal/copy**. Tidak ada route Mapping Karyawan paralel ditemukan di `routes/api.php`.

`UserJobPositionController::ensureAccess()` memakai `HRMenuAccessGroup::JOB_POSITION->hasAccessForUser()`. Gate ini menggabungkan tiga entry allowlist existing dengan full-access identity dari `HRRoleAccessService`. Sementara menu di `layout.blade.php` hanya memakai `hasFullAccess()`. **Preserve union izin manage existing**; jangan mengganti semuanya menjadi dua identity full-access competency, dan jangan memakai nama/jabatan dari request sebagai otoritas.

| Surface existing | Audit statis | Desain enforcement Gate 2 |
| --- | --- | --- |
| GET `user-job-position.index` | Gate manage; query mapping, users, positions, departments, sections tidak scoped untuk reader. | `canViewMapping`; scope mapping dan seluruh opsi/filter sebelum paginate/serialize. HR manage tetap full list. |
| GET `user-job-position.api.by-user` | `getPositionsByUser()` tidak memanggil `ensureAccess()` atau authorisasi target `user_id`; mengembalikan posisi dan approval-route relations. | Tutup akses staff; validasi dan authorisasi target; kembalikan hanya assignment/field yang boleh dibaca. |
| GET `user-job-position.api.working-experience.index` | Memiliki gate manage dan validasi `exists`; tidak mempunyai subordinate scope. | Izin view dan employee scope yang sama untuk modal detail dan direct AJAX. |
| POST store, PUT update, PATCH toggle-active, DELETE destroy mapping | Seluruhnya memakai gate manage yang sama. Destroy sebenarnya menonaktifkan assignment, bukan hard delete. | Pertahankan manage-only, termasuk untuk head yang membidik bawahan sendiri. Jangan mengubah deactivate/audit behavior. |
| POST/PUT/DELETE Working Experience | Controller gate manage; FormRequest `authorize()` true. Update tidak menerima reassignment `user_id` lewat validated fields. | Manage-only sebelum validasi sensitif/write; pertahankan owner dari bound model dan validasi existing. |
| POST import dan GET template | Gate manage. Import dapat menulis banyak employee. | Tetap HR/Admin manage-only; head tidak mendapat jalur bulk bypass. |
| Detail mapping | Tidak ada route show standalone; detail ada pada modal index dan Working Experience AJAX. | Reader memakai modal detail tanpa editable form; bila kemudian ditambah show route, harus memakai guard sama. Tidak diperlukan route baru sekarang. |

Risiko boundary terfokus: authenticated staff memasukkan `user_id` lain pada by-user; head mengganti filter/query/ID atau memanggil mutation langsung setelah UI diperluas. Temuan bersumber kode, **runtime reproduction NOT RUN**; tidak memberi severity atau mengklaim exploit telah dibuktikan. Memperluas `JOB_POSITION` secara global berisiko ikut membuka Master Job Position dan aksi kelola. Jangan memakai satu flag untuk semua operasi.

### 5.2 Desain scope baca minimum

1. Tambahkan operasi khusus Mapping Karyawan pada **service HR existing**, dengan izin view/manage yang terpisah. `canManageMapping` mempertahankan kontrak gate manage existing; `canViewMapping` menambahkan hanya head yang eligible. Jangan mengubah global `hasFullAccess`, allowlist, atau matrix approval.
2. Head eligible berasal dari assignment actor yang efektif sekarang melalui `UserJobPosition::activeAt()` dan master posisi aktif dengan level `sec_head/dept_head/div_head`. Jangan hanya `first()` assignment, role-name prefix, section teks pada users, atau `is_active` tanpa tanggal. Master/hierarki kosong atau tidak valid menolak akses, bukan full fallback.
3. Setelah HR memvalidasi graph reporting, telusuri edge **approver position → subordinate position** pada mapping target existing. Ambil transitive closure semua posisi aktif actor, union + deduplicate; exclude self dan ancestors. Level 0 tidak otomatis diabaikan atau dianggap memberi hak staff. Makna level 0/reporting graph adalah blocker keputusan B6.
4. Hubungkan closure ke assignment bawahan yang efektif dan posisi aktif. Scope list pada **row mapping yang diizinkan**, bukan mengambil semua assignment employee yang kebetulan punya satu posisi bawahan. Multiple position target di cabang lain tidak boleh bocor.
5. Employee yang memiliki assignment bawahan eligible dapat dibaca riwayat Working Experience-nya sebagai satu histori employee. Jangan memperluas lookup employee lain berdasarkan kesamaan nama jabatan/section historis. Mapping nonaktif/future/expired tidak menciptakan hak baca baru.
6. Gunakan scope yang sama untuk list, pencarian OR, pagination totals, pilihan filter, by-user dan Working Experience. Filter pengguna hanya mempersempit scope. List empty mengembalikan empty state, endpoint objek di luar scope ditolak konsisten tanpa data; untuk actor tanpa izin 403, guest mengikuti kontrak auth existing.
7. Traversal finite dengan visited set/cycle detection; malformed edge/siklus gagal tertutup untuk cabang terkait dan dilaporkan. Tidak menulis ulang graph. Batched loading/scoped queries, tidak query per employee atau memuat seluruh User beserta field sensitif untuk reader. Jangan menambah cache scope tanpa invalidasi assignment.
8. Jangan memakai ulang `getAccessibleJobPositions()` secara langsung untuk kontrak ini: service existing memakai pendekatan section/department/approval scope dan nama posisi, bukan recursive reporting closure dengan date-effective assignments. Tambahkan metode khusus agar akses competency/training existing tidak berubah.

View memakai flag server `canManageMapping` untuk form assign/edit, toggle, delete, import, template dan WE CRUD. Head melihat tombol detail/riwayat read-only, dengan text/empty/error/loading state, tanpa dropdown seluruh employee. Reuse renderer `weLoadData(..., isReadOnly)` yang sudah ada; hapus mutation markup/payload bagi reader, tetapi backend tetap otoritatif. Bersihkan hasil dan abaikan stale AJAX saat berpindah employee/menutup modal agar data sebelumnya tidak tampil untuk target baru.

Assignment HR existing dikelola melalui `KmOrganizationAssignmentService` dengan transaction, overlap checks, audit dan deactivate; seluruhnya dipertahankan. PR 1 tidak mengimpor atau mengubah assignment database pada Gate 1.

## 6. Crosswalk PR 2 — hanya 2025, bukan executable migration

### 6.1 Syarat masuk dan bentuk ledger mapping

Untuk exact 281-row legacy assessment cohort pada hash §2.2, **business year 2025 sudah diterima melalui USER_ATTESTATION**: catat `tahun_penilaian=2025`, `business_year=2025`, `period_evidence_type=USER_ATTESTATION` dan rujukan keputusan pengguna/addendum. B1 tidak menunggu independent audit-year proof atau interview ulang. Timestamp, default tahun, usia record, status final dan nama file tetap bukan penentu periode dan tidak ditulis ulang.

Technical eligibility tetap mensyaratkan provenance/identity yang benar, employee NPK unik valid, posisi/master/category, score/status, lineage dan natural-key checks serta tidak mengubah protected data. **Sebelum checks selesai, source assessment tetap QUARANTINE dengan year accepted; hanya yang lolos dapat menjadi INSERT_CANDIDATE, bukan INSERT_APPROVED.** Input/cohort berbeda tidak otomatis mewarisi attestation ini. Source training inserts ditetapkan nol menurut B2/Q15; target training diselidiki tanpa asumsi keaslian atau pengganti.

Crosswalk mempunyai `snapshot_hash`, source table/PK dan locator statement/tuple, opaque employee reference, mapped target IDs, business identity/version, `business_year`, `period_evidence_type`, attestation reference/cohort, method, confidence, reviewer, approval reference, dan mapping revision. Untuk cohort 281 gunakan year 2025 / USER_ATTESTATION, tanpa menyebutnya independent period proof. PK source disimpan sebagai provenance, tidak sebagai target PK. Approval melekat pada hash/revision; input berubah berarti approval kedaluwarsa.

| Entity source → target | Resolusi dan field penting | Stop/klasifikasi |
| --- | --- | --- |
| `users` → lookup target `users` | NPK string valid, nonblank/nonzero dan unik di kedua sisi; preserve leading zero yang bermakna. Simpan source-user → target-user mapping terpisah, tanpa menulis user. | Duplicate/ambiguous/missing → QUARANTINE. Nama/email/PK numerik tidak menjadi fallback. 105 kandidat identitas belum memberi izin transaksi. |
| `tc_job_positions` / assessment text position → `mst_job_positions` | Legacy numeric master position melalui row legacy, kemudian label + konteks organisasi historis yang disahkan HR ke target ID. Aliases eksplisit dan disetujui; jangan memaksa historical position menjadi current assignment employee. | Exact label hanya kandidat. Ambiguitas/gap → QUARANTINE; jangan membuat/mengubah jabatan aktif. |
| Category → `tc_poin_kategoris` | Verifikasi makna kategori dan rubric 1–4, bukan ID/label saja; ketiga label berbeda. | Perubahan standar/kategori perlu keputusan HR; default no category write. |
| TC/SK/Additional → target master sesuai jenis | Identitas mencakup jenis, mapped position, kategori bermakna, definisi kompetensi, standar dan versi deskripsi. Master insert hanya jika direferensikan assessment/training eligible dan tidak ada pasangan valid. | ID-only/name-only/fuzzy auto merge dilarang. Konflik existing → QUARANTINE atau UPDATE_CANDIDATE setelah mismatch terbukti; signed field-level approval tetap diperlukan dan kini 0. Shared master lintas tahun default RETAIN. |
| Legacy assessment → `trs_penilaian_tcs` | Source transaction + resolved employee/position + setiap master terisi + **2025 USER_ATTESTATION untuk exact cohort 281**; pertahankan component values/null, literal timestamps/provenance serta status/lock menurut mapping HR yang masih pending. Generated target IDs baru; row identity dan natural/unique key untuk tiap butir diperiksa. | 281 benturan source PK tidak menyebabkan overwrite. Technical checks belum selesai → QUARANTINE dengan year accepted; lolos checks → INSERT_CANDIDATE saja. Conflict score/status/row packing tetap QUARANTINE; tidak split/merge/backdate atau fabricate approver/history. |
| History/approval → `detail_penilaian_tcs` | Hanya jika actor, posisi, kejadian dan hubungan periode/assessment dapat dibuktikan dari sumber tepercaya. Tidak ada parent ID langsung; before snapshot tidak dibuat-buat. | Satu source detail tetap QUARANTINE sampai lineage tervalidasi. 723 target detail tidak dipindah/dihapus dengan filter posisi/timestamp. |
| Legacy training → `mst_pd_pengajuans` | VERIFIED STATIC: source **0 row**. USER-CONFIRMED Q15=C: tidak ada authentic replacement source; **0 legacy training inserts planned**, B2 closed sebagai known limitation. Bedakan `tahun_aktual` dari `tahun_usulan`/entry dates pada rekonsiliasi target. | Tidak mencari replacement sebagai precondition; tidak membuat sejarah dari Faker/demo/importer dummy/assessment gaps/guessed logs. Jika genuine source kelak muncul, lakukan review/scope/approval baru dengan mapping employee/position/section/category/master/assessment/role/modifier yang eksplisit, bukan numeric legacy IDs. |
| Existing training 2025 → reconciliation | Review T25-A/B/C/D; relink hanya bila parent baru/survivor terbukti cocok employee, position, period dan master kategori. Pertahankan evaluation/attachment/provenance. | Orphan atau same-PK bukan dasar relink. UPDATE_CANDIDATE tidak boleh dieksekusi tanpa signed approval manifest; child REVIEW_DELETE harus direview sendiri, bukan ikut parent approval. Kini 0 update/delete approved. |
| Participants/attachments/config | Relasi participant dipertahankan per training yang disahkan; file butuh inventaris terotorisasi. Config tahun aktif bukan bahan migrasi. | Participant 2027/config OUT_OF_SCOPE. Tidak menghapus file atau menyalin path legacy tanpa verifikasi. |

Dalam future candidate ledger untuk 281 source, tahun target harus eksplisit `tahun_penilaian=2025` berdasarkan USER_ATTESTATION, bukan default 2026 atau `created_at`; historical status/lock tetap menunggu mapping B5, bukan langsung final/approved. `modified_at` pada assessment adalah numeric actor ID, tetapi pada training/history dipakai string actor/group, bukan timestamp: map sesuai makna masing-masing, bukan cast generik.

### 6.2 Provenance dan kelemahan importer existing

`CompetencyTrainingDataImportService.php` secara statis memang membaca dump tanpa mengeksekusinya dan mempunyai insert-only/protected-table checks. Namun kontraknya **tidak sesuai PR 2 final**:

- `preparePlan()` hanya menerima years `[2025,2026]`, memanggil database untuk schema/data target, dan default membuat 50 assessment serta 25 training dummy **per tahun**.
- `planSourceAssessments():480` memilih `created_at` year 2025, menerima source `id_user` jika numeric ID itu ada di target, serta memungkinkan position fallback dari assignment aktif user. Tidak membaca source users untuk NPK crosswalk.
- `planSourceDetails():582` juga memakai `created_at`; status source disalin tanpa business status mapping tervalidasi. Masters direncanakan dari inventory source, bukan hanya closure transaksi 2025 yang disetujui.
- Dummy generation memakai marker `DUMMY-HR-CTD|...`, label `[DUMMY ...]`, serta data evaluasi sintetis. Command `ImportCompetencyTrainingDataCommand` default source `(4)` dan opsi dummy nonzero. Bahkan mode `--dry-run` mengakses DB dan membuat rencana dummy, sehingga **tidak dijalankan** pada Gate 1.
- `DummyCompetencyTraining2025_2026Seeder` juga mempunyai Faker/random dan `updateOrCreate`; keberadaan source seeder bukan bukti ia membuat target snapshot atau penetapan jumlah row yang berasal darinya.

Pada seluruh target assessment/detail/training dan ketiga master ditemukan **0 marker `DUMMY-HR-CTD|`**, serta **0 row mengandung kata `dummy`** pada field literalnya. Ini **tidak membuktikan real**, dan sekaligus tidak memberi atribusi importer spesifik. B3/B4 memerlukan per-record provenance/lineage dari bukti non-replacement yang benar-benar tersedia dan diotorisasi, misalnya execution log, backup before/after atau HR corroboration. Q15 menerima tidak adanya replacement source; jangan menebak log atau menggunakannya untuk menciptakan kejadian training baru.

Usulan Gate 2: reuse parser literal/pola pelaporan existing setelah diuji; isolasikan jalur rekonsiliasi 2025 approved-manifest dari generator fixture. Jangan mengubah alat dummy/test menjadi cleaner produksi, jangan menjalankan command existing apa adanya, dan jangan merancang jalur production dengan default apply/dummy. Bentuk minimal command/service dapat diputuskan saat implementasi dari kontrak ini; tidak perlu platform ETL atau dependency baru.

### 6.3 Mapping status, locking, dan score

| Aspek | Bukti target repository | Mapping historis |
| --- | --- | --- |
| Assessment | Ada draft `0`, submit/review `1/2`, cabang Div `3`, final `4`; `kirimDept()` dapat menuju 3 atau 4 menurut jalur existing. | Legacy `1/2/3` → target: **PENDING HR/DBA**, bukan ordinal copy/offset. Simpan source status sebagai provenance. |
| Lock | `TrsPenilaianTc::lockPreviousYears()` melakukan UPDATE otomatis saat index assessment diakses. 320 target 2025 sudah locked. | Preserve historical read-only menurut mapping tervalidasi; tidak unlock/reapprove/reset demi migrasi. Browser QA kelak hanya di sandbox karena GET index bisa menulis. |
| Training | Model `status_1` 1/2/3 dan enum progress terpisah dari assessment. | Jangan menyamakan status assessment dengan progress training. Source kosong + Q15=C berarti rencana nol source inserts, bukan pending pencarian sumber; setiap koreksi status target tetap memerlukan evidence/approval tersendiri. |
| Score/total | Consumer `CompetencyAssessmentService` membaca komponen per kategori; diagnostik total berbeda antar snapshot. | HR menetapkan semantik historical total/null/nol dan row packing; jangan menghitung ulang existing atau mengubah skor pada PR 3. |

PR 4 tetap ditunda: tidak memperbaiki routing approval Sales atau struktur organisasi sebagai bagian mapping status historis.

## 7. Manifest klasifikasi dan rancangan candidate-delete per record

### 7.1 Klasifikasi dengan keadaan aman sekarang

QUARANTINE di sini berarti **status pada laporan**, bukan memindahkan/menghapus row dari aplikasi atau membuat tabel quarantine pada Gate 1.

Vocabulary `classification` tetap **RETAIN, INSERT_CANDIDATE, UPDATE_CANDIDATE, REVIEW_DELETE, DELETE_APPROVED, QUARANTINE, OUT_OF_SCOPE**. Counter `INSERT_APPROVED`/`UPDATE_APPROVED` mencatat keputusan data yang ditandatangani, bukan sinonim untuk label kandidat. **0 INSERT_APPROVED / 0 UPDATE_APPROVED / 0 DELETE_APPROVED** saat ini; scope Q13, attestation business-year, suspicion Q14 dan no-replacement Q15 tidak menaikkan counter tersebut.

| Klasifikasi | Kriteria | Keadaan saat audit dan tindakan |
| --- | --- | --- |
| RETAIN | Existing valid/required dependency dengan bukti cukup. | Protected data tetap disimpan. Validitas substantif 320 assessment/934 training 2025 belum disahkan; jangan melabeli seluruhnya valid. |
| INSERT_CANDIDATE | Source assessment mempunyai year accepted 2025 / USER_ATTESTATION dan lolos technical NPK/position/master/category/score/status/natural-key/dependency checks. | Proposal review saja, **0 approved**. Seluruh 281 mempunyai year accepted; sebelum technical checks selesai tetap QUARANTINE, bukan 281 INSERT_CANDIDATE otomatis. Planned legacy training inserts **0**. |
| UPDATE_CANDIDATE | Mismatch existing terbukti; field-level before/after dan dampak dependency tersedia. | Proposal review saja. **0 UPDATE_APPROVED**; belum ada relink atau recovery deskripsi existing disetujui. |
| REVIEW_DELETE | Dugaan dummy/duplicate/superseded/irrelevant dengan alasan bisnis yang dapat diuji dan parent/child terinventarisasi. | Bukan izin delete. Cohort T25-A/B/C/D masih QUARANTINE sampai bukti memadai; anomaly relasi saja tidak mempromosikannya menjadi usulan delete yang sah. |
| DELETE_APPROVED | Dummy/duplicate/superseded/irrelevant terbukti independen + exact-record signed HR manifest + dependency clearance + verified backup/restore dan rollback rehearsal. | **0**. Tidak ada delete set yang sah; tetap memerlukan izin eksekusi terpisah. |
| QUARANTINE | Technical NPK, posisi/master/category, lineage, score/status, natural-key atau provenance belum tuntas; periode record di luar attested cohort juga perlu evidence sesuai konteks. | Source: **281 assessment dengan year accepted 2025**, 1 detail yang lineage-nya belum jelas; target review: **320 assessment + 934 training**. Q14 suspicion bukan proof. Alasan dapat overlap; source/target bukan record identik. |
| OUT_OF_SCOPE | Transaksi bukan 2025, active mapping/accounts/unrelated modules/config. | Target **7 assessment 2026**, **18 training 2027**, **1 pivot participant 2027**, seluruh protected entities. No mutation. |

Master belum mempunyai approved insert/delete quota. Hanya dependency closure record eligible kelak yang dapat diusulkan; shared/current definitions tetap disimpan. Tidak ada bukti duplikasi pada unique assessment keys 2025 atau komposit master yang diperiksa. Jangan membuat delete proposal berbasis MIN(id).

Alur review: unresolved → QUARANTINE; bukti cukup → RETAIN atau kandidat tindakan spesifik; HR dapat reject/return/approve pada manifest yang ditandatangani. REVIEW_DELETE tidak menjadi DELETE_APPROVED tanpa bukti independen, dependency clearance, exact-record approval, backup dan restore/rollback rehearsal. Tidak ada classification, termasuk DELETE_APPROVED, yang sendirian mengizinkan eksekusi: izin user untuk environment/action dan manifest revision yang tepat tetap terpisah.

### 7.2 Schema manifest review

Manifest review kelak satu row per **exact target record**, bukan wildcard tahun/posisi; insert candidate yang belum memiliki target PK memakai `target_pk=NULL` dan mapping hasil ID yang eksplisit, bukan source PK yang dipakai ulang. Published plan ini hanya template/desain tanpa row employee nyata; **manifest approved saat ini kosong**. Actual PK, employee mapping, before/after payload, dokumen bukti personal dan **seluruh actual manifest** harus berada **di luar tracked repository** pada lokasi restricted yang disetujui HR/DBA. Lokasi/ACL/akses approver **PENDING HR/DBA (B9)**. Tugas ini tidak mengisi real per-record manifest, membuat private annex data, atau menjalankan SQL; pekerjaan itu memerlukan otorisasi berikutnya. Jangan membuat link publik atau memasukkan dump/credential dalam laporan.

| Field manifest | Isi/aturan |
| --- | --- |
| `manifest_revision`, `run_id`, `candidate_ref` | Identitas immutable; opaque ref dengan private lookup, bukan NPK/nama. |
| `source_snapshot_hash`, `target_snapshot_hash`, `mapping_revision`, `repository_ref` | SHA-256 input immutable yang diperiksa, versi crosswalk dan baseline kode. Perubahan source/target/mapping/record hash membatalkan approval sebelumnya; baseline data live kelak harus dibekukan lagi. |
| `table`, `target_pk`, `source_pk` | Tabel allowlist dan PK eksplisit pada annex privat; source PK tidak menjadi PK tujuan. Public review menggunakan opaque locator. |
| `employee_opaque_id`, `employee_npk_masked` | Opaque ID menjadi referensi utama; private lookup employee/NPK hanya tersedia kepada reviewer yang berhak. Masked NPK hanya jika diperlukan dalam annex restricted. Jangan gunakan plain hash NPK yang dapat ditebak sebagai anonymization. |
| `business_year`, `tahun_penilaian`, `period_evidence_type`, `period_evidence`, `attestation_ref`, `attested_cohort_hash`, `period_review_status` | Untuk source cohort 281: year 2025, USER_ATTESTATION dan reference keputusan pengguna/addendum yang terikat source hash + table/cohort; B1 CLOSED FOR BUSINESS-YEAR DECISION. Simpan literal timestamps terpisah. Future ledger mengaitkan setiap source locator ke cohort attested ini, bukan mengklaim independent year proof atau mengasumsikan attestation mencakup source/detail lain. |
| `record_origin`, `provenance_evidence`, `source_reference`, `target_reference` | Hash dump + table + INSERT ordinal + tuple ordinal; tambahan job log/workbook/attestation yang membuktikan asal. |
| `classification`, `candidate_action`, `reason_code`, `reason_evidence_ref` | Gunakan vocabulary §7.1. Catat operation usulan terpisah dari approval; QUARANTINE/INSERT_CANDIDATE/UPDATE_CANDIDATE/REVIEW_DELETE bukan instruksi apply. PROVEN_FIXTURE, PROVEN_DUPLICATE, VERIFIED_SUPERSEDED memerlukan evidence independen; ORPHAN_UNKNOWN hanya investigasi. |
| `before_payload_ref`, `after_payload_ref`, `before_hash`, `after_hash` | Snapshot exact field/null/status/lock/reference; hash canonical typed JSON. Delete memiliki after=tombstone/absent yang eksplisit. Tidak memuat password/account payload. |
| `parent_dependencies`, `child_dependencies`, `dependent_record_counts` | Seluruh parent/child inbound/outbound: table/relation, exact private IDs atau set reference, jumlah, PK-set hash, periode, status, protective/cascade behavior dan keputusan per dependency. FK, logical/ambiguous history, shared-master consumers, participants dan attachments ikut dicatat. Unknown adalah PENDING HR/DBA, tidak diganti 0. Cross-year dependency melarang efek tidak langsung pada protected data. |
| `survivor_pk`, `relink_manifest_refs`, `planned_action` | Untuk duplicate, buktikan survivor dan semua child remap. Relink adalah perubahan dengan approval sendiri. |
| `hr_reviewer`, `dba_reviewer`, `hr_reviewer_decision`, `dba_reviewer_decision`, `decision_reason`, `approver`, `approval_timestamp`, `approval_evidence` | Keputusan eksplisit PENDING/REJECT/RETURN/APPROVE, signature/reference terhadap exact-record manifest revision, business evidence dan before/after hash; HR owner untuk kebenaran bisnis, DBA untuk parent/child/dependency, user untuk izin environment/action. Scope/period confirmation Q13–Q15 atau code approval bukan data approval. Approval kosong berarti tidak boleh apply. |
| `backup_reference`, `backup_checksum`, `restore_rehearsal_evidence`, `rollback_action`, `rollback_verified_ref` | Backup terverifikasi meliputi affected rows/relations/files; restore drill dan hasil baseline counts/hash direkam, restore PK asli dan reverse relink memakai precondition hash. Klaim bisa rollback tanpa rehearsal bukan bukti. |
| `execution_state`, `result_counts`, `after_verification_ref` | Planned/applied/rolled-back kelak; tidak menulis klaim applied pada Gate 1. |

### 7.3 Usulan investigasi yang konkret, belum usulan delete final

| Candidate family untuk manifest per record | Bukti sekarang | Dependensi sekarang | Before → after yang boleh direncanakan |
| --- | --- | --- | --- |
| 490 target training T25-A | Missing numeric parent, bukan proven fixture. | Parent missing; 0 pivot 2025 dan 0 file field terisi pada snapshot. Penyebab/history/external evidence belum diketahui. | Before unchanged → **unchanged / QUARANTINE**. Delete hanya jika HR membuktikan dummy/obsolete, bukan karena orphan. |
| 433 T25-B dan 10 T25-C | Parent ada namun identitas/competency tidak cocok. | Parent serta periodenya harus dicatat; subset link menuju 2026 dilindungi. | Unchanged → unchanged; alternatif relink/supersede paling jauh UPDATE_CANDIDATE setelah bukti cukup, lalu signed approval per field/record. |
| 1 T25-D | Link koheren, asal kejadian belum terbukti. | Parent ada; tidak boleh dianggap survivor valid hanya dari relasi. | Unchanged → unchanged; RETAIN baru setelah validasi HR. |
| 320 assessment target 2025 | Provenance belum diketahui; unique-key duplicate groups 0. | Profil child 53/132/106/28/1; history ambiguous. | Tidak ada delete proposal final. Parent tanpa child juga tetap disimpan. |
| Master terkait record approved kelak | Shared lintas tahun terbukti pada 3/2/4 master. | Hitung seluruh reference dan consumer, bukan hanya 2025. | Default RETAIN. Drop master dilarang; konflik definisi diselesaikan tanpa mengubah 2026+. |

Untuk candidate yang kelak terbukti: tampilkan satu baris before/after per PK beserta jumlah dependensi dan reversal, lalu minta persetujuan **atas manifest konkret**. File ini tidak menyertakan SQL destruktif atau placeholder query untuk diisi operator.

Dependency order kelak harus mengikuti graph yang disetujui: backup parent+child; insert parent/survivor yang sah; relink hanya child approved; validasi; cleanup child approved sebelum parent approved. **Tidak ada cascade implisit ke non-2025**. Ambiguous detail tetap disimpan dan memblokir delete parent sampai lineage/retention terselesaikan. Master tidak menjadi target drop.

## 8. Rencana PR 3 — informasi kompetensi dan panduan 1–4

### 8.1 Flow aktif dan kendala

- Create: `/buat-penilaian` → `PenilaianTCController::createPenilaian()` → `tc_penilaian.sc_penilaian`; AJAX `getJobPositionData()` menghasilkan header/score input tanpa deskripsi master.
- Edit/Dept/Div/View: `editTrs*`, `viewTrs`, `previewTrs`, `yearlyHistory` menuju `edit_penilaian`, `dept_penilaian`, `view_penilaian`, dan **`privew_penilaian`** (ejaan existing). AJAX `getDataTrs()` menyediakan relation master.
- Modal detail existing pada create/edit/dept/view memakai `job.positions.details2` → **`TcController::fetchDetails2()`**, tiga master dengan `poinKategori`. General accordion sudah menampilkan rubric category 1–4. Modal sudah mempunyai kolom description dan fallback tingkat → category.
- `fetchDetails2()` saat ini tidak mempunyai object access check; menerima angka atau mencari MIN(id) dari label. `getDataTrs()` tidak mempunyai check target dan tidak membatasi tahun. `getJobPositionDataEdit()` juga belum menjaga objek secara memadai. Semua berada dalam auth group, bukan otomatis subordinate authorization. Ini risiko data/IDOR pada jalur yang hendak digunakan, **runtime NOT RUN**.
- `getJobPointKategori()` membaca `pk.id_job_position/id_tc/id_sk/id_ad/standar_poin/tujuan/deskripsi` yang tidak ada pada kedua schema kategori. Jangan memilih endpoint itu sebagai basis UI baru; pencarian menunjukkan create sudah tidak memakainya. Tidak perlu memperbaiki endpoint legacy tak terpakai untuk memperluas tugas.
- JS existing membentuk HTML dari string master. Pemulihan deskripsi legacy dapat memperbesar risiko stored HTML injection; gunakan text rendering/escaping pada semua konten baru, bukan interpolasi raw ke `.html()`.

### 8.2 Desain minimum yang diusulkan

1. Tambahkan tombol teks **“Informasi [nama kompetensi]”** pada header kompetensi; buka disclosure kontekstual, tanpa mengubah input score/hidden fields. Gunakan ID stabil **jenis + master ID**, bukan dedup nama saja. Header, deskripsi dan score tidak boleh tertukar untuk label sama di dua kategori.
2. Panel menampilkan nama, kategori, deskripsi spesifik, standar existing, dan daftar skor 1–4 dari `poinKategori`. Kalau deskripsi kosong: **“Deskripsi kompetensi belum tersedia.”** Jangan mengganti deskripsi spesifik dengan rubric generik seolah informasinya lengkap.
3. Gunakan field description tersedia pada schema target. Tidak bergantung pada `deskripsi_level_*`, tidak menambah schema. Jika live schema kelak terbukti mempunyai tier, pemakaiannya perlu keputusan sumber tersendiri; default rubric tetap 1–4 existing.
4. Reuse data `fetchDetails2()` sekali per position dengan authorisasi server, payload whitelist, dan category eager loading; pertahankan root payload yang dipakai caller existing. Hindari request per input/employee. Guard metadata harus meliputi actor competency yang sah dan posisi yang sah, tanpa membuka staff atau mengubah manage master.
5. `getDataTrs()`/jalur edit yang dipakai harus menerapkan guard objek dan periode request yang tervalidasi untuk menjaga informasi historis/score tidak bercampur 2025/2026. Perubahan hanya read filtering yang diperlukan; tidak mengubah score save/approval. Existing caller dan responsnya harus dikarakterisasi terlebih dahulu pada Gate 2.
6. Render panel dari DOM text nodes/jQuery `.text()` dan Blade escaped output; tidak menafsirkan deskripsi sebagai HTML. Tutup/reset panel dan abaikan respons AJAX usang saat position berubah. Error metadata tidak menghapus score yang sudah diisi.
7. Pertahankan accordion umum dan modal ringkasan existing. Reuse partial Blade untuk konten kontekstual lintas form bila perlu; tidak ada CSS framework/Vite entry/dependency baru atau redesign global.

Pemulihan description legacy **terpisah dari UI**: hanya melalui master crosswalk approved, before/after, dependency analysis, dan persetujuan update existing. Bila master dipakai 2026+, jangan menimpa record bersama. Prioritaskan rendering existing data + empty state; alias/version display historis dapat diusulkan setelah HR memilih sumber, tetapi belum menyetujui tabel/abstraksi/versioning baru.

### 8.3 Acceptance accessibility

Tombol menggunakan native button `type=button`, accessible name yang menyebut competency, `aria-expanded` dan `aria-controls` dengan ID unik. Enter/Space berfungsi, fokus terlihat dan tidak tertutup, tab order mengikuti posisi header. Isi terbaca tanpa hover dan tidak hanya dibedakan dengan warna. Score input tetap memiliki label/association employee dan kompetensi; panduan terkait melalui `aria-describedby` bila sesuai. Panel/empty/error state memiliki teks, asynchronous status diumumkan sewajarnya. Jika modal existing dipakai, Escape/close dan pengembalian fokus ke trigger diuji.

Uji desktop, lebar 320 CSS px dan zoom 400%; informasi panel reflow meskipun tabel data membutuhkan horizontal scroll. Uji kontras teks normal 4.5:1, kontrol/fokus yang relevan 3:1, target tombol 24×24 CSS px atau spacing exception yang sah. Ini target uji, **bukan klaim conformance**. Referensi: [WCAG 2.2](https://www.w3.org/TR/WCAG22/) dan [WAI-ARIA disclosure pattern](https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/). Focus Visible 2.4.7 berbeda dari Focus Not Obscured 2.4.11; tidak mengklaim Focus Appearance AAA sebagai syarat AA.

## 9. Blocker dan keputusan yang masih diperlukan

**USER-CONFIRMED:** Q13=A, Q14=C, Q15=C dan business period seluruh exact 281-row legacy cohort sudah final. **B1 CLOSED FOR BUSINESS-YEAR DECISION; B2 CLOSED AS NO REPLACEMENT SOURCE.** Keduanya tidak meminta interview, independent year proof atau replacement source lagi. B3–B9 tetap **PENDING HR/DBA** sesuai lingkupnya; live verification, code, staging, backup/restore dan data actions **NOT RUN / NOT AUTHORIZED**.

| ID | Kekurangan bukti / keputusan | Pemilik | Dampak / closure gate |
| --- | --- | --- | --- |
| B1 | **CLOSED FOR BUSINESS-YEAR DECISION — USER-CONFIRMED:** tepat 281 source assessment dalam immutable legacy hash §2.2 adalah business period 2025, `period_evidence_type=USER_ATTESTATION`. | **User** sebagai pemberi attestation; HR Assessment Owner/DBA mencatat dan menjaga scope referensinya pada review berikutnya. | Tidak menunggu konfirmasi tahun lagi. Preserve literal 10 created/2025 dan 271 created/2026; future input harus cohort/hash sama. Ini bukan independent year proof atau write approval; technical identity/master/status/natural-key checks tetap pending pada M2 dan blocker terkait, bukan membuka ulang B1. |
| B2 | **CLOSED AS NO REPLACEMENT SOURCE — USER-CONFIRMED Q15=C:** tidak tersedia authentic replacement training 2025; dump source 0 row; **0 legacy training inserts planned**. | **User + HR Training Owner** untuk pencatatan limitation; DBA menjaga no-synthetic guard. | Bukan prerequisite menemukan sumber baru; ketiadaan source tidak membuktikan target invalid. Data yang kelak proven obsolete/delete-approved dapat meninggalkan nol training tanpa pengganti. Sumber autentik yang baru muncul kelak memerlukan scope/review/approval baru. |
| B3 | **OPEN / PENDING HR/DBA:** provenance 934 training/320 assessment; Q14=C mencurigai seluruh 934 training, tetapi independent per-record proof dummy/duplicate/superseded/irrelevant belum ada. | **HR Data Owner + Import Operator**, dengan DBA untuk bukti teknis. | Blokir data update/delete tanpa signed exact-record evidence; bukan bulk timestamp/status/generator atau suspicion pengguna saja. T25-D tetap perlu bukti kejadian; kedua target cohort tidak dinyatakan dummy. |
| B4 | Akar 490 missing references, 433 cross-user dan 10 selected-master mismatch belum terbukti; ada 12 links ke assessment 2026. | **DBA + Workflow Owner**. | Blokir relink/cleanup; telusuri backup/log sebelum unique cleanup, source IDs dan lineage. Jangan mengatribusikan cause tanpa bukti atau merusak protected period. |
| B5 | Mapping status/approval/lock, score/total/nol dan row packing historis. | **HR Approver proses lama/baru**. | Mapping terversi yang disahkan; tidak membuat approval/history atau menghitung ulang existing secara sepihak. |
| B6 | Crosswalk posisi/master/category; level 0, approval versus reporting graph, direct/indirect dan multiple effective assignments. | **HR Organization Owner + Authorization Owner**. | Validasi management graph sebelum Gate 2 PR 1; PR 2 mapping ambiguous tetap tertahan. Tidak mengubah active mapping atau Sales hierarchy untuk menutup gap. |
| B7 | Governing source deskripsi dan izin perubahan shared master/consumer di luar 2025. | **HR Competency Owner**. | Recovery description legacy blocked sampai mapping/impact/approval terpisah. **Tidak memblokir PR 3 existing-data-only** dengan rubric 1–4/empty fallback, setelah security/UX review dan code approval. |
| B8 | Runtime schema/ledger drift, engine/server dan data live belum diverifikasi, termasuk tier description, year/lock, `sharing_knowledge` dan dedupe migration. | **DBA + Staging Operator**. | Preflight kelak atas lingkungan yang disahkan; metadata dump bukan bukti live. Tidak replay migration/cleaner demi melengkapi laporan. Drift membatalkan manifest approval yang terpengaruh. |
| B9 | Approved restricted location di luar tracked repo, annex ACL/revision, backup/file coverage, restore/rollback rehearsal, timing dan go-live authorization belum ada. | **HR + DBA + User/Execution Owner**. | Tidak ada staging write/production action tanpa izin environment/action terpisah dan backup/restore evidence; Gate 2 code approval tidak menggantikannya. |

Business-scope/design decisions Gate 1 telah dicatat final; PR 2 tetap **DATA ACTIONS PENDING VALIDATION AND SEPARATE APPROVAL**, bukan migration-ready. Remaining actionable blockers adalah B3 proof, B4 lineage, B5 score/status, B6 hierarchy/crosswalk, B7 description governance, B8 runtime schema dan B9 backup/rollback/exact approvals. PR 1/PR 3 tetap independen melalui gate masing-masing §13. Penutupan B1/B2 tidak menutup technical blockers atau memberi record-write approval.

## 10. Strategi staging, dry-run, dan rollback — rencana untuk gate berikutnya

### 10.1 Gate dan isolasi

1. **Freeze bukti:** hash source, target, code ref dan mapping revision; minimalkan extract, annex privat, audit counts. Parser menolak statement/shape tak didukung dan melaporkan exception tanpa raw row/credential.
2. **Resolve remaining technical review PR 2:** gunakan business-year attestation B1 yang sudah closed dan no-replacement constraint B2; HR/DBA memvalidasi exact identity/master/category/score/status/natural keys, provenance dan dependencies sesuai B3–B9 atau mempertahankan QUARANTINE. Signed reason/action review terpisah dari keputusan Q13–Q15. Tidak mencari training source baru, tidak membuat pengganti, dan tidak memaksakan seluruh row lolos. Langkah data ini tidak menahan PR 1/PR 3 yang memenuhi gate masing-masing.
3. **Gate 2 code approval terpisah per jalur:** PR 1 setelah B6 graph/authorization tervalidasi; PR 3 existing-data-only setelah security/UX review tanpa menunggu recovery B7; PR 2 sesuai keputusan data/mapping dan kontrak safe tooling. Default jalur data read-only, tahun allowlist tunggal 2025, no-dummy, no apply ke connection yang tidak disahkan.
4. **Staging terisolasi terotorisasi:** schema source-reference dan target-rehearsal berbeda, nama berakhiran `_testing` untuk test DB, host/credential terpisah dari production; jangan mengeksekusi kedua raw dump yang menyebut nama database sama ke schema yang sama. Gunakan extract/schema yang direview, bukan menjalankan seluruh dump/migration/seeder tanpa inspeksi. Network outbound, email, FCM, queue side effects dan scheduler dinonaktifkan/difake; file storage staging terpisah. Data disanitasi/pseudonymized dengan relasi tetap konsisten sebelum test aplikasi.
5. **Dry-run tanpa write:** read-only connection/role, tanpa model event atau panggilan index yang mengunci tahun otomatis; menghasilkan crosswalk, conflict report, klasifikasi, before/after dan dependency counts. Akses DB belum dilakukan pada Gate 1 ini.
6. **Rehearsal apply di disposable staging:** hanya setelah persetujuan eksplisit staging write; exact manifest IDs, before-hash preconditions, transaksi per dependency unit/batch dengan ukuran yang diukur, checkpoint serta ID mapping yang tahan retry. Tidak memasukkan DDL dalam transaksi DML. Uji kegagalan di tengah batch dan restore.
7. **Review hasil + izin eksekusi produksi tersendiri:** operator/user menyetujui manifest final, counts, backup, restore evidence, scope maintenance dan protected fingerprints; tidak ada go-live otomatis dari status plan/code selesai.

### 10.2 Kontrak apply yang harus diwujudkan kemudian

- Tidak ada `REPLACE INTO`, blind upsert by old ID, TRUNCATE, blanket delete/update berdasarkan tahun, drop master, disable-FK untuk memaksa data, atau panggilan cleaner/seeder existing.
- Insert memakai target-generated IDs; gunakan source fingerprint + mapping revision sebagai idempotency/provenance key dalam journal yang disetujui. Penyimpanan journal harus durable dan atomic terhadap batch; bila memerlukan schema baru, minta persetujuan schema tersendiri, jangan mengada-ada tabel di Gate 1.
- INSERT_CANDIDATE, UPDATE_CANDIDATE, REVIEW_DELETE dan QUARANTINE tidak pernah menjadi instruksi apply. Perubahan existing hanya dapat terjadi dalam tugas eksekusi terpisah jika exact operation/record/field, signed HR decision, manifest revision, input/record hash, dependency clearance, backup/restore evidence dan izin user untuk environment/action semuanya cocok. Mismatch/missing approval/out-of-scope dependency menghentikan unit tanpa partial write. Validasi invariant dan jumlah affected rows sebelum commit.
- Serialize pekerjaan rekonsiliasi; writer lain pada affected scope dihentikan saat apply yang disetujui, dan recheck di dalam transaksi. Lock acquisition konsisten dan retry bounded pada deadlock. Jangan memakai `SKIP LOCKED` yang melewatkan row dalam reconciliation. Master yang tidak punya unique business key memerlukan serialization, bukan check-then-insert bebas race.
- Recovery tidak memakai nama/timestamp/PK range sebagai identitas. Dry-run kedua atas input/hash sama harus menghasilkan report deterministik. Apply kedua tidak menambah atau menimpa record yang sudah benar; perubahan approval/source menghasilkan run/review baru.
- Semua protected data diperiksa sebelum/sesudah: users/account byte-preserving fingerprint di sistem privat, assignments/approval/config, non-2025 transactions, shared master content dan unrelated modules. Repository/report tidak menampilkan credential values atau per-account hashes.

### 10.3 Rollback yang dapat direview

| Skenario | Rencana pemulihan |
| --- | --- |
| Gagal sebelum batch commit | Roll back seluruh dependency unit; jangan checkpoint berhasil; tidak ada external side effect. |
| Insert sudah committed | Journal mencatat exact new IDs + relationships; reverse hanya row run ini setelah dependency check. Tidak membersihkan dengan tahun/prefix timestamp. |
| Update/relink sudah committed | Restore exact before payload, status/lock, actor dan reference dari journal, hanya jika current after-hash cocok; conflict baru berhenti untuk review. |
| Delete approved sudah committed | Restore parent dengan PK asli sebelum child/relink yang tergantung, termasuk pivot/approved history/file backup. Tidak mengembalikan surrogate ID yang berbeda. |
| DDL/schema mismatch | Hentikan run; DDL bukan rollback DML. Tidak menjalankan migration down/up untuk recovery otomatis. |
| Kerusakan luas / journal tak cukup | DBA melakukan restore backup teruji ke instance terpisah, rekonsiliasi post-run legitimate writes, kemudian cutover dengan izin user. Full DB restore langsung ke live dapat menghilangkan perubahan modul lain dan bukan langkah otomatis. |
| PR 1/PR 3 kode | Revert hanya patch PR yang telah diisolasi, preserve dirty work; permission/data recovery dievaluasi terpisah. Tidak reset repository atau mengubah assignment agar UI lama cocok. |

Backup kelak mencakup schema, affected parents/children, referensi shared/protected dan file yang benar-benar berubah, ditambah baseline counts/hash dan journal. Simpan terenkripsi/private, verifikasi checksum dan restore drill pada database terisolasi. RPO/RTO, retensi, operator dan backup location **PENDING**; belum ada backup/restoration yang dilakukan dalam audit ini.

## 11. Acceptance tests yang harus dilaksanakan setelah diotorisasi

Semua butir di bawah **PLANNED / NOT RUN**, kecuali pemeriksaan offline pada §2.3. Tidak menjalankan PHPUnit/Artisan/build saat Gate 1 karena dapat bootstrap aplikasi/DB dan bukan diperlukan untuk perubahan dokumen.

| Area | Skenario dan hasil yang diterima |
| --- | --- |
| Q13–Q15 / approval boundary | Scope Q13, business-year USER_ATTESTATION, suspicion Q14 dan no-replacement Q15 tercatat sebagai USER-CONFIRMED, bukan VERIFIED STATIC proof keaslian atau data approval. Counter tetap **0 INSERT_APPROVED / 0 UPDATE_APPROVED / 0 DELETE_APPROVED**; code approval bukan staging/production permission. Tidak ada candidate mendapat write karena keputusan bisnis saja. |
| Parser | Synthetic literals: quote/comma/semicolon/newline/NULL/ZEROFILL, multi-INSERT, unsupported expressions rejected; counts fixture konsisten. Input SQL tidak pernah dieksekusi. |
| Identity | Unique nonzero NPK map lintas ID; duplicate/null/zero/unmatched ditolak. Pertahankan leading zero; jangan name-only match. 281 same-PK assessment tidak overwrite target. |
| Periode / B1 closure | Exact immutable cohort 281 menerima `business_year=2025`, `tahun_penilaian=2025`, `period_evidence_type=USER_ATTESTATION` meskipun literal created_at 10/2025 dan 271/2026; seluruh timestamps tetap sama. Attestation bukan independent audit-year proof; technical failures tetap QUARANTINE. Source/hash/cohort berbeda menghentikan material changes, tidak mewarisi attestation. Target 7 assessment 2026/18 training 2027/pivot 2027 unchanged. |
| Master/shape | TC/SK/AD IDs dipetakan terpisah; ambiguous position/category/master ditolak; missing source master dicatat; multi-competency row tidak kehilangan komponen. Shared description/standard tidak berubah. |
| No replacement / B2 closure | Source 0 + Q15=C menghasilkan **0 planned legacy training inserts**, bukan permintaan sumber baru. Tidak ada Faker/demo/importer dummy/assessment gap/guessed logs menjadi sejarah training. Source absence/suspicion Q14 bukan delete permission; setelah exact obsolete records kelak delete-approved, periode yang kosong tetap kosong tanpa synthetic replacement. |
| Link/provenance | Baseline partisi T25-A/B/C/D = **490/433/10/1**, total 934; 490 missing links melibatkan 317 distinct IDs. Dua belas links numerik ke assessment 2026 tidak direlink/diubah otomatis dan protected parents tetap unchanged. Semua cohort dipertahankan tanpa approval; coherent link T25-D tidak otomatis autentik. Hypothesis cleanup migration memerlukan bukti, bukan test yang mengasumsikan asal. |
| Manifest | Template memuat opaque identities, source/target locators, source/target/cohort hashes, business-year USER_ATTESTATION, reason evidence, before/after hashes, inbound/outbound dependency closure termasuk cross-year, HR/DBA reviewer/approval dan backup/restore/rollback evidence. Tanpa signed exact-record decision dan izin eksekusi terpisah → no write. Candidate tidak dieksekusi; protected child/shared consumer memblokir parent action. Actual manifest/PII hanya di approved restricted location di luar tracked repo pada mission berikutnya yang diotorisasi; tugas ini tidak mempopulasinya. |
| Integrity/retry | Same input menghasilkan report identik; repeat apply idempotent; failure/retry tidak meninggalkan child tanpa parent atau master duplikat. True concurrent attempts diuji setelah sandbox tersedia; serial replay bukan concurrency evidence. |
| Historical state | Score/component/total/null, actor/history, approved status dan lock sesuai mapping; tidak menjalankan transisi approval/synthetic history untuk import. |
| PR 1 manage | Semua identity manage existing tetap dapat list/create/update/toggle/deactivate mapping, WE CRUD/import/template; transaction/audit/overlap checks existing tetap berjalan. Tidak ada privilege baru pada Master Job Position. |
| PR 1 reader | Sec/Dept/Div Head melihat direct + indirect descendants; actor/target multiple active positions; future/expired/inactive assignment dan inactive master tidak membuka akses. Bawahan tidak langsung pada cabang sah terlihat, sibling/ancestor/self/out-of-scope mapping tidak bocor. |
| PR 1 IDOR | Guest/staff/head-forged query/`position_id`/`user_id`/route-bound WE ID diuji. Semua mutation, termasuk HTTP method spoof/import direct call, ditolak bagi head; DB state, assignment audit dan upload storage unchanged. List count/options dan AJAX tidak bocor. |
| PR 1 detail | Working Experience hanya untuk employee eligible; query name search tidak membypass OR scope; empty/history lintas tahun ditampilkan sesuai kontrak; stale responses tidak tertukar. |
| PR 3 | Informasi competency yang benar untuk TC/SK/AD tersedia di create/edit/dept/div/view/history; missing description dijelaskan; label sama/ID berbeda tidak digabung. Rubric 1–4 existing tetap tampil. |
| PR 3 security | Markup berbahaya pada description tampil sebagai teks; metadata/score endpoint menolak position/year di luar izin; root JSON contract caller existing dipertahankan. |
| PR 3 score/approval | Membuka/tutup panel, AJAX error dan mengganti posisi tidak menyimpan/mengubah nilai/status/lock. Submission payload names/IDs/CSRF/method, perhitungan dan jalur Sales/non-Sales existing sama. |
| Accessibility | Keyboard, screen reader, accessible names/expanded state, focus/return focus, labels, empty/error/loading, kontras, 320px/400% dan close/Escape diuji manual; automated checker saja tidak cukup. |
| Consumers/rollback | Reconcile dashboard competency/TCPD, training proposal/approval/follow-up/history/export dengan periode 2025; protected fingerprints sama; rollback drill mengembalikan exact baseline affected rows/files dan tidak menimpa legitimate unrelated writes. |

Existing tests yang relevan: `tests/Unit/HRRoleAccessServiceTest.php`, `HRMenuAccessGroupTest.php`, `HRMenuServiceTest.php`, `JobPositionAccessServiceTest.php`, `CompetencyTrainingDataImportServiceTest.php`, `WorkingExperienceValidationServiceTest.php`, serta `tests/Feature/WorkingExperienceImportUploadTest.php`, `TrainingHistoryEndpointTest.php` dan tests unit training. Coverage lama tidak mencukupi: access tests menggunakan fake scope/role; import test hanya 3 kasus parser/position, bukan migrasi NPK/periode/cleansing penuh. Perlu feature authorization matrix dan test manifest/rollback baru dengan fixture sintetis.

Workflow kelak: mulai test file relevan; kemudian scoped PHP lint/Pint, route contract, Blade compile bila diperlukan, dan suite affected HR. PHPUnit memaksa MySQL `fastware_adsi_1_testing`; verifikasi sandbox/connection tanpa menampilkan secret sebelum test, jalankan DB suites serial. `npm.cmd run build` hanya bila perubahan menyentuh aset Vite; halaman HR ini memakai legacy inline/static assets, sehingga build saja tidak memverifikasi JavaScript-nya. Browser QA wajib pada sandbox. Seluruh command eksekusi ini belum diotorisasi/dijalankan pada Gate 1.

## 12. File-impact list untuk Gate 2, bukan daftar perubahan saat ini

| Mission/PR | File konkret | Perubahan yang diusulkan / batas |
| --- | --- | --- |
| PR 1 | `app/Http/Controllers/UserJobPositionController.php` | Pisahkan view/manage guard, scope list/options/by-user/WE, explicit safe serialization. Preserve mutation behavior existing. |
| PR 1 | `app/Services/HR/JobPositionAccessService.php` | Metode khusus scope Mapping Karyawan/descendant closure; jangan mengganti semantik competency/training global. |
| PR 1 inspect/preserve | `app/Services/HR/HRRoleAccessService.php`, `app/Enums/HRMenuAccessGroup.php`, `app/Models/UserJobPosition.php`, `MstJobPosition.php`, `MstPositionApproval.php` | Sumber eligibility/manage/activeAt/graph; perubahan hanya bila benar-benar diperlukan oleh scoped helper, tanpa mengganti full access/allowlist/hierarchy. |
| PR 1 | `resources/views/user_job_position/index.blade.php`, `resources/views/layout.blade.php` | Read-only reader, menu Mapping terpisah dari Master Job Position, manager controls tetap. |
| PR 1 review only | `StoreWorkingExperienceRequest.php`, `UpdateWorkingExperienceRequest.php`, `ImportWorkingExperienceRequest.php` di `app/Http/Requests/` | Pastikan mutation authorization sebelum validasi sensitif; patch authorize bila perlu, tanpa mengganti rule atau fallback import lama yang tidak diminta. |
| PR 1 preserve | `app/Services/KnowledgeManagement/KmOrganizationAssignmentService.php`, `app/Imports/WorkingExperienceImport.php` | Preserve atomicity/audit/import behavior, bukan refactor atau migrasi assignment. |
| PR 2 | `app/Services/HR/CompetencyTrainingDataImportService.php`, `app/Console/Commands/ImportCompetencyTrainingDataCommand.php` | Jalur reconciliation 2025/no-dummy/approved-manifest yang terpisah dari fixture, NPK/period/status mapping, report/idempotency/guard; bentuk patch minimal ditetapkan Gate 2. Tidak menjalankan tool existing. |
| PR 2 review only | `app/Models/TrsPenilaianTc.php`, `DetailTcPenilaian.php`, `TcPeopleDevelopment.php`; migrations yang disebut §4 | Kontrak relasi/lock/constraint; bukan izin mengubah model/schema atau menjalankan cleaner. |
| PR 3 | `app/Http/Controllers/PenilaianTCController.php`, `app/Http/Controllers/TcController.php` | Metadata/score read scope dan informasi yang diperlukan; jangan mengubah write/approval flow. |
| PR 3 | `resources/views/tc_penilaian/sc_penilaian.blade.php`, `edit_penilaian.blade.php`, `dept_penilaian.blade.php`, `view_penilaian.blade.php`, `privew_penilaian.blade.php` | Contextual info/escaping/accessibility. Reuse partial scoped baru hanya bila diperlukan. |
| Validation | Existing HR tests di §11; proposed `tests/Feature/HR/EmployeeMappingAuthorizationTest.php`, `tests/Feature/HR/CompetencyInformationTest.php`, `tests/Unit/HR/Competency2025ReconciliationPlanTest.php` | Nama test baru usulan; belum dibuat. Cover behavior boundaries, bukan mirror implementation. |
| Routes | `routes/web.php`, `routes/api.php` | Inspected. Existing routes cukup; tidak perlu route baru berdasarkan bukti sekarang. Preserve dirty hunk dan nama/method. |
| Consumer review | `app/Services/HR/TrainingDevelopmentService.php`, `TrainingHistoryQueryService.php`, `TrainingExportQueryService.php`, `TrainingParticipantService.php`, `app/Services/Competency/CompetencyAssessmentService.php`, `app/Services/Dashboard/CompetencyDashboardService.php`, `TcpdDashboardService.php`, `app/Http/Controllers/PdController.php`, `TrainingExportController.php` | Regression/impact inspection, bukan automatic code-change list. |
| Dokumentasi | **`docs/hr/GATE-1-HR-REVISION-PR1-PR3-PLAN.md`** | **Satu-satunya file repository diedit pada M1 final Q13–Q15 ini; canonical plan diperbarui in place, bukan membuat Gate 1 kedua.** |

Tidak mengubah `deploy-hr`, `export_tcpd_modules`, backup/dates/Copy/(1) source, seeders, SQL dumps, `.env`, schema/credentials, Sales/approval structure, Warehouse/Outstanding Material, global assets atau dependency manifests. New migration/index/provenance table bukan bagian yang sudah disetujui.

## 13. Mission sequencing dan gate keputusan

| Urut / jalur | Mission terfokus | Depends on / kondisi Gate 2 | Evidence status saat revisi |
| --- | --- | --- | --- |
| M1 — documentation closure, tugas ini saja | Catat Q13=A/Q14=C/Q15=C, attested 281/year 2025, B1/B2 closed terbatas, 0 planned source training inserts dan 0 data approvals; preserve bukti Q13, actual baseline/input hash dan dirty worktree. | Instruksi/plan/addendum diperiksa; tidak ada material input drift. | **USER-CONFIRMED decisions + VERIFIED STATIC document/input checks**. Hanya canonical Markdown berubah; berhenti setelah M1. |
| M2 — PR 2 read-only per-record reconciliation planning | Rancang pemeriksaan 281 source assessment year accepted yang masih QUARANTINE sampai technical checks; 320 target assessment; 934 training T25-A/B/C/D; minimum master/identity/natural-key/lineage exceptions. | Mission lanjutan tersendiri setelah M1. Dapat direncanakan dari snapshot tanpa DB; tanpa populasi real employee manifest di repo. B1/B2 tidak diinterview ulang. | **PROPOSED / NOT STARTED** pada tugas ini. Tidak ada SQL, actual candidate ledger atau data write di M1. |
| M3 — HR/DBA candidate evidence review | HR/DBA mereview per-record provenance, mapping B3–B8, inbound/outbound/cross-year/shared dependencies, before/after dan exact actions; private signed manifest/backup/reversal prerequisites B9. | M2 dan otorisasi akses data/lokasi restricted yang sesuai. Suspicion Q14 bukan proof; Q15 tidak memerlukan source pengganti. | **PENDING HR/DBA / NOT STARTED**. Record approval kini nol; review tidak memberi izin code/staging/production. |
| M4 — PR 1 independent readiness / code gate | Validasi graph B6 dan active multi-position subordinate scope, pisahkan HR/Admin full manage dari head read-only Mapping/Working Experience, audit backend list/detail/AJAX/CRUD/IDOR. | M1 + HR Organization/Authorization Owner mengesahkan graph; setelah itu **explicit Gate 2 code approval untuk PR 1**. Independen dari clearance data PR 2 M2/M3. | **PENDING HR/DBA** graph; code/tests/QA **NOT RUN / NOT AUTHORIZED**. Tidak mengubah graph atau Sales. |
| M5 — PR 3 independent readiness / code gate | Review metadata/read-authorization/escaping dan accessible contextual info dengan existing description + empty fallback dan rubric 1–4; preserve score/approval/locks. | M1 + security/UX readiness; kemudian **explicit Gate 2 code approval untuk PR 3**. Tidak menunggu M2/M3 atau recovery description B7. | Desain statis dipertahankan; code/browser QA **NOT RUN / NOT AUTHORIZED**. |
| M6 — PR 2 safe tooling / dry-run / staging gates | Setelah izin yang sesuai, implementasi no-dummy NPK-based idempotent tooling; read-only DB dry-run; kemudian disposable staging write dan backup/restore rehearsal. Training source inserts tetap 0. | M2/M3, B8/B9 dan **otorisasi terpisah** untuk code, DB read-only dry-run, serta staging write/rehearsal. Candidates/unresolved tidak otomatis applied. | **NOT RUN / NOT AUTHORIZED**. Code approval ≠ staging write ≠ production approval; hasil deterministic/protected fingerprints/rollback harus dibuktikan kelak. |
| M7 — PR 2 exact approved execution, bila ada | Final go/no-go hanya untuk exact record actions approved dengan hash/preconditions, dependencies, verified backup/restore evidence dan post-run reconciliation. Periode training dapat kosong setelah deletion yang sah; tidak membuat pengganti. | M6 + HR/DBA signed manifest + **explicit user production/action authorization**. Drift membatalkan approval yang terpengaruh. | **PENDING HR/DBA + USER / NOT STARTED**; tidak ada deployment atau data action pada M1. |

Urutan yang diusulkan: **M1 dokumentasi → M2 read-only reconciliation planning → M3 HR/DBA evidence review**; sesudahnya code/tooling/staging/execution hanya lewat explicit authorizations masing-masing. PR 1 **M4** dan PR 3 **M5** dapat dinilai independen setelah M1 ketika gate masing-masing siap, tanpa menunggu data clearance PR 2. Jalur data PR 2 adalah **M2 → M3 → M6 → M7**. Seluruh M2–M7 adalah usulan lanjutan, **tidak dimulai pada tugas ini**; PR 4 tetap **DEFERRED**.

### 13.1 Checklist final business decisions dan boundary M1

- [x] Q13=A, Q14=C, Q15=C dan business-year attestation seluruh 281 source assessment dicatat sebagai USER-CONFIRMED, bukan independent audit proof atau approval write; actual checkout/hash input diperiksa ulang tanpa drift.
- [x] B1 **CLOSED FOR BUSINESS-YEAR DECISION** hanya untuk immutable 281-row cohort; tahun 2025 accepted dengan USER_ATTESTATION, timestamps 10/2025 dan 271/2026 tetap literal. B2 **CLOSED AS NO REPLACEMENT SOURCE** dengan rencana 0 legacy training inserts; tidak meminta ulang tahun atau sumber pengganti.
- [x] Finding wajib, cohort 490/433/10/1, 12 cross-year links dan protected 2026/2027 dipertahankan; approval data tetap nol.
- [x] Desain/template manifest membedakan candidate/approval, memuat immutable hashes/locators, opaque employee, business-year USER_ATTESTATION, reason evidence, dependency closure, before/after, HR/DBA decision dan restore evidence; tidak mempopulasi actual manifest/PII.
- [x] Register B1–B9 lengkap dengan owner, B1/B2 ditutup terbatas dan B3–B9 tetap actionable; Q14 suspicion tidak berubah menjadi proof. PR 1/PR 3 tetap independen, PR 4 deferred; file impact/audit teknis yang benar dipertahankan.
- [ ] Mission M2 read-only planning dan M3 HR/DBA candidate review dikerjakan sebagai tugas terpisah; technical identity/master/category/score/status/natural-key checks dan B3–B9 sesuai action diselesaikan atau record tetap QUARANTINE. Graph B6 sebelum PR 1 code, security/UX sebelum PR 3 code.
- [ ] User memberi Gate 2 **code approval spesifik per PR**; saat ini belum ada.
- [ ] Private exact-record approval, environment authorization, verified backup dan rollback/restore rehearsal tersedia sebelum data action; saat ini seluruhnya pending/not run.

## 14. Verifikasi Gate 1 dan kondisi berhenti

**STATIC VERIFIED — audit awal yang digunakan ulang:** instruksi/skill, route/controller/service/model/view/test/migration trace, checksum, literal parsing seluruh dump dan 28 offline assertions sebagaimana §2.3. Bukti tersebut bukan test suite aplikasi dan bukan verifikasi live.

**VERIFIED STATIC — revisi final Q13–Q15:** `AGENTS.md` dibaca ulang dan fingerprint instruksi yang sudah dibaca diperiksa; actual branch/HEAD/origin/status cocok dengan baseline Q13. Addendum Q15 dibaca penuh dan dibandingkan dengan canonical plan; kedua dump diperiksa ukuran/SHA-256 dan cocok. Bukti teknis/counts Q13 digunakan ulang tanpa large parser/audit atau actual record-ledger population. Pemeriksaan teks/before-after diff meninjau seluruh occurrence B1/B2, period eligibility, training source, PENDING, 2025, approval counters dan final status; fingerprint instruksi/core HR dan 30 dirty files lainnya menjaga scope perubahan. Ini pemeriksaan dokumen, bukan passing application tests.

**USER-CONFIRMED / CLOSED LIMITED:** Q13=A, business-year 2025 seluruh exact 281 source assessment dengan USER_ATTESTATION (B1), suspicion terhadap seluruh 934 training (Q14=C), serta no replacement source/0 planned legacy training inserts (Q15=C/B2). Tidak menunggu interview atau independent year proof untuk B1, atau sumber baru untuk B2. Literal timestamps dan technical safeguards tidak berubah.

**PENDING HR/DBA:** B3 dummy/irrelevance proof, B4 bad-link lineage, B5 score/status, B6 hierarchy/crosswalk, B7 master-description governance, B8 runtime schema dan B9 private manifest/backup/rollback/exact approvals. Technical eligibility source tetap pending dan bukan B1 business-year ambiguity. Gate 2 code, staging dan production/action permissions belum diberikan; **0 INSERT_APPROVED / 0 UPDATE_APPROVED / 0 DELETE_APPROVED**.

**NOT RUN:** SQL termasuk SELECT/EXPLAIN; database connection; migration/seeder/importer (termasuk mode dry-run existing); application/PHPUnit/Artisan execution; frontend build; browser login/QA; staging/production verification; backup/restore rehearsal; security exploit reproduction; commit/push/deploy. Tidak ada hasil pada area tersebut yang dinyatakan lulus. No migration replay, cleanup seeder, blanket operation, atau cascade ke protected periods.

**Exact files changed pada M1 final Q13–Q15: hanya `docs/hr/GATE-1-HR-REVISION-PR1-PR3-PLAN.md`.** Canonical plan diperbarui in place; addendum/pendamping, kedua SQL dan seluruh file/hunk lain dipertahankan. Manifest hanya schema/template, tanpa real employee row atau query executable.

Business-scope/design Gate 1 sudah diperbarui dengan keputusan final pengguna. B1/B2 closed hanya pada keputusan tahun dan ketiadaan replacement; B3–B9 serta technical checks dan keaslian target tetap pending, approval data tetap nol. Jika obsolete training kelak terbukti dan exact deletion disetujui, data periode terkait boleh kosong tanpa replacement sintetis. Dokumen ini **bukan migration-ready atau izin menjalankan M2–M7**; berhenti setelah M1, tanpa implementasi atau data writes.

GATE 1 FINAL BUSINESS SCOPE RECORDED — PR 2 DATA ACTIONS PENDING VALIDATION AND SEPARATE APPROVAL
