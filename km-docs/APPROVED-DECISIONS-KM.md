# APPROVED DECISION REGISTER
## Knowledge Management

> **Status dokumen:** Approved  
> **Tanggal persetujuan:** 20 Juli 2026  
> Seluruh keputusan utama dan detail rekomendasi dalam dokumen ini telah disetujui oleh pengguna sebagai keputusan proyek.
>
> **Batas persetujuan:** Persetujuan ini tidak menghapus execution gate yang mensyaratkan validasi schema, database testing, baseline test, infrastructure capability, security review, legal/privacy review, staging test, atau persetujuan owner organisasi terkait sebelum fitur tertentu diterapkan ke production.
>
> **Amendment — Tanpa Queue Worker:** Sistem tidak menggunakan Laravel queue worker, Horizon, Redis queue, Supervisor-managed worker, atau daemon pemrosesan persisten. Proses ringan dilakukan sinkron pada request setelah validasi dan transaction safety. Proses berkala atau berat dijalankan melalui Laravel Scheduler/cron sebagai perintah Artisan berumur pendek, atau secara manual oleh administrator. Bila scheduler tidak dikonfigurasi, pemrosesan dokumen, reminder, reconciliation, dan sinkronisasi berkala tidak berjalan otomatis.

---

## Ringkasan Status

| ID | Keputusan Utama | Status Persetujuan |
|---|---|---|
| KM-DEC-001 | Versioning opsi B | Approved |
| KM-DEC-002 | Self-hosted conversion/OCR opsi B tanpa queue worker | Approved direction; menggunakan scheduled/manual Artisan command dan tetap conditional pada infrastructure/security gates |
| KM-DEC-003 | Approval satu tahap oleh HRGA Staff | Approved |
| KM-DEC-004 | Progress dipisahkan dari completion resmi | Approved |
| KM-DEC-005 | Master organisasi menggunakan `mst_job_position` | Approved; schema dan data contract wajib divalidasi |
| KM-DEC-006 | Notifikasi in-app/database saja | Approved |
| KM-DEC-007 | Thread dua level, satu reaction, mention via picker | Approved |
| KM-DEC-008 | Opening balance dan ledger untuk poin baru | Approved |
| KM-DEC-009 | Analytics operasional non-KPI | Approved |
| KM-DEC-010 | Assignment hybrid dengan target department | Approved |
| KM-DEC-011 | Responsive online-only | Approved |
| KM-DEC-012 | Export manual, lalu one-way API setelah completion stabil | Fase A approved; Fase B conditional |
| KM-DEC-013 | Recognition-only | Approved |
| KM-DEC-014 | Authoritative business record disimpan tanpa batas | Approved; organizational risk sign-off tetap diperlukan sebelum production |

---

## KM-DEC-001 — Versioning Dokumen dan Approval Ulang

**Keputusan utama:** Opsi B.

Perubahan file atau konten membuat versi baru dan wajib menjalani approval ulang. Metadata administratif tertentu dapat diubah tanpa approval ulang.

### Detail keputusan yang disetujui

- **Versi approved lama tetap published selama revisi menunggu:** Tidak.
- **Aturan publication:** setiap revisi harus menjalani approval ulang sebelum dapat dipublikasikan.
- **Status dokumen selama revisi menunggu:** `revision_pending`.
- **Akses pengguna umum selama revisi menunggu:** dokumen revisi tidak ditampilkan sebagai dokumen published sampai versi baru disetujui.
- **Format nomor versi:** major/minor, seperti `1.0`, `1.1`, dan `2.0`.
- **Perubahan file atau isi dokumen:** menaikkan major version, misalnya `1.1` menjadi `2.0`, dan wajib approval.
- **Perubahan metadata administratif:** menaikkan minor version, misalnya `1.0` menjadi `1.1`, tanpa approval ulang.

### Metadata yang direkomendasikan dapat diubah tanpa approval ulang

- Koreksi typo non-substantif.
- Tag atau keyword pencarian.
- Thumbnail.
- Internal administrative note.
- Urutan tampilan.
- PIC administratif.
- Metadata teknis file yang tidak mengubah isi atau akses.

