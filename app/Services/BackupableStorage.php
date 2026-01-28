<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Storage service that saves files to both R2 (cloud) and local disk.
 *
 * When files are uploaded to posts/, they are:
 * 1. Stored in R2 (primary cloud storage)
 * 2. Copied to local storage/app/public/posts/ (for backup downloads)
 *
 * Users can then download local files as ZIP and clear them to free disk space.
 * The R2 copies remain as the primary storage.
 */
class BackupableStorage
{
    private const LOCAL_DISK = 'public';
    private const CLOUD_DISK = 'r2';
    private const BACKUP_PATH = 'posts';

    /**
     * Store a file in both R2 and local storage.
     */
    public static function put(string $path, string $contents): bool
    {
        $cloud = Storage::disk(self::CLOUD_DISK);
        $local = Storage::disk(self::LOCAL_DISK);

        // Store in R2 (primary)
        $result = $cloud->put($path, $contents);

        // Also store locally if it's in the posts folder
        if ($result && str_starts_with($path, self::BACKUP_PATH . '/')) {
            $local->put($path, $contents);
        }

        return $result;
    }

    /**
     * Get the number of files in local backup.
     */
    public static function localFileCount(): int
    {
        $local = Storage::disk(self::LOCAL_DISK);

        if (!$local->exists(self::BACKUP_PATH)) {
            return 0;
        }

        return count($local->allFiles(self::BACKUP_PATH));
    }

    /**
     * Get total size of local backup files in bytes.
     */
    public static function localFileSize(): int
    {
        $local = Storage::disk(self::LOCAL_DISK);

        if (!$local->exists(self::BACKUP_PATH)) {
            return 0;
        }

        $size = 0;
        foreach ($local->allFiles(self::BACKUP_PATH) as $file) {
            $size += $local->size($file);
        }

        return $size;
    }

    /**
     * Get all local backup file paths.
     */
    public static function localFiles(): array
    {
        $local = Storage::disk(self::LOCAL_DISK);

        if (!$local->exists(self::BACKUP_PATH)) {
            return [];
        }

        return $local->allFiles(self::BACKUP_PATH);
    }

    /**
     * Delete all local backup files.
     * Returns the number of files deleted.
     */
    public static function clearLocal(): int
    {
        $local = Storage::disk(self::LOCAL_DISK);

        if (!$local->exists(self::BACKUP_PATH)) {
            return 0;
        }

        $count = count($local->allFiles(self::BACKUP_PATH));
        $local->deleteDirectory(self::BACKUP_PATH);

        return $count;
    }

    /**
     * Get the full local path for a file.
     */
    public static function localPath(string $file): string
    {
        return storage_path('app/public/' . $file);
    }

    /**
     * Get the R2 disk for direct operations.
     */
    public static function cloud()
    {
        return Storage::disk(self::CLOUD_DISK);
    }

    /**
     * Get the local disk for direct operations.
     */
    public static function local()
    {
        return Storage::disk(self::LOCAL_DISK);
    }
}
