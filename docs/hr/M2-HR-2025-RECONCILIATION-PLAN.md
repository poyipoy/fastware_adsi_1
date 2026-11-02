# Mission 02 — HR 2025 Read-Only Reconciliation Planning

Tanggal: **16 Oktober 2026**. Repository: `poyipoy/fastware_adsi_1`.

**Scope: M2 saja.** Dokumen ini melanjutkan [Gate 1 final](GATE-1-HR-REVISION-PR1-PR3-PLAN.md), bukan menggantikannya atau membuka kembali interview Q13–Q15. Tidak ada approved data-change manifest atau executable migration script yang dibuat.

**NO SQL EXECUTED — NO DATABASE CONNECTION — NO APPLICATION CODE MODIFIED.** Tidak menjalankan Artisan, migration, seeder, importer termasuk `--dry-run`, application bootstrap, dummy generation, staging/production writes, relink, overwrite, perubahan status, commit, push, atau deploy. Satu-satunya file repository yang dibuat pada M2 adalah dokumen ini. Dokumen Gate 1 dan dirty worktree lainnya dipertahankan.

## 1. Authority, baseline dan batas bukti

### 1.1 Keputusan yang tetap final

| Keputusan | Perlakuan pada M2 |
| --- | --- |
| Q13=A | Assessment business period 2025, minimum competency masters terkait dan investigasi/rekonsiliasi target 2025; schema authority adalah target `(12)`. Tidak melakukan full HR migration. |
| B1 / USER_ATTESTATION | Seluruh **281** source assessment pada immutable input di bawah adalah business year **2025** menurut pengguna. `business_year=2025`, future `tahun_penilaian=2025`, `period_evidence_type=USER_ATTESTATION`. **CLOSED FOR BUSINESS-YEAR DECISION**, bukan independent audit-year proof. Literal timestamps tidak berubah. |
| Q14=C | Seluruh **934** training target 2025 dicurigai dummy/tidak relevan, tetapi belum terbukti. Cohort T25-A/B/C/D tetap investigation/quarantine. |
| Q15=C / B2 | **CLOSED AS NO REPLACEMENT SOURCE**. Rencana **0 legacy training inserts**. Tidak membuat training dari Faker, demo/fixture, generator importer, assessment gaps atau guessed logs. Deletion yang kelak sah boleh meninggalkan periode tanpa training; tidak dibuat replacement sintetis. |
| Approval | **0 INSERT_APPROVED / 0 UPDATE_APPROVED / 0 DELETE_APPROVED**. Tahun bisnis atau scope yang disetujui tidak mengizinkan record write, code implementation, staging atau production action. |
| PR lain | PR 1 tetap HR/Admin full manage vs heads subordinate-only read/Working Experience read-only, graph B6 pending. PR 3 existing-data-only description/rubric 1–4 tetap independen. PR 4 Sales **DEFERRED**. Tidak mengerjakan ketiganya dalam M2. |

### 1.2 Baseline diperiksa pada M2

- Branch aktual **`main`**, HEAD **`6161d682e1eeaf81c9e83acad523af7c5b688b45`**, origin `https://github.com/poyipoy/fastware_adsi_1.git`.
- Sebelum M2: **17 tracked files modified + 14 untracked files**, termasuk Gate 1. Tidak ada perubahan baseline dibanding akhir M1; remote freshness tidak diperiksa/fetch.
- `AGENTS.md` dibaca ulang; fingerprint `CLAUDE.md` dan cognitive framework yang sudah dibaca lengkap tetap sama. Pencarian instruksi menemukan hanya root `AGENTS.md`. Skill yang digunakan: boost, mysql-patterns, sql-sentinel, sql-pro, security-audit, laravel-patterns, accessibility, plan-writing, backend-security-coder; terbatas pada analisis/pelaporan, tanpa menjalankan analyzer eksternal, target code atau workflow audit security menyeluruh.
- Gate 1 final: **91,586 byte**, SHA-256 **`3beca7d78cd627785f97ef188d94dc8906328996c41924233bbb29340858ae0b`**; isinya identik dengan hasil M1 final Q15. Status M2 “not started” di Gate 1 adalah snapshot saat M1 ditutup; dokumen M2 ini mencatat pekerjaan planning yang kini dilakukan. Canonical Gate 1 tidak diedit pada tugas ini.

| Input immutable | Byte | SHA-256 |
| --- | ---: | --- |
| `dms_adasi_rev1.sql` — legacy | 2,069,117 | `e697a99f80994ff8987b0a325f6b54ed7dc0f9767a2226f617afaeb7a1e9c3d8` |
| `dms_adasi_rev1 (12).sql` — target | 4,313,503 | `996ef43a253969d992f29d917cdc021df353c6c89f8081a005b4f8787b1ea3db` |

Kedua hash dan bytes cocok dengan Gate 1 serta bytes yang sudah diparse sebelumnya. Hasil parsing literal direuse di memori, lalu dilakukan agregasi M2 yang baru; tidak mengulang audit repository besar atau menjalankan dump. Header MySQL 8.0.30 adalah metadata snapshot, bukan verifikasi engine/server live.

### 1.3 Metode, coverage dan privacy

**VERIFIED STATIC** berarti literal/schema pada dua snapshot diperiksa secara offline. **USER-CONFIRMED** berarti keputusan di §1.1. **PENDING HR/DBA** berarti mapping semantik, keaslian kejadian, lineage dan persetujuan belum terbukti. **NOT RUN** berlaku untuk aplikasi, SQL, DB, staging, restore dan runtime checks.

Coverage komputasi: **281/281 source assessments**, **320/320 target assessments 2025**, **934/934 target training 2025**, serta reference ke record protected yang relevan. Setiap source row dievaluasi di memori untuk identity, posisi, master/category, score/status dan collision diagnostics. Semua target 2025 diperiksa membership, parent/master references dan kemungkinan repeat key. Ini bukan validasi manusia atas tiap employee atau matching semantik yang sudah selesai.

