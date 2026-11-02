# IMPLEMENTATION PLAN: PER-MATERIAL ARCHIVE (PARTIAL INVOICE ARCHIVING)
## Fastware Outstanding Material — Revision: Per-Material Archive & Hybrid Invoice Group Tracking

**Repository:** `poyipoy/fastware_adsi_1`  
**Module:** Outstanding Material  
**Technology Stack:** Laravel 10.10, PHP 8.2+, MySQL 8.0, Blade, Bootstrap 5.3.2, jQuery, DataTables (Server-Side)  
**Document Status:** `PROPOSED IMPLEMENTATION PLAN — PENDING USER APPROVAL`  
**Delivery Path:** `docs/outstanding-materials/GATE-3-PER-MATERIAL-ARCHIVE-PLAN.md`  
**Revision Date:** 2026-10-15  

---

## 1. Executive Summary & Business Intent

### Current Behavior (Gate 1 & Gate 2)
The archive mechanism was strictly **all-or-nothing at the invoice level**:
- An invoice could be archived **only** when *every single material* in the canonical invoice group had `status = 'Received'` and `keterangan = 'Closed'`.
- If an invoice contained 10 items and 9 were Received & Closed, but 1 item was still On Shipment / Delay, the 9 completed items could not be archived and continued to clutter the active Material Index and invoice counts.

### Target Behavior (Per-Material Archive)
1. **Material-Level Archiving:**
   - Any individual material that is **`Received`** and **`Closed`** can be archived independently, without waiting for other materials in the same invoice.
2. **Hybrid / Partial Invoice Tracking:**
   - **Active Invoice List (`/outstanding-materials/show-based-on-invoice`):**
     If an invoice has partial active materials (e.g. 6 active out of 10), the invoice **still appears in the active list**, but its `Total Row` reflects only the remaining **6 active materials** (`COUNT = 6`), and its status badges, latest ETA, and urgency tier are computed solely from those active materials.
   - **Archived Invoices List (`/outstanding-materials/archived-invoices`):**
     The invoice **also appears in the archived list**, recording and displaying the **4 archived materials** (`COUNT = 4`).
   - **Active Material Index (`/outstanding-materials`):**
     Archived materials are immediately removed from active operational monitoring, DataTables rows, and KPI calculation.
   - **Complete Invoice Archiving:**
     When all materials of an invoice have been archived, the invoice completely disappears from the Active Invoice List and resides solely in the Archived Invoices list.
3. **UI Integration (Update Materials Modal):**
   - In `#invoiceUpdateModal` (as shown in the user screenshot), users can select rows and archive them directly using a dedicated **"Arsipkan Terpilih"** action, or update them to `Received` + `Closed` and archive them in a single workflow.
4. **Restoration:**
   - Materials can be restored individually from the archived invoice detail or the entire invoice group can be restored from the Archived Invoices list.

---

## 2. Architecture & Data Model

### 2.1 Database Schema Modification
Add `archived_at` directly to `outstanding_materials`:

```sql
ALTER TABLE `outstanding_materials`
    ADD COLUMN `archived_at` TIMESTAMP NULL DEFAULT NULL AFTER `keterangan`,
    ADD INDEX `om_materials_archived_at_idx` (`archived_at`);
```

**Migration File:**  
`database/migrations/2026_10_15_000002_add_archived_at_to_outstanding_materials_table.php`

**Data Synchronization Rule:**
During migration execution, any material whose parent invoice header already has `archived_at IS NOT NULL` will be synchronized:
```sql
UPDATE `outstanding_materials`
JOIN `outstanding_material_invoices` ON `outstanding_material_invoices.id` = `outstanding_materials.invoice_id`
SET `outstanding_materials.archived_at` = `outstanding_material_invoices.archived_at`
WHERE `outstanding_material_invoices.archived_at` IS NOT NULL
  AND `outstanding_materials.archived_at` IS NULL;
```

---

## 3. Detailed Implementation Phases