### Metadata yang tetap wajib approval ulang

- Judul yang mengubah makna.
- Kategori.
- Klasifikasi keamanan.
- Target department.
- Deskripsi substantif.
- Masa berlaku.
- File atau isi dokumen.

### Hak akses versi lama

Versi lama hanya dapat dilihat oleh:

- document owner;
- HRGA approver;
- KM administrator;
- auditor yang memiliki ability terkait.

Pengguna umum hanya dapat melihat versi current yang approved.

### Audit dan integritas

- **Change note:** wajib untuk setiap perubahan.
- **Checksum:** wajib menggunakan SHA-256 untuk setiap file version.
- Audit menyimpan:
  - actor;
  - timestamp;
  - nomor versi;
  - change note;
  - checksum;
  - status approval;
  - approver;
  - waktu keputusan.

### Migrasi dokumen existing

- Setiap dokumen existing dibuat sebagai versi `1.0`.
- Status version mengikuti status dokumen existing.
- File current menjadi file versi `1.0`.
- Checksum dihitung melalui perintah Artisan idempotent `km:backfill-document-checksums`, dijalankan manual atau melalui Laravel Scheduler/cron.
- Dokumen dengan status tidak jelas ditandai `migration_review_required`.

### Catatan risiko yang diterima

Menarik versi approved lama dari publikasi segera setelah revisi diajukan dapat menyebabkan periode ketika dokumen tidak tersedia. Keputusan owner tetap dipertahankan, tetapi dampak operasional ini perlu diterima secara eksplisit.

---

## KM-DEC-005 — Master Organisasi Authoritative

**Keputusan utama:** Opsi B, menggunakan `mst_job_position`.

### Detail keputusan yang disetujui

- **Sumber authoritative:** `mst_job_position`.
- **Foreign key aplikasi:** gunakan primary key immutable dari `mst_job_position`.
- Bila tabel belum memiliki primary key stabil:
  - tambahkan numeric `id`;
  - pertahankan `position_code` sebagai unique business identifier.
- Jangan gunakan nama position, department, atau section sebagai foreign key.
- Job position terhubung ke department melalui foreign key.
- Job position terhubung ke section melalui foreign key bila section tersedia.
- Department dan section menggunakan master table masing-masing.
- Relasi user menggunakan `users.job_position_id` atau tabel assignment organisasi khusus.

### Histori penempatan

Gunakan tabel effective-dated seperti `user_job_position_assignments` yang menyimpan:

- `user_id`;
- `job_position_id`;
- `effective_from`;
- `effective_until`;
- status aktif;
- sumber perubahan.

### Kondisi khusus

- User tanpa job position tetap dapat login.
- User tanpa mapping tidak otomatis masuk targeting organisasi.
- User tanpa mapping ditampilkan dalam reconciliation report.
- Mapping ganda aktif tidak diperbolehkan pada periode efektif yang sama.
- Perpindahan posisi menutup record lama dan membuat record baru.
- Histori pegawai nonaktif tidak dihapus.
- Snapshot organisasi wajib disimpan pada:
  - point ledger;
  - assignment;
  - completion;
  - approval tertentu;
  - analytics historis.
- `users.section` dipertahankan sementara sebagai read-only fallback.
- `users.section` tidak digunakan sebagai business key baru.
- Fallback dihentikan setelah reconciliation coverage mencapai 100%.

### Reconciliation report minimal

- User tanpa mapping.
- Mapping ganda.
- Job position tidak aktif.
- Department atau section tidak ditemukan.
- Perbedaan antara `users.section` dan master.

---

## KM-DEC-004 — Progress, Resume, dan Completion

**Keputusan utama:** progress dan resume dipisahkan dari completion resmi.

### Progress dan resume

- Progress disimpan per `user_id`, `document_version_id`, dan perangkat/session.
- Progress menyimpan:
  - halaman terakhir;
  - halaman unik yang telah dibuka;
  - persentase;
  - active reading time;
  - timestamp terakhir.