Tidak menulis daftar source/target employee, NPK, user IDs, nama, credential, actual PK manifest, payload personal atau row-level hashes ke repository/tool output. Data tersebut hanya dipakai di memori untuk perhitungan dan tidak diekspor. Public report ini berisi agregat, schema/field names dan rule. Tidak ada actual private review manifest yang dipopulasi; akses/lokasi annex kelak tetap B9.

Normalisasi diagnostic: trim + collapse whitespace + uppercase untuk label; NPK berupa string digit, trim, nonblank/nonzero, unik di kedua sisi, **leading zeros tidak dibuang**. ID numerik dinormalisasi tanpa kehilangan presisi untuk membandingkan reference, bukan untuk menyimpulkan business identity. Tidak memakai fuzzy matching, alias importer existing yang belum disetujui, nama employee, role-name atau PK equality sebagai fallback.

## 2. Ringkasan coverage dan koreksi bukti Gate 1

| Entity / scope | Legacy | Target | Keputusan M2 |
| --- | ---: | ---: | --- |
| Assessment | **281**, year 2025 USER_ATTESTATION | **320** tahun 2025 | Seluruhnya dicakup planning; source masih QUARANTINE sampai checks/crosswalk/review tuntas. Target tetap disimpan sambil direview. |
| Assessment protected | Tidak ditentukan dari timestamp | **7** tahun 2026 | OUT_OF_SCOPE; tidak relink/delete/reset. |
| Training | **0** | **934** tahun 2025 | 0 source inserts; seluruh target investigasi, bukan proven dummy. |
| Training protected | — | **18** tahun 2027 | OUT_OF_SCOPE; semua `id_trs=NULL`, bukan dianggap invalid karena itu. |
| Participant pivot | Tidak ada tabel | **1**, parent training 2027 | OUT_OF_SCOPE; pertahankan parent dan pivot. |
| Detail/history | **1** | **723** | Tidak ada reliable assessment/year FK; no auto lineage/delete. |
| Source minimum referenced master definitions yang ada | **229** | Target catalogs TC/SK/AD: **207/205/203** | 229 adalah upper-bound closure sebelum pemilihan transaksi/crosswalk disetujui, bukan insert quota. |

Timestamp source tetap **10 created_at 2025 + 271 created_at 2026**; semuanya business period 2025 melalui attestation. B1 tidak dibuka kembali. Target 2025 tetap **312 status 4 + 8 status 2**, seluruh 320 locked. Legacy statuses **1:7 / 2:32 / 3:242** belum mempunyai approved status mapping.

### 2.1 Koreksi M2-E01: `total_nilai` source bukan jumlah komponen yang terbukti

Ada satu ketidaktepatan narasi pada Gate 1 §3.2: pernyataan “Legacy total sama dengan penjumlahan komponen” **tidak didukung dump**. Pemeriksaan M2 menemukan `total_nilai` **eksplisit NULL pada seluruh 281 source row**, bukan nol dan bukan kolom yang dihilangkan. INSERT source menyertakan kolom tersebut; schema `int DEFAULT NULL`. Check lama yang hanya menghitung total nonnull yang tidak sama dengan sum menghasilkan 0 karena tidak ada total nonnull yang dapat dibandingkan.

Target 2025 mempunyai **320 total nonnull**, semuanya berbeda dari penjumlahan komponen. M2 tidak menyimpulkan bahwa total adalah average, sum, nilai kategori, atau corruption. **Semantik total tetap B5.** Preserve NULL source; jangan mengisi total sintetis atau merecompute target. Koreksi ini dicatat di sini untuk HR/DBA review; canonical Gate 1 tidak diam-diam diubah dan temuan teknis lain yang masih benar digunakan ulang.

## 3. Assessment source: identity dan posisi untuk seluruh 281 row

### 3.1 NPK dan legacy/target user IDs

| Diagnostic | Jumlah / hasil |
| --- | --- |
| Source users / target users | **110 / 114**; tidak ada account write. |
| Source NPK nol / target NPK nol | **3 / 7 user rows**; tidak ada NPK nondigit nonblank. |
| Unique valid nonzero NPK match | **105 pasangan** source → target. Numeric user ID kebetulan sama pada 105 pasangan; matching tetap berdasarkan NPK unik. |
| Source assessment identity resolved | **264 row**, mencakup **47 distinct mapped employees**. Ini kandidat identity, bukan persetujuan assessment. |
| Source assessment identity unresolved | **17 row**: **4 INVALID_OR_ZERO_NPK**, **13 SOURCE_NPK_AMBIGUOUS**. Tidak ada source-user-missing atau target-unique-match-missing pada subset lain yang diperiksa. |
| Bahaya fallback numeric user ID | Seluruh **17** unresolved row juga menemukan numeric user ID pada target; menerima ID itu akan melewati ambiguity check, bukan menyelesaikannya. |
| Target assessment identity lewat source PK | Seluruh **281** assessment PK legacy ditemukan di target, tetapi **0** pasangan mempunyai employee/NPK yang sama. Tidak ada hubungan bisnis yang sah dari collision itu. |

Tidak memakai nama, email, current assignment, atau angka user ID untuk menyelesaikan 17 exception. Private review kelak harus mencatat employee evidence dan keputusan HR yang spesifik; bila belum cukup tetap QUARANTINE, tanpa membuat user, menyatukan akun, atau mengubah credentials.

### 3.2 Posisi dan waterfall yang tidak tumpang tindih