### Phase 1: Database Migration & Model Scopes
1. **Create Migration:**
   - Minimal DDL adding `archived_at` (nullable timestamp) and index `om_materials_archived_at_idx` to `outstanding_materials`.
   - Backward compatible: `down()` cleanly drops column and index.
2. **Update `app/Models/OutstandingMaterial.php`:**
   - Add `'archived_at' => 'datetime'` to `$casts`.
   - Add `isArchived(): bool` helper (`return $this->archived_at !== null;`).
   - Update `scopeUnarchived(Builder $query)`:
     ```php
     public function scopeUnarchived(Builder $query): Builder
     {
         return $query->whereNull($this->qualifyColumn('archived_at'));
     }
     ```
   - Update `scopeArchived(Builder $query)`:
     ```php
     public function scopeArchived(Builder $query): Builder
     {
         return $query->whereNotNull($this->qualifyColumn('archived_at'));
     }
     ```
3. **Update `app/Models/OutstandingMaterialInvoice.php`:**
   - Keep `archived_at` as the header-level marker indicating all materials in the invoice are archived.

---

### Phase 2: Service Layer Methods (`OutstandingMaterialInvoiceService`)
Extend [`OutstandingMaterialInvoiceService`](file:///c:/laragon/www/fastware_adsi_1/app/Services/OutstandingMaterialInvoiceService.php):

1. **`archiveMaterials(array $materialIds, User|int|null $user = null): array`:**
   - Execute inside `DB::transaction()`.
   - Lock selected materials with `lockForUpdate()`.
   - Validate each material:
     - Must exist.
     - Must have `status = 'Received'`.
     - Must have `keterangan = 'Closed'`.
     - Must not already be archived (`archived_at IS NULL`).
   - Set `archived_at = now()` and `updated_by = $userId`.
   - Check the parent invoice header:
     - If all remaining active materials in the invoice group are now archived, mark `outstanding_material_invoices.archived_at = now()`.
   - Return count of archived materials and affected invoice keys.

2. **`restoreMaterials(array $materialIds, User|int|null $user = null): array`:**
   - Inside transaction under lock:
   - Set `archived_at = NULL` and `updated_by = $userId`.
   - Reset parent header `outstanding_material_invoices.archived_at = NULL` since the invoice now has active materials again.

3. **Update `archiveInvoiceGroup(OutstandingMaterial $anchor, ...)`:**
   - In addition to marking the header `archived_at = now()`, also batch update all materials in the group:
     `$materials->each->update(['archived_at' => $now])`.

4. **Update `restoreInvoiceGroup(OutstandingMaterial $anchor, ...)`:**
   - Reset `archived_at = NULL` on header AND on all child materials in the group.

5. **Mutation Assertions:**
   - In `assertNotArchived()`, check both header and individual material: if a material has `archived_at IS NOT NULL`, reject mutations on it with HTTP 422.

---

### Phase 3: Query Builder & DataTables Synchronization
In [`OutstandingMaterialController.php`](file:///c:/laragon/www/fastware_adsi_1/app/Http/Controllers/OutstandingMaterialController.php):

1. **Active Invoice List (`invoiceBaseQuery()`):**
   - Query: `OutstandingMaterial::query()->unarchived()->leftJoin(...)`
   - Groups materials having `outstanding_materials.archived_at IS NULL`.
   - Result:
     - An invoice with 10 total materials (4 archived, 6 active) will show `material_count = 6`.
     - Priority tiers, status badges, and latest ETA are calculated purely from the 6 active materials.
     - `is_archivable = 1` if all remaining 6 active materials are `Received` + `Closed`.
     - Invoices with 0 active materials are naturally excluded from the active invoice list!

2. **Archived Invoices List (`archivedInvoiceBaseQuery()`):**
   - Query: `OutstandingMaterial::query()->archived()->leftJoin(...)`
   - Groups materials having `outstanding_materials.archived_at IS NOT NULL`.
   - Result:
     - The invoice appears in the archived list with `material_count = 4` (the 4 archived materials).
     - Status shows `Received`, keterangan shows `Closed`.
     - `archived_at` shows the most recent archive timestamp.

3. **Invoice Materials for Modal (`invoiceMaterialsForAnchor()`):**
   - When opening `#invoiceUpdateModal` from the active invoice list:
     - Show active materials (`whereNull('archived_at')`) so users manage pending rows.
     - Add an indicator if user wants to see already archived rows.

4. **Invoice Detail View (`invoiceDetailData()`):**
   - Support viewing active materials by default, with filter/indicator for archived materials.

---

### Phase 4: Controller Endpoints & Actions
In `routes/web.php` and `OutstandingMaterialController`:

1. **New Route:**
   `POST /outstanding-materials/materials/archive` (`outstanding-materials.materials.archive`)
   - Payload: `{ material_ids: [1, 2, 3] }`
   - Controller method: `archiveMaterials(Request $request)`
   - Calls `invoiceService->archiveMaterials()`.
   - Returns JSON response with success message and count.

2. **New Route:**
   `POST /outstanding-materials/materials/restore` (`outstanding-materials.materials.restore`)
   - Payload: `{ material_ids: [1, 2, 3] }`
   - Calls `invoiceService->restoreMaterials()`.

3. **Update Existing Modal Endpoint (`updateInvoiceFieldsForAnchor`):**
   - Accept optional flag `'archive_closed' => 'boolean'`.
   - If user updates status to `Received` and keterangan to `Closed` with `archive_closed = true`, update fields AND archive those materials in one atomic transaction!

---

## 4. Verification & Testing Matrix

### Automated Tests to Implement:
1. **`test_single_and_batch_material_archive_succeeds_when_received_and_closed()`**:
   - Verify material with `status = 'Received'` and `keterangan = 'Closed'` can be archived.
   - Verify `archived_at` is set on the material record.
2. **`test_material_archive_fails_if_status_not_received_or_keterangan_not_closed()`**:
   - Verify rejection with 422 if material is still `On Shipment` or keterangan is `Delay`.
3. **`test_partial_invoice_appears_in_both_active_and_archived_lists()`**:
   - Create invoice with 5 materials: 2 archived (`Received` + `Closed`), 3 active (`On Shipment`).
   - Active list returns invoice with `material_count = 3`.
   - Archived list returns invoice with `material_count = 2`.
4. **`test_fully_archived_invoice_disappears_from_active_list()`**:
   - When all 5 materials are archived, invoice count in active list = 0 (invoice omitted from active list).
   - Invoice appears solely in archived list with `material_count = 5`.
5. **`test_material_restore_returns_material_to_active_invoice()`**:
   - Restoring a material resets `archived_at = NULL` and brings it back to the active list.
6. **`test_mutation_protection_rejects_modifying_archived_material()`**:
   - Direct edit, delete, or reassign on an archived material is rejected with HTTP 422.
7. **`test_regression_existing_81_tests_pass()`**:
   - Run full regression suite to guarantee zero regression on ETA sorting, documents, and legacy identity key handling.

---

## 5. Execution Steps Checklist

- [ ] **Step 1:** Create migration `2026_10_15_000002_add_archived_at_to_outstanding_materials_table.php` and run on testing DB.
- [ ] **Step 2:** Update model `OutstandingMaterial.php` (casts, helpers, scopes).
- [ ] **Step 3:** Extend `OutstandingMaterialInvoiceService.php` (`archiveMaterials()`, `restoreMaterials()`, update group methods).
- [ ] **Step 4:** Update `OutstandingMaterialController.php` (routes, endpoints, `invoiceBaseQuery`, `archivedInvoiceBaseQuery`).
- [ ] **Step 5:** Enhance `#invoiceUpdateModal` in `resources/views/outstanding_materials/invoice.blade.php` and detail views.
- [ ] **Step 6:** Update `resources/views/outstanding_materials/archived_invoices.blade.php`.
- [ ] **Step 7:** Write comprehensive automated tests in `tests/Feature/OutstandingMaterialPerMaterialArchiveTest.php`.
- [ ] **Step 8:** Run all automated tests, Pint code formatting, and Vite production build.
