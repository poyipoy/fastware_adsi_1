-- =========================================================================
-- MODUL HR FASTWARE - SQL EXPORT
-- Created At: 2026-07-31 10:12:46
-- Database Source: dms_adasi_rev1
-- =========================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;

-- TYPE: SCHEMA ONLY (15 TABLES, NO DATA)

-- Schema Tabel: `mst_departments`
DROP TABLE IF EXISTS `mst_departments`;
CREATE TABLE `mst_departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nama departemen',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mst_departments_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema Tabel: `mst_sections`
DROP TABLE IF EXISTS `mst_sections`;
CREATE TABLE `mst_sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nama section',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mst_sections_department_id_name_unique` (`department_id`,`name`),
  CONSTRAINT `mst_sections_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `mst_departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema Tabel: `mst_job_positions`
DROP TABLE IF EXISTS `mst_job_positions`;
CREATE TABLE `mst_job_positions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `position_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nama posisi, unik',
  `department_id` bigint unsigned DEFAULT NULL,
  `section_id` bigint unsigned DEFAULT NULL,
  `job_level` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_key_position` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mst_job_positions_position_name_unique` (`position_name`),
  KEY `mst_job_positions_department_id_foreign` (`department_id`),
  KEY `mst_job_positions_section_id_foreign` (`section_id`),
  CONSTRAINT `mst_job_positions_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `mst_departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mst_job_positions_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `mst_sections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema Tabel: `mst_position_approvals`
DROP TABLE IF EXISTS `mst_position_approvals`;
CREATE TABLE `mst_position_approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `position_id` bigint unsigned NOT NULL,
  `approval_level` tinyint unsigned NOT NULL COMMENT 'Level approval: 1 = Section Head, 2 = Dept Head, 3 = Div Head',
  `approver_position_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_position_approval_level` (`position_id`,`approval_level`),
  KEY `mst_position_approvals_approver_position_id_foreign` (`approver_position_id`),
  CONSTRAINT `mst_position_approvals_approver_position_id_foreign` FOREIGN KEY (`approver_position_id`) REFERENCES `mst_job_positions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mst_position_approvals_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `mst_job_positions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=436 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema Tabel: `user_job_positions`
DROP TABLE IF EXISTS `user_job_positions`;
CREATE TABLE `user_job_positions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `mst_job_position_id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `effective_from` date DEFAULT NULL,
  `effective_until` date DEFAULT NULL,
  `assignment_source` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'legacy',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `km_user_job_positions_effective_index` (`user_id`,`is_active`,`effective_from`,`effective_until`),
  KEY `km_user_job_positions_position_effective_index` (`mst_job_position_id`,`is_active`,`effective_from`,`effective_until`),
  CONSTRAINT `user_job_positions_mst_job_position_id_foreign` FOREIGN KEY (`mst_job_position_id`) REFERENCES `mst_job_positions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_job_positions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema Tabel: `mst_pd_active_years`
DROP TABLE IF EXISTS `mst_pd_active_years`;
CREATE TABLE `mst_pd_active_years` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mst_pd_active_years_year_unique` (`year`),
  KEY `mst_pd_active_years_updated_by_foreign` (`updated_by`),
  CONSTRAINT `mst_pd_active_years_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema Tabel: `mst_tcs`
DROP TABLE IF EXISTS `mst_tcs`;
CREATE TABLE `mst_tcs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `id_job_position` int NOT NULL,
  `id_poin_kategori` int NOT NULL,
  `keterangan_tc` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `sub_kategori` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deskripsi_tc` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nilai` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Schema Tabel: `mst_soft_skills`
DROP TABLE IF EXISTS `mst_soft_skills`;
CREATE TABLE `mst_soft_skills` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `id_job_position` int NOT NULL,
  `id_poin_kategori` int NOT NULL,
  `keterangan_sk` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi_sk` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nilai` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Schema Tabel: `mst_additionals`
