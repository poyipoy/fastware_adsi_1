<?php

namespace App\Services\KnowledgeManagement;

use App\Enums\KnowledgeManagement\KmProcessingStatus;
use App\Enums\KnowledgeManagement\KmThumbnailStatus;
use App\Exceptions\KnowledgeManagement\KmDocumentInfectedException;
use App\Models\KmDocumentVersion;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class KmDocumentProcessingService
{
    public function process(KmDocumentVersion $version): KmDocumentVersion
    {
        $temporaryDirectory = $this->temporaryDirectory($version);
        File::ensureDirectoryExists($temporaryDirectory, 0700, true);

        try {
            $original = $this->verifiedOriginalPath($version);
            $this->scanForViruses($original);
            $version->forceFill(['antivirus_status' => 'clean'])->save();
            $pdf = $this->normalizeToPdf($version, $original, $temporaryDirectory);
            $this->assertPdf($pdf);
            $text = $this->extractText($pdf, $temporaryDirectory);
            $pageCount = $this->pageCount($pdf);
            if (mb_strlen(trim($text)) < 20) {
                $text = $this->ocr($pdf, $temporaryDirectory);
            }
            $thumbnail = $this->thumbnail($pdf, $temporaryDirectory);
            $output = $this->persistOutputs($version, $pdf, $thumbnail);
            $thumbnailPath = $output['thumbnail_path'];
            unset($output['thumbnail_path']);

            $version->forceFill([
                ...$output,
                'page_count' => $pageCount,
                'extracted_text' => trim($text) !== '' ? trim($text) : null,
                'processing_status' => KmProcessingStatus::READY,
                'antivirus_status' => 'clean',
                'last_error' => null,
                'next_attempt_at' => null,
                'processed_at' => now(),
            ])->save();

            $this->synchronizeDocumentThumbnail($version, $thumbnailPath);

            return $version->refresh();
        } catch (KmDocumentInfectedException $exception) {
            $version->forceFill([
                'processing_status' => KmProcessingStatus::QUARANTINED,
                'antivirus_status' => 'infected',
                'last_error' => mb_substr($exception->getMessage(), 0, 4000),
                'next_attempt_at' => null,
            ])->save();

            throw $exception;
        } catch (Throwable $exception) {
            $this->recordFailure($version, $exception);

            throw $exception;
        } finally {
            File::deleteDirectory($temporaryDirectory);
        }
    }

    /** @return array<string, array{available: bool, configured: string}> */
    public function capabilities(): array
    {
        $binaries = [
            'antivirus' => (string) config('knowledge_management.processing.antivirus.binary'),
            'libreoffice' => (string) config('knowledge_management.processing.libreoffice.binary'),
            'pdftotext' => (string) config('knowledge_management.processing.poppler.pdftotext'),
            'pdfinfo' => (string) config('knowledge_management.processing.poppler.pdfinfo'),
            'pdftoppm' => (string) config('knowledge_management.processing.poppler.pdftoppm'),
            'tesseract' => (string) config('knowledge_management.processing.tesseract.binary'),
        ];

        $result = [];
        foreach ($binaries as $name => $binary) {
            $result[$name] = [
                'available' => $this->binaryAvailable($binary),
                'configured' => $binary,
            ];
        }

        return $result;
    }

    private function verifiedOriginalPath(KmDocumentVersion $version): string
    {
        if ($version->original_disk !== KmFileService::DISK
            || ! is_string($version->original_path)
            || ! $this->isSafeOriginalPath($version)
            || ! Storage::disk(KmFileService::DISK)->exists($version->original_path)) {
            throw new RuntimeException('File original versi tidak tersedia pada private storage.');
        }

        $path = Storage::disk(KmFileService::DISK)->path($version->original_path);
        $checksum = hash_file('sha256', $path);
        if ($checksum === false
            || ! is_string($version->original_checksum_sha256)
            || ! hash_equals($version->original_checksum_sha256, $checksum)) {
            throw new RuntimeException('Checksum file original versi tidak sesuai.');
        }

        return $path;
    }

    private function scanForViruses(string $path): void
    {
        $command = [
            (string) config('knowledge_management.processing.antivirus.binary'),
            '--no-summary',
        ];
        $database = config('knowledge_management.processing.antivirus.database');
        if (is_string($database) && $database !== '') {
            $command[] = '--database='.$database;
        }
        $command[] = $path;

        $process = $this->run(
            $command,
            (int) config('knowledge_management.processing.antivirus.timeout', 120),
            [0, 1],
        );

        if ($process->getExitCode() === 1) {
            throw new KmDocumentInfectedException('Antivirus mendeteksi file yang tidak aman.');
        }
    }

    private function normalizeToPdf(
        KmDocumentVersion $version,
        string $original,
        string $temporaryDirectory,
    ): string {
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if ($extension === 'pdf') {
            $target = $temporaryDirectory.DIRECTORY_SEPARATOR.'normalized.pdf';
            if (! File::copy($original, $target)) {
                throw new RuntimeException('File PDF tidak dapat disalin untuk normalisasi.');
            }

            return $target;
        }
        if (! in_array($extension, ['ppt', 'pptx'], true)) {
            throw new RuntimeException('Format file tidak didukung oleh pipeline dokumen KM.');
        }

        $profileDirectory = $temporaryDirectory.DIRECTORY_SEPARATOR.'libreoffice-profile';
        File::ensureDirectoryExists($profileDirectory, 0700, true);

        $this->run([
            (string) config('knowledge_management.processing.libreoffice.binary'),
            '-env:UserInstallation='.$this->localFileUrl($profileDirectory),
            '--headless',
            '--nologo',
            '--nodefault',
            '--nofirststartwizard',
            '--convert-to',
            'pdf',
            '--outdir',
            $temporaryDirectory,
            $original,
        ], (int) config('knowledge_management.processing.libreoffice.timeout', 120));

        $expected = $temporaryDirectory.DIRECTORY_SEPARATOR.pathinfo($original, PATHINFO_FILENAME).'.pdf';
        if (! File::isFile($expected)) {
            $candidate = collect(File::files($temporaryDirectory))
                ->first(static fn ($file): bool => strtolower($file->getExtension()) === 'pdf');
            $expected = $candidate?->getPathname() ?? $expected;
        }
        if (! File::isFile($expected)) {
            throw new RuntimeException('LibreOffice tidak menghasilkan PDF yang dapat diproses.');
        }

        return $expected;
    }

    private function assertPdf(string $path): void
    {
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw new RuntimeException('PDF hasil normalisasi tidak dapat dibaca.');
        }
        try {
            if (fread($stream, 5) !== '%PDF-') {
                throw new RuntimeException('Hasil normalisasi bukan PDF yang valid.');
            }
        } finally {
            fclose($stream);
        }
    }

    private function extractText(string $pdf, string $temporaryDirectory): string
    {
        $output = $temporaryDirectory.DIRECTORY_SEPARATOR.'content.txt';
        $this->run([
            (string) config('knowledge_management.processing.poppler.pdftotext'),
            '-layout',
            $pdf,
            $output,
        ], (int) config('knowledge_management.processing.poppler.timeout', 120));

        return File::isFile($output) ? (string) File::get($output) : '';
    }

    private function pageCount(string $pdf): ?int
    {
        $process = $this->run([
            (string) config('knowledge_management.processing.poppler.pdfinfo'),
            $pdf,
        ], (int) config('knowledge_management.processing.poppler.timeout', 120));
        if (preg_match('/^Pages:\s+(\d+)\s*$/mi', $process->getOutput(), $matches) !== 1) {
            return null;
        }

        return max(1, (int) $matches[1]);
    }

    private function ocr(string $pdf, string $temporaryDirectory): string
    {
        $prefix = $temporaryDirectory.DIRECTORY_SEPARATOR.'ocr-page';
        $this->run([
            (string) config('knowledge_management.processing.poppler.pdftoppm'),
            '-png',
            '-r',
            '150',
            $pdf,
            $prefix,
        ], (int) config('knowledge_management.processing.tesseract.timeout', 300));

        $text = [];
        foreach (File::glob($prefix.'-*.png') ?: [] as $image) {
            $process = $this->run([
                (string) config('knowledge_management.processing.tesseract.binary'),
                $image,
                'stdout',
                '-l',
                (string) config('knowledge_management.processing.tesseract.languages', 'ind+eng'),
            ], (int) config('knowledge_management.processing.tesseract.timeout', 300));
            $text[] = $process->getOutput();
        }

        return trim(implode("\n\n", $text));
    }

    private function thumbnail(string $pdf, string $temporaryDirectory): string
    {
        $prefix = $temporaryDirectory.DIRECTORY_SEPARATOR.'thumbnail';
        $this->run([
            (string) config('knowledge_management.processing.poppler.pdftoppm'),
            '-f', '1', '-singlefile', '-png', '-r', '120', $pdf, $prefix,
        ], (int) config('knowledge_management.processing.poppler.timeout', 120));
        $thumbnail = $prefix.'.png';
        if (! File::isFile($thumbnail)) {
            throw new RuntimeException('Thumbnail versi tidak berhasil dibuat.');
        }

        return $thumbnail;
    }

    /** @return array{normalized_pdf_disk: string, normalized_pdf_path: string, normalized_pdf_size_bytes: int, normalized_pdf_checksum_sha256: string, thumbnail_path: string} */
    private function persistOutputs(
        KmDocumentVersion $version,
        string $pdf,
        string $thumbnail,
    ): array {
        $base = 'documents/'.$version->km_pengajuan_id.'/versions/'.$version->getKey();
        $pdfPath = $base.'/normalized.pdf';
        $thumbnailPath = 'thumbnails/'.$version->km_pengajuan_id.'/versions/'.$version->getKey().'.png';
        $this->writeLocalFile($pdf, $pdfPath);
        $this->writeLocalFile($thumbnail, $thumbnailPath);
        $checksum = hash_file('sha256', $pdf);
        if ($checksum === false) {
            throw new RuntimeException('Checksum PDF hasil normalisasi gagal dibuat.');
        }

        return [
            'normalized_pdf_disk' => KmFileService::DISK,
            'normalized_pdf_path' => $pdfPath,
            'normalized_pdf_size_bytes' => max(0, (int) File::size($pdf)),
            'normalized_pdf_checksum_sha256' => $checksum,
            'thumbnail_path' => $thumbnailPath,
        ];
    }

    private function writeLocalFile(string $source, string $target): void
    {
        $stream = fopen($source, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Output pemrosesan tidak dapat dibaca.');
        }
        try {
            Storage::disk(KmFileService::DISK)->writeStream($target, $stream, ['visibility' => 'private']);
        } finally {
            fclose($stream);
        }
    }

    private function synchronizeDocumentThumbnail(KmDocumentVersion $version, string $thumbnailPath): void
    {
        $version->document()->where('current_version_id', $version->getKey())->update([
            'thumbnail_path' => $thumbnailPath,
            'thumbnail_status' => KmThumbnailStatus::READY->value,
            'thumbnail_source_checksum' => $version->normalized_pdf_checksum_sha256,
            'thumbnail_generated_at' => now(),
            'thumbnail_failure_reason' => null,
        ]);
    }

    private function recordFailure(KmDocumentVersion $version, Throwable $exception): void
    {
        $attempts = (int) $version->processing_attempts;
        $maximum = max(1, (int) config('knowledge_management.processing.maximum_attempts', 3));
        $retryMinutes = config('knowledge_management.processing.retry_minutes', [5, 30, 120]);
        $terminal = $attempts >= $maximum;
        $delay = (int) ($retryMinutes[max(0, min($attempts - 1, count($retryMinutes) - 1))] ?? 120);
        $version->forceFill([
            'processing_status' => $terminal
                ? KmProcessingStatus::FAILED
                : KmProcessingStatus::RETRY_PENDING,
            'last_error' => mb_substr($exception->getMessage(), 0, 4000),
            'next_attempt_at' => $terminal ? null : now()->addMinutes(max(1, $delay)),
        ])->save();
    }

    private function run(array $command, int $timeout, array $acceptedCodes = [0]): Process
    {
        $process = new Process($command);
        $process->setTimeout(max(1, $timeout));
        $process->run();
        if (! in_array($process->getExitCode(), $acceptedCodes, true)) {
            throw new RuntimeException(trim($process->getErrorOutput()) ?: trim($process->getOutput()) ?: 'Proses eksternal gagal.');
        }

        return $process;
    }

    private function binaryAvailable(string $binary): bool
    {
        if ($binary === '') {
            return false;
        }
        if (str_contains($binary, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $binary) === 1) {
            return File::isFile($binary);
        }

        $finder = PHP_OS_FAMILY === 'Windows' ? ['where.exe', $binary] : ['which', $binary];
        try {
            return $this->run($finder, 5)->isSuccessful();
        } catch (Throwable) {
            return false;
        }
    }

    private function temporaryDirectory(KmDocumentVersion $version): string
    {
        $root = rtrim((string) config('knowledge_management.processing.temporary_directory'), '\\/');

        return $root.DIRECTORY_SEPARATOR.$version->getKey().'-'.Str::uuid();
    }

    private function localFileUrl(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $encoded = str_replace(['%2F', '%3A'], ['/', ':'], rawurlencode($normalized));

        return preg_match('/^[A-Za-z]:\//', $normalized) === 1
            ? 'file:///'.$encoded
            : 'file://'.$encoded;
    }

    private function isSafeOriginalPath(KmDocumentVersion $version): bool
    {
        $path = str_replace('\\', '/', (string) $version->original_path);

        return preg_match(
            '#^documents/'.preg_quote((string) $version->km_pengajuan_id, '#').'/[a-f0-9-]+\.(pdf|ppt|pptx)$#i',
            $path,
        ) === 1;
    }
}
