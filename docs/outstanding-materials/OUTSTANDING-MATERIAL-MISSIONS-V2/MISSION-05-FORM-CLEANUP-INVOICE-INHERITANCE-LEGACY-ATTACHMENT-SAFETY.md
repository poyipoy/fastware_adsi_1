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

# MISSION 05
## Form Cleanup, Invoice Inheritance & Legacy Attachment Safety

## DEPENDENCY

MISSION 04 sudah selesai.

Invoice page sudah menjadi jalur upload dokumen.

## OBJECTIVE

Menghapus upload Packing List/MTC dari form material dan menjaga konsistensi dokumen saat:

- Material dibuat.
- Material diedit.
- Invoice material dipindah.
- Material dihapus.
- Material baru ditambahkan dari invoice detail.

## FILE TARGET

```text
app/Http/Controllers/OutstandingMaterialController.php
resources/views/outstanding_materials/form.blade.php
routes/web.php
```

## REMOVE FORM FIELDS

Hapus dari form create/edit:

```text
Packing List
MTC
```

Hapus:

```text
input file
current file display
validation error khusus field tersebut
related JavaScript
```

Keterangan tetap ada.

## REMOVE MATERIAL FORM VALIDATION

Dari `validatedPayload()` hapus:

```text
packing_list validation
mtc validation
packing_list storage
mtc storage
```

Generic legacy `attachment` tidak perlu dihapus jika masih mungkin dipakai jalur lama.

Current implementation memiliki coupling legacy antara `attachment_path` dan `packing_list_path`. Hapus hanya handling Packing List/MTC dari form material. Jangan membawa coupling itu ke endpoint invoice baru, dan jangan melakukan refactor generic attachment tanpa bukti bahwa sudah tidak digunakan.

## PRESERVE EXISTING PATHS ON EDIT

Material edit biasa tidak boleh:

- Mengosongkan `packing_list_path`.
- Mengosongkan `mtc_path`.
- Menghapus file.
- Mengganti dokumen invoice.

Dokumen hanya diubah melalui invoice document endpoint.

## ADD MATERIAL FROM DETAIL

Tombol dari MISSION 03 membawa:

```text
invoice_context=<anchor material id>
```

Pada `create()`:

1. Resolve anchor material.
2. Ambil invoice number.
3. Kirim ke form sebagai locked context.
4. Render Number Invoice sebagai readonly.
5. Sertakan hidden `invoice_context_id`.

Pada `store()`:

- Jika context valid, gunakan invoice dari anchor.
- Jangan hanya mempercayai input `number_invoice`.
- Manager tetap dapat membuat material global jika form dibuka tanpa context.

## DOCUMENT INHERITANCE ON CREATE

Jika material baru dibuat ke invoice yang sudah memiliki dokumen:

- Inherit `packing_list_path`.
- Inherit `mtc_path`.
- Jangan membuat upload baru.
- Jangan membuat copy file fisik.

Pilih source path secara deterministik dari invoice.

Jika invoice memiliki legacy path inconsistency:

- Jangan memilih secara diam-diam.
- Gunakan path yang sudah dinormalisasi oleh workflow baru jika tersedia.
- Jika tidak dapat dipastikan, simpan null dan catat warning log atau laporan.

## INVOICE CHANGE ON EDIT

Jika `number_invoice` berubah:

1. Jangan membawa dokumen invoice lama ke invoice baru.
2. Cari dokumen existing pada destination invoice.
3. Jika destination memiliki dokumen konsisten:
   - Set row ke destination paths.
4. Jika destination tidak memiliki dokumen:
   - Set row paths menjadi null.
5. Jangan menghapus file source invoice jika masih direferensikan row lain.
6. Jangan mengubah row lain tanpa kebutuhan.

Gunakan transaction apabila update melibatkan penyesuaian path.

## SAFE DESTROY

Current delete behavior berisiko karena dokumen invoice disimpan pada banyak row.

Saat satu material dihapus:

1. Catat path row.
2. Hapus row atau soft-delete sesuai model.
3. Setelah berhasil, cek apakah path masih direferensikan row lain.
4. Hapus file hanya jika reference count nol.
5. Jangan null-kan dokumen row lain.
6. Jangan menghapus file invoice yang masih memiliki material lain.

Buat helper reusable, misalnya:

```php
private function deleteStoredAttachmentIfUnreferenced(
    ?string $path
): void;
```

Reference check harus mempertimbangkan:

```text
attachment_path
packing_list_path
mtc_path
```

## FORM UI

Jika invoice context terkunci:

- Input `number_invoice` readonly.
- Tampilkan info bahwa material akan masuk ke invoice tersebut.
- Back button kembali ke detail invoice anchor.

Jika tanpa context:

- Input tetap dapat diedit sesuai behavior existing.
- Back button mengikuti flow existing.

## TESTS

Minimal:

- Form tidak memiliki field Packing List.
- Form tidak memiliki field MTC.
- Store berhasil tanpa file.
- Update berhasil tanpa file.
- Existing paths tidak hilang saat edit field lain.
- Create from invoice context mengunci invoice.
- Tampered invoice input tidak mengalahkan context anchor.
- Material baru mewarisi document paths invoice.
- Global create tetap bekerja.
- Pindah invoice tidak membawa file invoice lama.
- Pindah ke invoice yang memiliki dokumen mewarisi destination paths.
- Destroy satu row tidak menghapus shared invoice file.
- Destroy row terakhir menghapus file jika tidak direferensikan tempat lain.
- Legacy `attachment_path` tidak rusak.

## ACCEPTANCE CRITERIA

- Upload PL/MTC hilang dari form.
- Form submit tetap berhasil.
- Invoice context bekerja.
- Document paths konsisten saat create/edit/delete.
- Shared files tidak terhapus salah.
- Tidak ada mutation dokumen melalui form material.
- Test mission lulus.

## FINAL REPORT

```text
1. Fields Removed
2. Validation Changes
3. Invoice Context Flow
4. Document Inheritance Rules
5. Invoice Move Handling
6. Safe Delete Handling
7. Tests Executed
8. Remaining Legacy Risks
```

## STOP CONDITION

Berhenti setelah form cleanup dan consistency selesai.

Jangan mengerjakan MISSION 06.

---
