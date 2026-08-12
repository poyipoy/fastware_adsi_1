<?php

namespace App\Services\Warehouse;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use RuntimeException;

class WarehouseResetBackupService
{
    /**
     * @param  array<int, string>  $tables
     * @param  array<string, int>  $counts
     * @return array{path: string, manifest_path: string, sha256: string, bytes: int}
     */
    public function create(array $tables, array $counts): array
    {
        $database = (string) config('database.connections.mysql.database');
        $connection = (array) config('database.connections.mysql');
        $binary = $this->resolveBinary();
        $directory = storage_path('app/warehouse-reset-backups');

        if (! File::isDirectory($directory) && ! File::makeDirectory($directory, 0700, true)) {
            throw new RuntimeException('Direktori backup Warehouse tidak dapat dibuat.');
        }

        $stamp = now(config('app.timezone', 'Asia/Jakarta'))->format('Ymd-His');
        $base = $directory.DIRECTORY_SEPARATOR.'warehouse-before-reset-'.$stamp;
        $sqlPath = $base.'.sql';
        $manifestPath = $base.'.json';

        $arguments = [
            $binary,
            '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
            '--port='.(string) ($connection['port'] ?? '3306'),
            '--user='.(string) ($connection['username'] ?? ''),
            '--single-transaction',
            '--skip-lock-tables',
            '--result-file='.$sqlPath,
            $database,
            ...$tables,
        ];

        $process = new Process(
            $arguments,
            base_path(),
            ['MYSQL_PWD' => (string) ($connection['password'] ?? '')],
            null,
            180,
        );
        $process->run();

        if (! $process->isSuccessful()) {
            $error = trim($process->getErrorOutput());
            throw new RuntimeException('Backup SQL Warehouse gagal: '.($error !== '' ? $error : 'mysqldump exit code '.$process->getExitCode()));
        }

        if (! File::isFile($sqlPath) || (int) File::size($sqlPath) <= 0) {
            throw new RuntimeException('Backup SQL Warehouse kosong atau tidak terbentuk.');
        }

        $sha256 = hash_file('sha256', $sqlPath);
        if (! is_string($sha256) || $sha256 === '') {
            throw new RuntimeException('Checksum backup SQL Warehouse tidak dapat dibuat.');
        }

        $sql = File::get($sqlPath);
        foreach ($tables as $table) {
            if (! str_contains($sql, '`'.$table.'`')) {
                throw new RuntimeException(sprintf('Backup SQL tidak memuat tabel Warehouse %s.', $table));
            }
        }

        $manifest = [
            'database' => $database,
            'created_at' => now(config('app.timezone', 'Asia/Jakarta'))->toIso8601String(),
            'tables' => $tables,
            'counts_before' => $counts,
            'sql_path' => $sqlPath,
            'sha256' => $sha256,
            'bytes' => (int) File::size($sqlPath),
        ];

        if (File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
            throw new RuntimeException('Manifest backup SQL Warehouse tidak dapat ditulis.');
        }

        return [
            'path' => $sqlPath,
            'manifest_path' => $manifestPath,
            'sha256' => $sha256,
            'bytes' => (int) File::size($sqlPath),
        ];
    }

    private function resolveBinary(): string
    {
        $configured = trim((string) env('WAREHOUSE_MYSQLDUMP_BINARY', ''));
        if ($configured !== '' && File::isFile($configured)) {
            return $configured;
        }

        $whereCommand = PHP_OS_FAMILY === 'Windows' ? 'where.exe' : 'which';
        $where = new Process([$whereCommand, 'mysqldump'], base_path(), null, null, 10);
        $where->run();
        if ($where->isSuccessful()) {
            $resolved = trim((string) strtok($where->getOutput(), "\r\n"));
            if ($resolved !== '' && File::isFile($resolved)) {
                return $resolved;
            }
        }

        $candidates = [
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];
        foreach ($candidates as $candidate) {
            if (File::isFile($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('Binary mysqldump tidak ditemukan. Atur WAREHOUSE_MYSQLDUMP_BINARY atau pasang client MySQL.');
    }
}
