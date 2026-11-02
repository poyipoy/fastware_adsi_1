# PR 2 — finalisasi posisi dan persiapan dependensi HR 2025

26 Oktober 2026. Branch `main`, HEAD `6161d682e1eeaf81c9e83acad523af7c5b688b45`. Worktree sudah dirty sebelum pekerjaan ini. Tool rekonsiliasi, SQL, aplikasi, PR 1/PR 3/PR 4 dan paket review sebelumnya tidak diubah. Laporan ini satu-satunya file repository yang ditambahkan oleh tugas ini; rincian record berada di lokasi privat.

**Workbook:** `C:\laragon\private\fastware-2025-review\position-finalization-20261026-033902\HR2025-position-finalization-and-dependencies.xlsx`.

Tujuh sheet: Summary; Final Position Crosswalk (46); Remaining Position Exceptions (24, termasuk 4 SKIP); Dependency Impact (229 definisi tersedia + 5 definisi hilang + 3 kategori); Dummy Data Review (1.894 record target); Migration Readiness (281); Schema Relationships (8). Semua kolom keputusan dan attestasi HR/DBA kosong. Data dan locator diperlakukan sebagai teks; detail teknis tersedia pada kolom tersembunyi dan JSON privat.

## Fondasi posisi

| Status | Label legacy | Assessment terdampak |
| --- | ---: | ---: |
| USER_ACCEPTED | 19 | 150 |
| PROPOSED | 3 | 9 |
| AMBIGUOUS | 2 | 15 |
| UNMATCHED | 18 | 89 |
| SKIP_BY_USER — SOH | 4 | 12 |
| Di luar katalog posisi legacy | — | 6 |
| **Total** | **46** | **281** |

**22 label memiliki 22 target unik**, tanpa konvergensi antarposisi. Ini terdiri dari 19 pasangan yang diterima pengguna dan 3 usulan; bukan 22 persetujuan HR/DBA. Dua posisi ambigu masih memiliki masing-masing 2 dan 3 alternatif berkonteks ADASI, tanpa kecocokan nama/alias konservatif yang menentukan satu target. Delapan belas lainnya tidak memiliki bukti kesetaraan yang memadai. Alternatif, alasan dan worksheet/baris ADASI disimpan privat; target tidak dipaksakan. Empat SOH tidak dimasukkan ke binding maupun input pending; assessment dan master sumbernya tetap dipertahankan.

Workbook keputusan terbaru benar-benar dibaca dan disalin dari input yang tersedia: `HR2025-semi-automatic-mapping-keputusan-posisi.xlsx`, SHA-256 `eada99f66be119e4f991a972b0f57c2c11099e6ff3951f2ec975a5cda36470fb`. ADASI tetap acuan penamaan/struktur target, dengan periode historis posisi **UNVERIFIED**, karena referensi juga memuat organisasi aktual 2026. Keputusan pengguna disimpan terpisah dari bukti historis dan persetujuan HR/DBA.

Binding posisi dibekukan dengan SHA-256 `bfa346fafb7359fbba81baa5c738ba5cb9f447a7e95d608df9808929e2f7e037`. Semua dependensi memakai lookup dari fondasi yang sama; posisi tidak dicocokkan ulang untuk setiap kompetensi. `position-crosswalk.final.private.json` merekam keputusan pengguna; `crosswalk.pending.json` memakai kontrak engine existing dengan `decision=PENDING`, tanpa menaikkan USER_ACCEPTED menjadi APPROVED.

## Dependensi nyata

Target memakai referensi posisi langsung pada master TC/SK/Additional, referensi komponen langsung pada assessment, `id_trs` pada training, serta pivot peserta training. Kolom SQL, constraint, model aktif dan kode rekonsiliasi diperiksa. FK terdeklarasi dibedakan dari relasi logis; tidak adanya FK tidak membuktikan tidak adanya dependensi. Pivot organisasi aktif tidak dipakai sebagai pengganti bukti posisi historis.