Source `tc_job_positions`: **99 rows / 46 normalized labels**; target `mst_job_positions`: **56 unique labels**. Terdapat **5** pasangan label exact-normalized; ini bukan approved semantic/historical position crosswalk. **25** source assessment mempunyai satu exact-label target-position candidate, **256** belum mempunyai exact-label candidate. Alias bahasa/singkatan/rename tidak dipaksakan.

Waterfall berikut memberi satu primary stop reason per source assessment dengan urutan identity → posisi → source master existence → target master bridge. Reason lain dapat tetap menyertai record pada private ledger kelak.

| Primary stop | Jumlah | Alasan spesifik / tindak lanjut |
| --- | ---: | --- |
| W1 — identity unresolved | **17** | 4 NPK nol + 13 NPK source ambigu; HR identity evidence, no ID/name fallback. |
| W2 — posisi belum punya exact candidate setelah identity resolved | **252** | Butuh historical alias/context B6. Tidak membuat atau mengganti active target position. |
| W3 — source master hilang pada subset identity+posisi candidate | **2** | Reference menunjuk definisi source yang tidak tersedia; tidak membuat master dari tebakan. |
| W4 — source masters ada, tetapi target master crosswalk belum terselesaikan | **10** | Candidate posisi bukan approved mapping; tidak ada contextual master match melalui metode konservatif §4. |
| Total | **281** | **17 + 252 + 2 + 10 = 281**, seluruhnya year accepted 2025 dan masih QUARANTINE. |

Terdapat **12** row dengan NPK candidate dan exact-position candidate; **13** dari 25 exact-position rows justru termasuk identity exceptions. Jadi angka 25 tidak boleh diperlakukan sebagai 25 transaksi siap import.

Issue berikut **overlap**, bukan tambahan row di luar waterfall:

| Reason tambahan | Row assessment terdampak |
| --- | ---: |
| POSITION_NO_EXACT_CANDIDATE | **256** |
| MISSING_LEGACY_MASTER | **9** |
| SOURCE_MASTER_POSITION_UNRESOLVED | **6** |
| SOURCE_MASTER_CATEGORY_UNRESOLVED | **1** |
| REFERENCE_WITH_NULL_SCORE | **12** |
| SCORE_WITHOUT_REFERENCE | **4** |
| ZERO_SCORE_REVIEW | **5** |

Master-position diagnostic menelusuri numeric `master.id_job_position` ke legacy `tc_job_positions.id`, sesuai hipotesis relasi Gate 1. Pada reference yang dapat di-resolve, label posisi master sama dengan label posisi assessment; tidak ditemukan mismatch label. Ketiadaan declared FK dan unresolved keys tetap memerlukan B6 review; ini tidak membuktikan semantic mapping ke posisi target.

## 4. Minimum crosswalk TC / Soft Skills / Additional

### 4.1 Closure source yang benar-benar direferensikan

Hitungan reference berasal dari semua nonnull `id_tc/id_sk/id_ad` pada 281 source assessment. Jenis master adalah bagian identitas; ID sama pada tabel berbeda bukan record yang sama.

| Item | TC | Soft Skills | Additional |
| --- | ---: | ---: | ---: |
| Seluruh catalog source | 193 | 144 | 75 |
| Nonnull reference occurrences | **281** | **142** | **48** |
| Distinct referenced IDs | **134** | **75** | **25** |
| Referenced definitions ditemukan | **133** | **75** | **21** |
| Distinct referenced IDs tidak ditemukan | **1** | **0** | **4** |
| Assessment occurrences dengan missing definition | **5** | **0** | **4** |
| Deskripsi nonblank di referenced definitions yang ada | **133** | **75** | **20** |
| Referenced definitions dengan unresolved legacy position key | **6** | **4** | **0** |
| Referenced definitions dengan unresolved category | **0** | **0** | **1** |
| Catalog source yang tidak direferensikan cohort | **60** | **69** | **54** |

Jumlah available minimum source definitions **133+75+21=229**; **5 distinct missing definitions** perlu ditangani sebagai exception, bukan invented rows. **183** source catalog definitions yang tidak direferensikan tidak termasuk import closure saat ini, tetapi tidak dihapus dari dump. Reference occurrences **471** bukan 471 transaksi atau master baru. Unresolved position keys pada referenced subset berbeda dari hitungan seluruh catalog Gate 1; cakupannya lebih sempit.

Satu referenced Additional mempunyai category dan standard `nilai` NULL. Jangan memilih kategori/standar default. Setelah HR menetapkan transaksi/canonical version mana yang sah, hitung ulang closure; 229 bukan daftar `INSERT_CANDIDATE` atau alasan menyalin seluruh catalog.

### 4.2 Candidate matching yang sudah diperiksa

Metode konservatif: jenis → historical position candidate → category candidate berdasarkan rubric → exact normalized label/definition context → standard/version review. Tidak menerapkan synonyms/fuzzy score. Deskripsi/standar adalah bagian business comparison, bukan field yang boleh ditimpa hanya karena label sama.

| Diagnostic referenced source masters | TC | Soft Skills | Additional |
| --- | ---: | ---: | ---: |
| Label source ditemukan pada target mana pun, tanpa scope posisi | **0** | **3** | **0** |
| Unique label-only target candidates | **0** | **3** | **0** |
| Exact target-position candidate dari legacy master position | **19** | **10** | **1** |
| Contextual match jenis+posisi+category+label | **0** | **0** | **0** |
| Contextual match ditambah standard sama | **0** | **0** | **0** |
| Source master PK yang kebetulan ada pada target | **80** | **75** | **21** |
| Pada collision PK tersebut, label source/target sama | **0** | **0** | **0** |

**Semantic master crosswalk belum selesai / PENDING HR/DBA.** Nol exact contextual match tidak membuktikan tidak ada padanan bisnis: definisi dapat di-rename atau posisi dapat mempunyai approved alias yang belum tersedia. Tiga label SK yang sama belum memenuhi konteks dan bukan accepted pair. Tidak menandai 229 master sebagai insert hanya karena exact matching gagal.

