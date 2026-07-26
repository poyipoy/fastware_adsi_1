# PENDING DECISIONS: Knowledge Management

Dokumen ini adalah daftar keputusan bisnis, governance, privacy, dan infrastruktur yang belum boleh diasumsikan oleh AI coding agent. Item di bawah **bukan scope** dari ketiga mission KM. Sebuah item hanya boleh dipindahkan ke mission baru setelah trigger pembukaan yang tercantum terpenuhi dan keputusan tertulis ditambahkan sebagai acceptance criteria yang dapat diuji.

## Cara Menggunakan Dokumen Ini

- Owner keputusan bertanggung jawab memilih opsi atau menyetujui alternatif tertulis; coding agent hanya boleh memberikan spike/prototype read-only bila diminta.
- Satu keputusan dapat membuka satu atau beberapa mission lanjutan, tetapi tidak boleh disisipkan diam-diam ke mission Jangka Pendek, Menengah, atau Panjang.
- Rekomendasi audit adalah default untuk diskusi, bukan persetujuan stakeholder.
- Dokumen keputusan minimum memuat owner, tanggal efektif, scope user/data, transition matrix atau data contract, exception, audit/retention, dan acceptance criteria.

## KM-DEC-001 — Versioning Dokumen dan Approval Ulang

- **Owner:** Product Owner KM, Process Owner Approval, dan Compliance/Document Control.
- **Keputusan yang dibutuhkan:** apa yang membentuk versi baru, versi mana yang tampil sebagai current/published, perubahan apa yang wajib approval ulang, dan apa yang terjadi pada versi approved ketika revisi baru masih menunggu.
- **Opsi:** (A) setiap perubahan file atau metadata membuat versi dan selalu approval ulang; (B) file/konten immutable membuat versi dan approval ulang, sedangkan metadata administratif tertentu boleh diubah tanpa approval; (C) file diganti in-place tanpa histori versi.
- **Rekomendasi audit:** pilih B; pertahankan versi approved lama tetap published sampai versi baru approved, catat actor/change note/checksum, dan definisikan whitelist metadata administratif yang tidak memicu approval.
- **Fitur terblokir:** `km_document_versions`, current/published version, histori/download versi, compare version, version-aware approval, dan full-text isi dokumen yang terikat versi.
- **Trigger pembukaan mission:** ADR disetujui seluruh owner dan memuat state/transition matrix, aturan numbering, daftar perubahan yang memicu approval, hak melihat versi lama, serta strategi migrasi file existing.

## KM-DEC-002 — PPT/PPTX, OCR, dan Provisioning Binary

- **Owner:** IT Infrastructure, Information Security, dan Product Owner KM.
- **Keputusan yang dibutuhkan:** format upload resmi dan apakah server menyediakan konversi Office, thumbnail, ekstraksi teks, dan OCR beserta kapasitas/sandbox/support-nya.
- **Opsi:** (A) production tetap PDF-only; (B) self-hosted Poppler + LibreOffice headless + OCR engine di worker terisolasi; (C) managed conversion/OCR service dengan kontrak pemrosesan data.
- **Rekomendasi audit:** pertahankan PDF-only dengan fallback thumbnail sampai environment worker terisolasi, antivirus, timeout, retry, observability, dan capacity test disetujui; jangan menjalankan LibreOffice pada request web.
- **Fitur terblokir:** preview/thumbnail PPT/PPTX, konversi Office ke PDF, full-text isi PDF/PPT/PPTX, OCR scan, serta provisioning Poppler/LibreOffice/Tesseract atau service eksternal.
- **Trigger pembukaan mission:** architecture/security review menyetujui opsi, daftar MIME/ukuran, lokasi pemrosesan, SLA/retry, resource limit, malware scanning, data residency, lisensi, serta bukti capability pada environment staging.

## KM-DEC-003 — Approval Berjenjang, SLA, Escalation, dan Review Cycle

