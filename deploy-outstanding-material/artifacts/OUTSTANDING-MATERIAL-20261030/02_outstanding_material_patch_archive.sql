-- 1. Tambahkan kolom archived_at dan indeks pada tabel header invoice
ALTER TABLE `outstanding_material_invoices` 
ADD COLUMN `archived_at` timestamp NULL DEFAULT NULL AFTER `document_review_required`,
ADD INDEX `om_invoices_archived_at_idx` (`archived_at`);

-- 2. Tambahkan kolom archived_at dan indeks pada tabel detail material
ALTER TABLE `outstanding_materials` 
ADD COLUMN `archived_at` timestamp NULL DEFAULT NULL AFTER `keterangan`,
ADD INDEX `om_materials_archived_at_idx` (`archived_at`),
ADD INDEX `om_delay_eta_wh_idx` (`estimasi_delay_eta_warehouse`);