Minimum private crosswalk kelak: source snapshot + type + source master locator; legacy position context; approved target position; source/target category; definition/standard/description version; candidate target master atau alasan kebutuhan master baru; dependency set; method/evidence; reviewer decision. Insert master baru hanya dapat diusulkan bila transaksi yang memerlukannya lolos dan tidak menduplikasi business definition existing. Shared target masters tidak diubah/dihapus.

### 4.3 Category mapping: candidate berbasis rubric, belum keputusan HR

`id_poin_kategori` adalah kategori rubric, berbeda dari **jenis** TC/SK/Additional dan label `kategori_competency` training. Jangan menganggap jenis SK otomatis berarti category ID 2 atau menyamakan ketiga discriminator itu.

| Source category ID | Target category ID candidate | Bukti statis | Status |
| --- | --- | --- | --- |
| 1 | 1 | Empat rubric 1–4 sama setelah normalisasi; unique rubric signature di target. | CANDIDATE / B6 pending. |
| 2 | 2 | Sama, unique rubric signature. | CANDIDATE / B6 pending. |
| 3 | 3 | Sama, unique rubric signature. | CANDIDATE / B6 pending. |

Pasangan ini ditemukan dari **konten empat rubric**, bukan asumsi ID stabil. Ketiga category labels berbeda antar dump; semantic rename/pemakaian tetap perlu disahkan. Coverage category candidate pada available referenced masters: TC **133/133**, SK **75/75**, Additional **20/21**. Tidak menulis category/rubric, memulihkan description, atau menganggap candidate sebagai approval.

Target descriptions nonblank tetap TC **7/207**, SK **5/205**, Additional **3/203**. Pemulihan legacy descriptions adalah B7, terpisah dari matching dan UI PR 3; tidak copy by ID/name atau menimpa shared consumers.

## 5. Score, status, actor dan source-key repetition

### 5.1 Nilai source dan target tidak boleh dibandingkan sebelum business identity match

| Component | Source score 0 | Source 1 | Source 2 | Source 3 | Source 4 | Source NULL |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| TC | **5** | 27 | 80 | 120 | 45 | **4** |
| SK | 0 | 10 | 82 | 44 | 4 | **141** |
| Additional | 0 | 22 | 12 | 9 | 0 | **238** |

NULL SK/AD dapat wajar bila reference kategori tidak ada, tetapi ditemukan **4 TC / 3 SK / 8 AD** occurrences dengan reference terisi dan score NULL; union **12 source rows**. Ada **1 SK / 3 AD** score terisi tanpa master reference; union **4 rows**. Semua 5 nilai nol berada pada TC. Tidak ditemukan malformed numeric score atau nilai di luar 0–4; ini bukan alasan menafsirkan nol sebagai valid/invalid tanpa HR.

Seluruh target 2025 mempunyai TC+SK+AD reference dan score komponen 1–4; tidak ada missing user/position/master pada 320 row. Bentuk source: **139 TC saja, 94 TC+SK, 48 TC+SK+AD**. Source SK/AD nullable dan target row packing berbeda; jangan memecah, menggabungkan, mengisi nol, atau menyalin total hanya agar skema cocok. `total_nilai` mengikuti koreksi §2.1.

### 5.2 Status dan identity modifier

- Source statuses **1:7 / 2:32 / 3:242**; target 2025 **2:8 / 4:312**, locked **320**. Mapping ordinal, offset status atau menjadikan semua final tidak dibenarkan. B5 harus menentukan makna dan historical lock yang tepat.
- Source numeric `modified_at` dapat memperoleh NPK actor candidate pada **271** row; **10** row actor masih ambigu/unmatched dengan aturan ketat. Actor identity candidate bukan bukti siapa yang benar-benar approve, kapan, atau level approval-nya.
- `modified_updated` dan history dapat mengandung nama/free text. Simpan provenance secara privat, jangan mengubah string itu menjadi timestamp/approver ID berdasarkan tebakan. Jangan mengambil approver historis dari current Sales/reporting graph.

### 5.3 Repeated source keys: bukan izin membuang sejarah

Perbandingan berikut menggunakan source identity/position/reference yang ada sebagai diagnostic, seluruhnya dalam business year 2025 yang di-attest. Key harus diuji ulang setelah approved mapping; alias/merge dapat menambah collision.

| Diagnostic key | Grup berulang | Row dalam grup | Keterangan |
| --- | ---: | ---: | --- |
| User + posisi + seluruh tuple TC/SK/AD | **10** | **32** | 4 grup berisi 2 row, 6 grup berisi 4 row; **5 grup** berbeda score atau status. |
| Payload semua kolom source kecuali `id`, `created_at`, `updated_at` | **6** | **23** | 1 grup berisi 3 row, 5 grup berisi 4 row; repetition payload tidak membuktikan satu kejadian historis yang sama. |
| User + posisi + tahun + TC ID | **16** | **44** | **10** grup berbeda TC score; **6** berbeda status. |
| User + posisi + tahun + SK ID | **12** | **32** | **3** grup berbeda SK score; **5** berbeda status. |
| User + posisi + tahun + AD ID | **0** | **0** | Tidak ditemukan repeated component key pada source. |

Union source rows yang berada dalam repeated TC/SK/AD component key adalah **44**, bukan penjumlahan 44+32. Semua 32 rows pada full-reference-tuple groups memiliki NPK candidate yang unik; jadi masalah repetition tidak otomatis selesai dengan NPK matching.