- **Owner:** Process Owner KM, Department Heads, HRGA/Compliance, dan Internal Audit.
- **Keputusan yang dibutuhkan:** jumlah/tugas approver, pemetaan approver, aturan skip/delegasi, definisi hari kerja, SLA per tahap, escalation, serta kapan dokumen wajib review ulang.
- **Opsi:** (A) pertahankan workflow satu tahap; (B) workflow tetap dua tahap; (C) workflow configurable per kategori/departemen.
- **Rekomendasi audit:** pertahankan satu tahap sampai RACI dan kalender kerja disahkan; bila kebutuhan stabil, mulai dengan dua tahap tetap sebelum membangun workflow designer dinamis.
- **Fitur terblokir:** `km_approval_requests`, workflow steps, multi-stage approval, due date, SLA dashboard, reminder/escalation, delegasi approver, dan periodic review cycle.
- **Trigger pembukaan mission:** RACI, approver source, state/transition matrix, kalender/libur/timezone, SLA, delegation/escalation rules, dan review frequency disetujui serta tersedia contoh skenario UAT.

## KM-DEC-004 — Definisi Progress, Resume, dan Completion

- **Owner:** Learning & Development, Product Owner KM, HR Performance, dan Compliance.
- **Keputusan yang dibutuhkan:** bukti minimum “selesai membaca”, threshold progress/time, perilaku multi-tab/device, versi yang diselesaikan, dan apakah completion yang sama sah untuk poin, compliance, serta penilaian HR.
- **Opsi:** (A) mencapai halaman terakhir; (B) threshold halaman + waktu aktif + explicit confirmation; (C) acknowledgment/quiz sebagai bukti completion; masing-masing dapat dipisah antara UX progress dan compliance completion.
- **Rekomendasi audit:** pisahkan resume/progress sebagai convenience dari completion resmi; gunakan event idempotent per user-versi dan pilih bukti compliance yang lebih kuat daripada sekadar membuka halaman terakhir.
- **Fitur terblokir:** progress persentase, resume lintas perangkat, aturan completion versi, award poin berbasis completion resmi, compliance completion, dan konsumsi hasil oleh HR.
- **Trigger pembukaan mission:** owner menyetujui formula/threshold, event semantics, reset saat versi berubah, anti-replay, exception aksesibilitas, serta matriks penggunaan completion untuk UX/poin/compliance/HR.

## KM-DEC-005 — Master Organisasi Authoritative

- **Owner:** HRIS/Data Owner, HRGA, IT Architecture, dan pemilik master employee.
- **Keputusan yang dibutuhkan:** sumber kebenaran department/section/position, stable identifier, effective date/history, serta mapping user yang kosong, ganda, pindah, atau nonaktif.
- **Opsi:** (A) terus memakai string `users.section`; (B) memakai master job position/department internal dengan foreign key dan snapshot; (C) sinkronisasi dari HRIS eksternal sebagai authoritative source.
- **Rekomendasi audit:** gunakan stable ID authoritative dengan effective dating dan snapshot untuk histori; jangan memakai display string sebagai business key analytics/access jangka panjang.
- **Fitur terblokir:** access/targeting berbasis organisasi, rekomendasi per posisi, notification targeting, assignment dinamis, compliance denominator, dan leaderboard departemen.
- **Trigger pembukaan mission:** data contract/source owner disahkan, stable keys dan mapping coverage tersedia, aturan effective date/transfer/termination ditetapkan, serta reconciliation report staging diterima.

## KM-DEC-006 — Channel, Targeting, dan Privacy Notifikasi

- **Owner:** Product Owner KM, Internal Communications, Information Security/DPO, dan IT Operations.
- **Keputusan yang dibutuhkan:** event mana yang mengirim notifikasi, recipient, channel, preference/opt-out, quiet hours/digest, retry, dan informasi yang aman muncul pada lock-screen.
- **Opsi:** (A) database/in-app saja; (B) in-app + FCM; (C) in-app + FCM + email dengan preference per event/channel.
- **Rekomendasi audit:** mulai dari database notification queued; tambahkan channel eksternal hanya setelah template, consent/preference, redaksi lock-screen, rate limit, dan operational ownership jelas.
- **Fitur terblokir:** FCM/email KM, digest, reminder, mention notification, approval/SLA notification, preference center, targeting organisasi, dan privacy-safe payload.
- **Trigger pembukaan mission:** event-recipient-channel matrix, template/redaction, preference default, quiet hours/timezone, retry/dead-letter ownership, data privacy review, dan staging delivery test disetujui.

## KM-DEC-007 — Threading, Reaction, Mention, dan Insight Pilihan