- Progress lintas perangkat menggunakan nilai paling maju yang valid.
- Progress bukan bukti compliance.

### Completion resmi

Completion resmi tercapai jika seluruh kondisi berikut terpenuhi:

1. Minimal **90% halaman unik** telah dibuka.
2. Active reading time memenuhi:
   - minimum 60 detik;
   - rekomendasi 20 detik per halaman;
   - maksimum requirement 15 menit per dokumen.
3. Pengguna menekan konfirmasi eksplisit:
   - `Saya telah membaca dan memahami dokumen ini`.
4. Untuk dokumen compliance tertentu, quiz atau acknowledgment tambahan dapat diwajibkan melalui konfigurasi assignment.

### Aturan tambahan

- **Inactive timeout:** aktivitas dihentikan setelah 60 detik tanpa interaksi.
- **Multi-tab:** active time tidak boleh dihitung ganda.
- **Multi-device:** event digabung secara idempotent.
- **Completion berlaku:** per user dan per versi dokumen.
- Completion versi lama tetap disimpan dalam histori.
- Progress versi baru dimulai dari nol.
- Pengguna hanya wajib menyelesaikan ulang jika dibuat assignment baru.
- Poin diberikan satu kali setelah completion resmi per user-version.
- Compliance hanya dianggap selesai jika terdapat assignment aktif dan completion resmi.
- Completion tidak digunakan langsung untuk performance score pada fase saat ini.
- HRGA/KM Admin dapat mencatat completion manual untuk kebutuhan aksesibilitas dengan alasan, actor, timestamp, dan audit trail.
- Terapkan unique constraint pada `user_id + document_version_id + completion_type`.

---

## KM-DEC-003 — Approval Workflow

**Keputusan utama:** satu tahap approval oleh HRGA Staff.

### Detail keputusan yang disetujui

- HRGA Staff direpresentasikan sebagai role aplikasi, bukan string department.
- Gunakan ability:
  - `km.approve-document`;
  - `km.request-revision`;
  - `km.reject-document`.
- Seluruh user aktif dengan role HRGA Staff dan ability approval menggunakan shared approval inbox pada aplikasi.
- Keputusan pertama yang valid mengunci approval request.
- Submitter tidak dapat menyetujui dokumennya sendiri.
- Fallback approver adalah HRGA Supervisor atau KM Administrator.
- Bila tidak ada approver aktif, status menjadi `approval_blocked`.
- SLA approval: 3 hari kerja.
- Reminder: 1 hari kerja sebelum due date.
- Escalation: overdue ditampilkan kepada HRGA Supervisor.
- Delegasi dilakukan melalui reassignment formal oleh HRGA Supervisor/KM Admin.

### Status utama

- `draft`;
- `submitted`;
- `approved`;
- `revision_requested`;
- `rejected`;
- `cancelled`.

### Transisi

- `draft → submitted`;
- `submitted → approved`;
- `submitted → revision_requested`;
- `submitted → rejected`;
- `submitted → cancelled` oleh submitter sebelum keputusan;
- `revision_requested → submitted` setelah revisi.

Alasan wajib untuk:

- revision requested;
- rejected;
- reassignment;
- administrative override.

Periodic review default: setiap 12 bulan.

---

## KM-DEC-008 — Poin Historis dan Ledger

**Keputusan utama:** opening balance dari `users.km_total_poin`, kemudian ledger menjadi sumber authoritative.

### Detail keputusan yang disetujui

- **Tanggal efektif:** tanggal deployment ledger ke production.
- Opening balance dibuat sebagai satu entry immutable dengan event type `opening_balance`.
- Opening balance tidak direkonstruksi.
- `users.km_total_poin` menjadi cached aggregate untuk performa.

### Event poin awal

- Completion resmi dokumen: **5 poin**.
- Dokumen kontribusi approved dan published: **25 poin**.
- Komentar yang dipilih sebagai Insight Pilihan: **10 poin**.

Tidak ada poin untuk:

- view;
- membuka halaman terakhir;
- komentar biasa;
- reaction;
- mention;
- login.

### Aturan ledger