Target mempunyai tiga unique component indexes per user/position/year. Memaksakan satu row per source key secara mekanis akan menghilangkan **28 TC / 20 SK excess occurrences**, tetapi angka ini **bukan delete count atau rekomendasi konsolidasi**. HR/DBA harus menentukan apakah ini revision, repeat assessment, redundant copy, atau variasi row packing dan bagaimana historical evidence dipertahankan dalam kontrak target. Tidak memilih MIN/MAX ID, latest timestamp, atau status terbesar sebagai survivor. Dump source tetap immutable.

## 6. Rancangan pembandingan 281 source dengan 320 target 2025

### 6.1 Apa yang dapat dibandingkan sekarang

| Coverage diagnostic | Source / target |
| --- | --- |
| Source distinct employee references | **49** raw refs; **47** dapat dipetakan melalui NPK unik. |
| Target 2025 distinct employees | **80**. |
| Mapped source employees mempunyai target assessment 2025 | **37 employees / 196 source rows**. |
| Mapped source employees tanpa target assessment 2025 | **10 employees / 68 source rows**. Tidak otomatis INSERT_CANDIDATE. |
| Target rows dengan employee termasuk resolvable source cohort | **148**, pada 37 employees. |
| Target rows dengan employee di luar resolvable source cohort | **172**, pada 43 employees. Source absence bukan alasan delete. |
| Source mempunyai NPK + exact-position candidate | **12 rows**, hanya **2 distinct employee-position pairs**. |
| Target assessment pada dua pair itu | **8 rows**; tiap source row menemukan **4** target rows pada pair-nya. Ini group candidate, bukan one-to-one match. |
| Established full source-target assessment business identity | **0** melalui conservative crosswalk saat ini; master/status mapping belum tuntas. |

Tidak ditemukan duplicate group pada masing-masing tiga unique component keys dalam 320 target rows. Ini menjelaskan constraint snapshot, bukan bukti bahwa semua target genuine atau tidak ada historical duplication dengan bentuk lain. Seluruh target 2025 tetap dipertahankan untuk review B3.

### 6.2 Urutan comparison yang harus diwujudkan pada review berikutnya

1. Bind immutable snapshot + source locator ke attested cohort 281; year accepted 2025 dengan literal timestamps dipertahankan.
2. Resolve employee memakai NPK unik; unresolved tetap exception. Resolve historical position secara eksplisit tanpa menulis active mapping.
3. Resolve tiap nonnull competency reference berdasarkan jenis dan approved master/category crosswalk; incomplete source definition/score/reference tidak diisi secara sintetis.
4. Bentuk component identity `(mapped employee, mapped position, business year, type, mapped competency master)` dan periksa seluruh component unique constraints. Pertahankan row grouping/provenance source agar tidak kehilangan audit saat membandingkan dengan target compound rows.
5. Cocokkan source dan target berdasarkan identity yang tervalidasi, lalu bandingkan score/null/total, status, lock, actor dan history. Perbedaan value baru bermakna sesudah identity terbukti.
6. **RETAIN** hanya jika existing business record dan dependensinya terbukti benar. **INSERT_CANDIDATE** hanya jika source lolos dan identity tidak ada di target. **UPDATE_CANDIDATE** butuh conflict yang terbukti plus exact field-level after proposal. **REVIEW_DELETE** butuh bukti redundansi/irrelevance, survivor/lineage dan dependency closure. Unresolved tetap **QUARANTINE**.
7. Tidak ada record mutation dari classification. Keputusan HR/DBA pada exact manifest, backup/restore evidence dan izin user untuk code/environment/action tetap terpisah.

**Bagian yang belum selesai:** semantic position/master/category mapping, penetapan canonical/repeated assessment representation, status/score/actor mapping dan validated source-target record pairs. Offline M2 telah mengukur gap dan mengelompokkan semua row; tidak mengklaim bahwa crosswalk tersebut sudah terverifikasi atau bahwa 281 akan seluruhnya diinsert.

## 7. Evaluasi seluruh 934 target training 2025

### 7.1 Cohort dan kategori

Setiap target training 2025 memiliki tepat satu selected competency reference. Semua employee, position dan selected master ditemukan pada target; master-position reference juga konsisten secara numerik. Ini tidak membuktikan autentisitas event atau kebenaran assessment link.

| Cohort | Total | TC / technical | SK / softskill | Additional | Hasil / status |
| --- | ---: | ---: | ---: | ---: | --- |
| T25-A | **490** | 166 | 162 | 162 | Parent assessment ID hilang, **317 distinct missing IDs**; QUARANTINE. |
| T25-B | **433** | 132 | 158 | 143 | Parent ID ditemukan, employee berbeda; QUARANTINE. |
| T25-C | **10** | 1 | 5 | 4 | Employee/posisi/tahun cocok, selected competency master berbeda; QUARANTINE. |
| T25-D | **1** | 0 | 1 | 0 | Link employee/posisi/tahun/selected master koheren, event provenance belum terbukti; QUARANTINE. |
| Total | **934** | **299** | **326** | **309** | **0 DELETE_APPROVED**; bukan 933/934 junk yang terbukti. |

- Seluruh **12** training-2025 → assessment-2026 links berada di **T25-B**, mengenai **7 distinct protected assessment parents**, yakni seluruh 7 assessment 2026 pada snapshot. Jangan menyentuh parent, child atau protected history melalui cascade/relink.
- **416** position mismatches pada parent yang ditemukan juga berada di T25-B; angka ini overlap, bukan tambahan cohort.
- Pada T25-A, **0** missing numeric parent IDs ditemukan pada 281 supplied legacy assessment IDs. Jadi numeric reattachment ke input legacy ini juga tidak tersedia; jangan menyimpulkan penyebab orphan atau absence event dari fakta itu.
- Semua 934 berstatus pengajuan 3 / progress Done, created tanggal 2026-07-07; tidak ada recognized dummy marker pada audit Gate 1. Timestamp/status seragam, source kosong dan adanya dummy generators tidak menetapkan provenance.
- Q15 tetap rencana **0 source training inserts**. Tidak menjadikan assessment gap sebagai training event pengganti; approved deletion kelak boleh meninggalkan nol training.