- **Owner:** Product Owner KM, Community/Content Moderator, HR/Legal, dan Information Security.
- **Keputusan yang dibutuhkan:** kedalaman thread, model reaction, siapa yang dapat mention/select/moderate, edit/delete window, abuse handling, dan audit visibility.
- **Opsi:** (A) komentar datar yang ada; (B) thread maksimal dua level + satu reaction aktif per user + mention via user picker; (C) forum penuh dengan thread/reaction fleksibel.
- **Rekomendasi audit:** pilih B dengan moderation status, soft delete, actor/timestamp untuk Insight Pilihan, rate limit, dan policy eksplisit; hindari parser mention bebas dari display name.
- **Fitur terblokir:** reply/thread, reactions, mentions, notification sosial, moderation queue, dan Insight Pilihan.
- **Trigger pembukaan mission:** UX prototype dan moderation policy disetujui, role/ability matrix tersedia, edit/delete/retention rule ditetapkan, serta abuse/reporting UAT scenarios ditulis.

## KM-DEC-008 — Poin Historis, Ledger, Badge, Tier, dan Leaderboard Departemen

- **Owner:** HRGA, Product Owner KM, Finance/Internal Audit, dan HRIS Data Owner.
- **Keputusan yang dibutuhkan:** perlakuan saldo historis yang mungkin terduplikasi, sumber kebenaran poin baru, reversal/expiry, threshold badge/tier, dan atribusi departemen ketika user berpindah.
- **Opsi:** (A) pertahankan `users.km_total_poin` sebagai opening balance lalu ledger untuk event baru; (B) reset saldo pada tanggal efektif; (C) hitung ulang dari transaksi unik yang dapat dibuktikan.
- **Rekomendasi audit:** pilih A dengan label opening balance yang teraudit, event key unik untuk poin baru, reconciliation report, dan snapshot departemen pada waktu award; jangan mengarang koreksi historis yang tidak dapat dibuktikan.
- **Fitur terblokir:** migrasi/reconciliation poin historis, authoritative `km_point_ledger`, recalculate/reversal, badge, tier, point expiry, dan leaderboard departemen.
- **Trigger pembukaan mission:** keputusan saldo per tanggal efektif ditandatangani, formula/event/reversal/expiry disahkan, badge/tier catalog tersedia, org snapshot siap, dan Finance/Internal Audit menerima reconciliation plan.

## KM-DEC-009 — KPI Analytics dan Hak Akses Data Individual

- **Owner:** Executive Sponsor, HRGA/L&D Analytics, Compliance, DPO, dan Internal Audit.
- **Keputusan yang dibutuhkan:** definisi KPI/denominator, periode/timezone, target, data latency, siapa boleh melihat data individu, purpose limitation, serta cara koreksi data.
- **Opsi:** (A) aggregate operational metrics non-KPI; (B) KPI agregat team/department dengan minimum cohort; (C) dashboard individual untuk manager/HR dengan audit akses.
- **Rekomendasi audit:** tetap pada A sampai KPI dictionary dan privacy access matrix disahkan; dashboard materi populer pada mission Jangka Panjang secara eksplisit non-KPI dan tidak membuka item ini.
- **Fitur terblokir:** KPI resmi, completion rate formal, department/employee drill-down, scheduled executive report, individual activity export, dan penggunaan analytics untuk performance decision.
- **Trigger pembukaan mission:** KPI dictionary, denominator, source lineage, owner, refresh SLA, cohort/privacy rule, role matrix, audit log, retention, dan sample report UAT disetujui.

## KM-DEC-010 — Assignment dan Compliance Tracking

- **Owner:** Compliance, L&D, Department Process Owners, dan HRGA.
- **Keputusan yang dibutuhkan:** siapa dapat menugaskan materi, target assignment, versi yang wajib, due date, exemption/waiver, reassignment saat org berubah, dan definisi compliant/overdue.
- **Opsi:** (A) assignment manual per user; (B) rule dinamis per organisasi; (C) hybrid rule dengan snapshot user pada saat publish/assign.
- **Rekomendasi audit:** pilih C; simpan snapshot target dan versi dokumen agar denominator/history tidak berubah ketika organisasi atau dokumen berubah.
- **Fitur terblokir:** `km_assignments`, assignment user snapshot, deadline/reminder, compliance dashboard/export, overdue/escalation, dan automatic targeting.
- **Trigger pembukaan mission:** assignment RACI, target source, version/completion dependency, due/exemption/reassignment rules, notification dependency, retention, dan UAT denominator examples disetujui.

## KM-DEC-011 — PWA dan Dokumen Offline