- Completion event key: `completion:{user}:{document_version}`.
- Contribution event key: `published:{document_version}:{owner}`.
- Insight event key: `selected_insight:{comment}:{author}`.
- Terapkan unique database constraint pada event key.
- Reversal dibuat sebagai compensating ledger entry.
- Entry lama tidak diedit atau dihapus.
- Poin tidak memiliki expiry karena bersifat recognition-only.
- Manual adjustment hanya dapat dilakukan oleh KM Admin/HRGA authorized.
- Manual adjustment wajib menyimpan alasan dan supporting reference.
- Snapshot department wajib disimpan ketika poin diberikan.
- Leaderboard menggunakan department snapshot saat award.
- Perintah terjadwal `km:reconcile-points` membandingkan total ledger dengan cached aggregate setiap hari melalui Laravel Scheduler/cron.
- Adjustment, reversal, dan recalculation dicatat dalam audit trail.

---

## KM-DEC-010 — Assignment dan Compliance

**Keputusan utama:** model hybrid dengan target department.

### Hak akses

**HRGA dapat:**

- membuat assignment untuk seluruh department;
- melihat seluruh assignment;
- memberikan exemption;
- membatalkan atau melakukan reassignment.

**Department Head dapat:**

- membuat assignment hanya untuk department sendiri;
- melihat status department sendiri;
- mengajukan exemption;
- tidak menyetujui permintaan exemption sendiri.

### Aturan yang disetujui

- Target assignment disimpan sebagai snapshot user saat assignment dipublikasikan.
- Assignment terikat ke satu `document_version_id`.
- Due date default: 14 hari kalender.
- Due date dapat diubah sebelum assignment dipublikasikan.

### Status assignment user

- `assigned`;
- `in_progress`;
- `completed`;
- `overdue`;
- `exempted`;
- `cancelled`.

Hanya `completed` dan `exempted` yang dianggap compliant.

### Exemption dan perubahan organisasi

- Exemption membutuhkan alasan.
- Exemption dapat memiliki tanggal kedaluwarsa.
- Exemption disetujui HRGA.
- Seluruh perubahan diaudit.
- Assignment snapshot lama tetap dipertahankan ketika user pindah department.
- HRGA dapat membatalkan atau membuat assignment baru.
- Pegawai baru menerima assignment dari active assignment rule melalui perintah terjadwal harian `km:sync-assignment-rules`.
- Versi baru dokumen tidak otomatis mengubah assignment existing.
- Reassignment versi baru dibuat secara eksplisit.

### Reminder

- In-app reminder pada H-3 dan H-1 dibuat oleh perintah terjadwal `km:send-assignment-reminders`.
- Overdue notification dibuat satu hari setelah due date oleh perintah terjadwal `km:mark-overdue-assignments`.
- Compliance menggunakan completion resmi KM-DEC-004.
- Denominator report berdasarkan snapshot target saat assignment dipublikasikan.

---

## KM-DEC-009 — KPI dan Analytics

**Keputusan utama:** metrik operasional agregat non-KPI.

### Metrik yang diperbolehkan

- Jumlah dokumen published.
- Jumlah dokumen per kategori.
- Jumlah view agregat.
- Materi populer.
- Jumlah submission.
- Waktu approval rata-rata secara agregat.
- Jumlah assignment.
- Jumlah completion agregat.
- Jumlah komentar dan reaction agregat.

### Batasan

- Tidak menampilkan score individu.
- Tidak digunakan untuk performance appraisal.
- Tidak ada ranking individu untuk HR.
- Tidak menyebut metrik sebagai KPI resmi.
- Drill-down individu hanya untuk kebutuhan operasional dengan ability khusus.
- Data individual tidak boleh diekspor sebagai laporan performance.
- Minimum cohort: 5 user untuk statistik department.
- Group dengan kurang dari 5 user ditampilkan sebagai `insufficient cohort`.
- Refresh data: harian melalui query agregat langsung atau perintah Artisan terjadwal; tidak menggunakan queue worker.
- Retention mengikuti KM-DEC-014.
- Audit akses individual wajib bila drill-down operasional dibuka.

