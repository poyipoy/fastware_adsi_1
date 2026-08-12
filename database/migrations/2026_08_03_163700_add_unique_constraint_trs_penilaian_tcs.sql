-- ============================================================================
-- SQL SCRIPT: Clean Duplicates & Add UNIQUE Constraints on trs_penilaian_tcs
-- File: database/migrations/2026_08_03_163700_add_unique_constraint_trs_penilaian_tcs.sql
-- ============================================================================

-- STEP 1: Hapus data duplikat untuk Technical Competency (id_tc)
-- Mempertahankan baris dengan ID paling awal (t2.id < t1.id)
DELETE t1 FROM trs_penilaian_tcs t1
INNER JOIN trs_penilaian_tcs t2 
    ON t1.id_user = t2.id_user
   AND t1.id_job_position = t2.id_job_position
   AND t1.tahun_penilaian = t2.tahun_penilaian
   AND t1.id_tc = t2.id_tc
   AND t1.id_tc IS NOT NULL
   AND t1.id > t2.id;

-- STEP 2: Hapus data duplikat untuk Soft Skill (id_sk)
DELETE t1 FROM trs_penilaian_tcs t1
INNER JOIN trs_penilaian_tcs t2 
    ON t1.id_user = t2.id_user
   AND t1.id_job_position = t2.id_job_position
   AND t1.tahun_penilaian = t2.tahun_penilaian
   AND t1.id_sk = t2.id_sk
   AND t1.id_sk IS NOT NULL
   AND t1.id > t2.id;

-- STEP 3: Hapus data duplikat untuk Additional Competency (id_ad)
DELETE t1 FROM trs_penilaian_tcs t1
INNER JOIN trs_penilaian_tcs t2 
    ON t1.id_user = t2.id_user
   AND t1.id_job_position = t2.id_job_position
   AND t1.tahun_penilaian = t2.tahun_penilaian
   AND t1.id_ad = t2.id_ad
   AND t1.id_ad IS NOT NULL
   AND t1.id > t2.id;

-- STEP 4: Tambahkan UNIQUE Index / Constraints ke tabel trs_penilaian_tcs
ALTER TABLE `trs_penilaian_tcs` 
ADD CONSTRAINT `uq_penilaian_tc` UNIQUE (`id_user`, `id_job_position`, `tahun_penilaian`, `id_tc`),
ADD CONSTRAINT `uq_penilaian_sk` UNIQUE (`id_user`, `id_job_position`, `tahun_penilaian`, `id_sk`),
ADD CONSTRAINT `uq_penilaian_ad` UNIQUE (`id_user`, `id_job_position`, `tahun_penilaian`, `id_ad`);