DROP TABLE IF EXISTS `mst_additionals`;
CREATE TABLE `mst_additionals` (
  `id` bigint(20) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `id_job_position` int DEFAULT NULL,
  `id_poin_kategori` int DEFAULT NULL,
  `keterangan_ad` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deskripsi_ad` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nilai` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Schema Tabel: `tc_poin_kategoris`
DROP TABLE IF EXISTS `tc_poin_kategoris`;
CREATE TABLE `tc_poin_kategoris` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul_keterangan` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi_1` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deskripsi_2` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deskripsi_3` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deskripsi_4` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Schema Tabel: `working_experiences`
DROP TABLE IF EXISTS `working_experiences`;
CREATE TABLE `working_experiences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `year_start` smallint unsigned NOT NULL,
  `year_end` smallint unsigned DEFAULT NULL COMMENT 'NULL = masih menjabat (Present)',
  `job_position` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `section` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departemen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `working_experiences_user_id_year_start_index` (`user_id`,`year_start`),
  CONSTRAINT `working_experiences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema Tabel: `mst_pd_pengajuans`
DROP TABLE IF EXISTS `mst_pd_pengajuans`;
CREATE TABLE `mst_pd_pengajuans` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `id_role` int DEFAULT NULL,
  `id_job_position` bigint unsigned DEFAULT NULL,
  `id_user` int DEFAULT NULL,
  `section_id` bigint unsigned DEFAULT NULL,
  `id_tc` int DEFAULT NULL,
  `id_sk` int DEFAULT NULL,
  `id_ad` int DEFAULT NULL,
  `id_trs` int DEFAULT NULL,
  `program_training` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `program_training_plan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kategori_competency` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `competency` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `due_date_plan` date DEFAULT NULL,
  `lembaga` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lembaga_plan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `keterangan_tujuan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `keterangan_plan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `keterangan_tolak` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `biaya` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `biaya_plan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tahun_aktual` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tahun_usulan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `relevansi` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alasan_relevansi` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rekomendasi` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alasan_rekomendasi` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kelengkapan_materi` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `metode_pengajaran` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fasilitas` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lainnya_1` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `metode_evaluasi` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `minat` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `daya_serap` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `penerapan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lainnya_2` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `diketahui` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dievaluasi` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tgl_pengajuan` date DEFAULT NULL,
  `tgl_konfirm` date DEFAULT NULL,
  `lokasi` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `efektif` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catatan_tambahan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_1` int DEFAULT NULL,
  `status_2` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_sharing_knowledge` tinyint(1) NOT NULL DEFAULT '0',
  `objective_learning` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `objective_learning_aktual` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modified_at` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `modified_updated` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Schema Tabel: `mst_pd_pengajuan_participants`
DROP TABLE IF EXISTS `mst_pd_pengajuan_participants`;
CREATE TABLE `mst_pd_pengajuan_participants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `people_development_id` bigint NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pd_participants_parent_user_unique` (`people_development_id`,`user_id`),
  KEY `pd_participants_user_index` (`user_id`),
  CONSTRAINT `pd_participants_parent_fk` FOREIGN KEY (`people_development_id`) REFERENCES `mst_pd_pengajuans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pd_participants_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema Tabel: `trs_penilaian_tcs`
DROP TABLE IF EXISTS `trs_penilaian_tcs`;
CREATE TABLE `trs_penilaian_tcs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_job_position` bigint unsigned DEFAULT NULL,
  `id_tc` int DEFAULT NULL,
  `id_sk` int DEFAULT NULL,
  `id_ad` int DEFAULT NULL,
  `id_user` int DEFAULT NULL,
  `nilai_tc` int DEFAULT NULL,
  `nilai_sk` int DEFAULT NULL,
  `nilai_ad` int DEFAULT NULL,
  `total_nilai` int DEFAULT NULL,
  `status` int NOT NULL,
  `tahun_penilaian` smallint unsigned NOT NULL DEFAULT '2026' COMMENT 'Tahun periode penilaian',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'True jika data tahun lama terkunci read-only',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modified_at` int DEFAULT NULL,
  `modified_updated` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trs_penilaian_tcs_tahun_penilaian_is_locked_index` (`tahun_penilaian`,`is_locked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Schema Tabel: `detail_penilaian_tcs`
DROP TABLE IF EXISTS `detail_penilaian_tcs`;
CREATE TABLE `detail_penilaian_tcs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `id_job_position` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `keterangan_detail` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `keterangan_sebelum` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catatan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modified_at` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nilai_sebelum` text COLLATE utf8mb4_general_ci COMMENT 'JSON snapshot nilai BEFORE perubahan',
  `corrected_by_role` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'section_head|dept_head — siapa yang koreksi post-approval',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=723 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


SET FOREIGN_KEY_CHECKS = 1;