---

## KM-DEC-006 — Notifikasi

**Keputusan utama:** database/in-app notification saja.

### Event-recipient matrix awal

| Event | Recipient |
|---|---|
| Dokumen diajukan | HRGA Staff approver |
| Dokumen approved | Document owner |
| Revisi diminta | Document owner |
| Dokumen rejected | Document owner |
| Assignment dibuat | Assigned users |
| Assignment mendekati due date | Assigned user |
| Assignment overdue | Assigned user dan Department Head |
| User di-mention | Mentioned user |
| Insight dipilih | Comment author |
| Versi baru ditugaskan | Assigned users |

### Detail keputusan yang disetujui

- Mandatory notification:
  - approval;
  - revision/rejection;
  - assignment;
  - due reminder;
  - overdue;
  - mention.
- Reaction dan aktivitas sosial umum tidak mengirim notifikasi pada fase awal.
- Status read/unread disimpan per notification.
- Notifikasi yang berasal dari tindakan langsung pengguna dibuat secara sinkron setelah business transaction berhasil.
- Pembuatan notification harus menggunakan transaction boundary atau mekanisme after-commit agar notification tidak tersimpan ketika transaksi utama gagal.
- Reminder approval, reminder assignment, dan overdue notification dibuat melalui perintah Artisan terjadwal.
- Tidak menggunakan Laravel queue, failed-jobs table, dead-letter queue, Horizon, atau persistent worker.
- Kegagalan notifikasi sinkron menyebabkan operasi terkait dibatalkan atau dicatat sebagai application error sesuai transaction boundary.
- Perintah terjadwal harus idempotent dan dapat dijalankan ulang tanpa menghasilkan notification ganda.
- Rate limiting: satu notification per unique business event.
- Duplicate suppression menggunakan unique event key pada database.
- Preference center belum dibuat pada fase awal.
- FCM dan email tidak termasuk scope.

---

## KM-DEC-007 — Fitur Sosial

**Keputusan utama:**

- Thread maksimal dua level.
- Satu reaction aktif per user pada satu komentar.
- Mention menggunakan user picker.

### Detail keputusan yang disetujui

- Reply terhadap reply tetap ditampilkan pada tingkat kedua.
- Reaction awal:
  - `Helpful`;
  - `Insightful`;
  - `Agree`.
- User dapat mengganti reaction.
- Mention hanya melalui user picker.
- Hanya user aktif yang memiliki akses ke dokumen yang dapat dipilih.
- Maksimal 10 mention per komentar.
- Edit komentar maksimal 30 menit setelah dibuat.
- Delete oleh author maksimal 30 menit.
- Moderator dapat menyembunyikan atau menghapus kapan saja dengan alasan.
- Penghapusan menggunakan soft delete.
- Isi asli tetap tersedia untuk moderator/audit.
- Moderator adalah KM Admin dan HRGA user dengan moderation ability.

### Report abuse

Kategori awal:

- inappropriate;
- confidential information;
- harassment;
- spam;
- other.

### Rate limit

- Maksimal 10 komentar per 10 menit.
- Maksimal 30 reaction per menit.

### Insight Pilihan

- Dapat ditetapkan oleh document owner atau moderator.
- Maksimal 3 insight per dokumen.
- Actor dan timestamp wajib ditampilkan.
- Pembatalan pilihan dicatat dalam audit log.
- Mention dan selected insight menggunakan in-app notification.

---

## KM-DEC-002 — PPT/PPTX, OCR, dan Binary

**Keputusan utama:** self-hosted Poppler, LibreOffice headless, dan OCR engine tanpa queue worker.

### Format dan ukuran

- Format resmi:
  - PDF;
  - PPT;
  - PPTX.
- Format lain ditolak pada fase awal.
- Ukuran maksimum: 50 MB per file.

### Model pemrosesan tanpa worker

- Sistem tidak menjalankan `php artisan queue:work`, Horizon, Redis queue, Supervisor worker, atau daemon pemrosesan persisten.
- Web request upload hanya:
  - memvalidasi MIME dan ukuran;
  - menyimpan file original;
  - membuat record dokumen dengan status `pending_processing`;
  - mengembalikan respons kepada pengguna.