- Dari **229 definisi tersedia** (133 TC, 75 SK/DK, 21 Additional), **101** mengikuti binding posisi unik. Ada **24** definisi dengan kandidat semantik/rubrik berkonteks posisi: **18** usulan target tunggal dan **6** ambigu, dengan **30** pasangan pembanding. Sepuluh usulan sumber berkonvergensi ke target bersama dan tetap memerlukan review; tidak ada merge otomatis.
- **77** definisi pada posisi terikat tidak memiliki padanan yang cukup masuk akal: `HISTORICAL_PRESERVATION_CANDIDATE`, bukan izin membuat master. **92** masih terhalang posisi unresolved/ambigu; **36** mengikuti posisi SOH yang di-skip. Jumlahnya tetap 229. **5** definisi sumber hilang dicatat terpisah; **183** master sumber yang tidak direferensikan cohort tidak dimasukkan ke migrasi minimum.
- **159 assessment** memiliki kandidat posisi, termasuk **146** dengan NPK unik; **137** di antaranya memakai posisi USER_ACCEPTED. Dari seluruh cohort, **21** memiliki kandidat konteks untuk seluruh komponen dan **19** memiliki usulan komponen unik. Angka ini bukan kesetaraan event, full mapping tervalidasi, atau jumlah aman untuk insert. Sebanyak 17 identitas masih unresolved.
- Semua **281 assessment sumber** tetap asli periode bisnis **2025 USER_ATTESTATION**. Literal timestamp tetap 10 created_at-2025 / 271 created_at-2026; asal timestamp dari seeder dicatat sebagai pernyataan pengguna, bukan pembuktian teknis independen. Skor, nol, grouping, timestamp dan **281 total_nilai=NULL** dipertahankan. Locator numerik engine untuk Additional legacy dibedakan dari ID literal berpadded nol yang tetap disimpan; normalisasi ini tidak menghapus nol bermakna pada NPK dan tidak menjadi pencocokan PK lintas database.
- **44** anggota repeated-component sumber tetap terpisah dari **36** anggota potensi benturan kunci setelah proyeksi kandidat saat ini. Proyeksi kandidat menemukan **47** assessment sumber berpotensi berbenturan dengan kunci komponen target; registri risiko lama **92** tetap tersimpan, tidak dianggap selesai karena pool kandidat menyempit. Seluruh **281 PK assessment legacy** bertabrakan dengan PK target; PK tidak dipakai untuk pencocokan atau overwrite. Status/locking, modifier/approver, NULL/nol dan kesetaraan event tetap pending. Tidak ada pemilihan survivor.

## Pernyataan dummy dan proteksi

Inventaris read-only mencatat **615 master target created_at-2026** serta **934 training bisnis-2025 created_at-2026** sebagai cakupan **USER_DECLARED_DUMMY**. Pernyataan ini dipertahankan, tetapi belum menjadi bukti provenance independen atau izin menghapus/memakai ulang master. Usulan remapping master secara eksplisit membawa blocker provenance target tersebut. Assessment target tidak otomatis dinyatakan dummy dari pernyataan tentang skill/training.

Training 2025 tetap T25-A **490**, T25-B **433**, T25-C **10**, T25-D **1**; semuanya QUARANTINE/review. **12** tautan ke assessment-2026 dilindungi. Inventaris target memuat 615 master + 952 training + 327 assessment = **1.894 record**, dengan referensi, dependency, hash before=after, dan nol persetujuan delete.

Proteksi dipertahankan untuk **7 assessment-2026**, **18 training-2027**, **1 pivot peserta**, **18 master yang mempunyai konsumen non-2025**, **104 baris mapping posisi pada snapshot**, serta **123 baris approval posisi**. Deklarasi created_at-2026 tidak mengatasi proteksi periode 2026/2027. **723 detail/history** tetap lineage-unverified; tidak dapat dibersihkan berdasarkan posisi atau timestamp. Closure ini terbatas pada schema/data snapshot dan relasi kode yang diperiksa; live schema, consumer runtime dan provenance tetap perlu DBA. Tidak ada delete plan executable atau pengubahan organisasi/Sales.

