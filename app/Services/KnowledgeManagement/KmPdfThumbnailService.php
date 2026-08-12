<?php

namespace App\Services\KnowledgeManagement;

use App\Models\KmPengajuan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class KmPdfThumbnailService
{
    public function probeCapability(): ?string
    {
        if (! config('knowledge_management.thumbnail.enabled', false)) {
            return 'Pembuatan thumbnail dinonaktifkan.';
        }

        $process = new Process([
            (string) config('knowledge_management.thumbnail.binary', 'pdftoppm'),
            '-v',
        ]);
        $process->setTimeout(min(10, (int) config('knowledge_management.thumbnail.timeout', 30)));

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            return 'Pemeriksaan pdftoppm melewati batas waktu.';
        } catch (\Throwable) {
            return 'Binary pdftoppm tidak dapat dijalankan.';
        }

        if (! $process->isSuccessful()) {
            return 'Binary pdftoppm tidak tersedia atau mengembalikan exit code '.($process->getExitCode() ?? -1).'.';
        }

        return null;
    }

    public function sourceValidationError(string $sourcePath): ?string
    {
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            return 'File sumber tidak tersedia.';
        }

        $size = filesize($sourcePath);
        $maximum = (int) config('knowledge_management.thumbnail.max_file_bytes', 52_428_800);
        if ($size === false || $size <= 0) {
            return 'File sumber kosong atau tidak dapat dibaca.';
        }
        if ($size > $maximum) {
            return 'Ukuran file sumber melebihi batas thumbnail.';
        }

        return null;
    }

    public function generate(KmPengajuan $document, string $sourcePath): string
    {
        if (($sourceError = $this->sourceValidationError($sourcePath)) !== null) {
            throw new RuntimeException($sourceError);
        }

        $timeout = (int) config('knowledge_management.thumbnail.timeout', 30);
        $disk = (string) config('knowledge_management.thumbnail.disk', 'km_private');
        $tempDir = storage_path('app/temp/km_thumb_'.Str::random(20));

        if (! is_dir($tempDir) && ! mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
            throw new RuntimeException('Temporary directory thumbnail tidak dapat dibuat.');
        }

        $outputPrefix = $tempDir.DIRECTORY_SEPARATOR.'thumb';

        try {
            $process = new Process([
                (string) config('knowledge_management.thumbnail.binary', 'pdftoppm'),
                '-r',
                (string) config('knowledge_management.thumbnail.scale', 150),
                '-f',
                '1',
                '-l',
                '1',
                '-singlefile',
                '-png',
                $sourcePath,
                $outputPrefix,
            ]);
            $process->setTimeout($timeout);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException(
                    'Konversi PDF gagal dengan exit code '.($process->getExitCode() ?? -1).'. '
                    .$this->sanitizeReason($process->getErrorOutput()),
                );
            }

            $pngPath = $outputPrefix.'.png';
            $imageInfo = is_file($pngPath) ? @getimagesize($pngPath) : false;
            if ($imageInfo === false || ($imageInfo[2] ?? null) !== IMAGETYPE_PNG) {
                throw new RuntimeException('pdftoppm tidak menghasilkan PNG yang valid.');
            }

            $targetPath = 'thumbnails/'.$document->getKey().'/'.Str::uuid().'.png';
            $stream = fopen($pngPath, 'rb');
            if ($stream === false) {
                throw new RuntimeException('PNG hasil konversi tidak dapat dibaca.');
            }

            try {
                $stored = Storage::disk($disk)->writeStream($targetPath, $stream, ['visibility' => 'private']);
            } finally {
                fclose($stream);
            }

            if (! $stored) {
                throw new RuntimeException('PNG thumbnail tidak dapat disimpan.');
            }

            return $targetPath;
        } finally {
            $this->cleanTempDirectory($tempDir);
        }
    }

    public function deleteThumbnail(?string $path): void
    {
        if ($this->isSafeThumbnailPath($path)) {
            Storage::disk((string) config('knowledge_management.thumbnail.disk', 'km_private'))->delete($path);
        }
    }

    public function isSafeThumbnailPath(?string $path, ?int $documentId = null): bool
    {
        if (! is_string($path) || $path === '') {
            return false;
        }

        $idPattern = $documentId === null
            ? '[1-9][0-9]*'
            : preg_quote((string) $documentId, '#');
        $uuidPattern = '[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}';
        $legacyPattern = '[A-Za-z0-9]{32}';
        $versionPattern = 'versions/[1-9][0-9]*';

        return preg_match(
            '#^thumbnails/'.$idPattern.'/(?:'.$uuidPattern.'|'.$legacyPattern.'|'.$versionPattern.')\.png$#',
            $path,
        ) === 1;
    }

    public function sanitizeReason(string $reason): string
    {
        $safe = preg_replace('/[^\x20-\x7E]/', '', $reason) ?? '';

        return mb_substr(trim($safe), 0, 500);
    }

    private function cleanTempDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$entry;
            if (is_dir($path)) {
                $this->cleanTempDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