- Konversi, thumbnail, ekstraksi teks, OCR, dan antivirus tidak dijalankan di dalam request upload.
- Pemrosesan dilakukan oleh perintah Artisan berumur pendek:
  - `php artisan km:process-pending-documents --limit=1`.
- Perintah dapat dijalankan:
  - otomatis melalui Laravel Scheduler/cron; atau
  - manual oleh administrator.
- Bila scheduler tidak tersedia, dokumen tetap berstatus `pending_processing` sampai administrator menjalankan perintah secara manual.
- Gunakan atomic claim atau database lock agar satu dokumen tidak diproses oleh dua eksekusi bersamaan.
- Gunakan `withoutOverlapping()` atau lock ekuivalen pada scheduler.
- Satu eksekusi memproses satu dokumen secara default untuk membatasi penggunaan resource.

### Keamanan pemrosesan

- Perintah Artisan dijalankan dengan OS user non-root yang dibatasi.
- Network access proses dinonaktifkan kecuali dependency internal yang disetujui.
- Office macro tidak dieksekusi.
- Antivirus menggunakan ClamAV atau scanner internal sebelum konversi.
- PPT/PPTX dikonversi menjadi PDF.
- Poppler menghasilkan thumbnail.
- Tesseract digunakan hanya ketika ekstraksi teks normal tidak tersedia.
- Binary path, timeout, dan resource policy dibaca dari konfigurasi aplikasi.

### Timeout, retry, dan resource limit awal

- Konversi Office: 120 detik.
- Ekstraksi PDF: 120 detik.
- OCR: 300 detik.
- Maksimal 3 attempt per dokumen.
- Retry tidak menggunakan queue; status retry disimpan melalui:
  - `processing_attempts`;
  - `last_error`;
  - `next_attempt_at`;
  - `processing_status`.
- Perintah scheduler mengambil dokumen `pending_processing` atau `retry_pending` yang sudah melewati `next_attempt_at`.
- Resource awal per proses:
  - 2 vCPU;
  - 2 GB memory;
  - temporary disk limit 1 GB.
- Temporary file dihapus maksimal 1 jam setelah proses selesai atau gagal melalui cleanup command terjadwal.

### Output

- Normalized PDF.
- Thumbnail lokal.
- Extracted text.
- Processing metadata.

### Observability

- Duration.
- Exit code.
- File size.
- Detected MIME.
- Attempt count.
- Failure category.
- Waktu mulai dan selesai.
- User/admin yang memicu pemrosesan manual, bila ada.

### Fallback kegagalan

- File original tetap tersimpan.
- Status menjadi `processing_failed` setelah attempt terakhir.
- User yang berwenang tetap dapat mengunduh file original.
- Preview dan full-text dinonaktifkan.
- Administrator dapat menjalankan retry manual.
- Capability dan capacity test staging wajib lulus sebelum production.

### Trade-off yang diterima

- Pemrosesan tidak instan setelah upload.
- Throughput lebih rendah dibanding dedicated worker.
- Scheduler/cron tetap diperlukan untuk otomasi.
- Tanpa scheduler, pemrosesan dan retry bersifat manual.
- Bila volume dokumen meningkat atau waktu proses mengganggu operasional, keputusan tanpa worker harus dibuka kembali sebagai architecture decision baru.

---

## KM-DEC-011 — PWA dan Offline

**Keputusan utama:** responsive online-only.

### Detail keputusan yang disetujui

- Tidak memasang service worker.
- Tidak membuat cache dokumen.
- Tidak menyimpan file private pada browser storage.
- Tidak mendukung background sync.
- Tidak mengklaim aplikasi dapat digunakan offline.
- Tampilan wajib berfungsi pada desktop, tablet, dan mobile mulai 320 CSS px.
- Koneksi terputus harus menghasilkan pesan yang jelas.
- Input pengguna yang belum dikirim tidak boleh hilang bila secara teknis dapat dipertahankan.

---

## KM-DEC-012 — Integrasi HR

