<?php

namespace App\Console\Commands;

use App\Services\BackupableStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use ZipArchive;

/**
 * Creates a ZIP archive of all local media files.
 *
 * This command is run in the background via BackupController::startFullArchive().
 * Progress is tracked via Cache and polled by the frontend.
 */
class CreateFullArchive extends Command
{
    protected $signature = 'backup:create-archive';
    protected $description = 'Create a ZIP archive of local media files';

    private const CACHE_KEY = 'backup_archive_progress';
    private const PROGRESS_UPDATE_INTERVAL = 50;

    public function handle(): int
    {
        try {
            return $this->createArchive();
        } catch (\Throwable $e) {
            $this->setProgress('failed', "Error: {$e->getMessage()}", 0);
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createArchive(): int
    {
        $this->setProgress('listing', 'Scanning local files...', 0);

        $files = BackupableStorage::localFiles();
        $totalFiles = count($files);

        if ($totalFiles === 0) {
            $this->setProgress('completed', 'No files to archive', 100, [
                'total_files' => 0,
                'processed_files' => 0,
            ]);
            $this->info('No files to archive');
            return Command::SUCCESS;
        }

        $this->info("Found {$totalFiles} files");
        $this->setProgress('archiving', "Creating archive ({$totalFiles} files)...", 5, [
            'total_files' => $totalFiles,
            'processed_files' => 0,
        ]);

        // Prepare ZIP file
        $zipFilename = 'media_' . now()->format('Y-m-d_His') . '.zip';
        $zipPath = $this->ensureTempDir() . '/' . $zipFilename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->setProgress('failed', 'Could not create ZIP file', 0);
            return Command::FAILURE;
        }

        // Add files to ZIP
        $processed = 0;
        $failed = 0;

        foreach ($files as $file) {
            $fullPath = BackupableStorage::localPath($file);

            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, $file);
                $processed++;
            } else {
                $failed++;
            }

            // Update progress periodically
            if (($processed + $failed) % self::PROGRESS_UPDATE_INTERVAL === 0) {
                $this->updateArchiveProgress($processed, $failed, $totalFiles);
            }
        }

        $zip->close();

        // Final progress update
        $fileSize = filesize($zipPath);
        $this->setProgress('completed', 'Archive ready for download', 100, [
            'total_files' => $totalFiles,
            'processed_files' => $processed,
            'failed_files' => $failed,
            'file_size' => $fileSize,
            'filename' => $zipFilename,
            'zip_path' => $zipPath,
            'completed_at' => now()->toIso8601String(),
        ]);

        $this->info("Done! {$zipFilename} ({$this->formatBytes($fileSize)})");

        return Command::SUCCESS;
    }

    private function updateArchiveProgress(int $processed, int $failed, int $total): void
    {
        $percent = 5 + ((($processed + $failed) / $total) * 90);
        $this->setProgress('archiving', "Archiving... ({$processed}/{$total})", round($percent), [
            'total_files' => $total,
            'processed_files' => $processed,
            'failed_files' => $failed,
        ]);
        $this->output->write("\r{$processed}/{$total}");
    }

    private function ensureTempDir(): string
    {
        $dir = storage_path('app/temp');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    // --- Static Progress Methods (used by BackupController) ---

    private function setProgress(string $status, string $message, int $progress, array $extra = []): void
    {
        self::updateProgress(array_merge([
            'status' => $status,
            'message' => $message,
            'progress' => $progress,
        ], $extra));
    }

    public static function updateProgress(array $data): void
    {
        $current = Cache::get(self::CACHE_KEY, []);
        $merged = array_merge($current, $data, ['updated_at' => now()->toIso8601String()]);
        Cache::put(self::CACHE_KEY, $merged, now()->addHours(24));
    }

    public static function getProgress(): array
    {
        return Cache::get(self::CACHE_KEY, [
            'status' => 'idle',
            'message' => 'No archive in progress',
            'progress' => 0,
        ]);
    }

    public static function clearProgress(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
