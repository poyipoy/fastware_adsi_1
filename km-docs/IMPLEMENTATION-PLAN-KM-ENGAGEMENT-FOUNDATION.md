# IMPLEMENTATION PLAN: KM Engagement Foundation

## Source of Truth

Mission aktif adalah `km-docs/MISSION-KM-ENGAGEMENT-FOUNDATION.md`. Spesifikasi implementasi rinci berada pada `plan-refactor-km.md`; dokumen ini menjadi pasangan governance-nya dan menjelaskan batas eksekusi yang mengalahkan instruksi commit/migration di plan tersebut.

Keputusan bisnis berasal dari `km-docs/APPROVED-DECISIONS-KM.md`, khususnya KM-DEC-003 sampai KM-DEC-009. `km-docs/PENDING-DECISIONS-KM.md` tidak diperlakukan sebagai backlog.

## Override Sesi

- Jangan commit, push, membuat branch, atau membuka PR.
- Jangan menjalankan migration Laravel atau SQL terhadap database aplikasi lokal.
- Test database hanya boleh digunakan ketika `APP_ENV=testing`, driver MySQL aktif, dan nama database berakhiran `_testing`.
- Migration dan SQL manual tetap dibuat serta diverifikasi secara statis/test-schema; urutan eksekusi aktual dicatat sebagai pekerjaan deployment nanti.
- Perubahan non-KM pada worktree adalah milik pengguna dan tidak boleh disentuh.

## Urutan Baku

1. Verifikasi fase 0, baseline route/build/test, dan inventory perubahan lokal.
2. Fase 1: audit reference lalu stabilization cleanup.
3. Fase 2: perbaikan layout dan konsistensi halaman analytics popular.
4. Fase 3: migration notification, model/service/controller/routes, hook approval, shell UI, dan tests.
5. Fase 4: migration progress, request/service/controller/query, viewer/dashboard UI, dan tests.
6. Fase 5: migration social insight, model/policy/service/request/routes, modal tunggal/UI, rate limiter, dan tests.
7. Fase 6: migration ledger, model/service/hooks/command/query leaderboard/UI, opening balance, reconciliation, dan tests.
8. Fase 7: SLA calculation/reminder service, lazy sweep, command/schedule, approval query/view/sort, dan tests.
9. Fase 8: update schema harness, lint/format/build/route/view/targeted/full-suite verification tanpa commit.
10. Fase 9: SQL manual, deployment manifest/script, execution note, daftar migration tertunda, rollout, dan rollback.

## Schema dan Deployment Gate

- Migration dibuat additive dan legacy-aware dengan `Schema::hasTable()`/`Schema::hasColumn()` serta nama index/constraint eksplisit.
- Foreign key/unique hanya dibuat setelah preflight duplicate/orphan/type bersih; SQL manual harus berhenti dengan bukti bila target schema berbeda.
- `user_job_positions` yang tersedia saat ini tidak effective-dated. Ledger menyimpan snapshot department pada waktu award dan boleh memakai `users.section` hanya sebagai fallback sementara. Production rollout tetap memerlukan validasi coverage master organisasi.
- Kode aplikasi baru dipublikasikan hanya setelah seluruh migration group berhasil pada target; deployment tidak boleh meninggalkan code ledger/progress aktif pada schema parsial.
- Rollback aplikasi lebih diutamakan daripada drop data additive yang sudah dipakai. Database rollback production memerlukan backup dan persetujuan terpisah.

## Verification Matrix

- Stabilization: reference scan, autoload/route boot, model regression.
- Notification: ownership, unread count, event matrix, after-commit behavior, duplicate event key.
- Progress: validation, monotonic bitmap, active-time cap, multi-request replay, completed no-op, gated completion.
- Insight: document access, depth, mention eligibility, edit/delete window, moderator reason, reaction uniqueness, featured mutex/limit.
- Ledger: opening balance, award/replay, transaction rollback, append-only guard, reconcile, department snapshot/cohort.
- SLA: working-day boundaries, reminder/overdue thresholds, repeated sweep, recipient allow-list, query sort/badge.
- UI: shell notification keyboard/focus states, dashboard progress/resume, single insight modal, leaderboard toggle, analytics shell, mobile/desktop smoke.