## Verifikasi aktual

- SHA-256 SQL cocok dengan baseline: legacy `e697a99f80994ff8987b0a325f6b54ed7dc0f9767a2226f617afaeb7a1e9c3d8`; target `996ef43a253969d992f29d917cdc021df353c6c89f8081a005b4f8787b1ea3db`. Referensi ADASI privat tetap `d1d1064710c44f1373be6233827b449fce7c8fcb104726f21fd8e8ac729654a1`. Parsing literal existing dipakai untuk memverifikasi snapshot dan dependensi pivot; SQL tidak dieksekusi.
- Existing offline entry point dijalankan tanpa crosswalk dan dengan pending crosswalk. Hasil byte-identical: **0 reviewed mappings / 0 INSERT_CANDIDATE / 281 QUARANTINE / 0 approval / 0 koneksi DB / 0 write**. Tidak ada apply/delete mode atau input approval buatan. Dry-run bukan operasi yang masih terhalang; peningkatan eligibility terhalang bukti dan review.
- `php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Hr2025ReconciliationTest.php --do-not-cache-result`: **32 tests / 114 assertions PASS**. Pemeriksaan delivery privat: **16 tests PASS, 0 gagal/error/skipped**, meliputi input immutable, keputusan aktual, SKIP, fondasi tunggal, cakupan 229+5, payload/NULL/nol/timestamp, identitas, repeated keys, cohort, periode terlindungi, input pending, keputusan kosong dan XLSX aman. Pemeriksaan coverage awal dikoreksi agar memakai kontrak locator Additional berpadded nol; payload asli tidak diubah.
- Dua skrip PHP privat lolos `php -l`. Delapan hasil inti analisis direproduksi byte-identical; workbook dibaca ulang per sel, dengan pemeriksaan teks, field keputusan kosong, dropdown dan tidak adanya formula/hyperlink/macro/external link. Hasil rinci dan checksum ada di direktori privat.
- ACL NTFS owner-only dan lokasi di luar www/Git diperiksa. Scan transitif mencakup **67.339 direktori / 13 link**, tanpa unsafe link/error; **32** fingerprint konfigurasi web tetap sama; tidak ditemukan static server tambahan. Lima canary loopback memberi **404/404/404/400/400**. Ini bukti lokal pada waktu pemeriksaan, bukan attestasi deployment produksi.
- **Temuan keamanan input existing:** dua workbook input yang sudah berada pada root repository/www sebelum tugas ini memberikan **HTTP HEAD 200/200** melalui localhost. Isi tidak diambil melalui HTTP. File baseline tersebut tidak dipindah/dihapus, dan akses dari jaringan lain tidak diuji. Isolasi PASS di atas berlaku untuk direktori output privat, bukan salinan input lama di www; operator perlu mengamankan salinan webroot tersebut secara terpisah. Tidak ada perubahan konfigurasi web otomatis.
- Tidak menjalankan Laravel bootstrap/Artisan, DB-backed tests, staging/production, migrasi, seeder, importer lama, browser atau desktop Excel. Tidak ada perubahan database, PR 1/PR 3/PR 4, commit, push atau deploy.

**Satu langkah berikutnya:** HR/DBA meninjau fondasi posisi beku beserta exception dependensinya dalam workbook yang sama—terutama 2 target ambigu, 18 unmatched, 5 definisi hilang, 17 identitas, provenance master target, status/event dan benturan—tanpa mencocokkan posisi ulang per skill. Persetujuan mapping, bukti historis, exact action manifest/dependency closure, backup/restore rehearsal dan otorisasi migrasi/pembersihan tetap gerbang terpisah. Persiapan selesai; migrasi belum diotorisasi.

**JOB POSITION CROSSWALK & DEPENDENCY PREPARATION COMPLETE — PENDING MIGRATION AUTHORIZATION.**
