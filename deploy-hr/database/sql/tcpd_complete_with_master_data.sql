-- =========================================================================
-- MODUL HR FASTWARE - SQL EXPORT
-- Created At: 2026-07-31 10:12:46
-- Database Source: dms_adasi_rev1
-- =========================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;


INSERT INTO `users` (`id`, `role_id`, `name`, `section`, `npk`, `username`, `password`, `pass`, `fcm_token`, `email`, `telp`, `km_total_poin`, `file`, `file_name`, `is_active`, `created_at`, `updated_at`) VALUES
(126, 27, 'RAIHAN GILANG RAMADHAN', 'Accounting', 0, 'GILANG', '$2y$12$99HYbhE8D.oJEFsltNVZ5eOdE5vbwZ83JJ54vhjVXmWvqdNNRdU5y', '12345', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-07-01 06:30:25', '2026-07-03 10:49:18');

-- TYPE: COMPLETE SCHEMA (15 TABLES) + MASTER DATA CONTENTS

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

-- Data Master untuk tabel `mst_departments` (6 baris)
INSERT INTO `mst_departments` (`id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
('19', 'Finance, Accounting & HRGA', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('20', 'PDCA, Inventory, Procurement & IT', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('21', 'Sales Region 1 & 2', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('22', 'Sales Region 3 & 4', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('23', 'Logistic & Warehouse', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('24', 'Production', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17');

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

-- Data Master untuk tabel `mst_sections` (15 baris)
INSERT INTO `mst_sections` (`id`, `department_id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
('66', '19', 'Finance', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('67', '19', 'Accounting', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('68', '19', 'HRGA', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('69', '20', 'PDCA & Procurement Local', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('70', '20', 'Procurement Import & Inventory', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('71', '20', 'IT', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('72', '21', 'Sales Region 1', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('73', '21', 'Sales Region 2', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('74', '22', 'Sales Region 3', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('75', '22', 'Sales Region 4', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('76', '23', 'Logistic & Warehouse', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('77', '24', 'Production Cutting', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('78', '24', 'Production Heat Treatment', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('79', '24', 'Production MC & Machining Custom', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17'),
('80', '24', 'Technical Support QC & Maintenance', '1', '2026-07-03 17:00:17', '2026-07-03 17:00:17');

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

-- Data Master untuk tabel `mst_job_positions` (52 baris)
INSERT INTO `mst_job_positions` (`id`, `position_name`, `department_id`, `section_id`, `job_level`, `is_active`, `is_key_position`, `created_at`, `updated_at`) VALUES
('2', 'Accounting Staff', '19', '67', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('3', 'Admin Cutting Sheet (ACS)', '23', '76', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('5', 'Bubut Operator', '24', '79', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('8', 'Cutting Leader', '24', '77', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('9', 'Cutting Operator', '24', '77', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('10', 'Delivery Staff', '23', '76', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('13', 'Feeder Operator', '23', '76', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('14', 'Finance Sec Head', '19', '66', 'sec_head', '1', '0', '2026-06-30 10:06:25', '2026-07-04 14:21:54'),
('15', 'Finance Staff', '19', '66', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('16', 'Heat Treatment Operator', '24', '78', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('17', 'HRGA Staff', '19', '68', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('18', 'HT Admin', '24', '78', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('19', 'HT Leader', '24', '78', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('20', 'HT Operator', '24', '78', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('22', 'Inventory Staff', '20', '70', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('25', 'IT Staff', '20', '71', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('27', 'Logistic & Warehouse Sec Head', '23', '76', 'sec_head', '1', '0', '2026-06-30 10:06:25', '2026-07-04 14:21:54'),
('32', 'Maintenance Operator', '24', '80', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('33', 'MC Custom Leader', '24', '79', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('34', 'MC Custom Operator', '24', '79', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('36', 'MC Custom Staff', '24', '79', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('38', 'MC Leader', '24', '79', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('39', 'MC Operator', '24', '79', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('40', 'PPC Staff', '23', '76', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('41', 'Procurement Staff', '20', '69', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('43', 'Production Dept Head', '24', NULL, 'dept_head', '1', '1', '2026-06-30 10:06:25', '2026-07-04 14:21:54'),
('45', 'QC Operator', '24', '80', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('46', 'Sales Admin Region 1', '21', '72', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-04 18:23:44'),
('50', 'Sales Engineer Region 1', '21', '72', 'staff', '1', '1', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('51', 'Sales Engineer Region 2', '21', '73', 'staff', '1', '1', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('52', 'Sales Engineer Region 3', '22', '74', 'staff', '1', '1', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('53', 'Sales Engineer Region 4', '22', '75', 'staff', '1', '1', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('54', 'Sales Office Head Region 1', '21', '72', 'sec_head', '1', '0', '2026-06-30 10:06:25', '2026-07-04 14:21:54'),
('56', 'Sales Office Head Region 2', '21', '73', 'sec_head', '1', '0', '2026-06-30 10:06:25', '2026-07-04 14:21:54'),
('60', 'Warehouse Staff', '23', '76', 'staff', '1', '0', '2026-06-30 10:06:25', '2026-07-03 17:00:17'),
('62', 'Finance Accounting Sec Head', '19', '66', 'sec_head', '1', '0', '2026-07-01 13:15:05', '2026-07-04 14:21:54'),
('63', 'Finance, Accounting & HRGA Dept.Head', '19', NULL, 'dept_head', '1', '1', '2026-07-01 13:15:05', '2026-07-04 14:21:54'),
('64', 'HRGA & Legal Staff', '19', '68', 'staff', '1', '0', '2026-07-01 13:15:05', '2026-07-03 17:00:17'),
('65', 'PDCA Proc Inv IT Dept Head', '20', NULL, 'dept_head', '1', '1', '2026-07-01 13:15:05', '2026-07-04 14:21:54'),
('66', 'Logistic & Warehouse Foreman', '23', '76', 'staff', '1', '0', '2026-07-01 13:15:05', '2026-07-03 17:00:17'),
('67', 'Machining & MC Custom Sec Head', '24', '79', 'sec_head', '1', '1', '2026-07-01 13:15:05', '2026-07-04 14:21:54'),
('71', 'Cutting Foreman', '24', '77', 'staff', '1', '0', '2026-07-01 13:15:05', '2026-07-03 17:00:17'),
('75', 'Sales Dept Head Region 1&2', '21', NULL, 'dept_head', '1', '1', '2026-07-01 13:15:05', '2026-07-04 14:21:54'),
('76', 'Sales Dept. Head Region 3&4', '22', NULL, 'dept_head', '1', '1', '2026-07-01 13:15:05', '2026-07-04 14:21:54'),
('79', 'Production Cutting Sect. Head', '24', '77', 'sec_head', '1', '0', '2026-07-03 16:31:21', '2026-07-04 14:21:54'),
('80', 'Production Heat Treatment  Sect. Head', '24', '78', 'sec_head', '1', '0', '2026-07-03 16:31:21', '2026-07-04 14:21:54'),
('81', 'Technical Support QC & Maintenance  Sect. Head', '24', '80', 'sec_head', '1', '0', '2026-07-03 16:31:21', '2026-07-04 14:21:54'),
('82', 'Sales Div Head', NULL, NULL, 'div_head', '1', '0', '2026-07-03 16:31:21', '2026-07-04 14:21:54'),
('83', 'Logistic & Warehouses Dept Head', '23', NULL, 'dept_head', '1', '1', '2026-07-03 16:31:21', '2026-07-04 14:21:54'),
('84', 'Sales Admin Region 2', '21', '73', 'staff', '1', '0', '2026-07-04 18:23:22', '2026-07-04 18:23:22'),
('86', 'Key Account Management', NULL, NULL, 'staff', '1', '1', '2026-07-08 21:29:08', '2026-07-08 21:29:08'),
('87', 'Business Development', NULL, NULL, 'staff', '1', '1', '2026-07-08 21:29:29', '2026-07-08 21:29:29');

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

-- Data Master untuk tabel `mst_position_approvals` (107 baris)
INSERT INTO `mst_position_approvals` (`id`, `position_id`, `approval_level`, `approver_position_id`, `created_at`, `updated_at`) VALUES
('288', '3', '1', '27', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('289', '3', '2', '83', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('290', '5', '1', '67', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('291', '5', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('292', '8', '1', '79', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('293', '8', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('294', '9', '1', '79', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('295', '9', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('296', '10', '1', '27', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('297', '10', '2', '83', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('298', '13', '1', '27', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('299', '13', '2', '83', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('300', '14', '2', '63', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('303', '16', '1', '80', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('304', '16', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('306', '18', '1', '80', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('307', '18', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('308', '19', '1', '80', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('309', '19', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('310', '20', '1', '80', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('311', '20', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('314', '27', '2', '83', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('315', '32', '1', '81', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('316', '32', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('317', '33', '1', '67', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('318', '33', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('319', '34', '1', '67', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('320', '34', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('321', '36', '1', '67', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('322', '36', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('323', '38', '1', '67', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('324', '38', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('325', '39', '1', '67', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('326', '39', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('327', '40', '1', '27', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('328', '40', '2', '83', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('330', '45', '1', '81', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('331', '45', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('333', '50', '1', '54', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('334', '50', '2', '75', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('335', '51', '1', '56', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('336', '51', '2', '75', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('339', '54', '2', '75', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('340', '56', '2', '75', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('341', '60', '1', '27', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('342', '60', '2', '83', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('343', '62', '2', '63', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('345', '66', '1', '27', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('346', '66', '2', '83', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('347', '67', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('348', '71', '1', '79', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('349', '71', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('350', '79', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('351', '80', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('352', '81', '2', '43', '2026-07-03 17:19:31', '2026-07-03 17:19:31'),
('353', '2', '0', NULL, '2026-07-03 17:25:49', '2026-07-03 17:25:49'),
('354', '2', '1', '62', '2026-07-03 17:25:49', '2026-07-03 17:25:49'),
('355', '2', '2', '63', '2026-07-03 17:25:49', '2026-07-03 17:25:49'),
('356', '2', '3', NULL, '2026-07-03 17:25:49', '2026-07-03 17:25:49'),
('357', '25', '0', NULL, '2026-07-03 17:36:29', '2026-07-03 17:36:29'),
('358', '25', '1', '65', '2026-07-03 17:36:29', '2026-07-03 17:36:29'),
('359', '25', '2', '65', '2026-07-03 17:36:29', '2026-07-03 17:36:29'),
('360', '25', '3', NULL, '2026-07-03 17:36:29', '2026-07-03 17:36:29'),
('361', '22', '0', NULL, '2026-07-03 17:36:46', '2026-07-03 17:36:46'),
('362', '22', '1', '65', '2026-07-03 17:36:46', '2026-07-03 17:36:46'),
('363', '22', '2', '65', '2026-07-03 17:36:46', '2026-07-03 17:36:46'),
('364', '22', '3', NULL, '2026-07-03 17:36:46', '2026-07-03 17:36:46'),
('365', '41', '0', NULL, '2026-07-03 17:41:13', '2026-07-03 17:41:13'),
('366', '41', '1', '65', '2026-07-03 17:41:13', '2026-07-03 17:41:13'),
('367', '41', '2', '65', '2026-07-03 17:41:13', '2026-07-03 17:41:13'),
('368', '41', '3', NULL, '2026-07-03 17:41:13', '2026-07-03 17:41:13'),
('369', '15', '0', '14', '2026-07-04 15:29:23', '2026-07-04 15:29:23'),
('370', '15', '1', '62', '2026-07-04 15:29:23', '2026-07-04 15:29:23'),
('371', '15', '2', '63', '2026-07-04 15:29:23', '2026-07-04 15:29:23'),
('372', '15', '3', NULL, '2026-07-04 15:29:23', '2026-07-04 15:29:23'),
('380', '84', '0', NULL, '2026-07-04 18:23:22', '2026-07-04 18:23:22'),
('381', '84', '1', '56', '2026-07-04 18:23:22', '2026-07-04 18:23:22'),
('382', '84', '2', '75', '2026-07-04 18:23:22', '2026-07-04 18:23:22'),
('383', '84', '3', '82', '2026-07-04 18:23:22', '2026-07-04 18:23:22'),
('384', '46', '0', NULL, '2026-07-04 18:23:44', '2026-07-04 18:23:44'),
('385', '46', '1', '54', '2026-07-04 18:23:44', '2026-07-04 18:23:44'),
('386', '46', '2', '75', '2026-07-04 18:23:44', '2026-07-04 18:23:44'),
('387', '46', '3', '82', '2026-07-04 18:23:44', '2026-07-04 18:23:44'),
('388', '52', '0', NULL, '2026-07-06 17:18:48', '2026-07-06 17:18:48'),
('389', '52', '1', '76', '2026-07-06 17:18:48', '2026-07-06 17:18:48'),
('390', '52', '2', '76', '2026-07-06 17:18:48', '2026-07-06 17:18:48'),
('391', '52', '3', '82', '2026-07-06 17:18:48', '2026-07-06 17:18:48'),
('392', '53', '0', NULL, '2026-07-06 17:19:05', '2026-07-06 17:19:05'),
('393', '53', '1', '76', '2026-07-06 17:19:05', '2026-07-06 17:19:05'),
('394', '53', '2', '76', '2026-07-06 17:19:05', '2026-07-06 17:19:05'),
('395', '53', '3', '82', '2026-07-06 17:19:05', '2026-07-06 17:19:05'),
('396', '64', '0', NULL, '2026-07-06 23:44:42', '2026-07-06 23:44:42'),
('397', '64', '1', '63', '2026-07-06 23:44:42', '2026-07-06 23:44:42'),
('398', '64', '2', '63', '2026-07-06 23:44:42', '2026-07-06 23:44:42'),
('399', '64', '3', NULL, '2026-07-06 23:44:43', '2026-07-06 23:44:43'),
('400', '17', '0', NULL, '2026-07-06 23:44:57', '2026-07-06 23:44:57'),
('401', '17', '1', '63', '2026-07-06 23:44:58', '2026-07-06 23:44:58'),
('402', '17', '2', '63', '2026-07-06 23:44:58', '2026-07-06 23:44:58'),
('403', '17', '3', NULL, '2026-07-06 23:44:58', '2026-07-06 23:44:58'),
('408', '86', '0', NULL, '2026-07-08 21:29:08', '2026-07-08 21:29:08');
INSERT INTO `mst_position_approvals` (`id`, `position_id`, `approval_level`, `approver_position_id`, `created_at`, `updated_at`) VALUES
('409', '86', '1', NULL, '2026-07-08 21:29:08', '2026-07-08 21:29:08'),
('410', '86', '2', NULL, '2026-07-08 21:29:08', '2026-07-08 21:29:08'),
('411', '86', '3', NULL, '2026-07-08 21:29:08', '2026-07-08 21:29:08'),
('412', '87', '0', NULL, '2026-07-08 21:29:29', '2026-07-08 21:29:29'),
('413', '87', '1', NULL, '2026-07-08 21:29:29', '2026-07-08 21:29:29'),
('414', '87', '2', NULL, '2026-07-08 21:29:29', '2026-07-08 21:29:29'),
('415', '87', '3', NULL, '2026-07-08 21:29:29', '2026-07-08 21:29:29');

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

-- Data Master untuk tabel `user_job_positions` (98 baris)
INSERT INTO `user_job_positions` (`id`, `user_id`, `mst_job_position_id`, `is_active`, `effective_from`, `effective_until`, `assignment_source`, `created_at`, `updated_at`) VALUES
('1', '42', '62', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('2', '53', '2', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('3', '86', '14', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('4', '43', '15', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('5', '82', '15', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('6', '126', '2', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('7', '77', '63', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('8', '80', '17', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('9', '91', '64', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('10', '23', '25', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('11', '108', '41', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('12', '117', '22', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('13', '70', '65', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('14', '9', '13', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('15', '13', '10', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('16', '15', '10', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('17', '16', '13', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('18', '21', '13', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('19', '8', '3', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('20', '22', '66', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('21', '28', '60', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('22', '29', '3', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('23', '102', '27', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('24', '84', '67', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('25', '50', '5', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('26', '66', '36', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('27', '67', '34', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('28', '68', '5', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('29', '69', '33', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('30', '76', '34', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('31', '78', '34', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('32', '90', '34', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('33', '40', '5', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('34', '19', '39', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('35', '20', '39', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('36', '30', '39', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('37', '35', '39', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('38', '38', '39', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('39', '41', '38', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('40', '81', '16', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('41', '46', '43', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('42', '25', '79', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('43', '25', '80', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('44', '25', '81', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('45', '32', '16', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('46', '2', '9', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('47', '3', '9', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('48', '4', '16', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('49', '5', '9', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('50', '6', '9', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('51', '10', '8', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('52', '12', '40', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('53', '14', '40', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('54', '17', '9', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('55', '18', '9', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('56', '24', '9', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('57', '26', '9', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('58', '27', '9', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('59', '33', '9', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('60', '34', '71', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('61', '36', '8', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('62', '37', '45', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('63', '7', '32', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('64', '47', '20', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('65', '49', '19', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('66', '56', '20', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('67', '58', '18', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('68', '71', '18', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('69', '73', '32', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('70', '79', '20', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('71', '85', '20', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('72', '88', '20', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('73', '101', '19', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('74', '59', '82', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('75', '59', '83', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('76', '72', '54', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('77', '99', '75', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('78', '65', '56', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('79', '51', '52', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('80', '45', '76', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('81', '74', '46', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('82', '52', '51', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('83', '62', '51', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('84', '89', '50', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('88', '92', '51', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('89', '95', '50', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('91', '96', '50', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('92', '104', '51', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('93', '54', '53', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('94', '116', '51', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('95', '100', '53', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('96', '112', '52', '1', '2026-07-03', NULL, 'legacy', '2026-07-03 16:31:22', '2026-07-03 16:31:22'),
('97', '57', '84', '1', '2026-07-04', NULL, 'legacy', '2026-07-04 18:25:54', '2026-07-04 18:25:54'),
('98', '60', '84', '1', '2026-07-04', NULL, 'legacy', '2026-07-04 18:25:54', '2026-07-04 18:25:54'),
('99', '61', '84', '1', '2026-07-04', NULL, 'legacy', '2026-07-04 18:25:54', '2026-07-04 18:25:54'),
('100', '83', '84', '1', '2026-07-04', NULL, 'legacy', '2026-07-04 18:25:54', '2026-07-04 18:25:54'),
('105', '42', '15', '1', '2026-07-16', NULL, 'legacy', '2026-07-16 11:46:13', '2026-07-16 11:46:13'),
('106', '102', '15', '1', '2026-07-16', NULL, 'legacy', '2026-07-16 11:51:05', '2026-07-16 11:51:05');

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

-- Data Master untuk tabel `mst_pd_active_years` (2 baris)
INSERT INTO `mst_pd_active_years` (`id`, `year`, `is_active`, `updated_by`, `created_at`, `updated_at`) VALUES
('1', '2027', '0', '1', '2026-07-09 00:36:30', '2026-07-14 15:53:05'),
('2', '2026', '1', '91', '2026-07-14 15:53:05', '2026-07-15 16:31:12');

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

-- Tabel `mst_tcs` tidak memiliki data.

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

-- Tabel `mst_soft_skills` tidak memiliki data.

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

-- Tabel `mst_additionals` tidak memiliki data.

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

-- Data Master untuk tabel `tc_poin_kategoris` (3 baris)
INSERT INTO `tc_poin_kategoris` (`id`, `judul_keterangan`, `deskripsi_1`, `deskripsi_2`, `deskripsi_3`, `deskripsi_4`, `created_at`, `updated_at`) VALUES
('1', 'Description Skill of Process Plant', 'Pengoperasian Machine', 'Dapat melakukan setting material', 'Dapat melakukan QC awal', 'Dapat melakukan TPM', '2024-09-18 18:14:02', '2026-06-29 10:59:49'),
('2', 'Description Skill of Process Office & Quality', 'Mampu melakukan tetapi diawasi atasan', 'Dapat melakukan dengan pengawasan minimal', 'Dapat melakukan tanpa bimbingan', 'Mampu membantu melatih yang lain', '2024-09-18 18:14:02', '2026-06-29 10:59:49'),
('3', 'Description Skill of EHS', 'Mengerti', 'Mengerti dan mampu menjelaskan', 'Mampu mengajarkan ', 'Mampu mengimplementasi', '2024-09-18 18:15:08', '2026-06-29 10:59:49');

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

START TRANSACTION;

UPDATE `users`
SET `section` = CASE `id`
    WHEN 2 THEN 'Production Cutting'
    WHEN 3 THEN 'Production Cutting'
    WHEN 4 THEN 'Production Heat Treatment'
    WHEN 5 THEN 'Production Cutting'
    WHEN 6 THEN 'Production Cutting'
    WHEN 8 THEN 'Logistic & Warehouse'
    WHEN 9 THEN 'Logistic & Warehouse'
    WHEN 10 THEN 'Production Cutting'
    WHEN 11 THEN 'Technical Support, QC & Maintenance'
    WHEN 12 THEN 'Logistic & Warehouse'
    WHEN 13 THEN 'Logistic & Warehouse'
    WHEN 14 THEN 'Logistic & Warehouse'
    WHEN 15 THEN 'Logistic & Warehouse'
    WHEN 16 THEN 'Logistic & Warehouse'
    WHEN 17 THEN 'Production Cutting'
    WHEN 18 THEN 'Production Cutting'
    WHEN 21 THEN 'Logistic & Warehouse'
    WHEN 22 THEN 'Logistic & Warehouse'
    WHEN 23 THEN 'IT'
    WHEN 24 THEN 'Production Cutting'
    WHEN 25 THEN 'Production Cutting; Production Heat Treatment; Technical Support QC & Maintenance'
    WHEN 26 THEN 'Production Cutting'
    WHEN 27 THEN 'Production Cutting'
    WHEN 28 THEN 'Logistic & Warehouse'
    WHEN 29 THEN 'Logistic & Warehouse'
    WHEN 32 THEN 'Production Heat Treatment'
    WHEN 33 THEN 'Production Cutting'
    WHEN 34 THEN 'Production Cutting'
    WHEN 36 THEN 'Production Cutting'
    WHEN 42 THEN 'Finance'
    WHEN 43 THEN 'Finance'
    WHEN 45 THEN 'Sales Region 3 & 4'
    WHEN 46 THEN 'Production'
    WHEN 47 THEN 'Production Heat Treatment'
    WHEN 49 THEN 'Production Heat Treatment'
    WHEN 51 THEN 'Sales Region 3'
    WHEN 52 THEN 'Sales Region 2'
    WHEN 53 THEN 'Accounting'
    WHEN 54 THEN 'Sales Region 4'
    WHEN 56 THEN 'Production Heat Treatment'
    WHEN 57 THEN 'Sales Region 2'
    WHEN 58 THEN 'Production Heat Treatment'
    WHEN 59 THEN 'Logistic & Warehouse'
    WHEN 60 THEN 'Sales Region 2'
    WHEN 61 THEN 'Sales Region 2'
    WHEN 62 THEN 'Sales Region 2'
    WHEN 65 THEN 'Sales Region 2'
    WHEN 66 THEN 'Production MC & Machining Custom'
    WHEN 70 THEN 'PDCA, Inventory, Procurement & IT'
    WHEN 71 THEN 'Production Heat Treatment'
    WHEN 72 THEN 'Sales Region 1'
    WHEN 74 THEN 'Sales Region 1'
    WHEN 77 THEN 'Finance, Accounting & HRGA'
    WHEN 78 THEN 'Production MC & Machining Custom'
    WHEN 79 THEN 'Production Heat Treatment'
    WHEN 80 THEN 'HRGA'
    WHEN 81 THEN 'Production Heat Treatment'
    WHEN 82 THEN 'Finance'
    WHEN 83 THEN 'Sales Region 2'
    WHEN 85 THEN 'Production Heat Treatment'
    WHEN 86 THEN 'Finance'
    WHEN 88 THEN 'Production Heat Treatment'
    WHEN 89 THEN 'Sales Region 1'
    WHEN 91 THEN 'HRGA'
    WHEN 92 THEN 'Sales Region 2'
    WHEN 94 THEN 'Heat Treatment'
    WHEN 95 THEN 'Sales Region 1'
    WHEN 96 THEN 'Sales Region 1'
    WHEN 99 THEN 'Sales Region 1 & 2'
    WHEN 100 THEN 'Sales Region 4'
    WHEN 101 THEN 'Production Heat Treatment'
    WHEN 102 THEN 'Logistic & Warehouse'
    WHEN 104 THEN 'Sales Region 2'
    WHEN 107 THEN 'Sales Region I'
    WHEN 108 THEN 'PDCA & Procurement Local'
    WHEN 112 THEN 'Sales Region 3'
    WHEN 116 THEN 'Sales Region 2'
    WHEN 117 THEN 'Procurement Import & Inventory'
    ELSE `section`
END
WHERE `id` IN (2, 3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 21, 22, 23, 24, 25, 26, 27, 28, 29, 32, 33, 34, 36, 42, 43, 45, 46, 47, 49, 51, 52, 53, 54, 56, 57, 58, 59, 60, 61, 62, 65, 66, 70, 71, 72, 74, 77, 78, 79, 80, 81, 82, 83, 85, 86, 88, 89, 91, 92, 94, 95, 96, 99, 100, 101, 102, 104, 107, 108, 112, 116, 117);

SELECT ROW_COUNT() AS `affected_users`;

COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