**Keputusan utama:** export manual terlebih dahulu, kemudian one-way API setelah completion stabil.

### Fase A — Export manual

- Format: XLSX dan CSV.
- Data yang boleh diekspor:
  - employee identifier;
  - nama;
  - department snapshot;
  - document identifier;
  - document version;
  - assignment date;
  - due date;
  - completion status;
  - completion date;
  - exemption status.
- Data yang tidak diekspor:
  - detail halaman;
  - active reading telemetry;
  - komentar;
  - reaction;
  - aktivitas sosial.
- Role utama: HRGA authorized user.
- Department Head hanya dapat mengekspor department sendiri bila ability diberikan.
- Audit menyimpan actor, waktu, filter, jumlah record, dan checksum file export.
- Export tidak otomatis mengubah performance score.

### Completion dianggap stabil jika

- Formula telah disetujui owner.
- Berjalan minimal dua release cycle.
- Tidak ada critical defect terbuka.
- Event idempotency terbukti.
- Reconciliation completion minimal 99,5%.
- Exception process telah diuji.

### Fase B — One-way API

- Identity key menggunakan employee number/stable HRIS ID, bukan email.
- Arah integrasi: KM ke HRIS.
- Kontrak menggunakan versioned JSON API/event.
- Request diautentikasi dan ditandatangani.
- Event key unik dan idempotent.
- Retry Fase B menggunakan outbox table dan perintah Artisan terjadwal dengan exponential backoff; tidak menggunakan queue worker.
- Correction menggunakan correction event.
- Tersedia reconciliation report per periode.
- Data tidak otomatis memengaruhi score sampai governance performance disetujui.
- Sandbox HRIS wajib tersedia sebelum implementasi.

---

## KM-DEC-013 — Reward

**Keputusan utama:** recognition-only tanpa nilai ekonomi.

### Recognition yang diperbolehkan

- Badge.
- Tier.
- Leaderboard.
- Profile acknowledgment.
- Certificate internal non-monetary.
- Highlight contributor.

### Batasan

- Poin tidak dapat ditukar dengan uang atau barang.
- Tidak ada redemption.
- Tidak ada transfer poin antar-user.
- Tidak ada pembelian poin.
- Tidak ada cash-equivalent.
- Tidak ada pengurangan poin untuk benefit ekonomi.
- Seluruh badge dan tier harus berasal dari ledger authoritative.

---

## KM-DEC-014 — Retensi dan Legal Hold

**Keputusan utama:** data KM disimpan tanpa batas.

### Interpretasi yang direkomendasikan

Retensi tanpa batas diterapkan pada authoritative business records:

- dokumen dan versi;
- approval history;
- audit trail;
- assignment;
- completion;
- point ledger;
- komentar dan moderation record;
- notification business record;
- export audit record.

Retensi tanpa batas tidak diterapkan pada data operasional sementara yang dapat dibuat ulang.

### Data yang tetap boleh dibersihkan

- Temporary conversion file.
- Cache.
- Generated preview yang dapat dibuat ulang.
- Debug log.
- Failed temporary upload.
- Test data pada database testing.
- Antivirus quarantine setelah keputusan operasional.
- Metadata pemrosesan sementara yang tidak lagi diperlukan setelah audit window berakhir.

### Aturan tambahan

- Production business record tidak di-hard-delete.
- Koreksi dilakukan melalui status correction, tombstone, soft delete, atau compensating record.
- Test data hanya boleh dihapus dari environment testing.
- Legal hold tetap dicatat walaupun retention indefinite.
- Legal hold owner: Legal/Compliance.
- Risk acceptance membutuhkan persetujuan:
  - Legal;
  - Compliance/Document Control;
  - DPO/privacy representative;
  - Information Security;
  - IT Operations.
- Backup mengikuti kebijakan backup infrastruktur dan tidak otomatis harus disimpan selamanya.
- Capacity monitoring wajib memiliki laporan pertumbuhan storage.
- Keputusan retensi direview minimal setahun sekali.
- Privacy correction tetap diperbolehkan melalui proses auditable.
- Temporary file cleanup mengikuti KM-DEC-002.
- Operational log retention mengikuti kebijakan logging IT, dengan rekomendasi 90–365 hari.