### 7.2 Kemungkinan repeat training: diagnostic, belum duplicate proof

| Diagnostic grouping | Grup | Row | Arti |
| --- | ---: | ---: | --- |
| Employee + year + category + selected master + program, tanpa due date | **193** | **386** | Key terlalu lebar untuk event identity; repeat training/program dapat sah. |
| Employee + position + section + year + category + selected master + program + due date | **2** | **4** | Perlu review; bukan deletion set. |
| Seluruh business payload kecuali `id`, `created_at`, `updated_at` | **0** | **0** | Tidak ditemukan exact full-business-payload duplicates di 934 rows. |

Dua grup lebih ketat tersebut berbeda pada assessment link, vendor/provider dan budget fields; tidak ada pasangan identik pada seluruh payload. Satu grup berada di T25-A, satu grup melintasi T25-A/B. Karena tidak ada immutable event identity/HR corroboration, tetap **POSSIBLE_REPEAT_EVENT / QUARANTINE**, bukan auto REVIEW_DELETE atau survivor selection. Tidak menerbitkan IDs/detail employee atau vendor/budget values.

## 8. Dependency impact dan data protected

| Relation / impact | Bukti offline | Guard yang diperlukan |
| --- | --- | --- |
| Assessment 2025 → training | **432** training references ke **267** dari 320 parents. Parents dengan 0/1/2/3/4 child: **53/132/106/28/1**. | 53 parent tanpa training bukan bukti dummy; count/PK-set/period seluruh children wajib masuk private review. |
| Assessment 2025 → training non-2025 | **0** references pada snapshot. | Bukan jaminan live; drift/preflight ulang sebelum apply yang diotorisasi. |
| Training 2025 → assessment 2026 | **12 references / 7 parents**, seluruhnya T25-B. | Protected cross-year closure; no parent change atau automatic relink. |
| Training → participant pivot | **0** pivot pada 2025, **1** pivot pada training 2027. Declared FK mempunyai delete cascade. | Jangan bergantung cascade sebagai cleaner; 2027 parent/pivot tetap OUT_OF_SCOPE. |
| Assessment/master/training relationships | Tidak ada declared FK untuk assessment→master/person/position atau training→assessment/master; logical references tetap nyata. `id_trs` int vs parent bigint unsigned. | Ketiadaan FK bukan izin delete/relink dan bukan alasan menambah constraint dalam M2. |
| History/detail | **723** rows tanpa direct assessment FK/user/year. Posisi berasosiasi dengan assessment 2025 saja: **617**, 2026 saja: **10**, tidak keduanya: **96**; overlap both: **0** pada snapshot ini. | Position association bukan event/period lineage; semua history dipertahankan. Source detail 1 tidak otomatis ikut attestation assessment 281. |
| Shared master across years | TC **3**, SK **2**, Additional **4** distinct masters direferensikan 2025 dan assessment 2026. | Protect seluruh shared target definitions/description/standard; jangan overwrite untuk kebutuhan 2025. |
| Master reference closure target | Distinct master IDs dipakai assessment/training 2025: **171 TC / 174 SK / 171 AD**. Non-2025: **7 TC / 6 SK / 5 AD**. | Reference per jenis, tidak dijumlah sebagai row assessment. Tidak drop master yang tampak unused hanya dari subset ini. |
| Position / assignment consumers | Dump FK mencakup approval mappings, user assignments, KM document-version position links dan KM assignment audits. | Semua current mapping/org/Sales dan consumers tersebut protected; M2 tidak mengubahnya untuk memudahkan historical mapping. |
| Training files/evidence | 0 field `file` terisi pada training 2025 di snapshot. | Storage/physical documents **NOT INSPECTED**; tidak menyatakan 0 actual files atau menghapus storage. |

Dependency closure kelak mencakup inbound/outbound FK, logical IDs, source→target mapping, text/JSON history, attachments, pivots, shared definitions, report/dashboard/export readers dan side effects. Pemeriksaan dua dump tidak dapat membuktikan seluruh runtime/JSON/text relationship atau actual filesystem. Itu bagian yang **PENDING**, bukan nol dependencies.

## 9. Candidate classification: rules dan keadaan saat M2

Tidak ada actual approved change list. Classification di bawah adalah disposition analysis/proposal rule; QUARANTINE berarti label laporan, row tetap di tempatnya.

| Class | Rule | Keadaan yang dapat dinyatakan sekarang |
| --- | --- | --- |
| RETAIN | Existing corroborated business record atau required/protected dependency. | Protected data dipertahankan; belum ada target 2025 yang dinyatakan genuine hanya karena link/score lengkap. T25-D tetap perlu provenance. |
| INSERT_CANDIDATE | Source year accepted, NPK/posisi/master/category/score/status/lineage dan natural keys terselesaikan, target identity absent, minimum dependencies valid. | **0 technically established candidates saat ini**; 281 source QUARANTINE, bukan gagal tahun. 229 source master definitions bukan approved insert quota. Source training inserts planned 0. |
| UPDATE_CANDIDATE | Exact existing business identity dan correction source terbukti, before/after fields dan dependencies lengkap. | **0 established field-level proposals**; numeric bad link atau collision saja tidak menetapkan correct after target. |
| REVIEW_DELETE | Individually evidenced dummy/duplicate/superseded/irrelevant, affected history/parents/children dan survivor bila relevan diketahui. | **0 evidence-complete exact-record delete proposals**. Target suspicion 934, repeat-key groups, 490 orphan, locks/status/created date dan target source-absence tetap investigasi. |
| QUARANTINE | Identity/mapping/provenance/semantics/lineage/natural-key ambiguity. | **281 source assessments + 320 target assessments 2025 + 934 target training 2025**, pada domain yang terpisah. Source 1 detail dan unresolved master definitions juga ditahan; counts ini tidak dijumlahkan sebagai identity yang sama. |
| OUT_OF_SCOPE | Non-2025, account/credential/current hierarchy, unrelated modules; source master tanpa dependency ke selected cohort. | **7 assessment 2026 + 18 training 2027 + 1 participant pivot 2027**; **183 unreferenced source master definitions** tidak masuk import closure saat ini. No deletion. |
| DELETE_APPROVED | Bukti independen, exact-record HR/DBA approval, dependency-safe, verified backup/restore dan separately authorized execution. | **0**; tidak ditetapkan oleh M2. |

