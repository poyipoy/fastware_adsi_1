<?php
/**
 * Script untuk menghasilkan file SQL Dump Modul HR:
 * 1. hr_complete_with_master_data.sql -> Schema 15 tabel + Isi data master
 * 2. hr_schema_only_15_tables.sql     -> Schema 15 tabel (tanpa data)
 * 3. hr_master_data_only_insert.sql   -> Hanya INSERT data master
 */

$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    die("ERROR: File .env tidak ditemukan di: " . $envPath . "\n");
}

$env = parse_ini_file($envPath);
$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$dbName = $env['DB_DATABASE'] ?? 'dms_adasi_rev1';
$user = $env['DB_USERNAME'] ?? 'root';
$pass = $env['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("ERROR: Gagal koneksi ke database {$dbName}: " . $e->getMessage() . "\n");
}

$allTables = [
    'mst_departments',
    'mst_sections',
    'mst_job_positions',
    'mst_position_approvals',
    'user_job_positions',
    'mst_pd_active_years',
    'mst_tcs',
    'mst_soft_skills',
    'mst_additionals',
    'tc_poin_kategoris',
    'working_experiences',
    'mst_pd_pengajuans',
    'mst_pd_pengajuan_participants',
    'trs_penilaian_tcs',
    'detail_penilaian_tcs'
];

$masterTables = [
    'mst_departments',
    'mst_sections',
    'mst_job_positions',
    'mst_position_approvals',
    'user_job_positions',
    'mst_pd_active_years',
    'mst_tcs',
    'mst_soft_skills',
    'mst_additionals',
    'tc_poin_kategoris'
];

$sqlDir = __DIR__ . '/database/sql';
if (!is_dir($sqlDir)) {
    mkdir($sqlDir, 0777, true);
}

$header = "-- =========================================================================\n" .
          "-- MODUL HR FASTWARE - SQL EXPORT\n" .
          "-- Created At: " . date('Y-m-d H:i:s') . "\n" .
          "-- Database Source: {$dbName}\n" .
          "-- =========================================================================\n\n" .
          "SET FOREIGN_KEY_CHECKS = 0;\n" .
          "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n" .
          "SET NAMES utf8mb4;\n\n";

$footer = "\nSET FOREIGN_KEY_CHECKS = 1;\n";

// Helper: Generate CREATE TABLE SQL
function getCreateTableSql(PDO $pdo, string $table): ?string {
    try {
        $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
        $row = $stmt->fetch();
        if ($row && isset($row['Create Table'])) {
            return "DROP TABLE IF EXISTS `{$table}`;\n" . $row['Create Table'] . ";\n\n";
        }
    } catch (Exception $e) {
        echo "WARNING: Tabel `{$table}` tidak ditemukan di DB.\n";
    }
    return null;
}

// Helper: Generate INSERT INTO SQL
function getInsertSql(PDO $pdo, string $table): string {
    try {
        $stmt = $pdo->query("SELECT * FROM `{$table}`");
        $rows = $stmt->fetchAll();
        if (empty($rows)) {
            return "-- Tabel `{$table}` tidak memiliki data.\n\n";
        }

        $sql = "-- Data Master untuk tabel `{$table}` (" . count($rows) . " baris)\n";
        $columns = array_keys($rows[0]);
        $colNames = implode(", ", array_map(fn($col) => "`{$col}`", $columns));

        $chunks = array_chunk($rows, 100);
        foreach ($chunks as $chunk) {
            $sql .= "INSERT INTO `{$table}` ({$colNames}) VALUES\n";
            $valuesList = [];
            foreach ($chunk as $row) {
                $vals = [];
                foreach ($columns as $col) {
                    $val = $row[$col];
                    if ($val === null) {
                        $vals[] = "NULL";
                    } else {
                        $vals[] = $pdo->quote((string)$val);
                    }
                }
                $valuesList[] = "(" . implode(", ", $vals) . ")";
            }
            $sql .= implode(",\n", $valuesList) . ";\n";
        }
        return $sql . "\n";
    } catch (Exception $e) {
        return "-- Gagal mengambil data `{$table}`: " . $e->getMessage() . "\n\n";
    }
}

// 1. Generate Schema Only (15 tables)
$schemaOnlyContent = $header . "-- TYPE: SCHEMA ONLY (15 TABLES, NO DATA)\n\n";
foreach ($allTables as $t) {
    $createSql = getCreateTableSql($pdo, $t);
    if ($createSql) {
        $schemaOnlyContent .= "-- Schema Tabel: `{$t}`\n" . $createSql;
    }
}
$schemaOnlyContent .= $footer;
file_put_contents($sqlDir . '/hr_schema_only_15_tables.sql', $schemaOnlyContent);
echo "Generated: database/sql/hr_schema_only_15_tables.sql\n";

// 2. Generate Complete With Master Data (15 tables schema + Master tables data)
$completeContent = $header . "-- TYPE: COMPLETE SCHEMA (15 TABLES) + MASTER DATA CONTENTS\n\n";
foreach ($allTables as $t) {
    $createSql = getCreateTableSql($pdo, $t);
    if ($createSql) {
        $completeContent .= "-- Schema Tabel: `{$t}`\n" . $createSql;
    }
    if (in_array($t, $masterTables)) {
        $completeContent .= getInsertSql($pdo, $t);
    }
}
$completeContent .= $footer;
file_put_contents($sqlDir . '/hr_complete_with_master_data.sql', $completeContent);
echo "Generated: database/sql/hr_complete_with_master_data.sql\n";

// 3. Generate Master Data Only Insert
$masterDataOnlyContent = $header . "-- TYPE: MASTER DATA INSERTS ONLY (NO CREATE TABLE)\n\n";
foreach ($masterTables as $t) {
    $masterDataOnlyContent .= getInsertSql($pdo, $t);
}
$masterDataOnlyContent .= $footer;
file_put_contents($sqlDir . '/hr_master_data_only_insert.sql', $masterDataOnlyContent);
echo "Generated: database/sql/hr_master_data_only_insert.sql\n";

echo "SUCCESS: Seluruh SQL Dump berhasil dibuat di {$sqlDir}\n";