### Catatan risiko yang diterima

Menyimpan telemetry, notification teknis, log, dan temporary file tanpa batas tidak direkomendasikan. Pendekatan yang lebih aman adalah mempertahankan business record secara indefinite, tetapi membersihkan data operasional yang dapat dibuat ulang dan tidak memiliki nilai governance permanen.

---

## Arsitektur Eksekusi Tanpa Worker

### Komponen yang digunakan

- Laravel web application.
- Database aplikasi.
- Laravel Scheduler dengan satu cron entry, bila otomasi diaktifkan.
- Perintah Artisan idempotent untuk proses berkala dan berat.
- Binary lokal Poppler, LibreOffice headless, Tesseract, dan antivirus sesuai KM-DEC-002.

### Komponen yang tidak digunakan

- Laravel queue worker.
- `php artisan queue:work`.
- Laravel Horizon.
- Redis sebagai queue backend.
- Supervisor-managed queue process.
- Persistent document-processing daemon.
- Failed-jobs/dead-letter queue berbasis Laravel Queue.

### Scheduled commands minimum

- `km:process-pending-documents`
- `km:cleanup-temporary-files`
- `km:send-approval-reminders`
- `km:send-assignment-reminders`
- `km:mark-overdue-assignments`
- `km:reconcile-points`
- `km:sync-assignment-rules`
- `km:refresh-analytics`

Semua command harus:

- idempotent;
- memiliki database lock atau `withoutOverlapping`;
- mencatat hasil eksekusi;
- dapat dijalankan manual;
- gagal aman tanpa memproses record yang sama dua kali.

### Konsekuensi operasional

- Satu cron entry Laravel Scheduler tetap dibutuhkan agar otomasi berjalan.
- Tanpa cron, administrator harus menjalankan command secara manual.
- Tidak ada retry real-time.
- Pemrosesan dokumen dan reminder dapat mengalami keterlambatan sesuai interval scheduler.
- Arsitektur ini cocok untuk volume rendah sampai menengah dan harus dievaluasi ulang bila backlog atau waktu proses meningkat.

---

## Approval Record

- **Decision status:** Approved.
- **Approval recorded:** 20 Juli 2026.
- **Scope:** Seluruh keputusan utama dan detail operasional KM-DEC-001 sampai KM-DEC-014.
- **Change control:** Perubahan setelah persetujuan harus dicatat sebagai amendment terhadap decision ID terkait.
- **Execution control:** Coding agent tidak boleh menafsirkan status `Approved` sebagai izin untuk melewati database safety gate, baseline test, authorization review, security review, infrastructure validation, staging capability test, atau legal/privacy sign-off.
- **No-worker control:** Implementasi tidak boleh menambahkan `queue:work`, Horizon, Redis queue, Supervisor worker, atau daemon worker lain tanpa amendment baru. Laravel Scheduler/cron dan Artisan command berumur pendek tetap diperbolehkan.
- **Production readiness:** Item conditional hanya dapat masuk production setelah trigger pembukaan dan execution gate terkait terbukti terpenuhi.

---

## Status Akhir

| ID | Status final |
|---|---|
| KM-DEC-001 | Approved |
| KM-DEC-002 | Approved tanpa queue worker; implementation menunggu infrastructure/security validation dan scheduler/CLI readiness |
| KM-DEC-003 | Approved |
| KM-DEC-004 | Approved |
| KM-DEC-005 | Approved; schema/data contract validation required |
| KM-DEC-006 | Approved |
| KM-DEC-007 | Approved |
| KM-DEC-008 | Approved |
| KM-DEC-009 | Approved |
| KM-DEC-010 | Approved |
| KM-DEC-011 | Approved |
| KM-DEC-012 | Fase A approved; Fase B blocked sampai completion stabil dan integration gate terpenuhi |
| KM-DEC-013 | Approved |
| KM-DEC-014 | Approved with organizational risk sign-off required before production |