**0 INSERT_APPROVED / 0 UPDATE_APPROVED / 0 DELETE_APPROVED.** Opsi perubahan setelah M3 adalah conditional; tidak mengubah klasifikasi current menjadi approved. Source duplicate-like rows tidak dihapus dari immutable input atau digabung tanpa keputusan preservasi history. Jika master renamed/alias dapat dibuktikan HR kelak, recount matching/collision/closure terlebih dahulu, jangan memakai angka kandidat M2 sebagai quota eksekusi.

## 10. Issue matrix B3–B9 dan evidence yang masih diperlukan

B1/B2 tetap **CLOSED LIMITED** sebagaimana §1.1. Tidak memerlukan konfirmasi ulang tahun source atau pencarian training replacement.

| Blocker / owner | Bukti yang sudah tersedia | Belum terbukti / tindakan berikutnya |
| --- | --- | --- |
| **B3 — HR Data Owner + Import Operator, DBA** | 320 target assessment dan 934 training tercakup, status/lock/cohorts diketahui; exact target component duplicate groups 0; training full-payload duplicate groups 0. Q14 adalah suspicion pengguna. | Keaslian masing-masing event, fixture/obsolete proof dan source-of-truth conflict belum ada. Review exact-record non-replacement evidence/log/backup yang benar-benar tersedia; tidak menyimpulkan generator tertentu dijalankan. |
| **B4 — DBA + Workflow Owner** | 490 missing parent (317 IDs), 433 cross-user, 10 master mismatch; 12 links ke seluruh 7 assessment 2026; history 723 tidak punya reliable lineage. | Correct parent/event lineage dan sebab mismatch belum ditentukan. Jangan relink ke matching numeric legacy ID, timestamp atau current role; telusuri bukti kejadian sebelum proposal after. |
| **B5 — HR Approver + DBA** | Legacy status 7/32/242; source total NULL 281 (koreksi M2-E01); score/ref anomalies; 44 source rows mempunyai repeated component keys; 10 modifier identities unresolved. | Sign off status/lock/total/null/zero semantics dan representation repeat assessment/history; jangan pilih MIN/MAX/latest/final, fabricate actor atau menghitung total untuk menutup gap. |
| **B6 — HR Organization + Competency/Authorization Owners** | 105 NPK candidates, 17 assessment identity exceptions; 5/46 position-label candidates; minimum 229 masters + 5 missing IDs; category rubric pairs 3; contextual master matches 0. | Private identity decisions, historical aliases, missing definitions, semantic category/master equivalence serta natural-key collision treatment. Source-master-position semantics perlu divalidasi. Management graph PR 1 tetap gate independen; tidak menulis active assignments/Sales. |
| **B7 — HR Competency Owner** | Referenced source descriptions 133/75/20 vs sparse target; shared cross-year definitions 3/2/4. | Governance, approved description versions/aliases dan cross-year presentation impact. Tidak copy descriptions melalui ID/name; PR 3 existing-data-only tidak ditahan oleh recovery ini. |
| **B8 — DBA + Staging Operator** | Target dump schema/unique indexes dan discrepancy ledger/tier fields/history relationships sudah dicatat Gate 1. | Live schema/count/index/ledger drift **NOT VERIFIED**; authorized read-only preflight terpisah sebelum tooling/staging apply. Jangan replay dedupe migration atau schema migration untuk menyamakan dokumen. |
| **B9 — HR + DBA + User/Execution Owner** | Desain private manifest dan guard/rollback tersedia; actual manifest/backup/restore tidak dibuat/dijalankan. | Approve restricted location/ACL, evidence revision, exact HR/DBA decisions, verified backup/restore rehearsal, operational window dan authorization code/staging/production yang terpisah. |

Belum terselesaikan secara substantif: business source-target identity pairs, historical status/score mapping, semantic master/position/category crosswalk, record provenance dan safe change set. M2 menyelesaikan **planning dan offline gap coverage**, bukan mengklaim analisis semantik/owner validation tersebut selesai.

## 11. Rancangan private review manifest untuk M3, tanpa real employee rows

Tidak ada private annex atau populated manifest yang dibuat pada M2. Dokumen ini menetapkan field/rule saja. Actual employee-identifying manifest tidak boleh dikomit ke repository; gunakan lokasi restricted yang secara eksplisit disetujui HR/DBA/user. Public report tetap agregat, bukan daftar masked rows yang dapat direidentifikasi dari kombinasi posisi/skor.

