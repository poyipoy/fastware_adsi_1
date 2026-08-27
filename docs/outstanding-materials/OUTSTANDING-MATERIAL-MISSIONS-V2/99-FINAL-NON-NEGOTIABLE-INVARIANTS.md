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

# FINAL NON-NEGOTIABLE INVARIANTS

Seluruh mission dianggap gagal apabila salah satu kondisi berikut terjadi:

1. Index masih mengirim Edit/Delete untuk manager.
2. Invoice detail mandatory scope masih dapat dioverride dari request.
3. `material_ids` invoice lain dapat ikut bulk update.
4. Administrator non-Ilyas dapat upload PL/MTC.
5. User non-Sales dapat download PL/MTC melalui direct URL.
6. Active Sales mendapat akses create/edit/delete/import/update/upload.
7. Legacy viewer kehilangan akses melihat modul.
8. Upload satu jenis dokumen mengosongkan jenis dokumen lain.
9. File lama dihapus sebelum transaction berhasil.
10. File yang masih direferensikan row lain ikut terhapus.
11. Material baru atau pindah invoice membawa document path dari invoice yang salah.
12. Route spesifik ditempatkan setelah wildcard dan mengalami collision.
13. Agent mengklaim test atau QA berhasil tanpa menjalankannya.
14. Agent menjalankan test mutasi pada database non-testing.