- **Owner:** Information Security, IT Infrastructure, DPO, dan Product Owner KM.
- **Keputusan yang dibutuhkan:** apakah app shell atau dokumen boleh disimpan offline, perangkat yang didukung, encryption/key handling, expiry/revocation, logout/remote wipe, dan klasifikasi dokumen.
- **Opsi:** (A) responsive online-only; (B) PWA app shell tanpa cache dokumen private; (C) encrypted offline document cache dengan device management.
- **Rekomendasi audit:** pilih A atau B; jangan cache dokumen private offline sebelum threat model, managed-device requirement, revocation, dan storage encryption terbukti.
- **Fitur terblokir:** service worker production, installable PWA, offline document reading, background sync progress, dan push integration yang bergantung PWA.
- **Trigger pembukaan mission:** security architecture/threat model disetujui, hosting HTTPS/header siap, cache classification/expiry/revocation ditetapkan, device/browser matrix tersedia, dan offline leakage test plan diterima.

## KM-DEC-012 — Integrasi HR/Performance dan Governance Penilaian

- **Owner:** HR Performance, HRIS Product Owner, Legal/DPO, dan IT Integration.
- **Keputusan yang dibutuhkan:** data KM apa yang masuk penilaian, legal/business basis, arah sinkronisasi, identity mapping, correction/replay, effective date, dan siapa mengesahkan hasil.
- **Opsi:** (A) export manual untuk review tanpa auto-score; (B) one-way signed/versioned API event dari KM ke HR; (C) integrasi dua arah yang dapat memengaruhi score/workflow.
- **Rekomendasi audit:** mulai A, lalu B hanya setelah completion resmi stabil dan field contract disahkan; hindari integrasi dua arah sebelum ownership/koreksi/audit matang.
- **Fitur terblokir:** automatic performance credit, HR competency update, learning record sync, dashboard gabungan, dan workflow feedback dari HR ke KM.
- **Trigger pembukaan mission:** data contract/versioning, identity key, purpose/legal review, score governance, error/retry/idempotency, reconciliation, access/retention, serta sandbox HRIS tersedia dan disetujui.

## KM-DEC-013 — Reward Nyata dan Redemption

- **Owner:** HRGA, Finance, Procurement, Legal/Tax, Product Owner KM, dan Internal Audit.
- **Keputusan yang dibutuhkan:** jenis reward, funding/budget, eligibility, stock, approval, nilai poin, expiry, pajak, fraud control, fulfillment, cancellation, dan reversal.
- **Opsi:** (A) recognition-only tanpa nilai ekonomis; (B) katalog non-cash dengan stock dan approval; (C) cash/cash-equivalent redemption.
- **Rekomendasi audit:** pertahankan A sampai point ledger authoritative dan governance disetujui; bila menuju B/C, desain sebagai transaksi debit/credit teraudit, bukan pengurangan langsung saldo aggregate.
- **Fitur terblokir:** reward catalog, stock/reservation, redemption approval, fulfillment, poin debit, cancellation/refund/reversal, dan notification reward.
- **Trigger pembukaan mission:** policy reward ditandatangani seluruh owner, budget/stock owner ada, ledger siap, approval/status matrix dan tax/legal treatment jelas, fraud/race controls serta reconciliation UAT disetujui.

## KM-DEC-014 — Retensi, Legal Hold, dan Penghapusan Data KM

- **Owner:** Legal, Compliance/Document Control, DPO, Information Security, dan IT Operations.
- **Keputusan yang dibutuhkan:** masa simpan serta metode archive/delete/anonymize untuk file versi, approval audit, insight, read/view event, assignment/compliance, notification, export, dan backup; termasuk legal hold.
- **Opsi:** (A) simpan tanpa batas; (B) satu periode retensi untuk semua data KM; (C) retention schedule per record type/classification dengan legal hold dan auditable disposal.
- **Rekomendasi audit:** pilih C; file bisnis, audit, social content, telemetry, dan notification memiliki tujuan/risiko berbeda dan tidak semestinya memakai satu periode default.
- **Fitur terblokir:** automated archive/purge/anonymization, file version cleanup, event partitioning, notification cleanup, subject-data deletion workflow, dan storage capacity policy.
- **Trigger pembukaan mission:** retention schedule per record type disahkan, legal hold/release flow dan owner tersedia, backup propagation serta evidence-of-disposal ditetapkan, dan dry-run report pada staging diterima sebelum delete job diaktifkan.