| Field group | Ketentuan desain |
| --- | --- |
| Version/evidence | Immutable source/target SHA-256 + byte size, repository ref, mapping/report revision, source table + statement/tuple locator, target table + private PK locator. Target PK untuk absent identity NULL, bukan source PK yang dipakai ulang. |
| Employee | Opaque audit ID dengan private lookup; NPK/user-ID mapping dan evidence hanya di annex. Jangan mengekspor nama, NPK, credential, per-account hash atau plain hash NPK yang mudah ditebak. |
| Period | `business_year=2025`, `tahun_penilaian=2025`, `period_evidence_type=USER_ATTESTATION`, attestation reference dan exact source-cohort fingerprint/count 281; literal timestamps terpisah. Tidak mencakup detail/history atau snapshot lain secara implisit. |
| Identity/master | Source/target position/category/master/type, proposed alias/definition version, method, confidence, reviewer; no numeric fallback. Simpan row packing + component identity dan repeated-key group membership. |
| Issues/classification | Semua reason codes, primary waterfall reason, supporting evidence, classification, proposed operation; unknown tetap unknown. Tidak menjalankan operation karena label candidate. |
| Before/after | Exact typed fields/nulls/status/lock/actors/reference payload privately; canonical before/after hashes. Append-only provenance dan explicit absent/tombstone untuk proposal delete. No inferred scores/approvers. |
| Dependency closure | Parent dan child exact private references/counts/set hashes, inbound/outbound direction, FK action, logical/ambiguous history, files/pivots, shared consumers; flag 2026/2027 dan hard-stop protected impact. |
| HR/DBA decision | Per-record PENDING/RETURN/REJECT/APPROVE dengan reason/signature/timestamp pada manifest hash/revision. Business-year confirmation tidak menggantikan action approval. Unreviewed child tidak ikut parent approval. |
| Backup/rollback | Approved backup reference/checksum, restore rehearsal evidence dan expected restored counts/hash, reverse relink/insert/delete order, original PK recovery, conflict-after-write preconditions dan operator. |
| Execution boundary | Separate user permission untuk code, DB read/dry-run, staging writes dan production exact actions. Current approval counters semuanya nol; tidak ada applied/checkpoint claim pada M2. |

Future dry-run harus deterministik dari input + crosswalk revision, fail closed bila hash/approval/dependency drift, serta mengeluarkan classification counts tanpa writes atau identity leakage. Future apply membutuhkan idempotency, transaction/journal yang disetujui dan serialized execution; desain ini tidak menambah schema/service atau menjalankan tooling sekarang. Full DB restore ke live juga bukan rollback otomatis karena dapat menghilangkan legitimate unrelated writes.

## 12. Acceptance, verifikasi M2 dan handoff

### 12.1 Pemeriksaan offline yang benar-benar dilakukan

- Actual Git branch/HEAD/origin/status; input byte size/SHA-256 dan kesamaan dengan prior parsed bytes; canonical Gate 1 checksum/final decisions.
- Agregasi in-memory seluruh 281/320/934 scope: NPK and numeric-ID collision checks, source issue waterfall/overlap, referenced master closure/category candidate matching, source/target score-null/status diagnostics, repeat-key tests, target employee/pair coverage, training cohort/duplicate diagnostics dan cross-year/shared references.
- Pemeriksaan khusus M2-E01: `total_nilai` explicit NULL 281, included INSERT column, schema default NULL; tidak mereuse interpretasi lama yang salah.
- **15 offline audit consistency checks lulus**, mencakup coverage 281, identity/position/waterfall partitions, master occurrences/closure, training cohorts/categories/parent periods, target/source employee coverage, history association dan child-profile sums. Ini assertions agregasi, **bukan PHPUnit atau application tests**.
- Review public report untuk counts vs context, tanpa employee rows/PII/credentials, lalu whitespace/structure/status dan hash-preservation checks sebelum handoff.

**NOT RUN / NOT VERIFIED:** SQL/DB, application/Artisan/importer, runtime query plans, real concurrency, tests/build/browser, production/staging state, execution logs, actual attachments/storage, private manifest population, backup/restore, HR semantic decisions, data writes, commit/push/deploy. Tidak ada independent year proof baru; tahun tetap USER_ATTESTATION.

### 12.2 Acceptance berikutnya — direncanakan, tidak dijalankan

| Area | Harapan untuk mission berikutnya yang diotorisasi |
| --- | --- |
| Identity | 17 unresolved source rows tidak lolos via existing numeric target user IDs; no name fallback; leading-zero NPK terjaga. |
| Mapping | Exact label, rubric dan master candidate tidak otomatis accepted; type/category/position/version tervalidasi; missing source definition tidak diciptakan. |
| Year/score/history | Semua 281 menerima year 2025 pada exact cohort, timestamp dan source total NULL tetap literal; null/zero/score-without-reference tidak diubah. Repeated keys diputuskan HR dengan history preserved. |
| Target comparison | 172 target rows di luar resolvable source employee subset tidak dihapus karena absence; 12 group-candidate source rows tidak dianggap one-to-one dengan 8 target rows. |
| Training | 490/433/10/1 tetap total 934, suspicion bukan proof; repeat keys tidak auto-dedup, T25-D tidak auto-authentic. Source training inserts tetap nol. |
| Dependencies | Semua 12 cross-year links/7 parents, 18 training 2027/pivot, shared definitions 3/2/4, current assignments dan ambiguous detail tetap dilindungi. |
| Security/approval | No public employee-level manifest, safe error/log output, deterministic report, exact input/record hash binding; candidate tidak menjalankan action dan tidak menghasilkan side effects. |
| Rollback | Hanya setelah explicit authorization: verified backup, isolated restore rehearsal dan exact preconditions; affected/protected fingerprints dikembalikan tanpa menghapus legitimate unrelated changes. |

M2 berhenti setelah dokumen ini. Handoff berikutnya adalah **M3 HR/DBA candidate evidence review setelah user review/otorisasi tugas berikutnya**. Tidak memulai populated manifest, code PR 1/PR 3/PR 2 tooling, DB dry-run, staging atau execution. B1/B2 tidak diinterview ulang; B3–B9 tetap pending sesuai matriks di atas.

**MISSION 02 READ-ONLY RECONCILIATION PLANNING COMPLETE — PENDING HR/DBA REVIEW**
