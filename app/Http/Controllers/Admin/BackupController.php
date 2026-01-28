<?php

namespace App\Http\Controllers\Admin;

use App\Console\Commands\CreateFullArchive;
use App\Http\Controllers\Controller;
use App\Services\BackupableStorage;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles backup operations for the admin panel.
 *
 * Provides:
 * - Database dump downloads (full, structure-only, data-only)
 * - Local media file info and management
 * - ZIP archive creation and download
 */
class BackupController extends Controller
{
    /**
     * Download MySQL database dump.
     *
     * @queryParam type string One of: full, structure, data. Default: full.
     */
    public function downloadDatabase(): StreamedResponse
    {
        $type = request()->query('type', 'full');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);

        $suffix = match ($type) {
            'structure' => '_structure',
            'data' => '_data',
            default => '_full',
        };
        $filename = "{$database}_backup{$suffix}_" . date('Y-m-d_His') . ".sql";

        $flags = match ($type) {
            'structure' => '--no-data',
            'data' => '--no-create-info',
            default => '',
        };

        return response()->streamDownload(function () use ($database, $username, $password, $host, $port, $flags) {
            $cmd = sprintf(
                'mysqldump --ssl=0 --no-tablespaces -h %s -P %s -u %s -p%s %s %s 2>/dev/null',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                $flags,
                escapeshellarg($database)
            );
            passthru($cmd);
        }, $filename, ['Content-Type' => 'application/sql']);
    }

    /**
     * Get backup status info.
     */
    public function info(): JsonResponse
    {
        return response()->json([
            'local_files' => BackupableStorage::localFileCount(),
            'local_size' => BackupableStorage::localFileSize(),
            'database' => config('database.connections.mysql.database'),
        ]);
    }

    /**
     * Start archive creation in background.
     */
    public function startArchive(): JsonResponse
    {
        $progress = CreateFullArchive::getProgress();

        if (in_array($progress['status'], ['listing', 'archiving'])) {
            return response()->json([
                'success' => false,
                'message' => 'Archive already in progress',
                'progress' => $progress,
            ], 409);
        }

        CreateFullArchive::clearProgress();

        // Run in background
        $cmd = 'cd ' . base_path() . ' && php artisan backup:create-archive > /dev/null 2>&1 &';
        exec($cmd);

        return response()->json([
            'success' => true,
            'message' => 'Archive creation started',
        ]);
    }

    /**
     * Get archive creation progress.
     */
    public function archiveProgress(): JsonResponse
    {
        return response()->json(CreateFullArchive::getProgress());
    }

    /**
     * Clear archive progress and delete any pending ZIP.
     */
    public function clearArchive(): JsonResponse
    {
        $progress = CreateFullArchive::getProgress();

        if (!empty($progress['zip_path']) && file_exists($progress['zip_path'])) {
            @unlink($progress['zip_path']);
        }

        CreateFullArchive::clearProgress();

        return response()->json([
            'success' => true,
            'message' => 'Archive cleared',
        ]);
    }

    /**
     * Download the completed archive.
     *
     * @queryParam clear_local string Set to "1" to delete local files after download.
     */
    public function downloadArchive(): StreamedResponse
    {
        $progress = CreateFullArchive::getProgress();
        $clearLocal = request()->query('clear_local') === '1';

        if ($progress['status'] !== 'completed' || empty($progress['zip_path'])) {
            abort(404, 'No archive available');
        }

        $zipPath = $progress['zip_path'];
        $filename = $progress['filename'];

        if (!file_exists($zipPath)) {
            abort(404, 'Archive file not found');
        }

        $size = filesize($zipPath);

        return response()->streamDownload(function () use ($zipPath, $clearLocal) {
            readfile($zipPath);
            @unlink($zipPath);
            CreateFullArchive::clearProgress();

            if ($clearLocal) {
                BackupableStorage::clearLocal();
            }
        }, $filename, [
            'Content-Type' => 'application/zip',
            'Content-Length' => $size,
        ]);
    }

    /**
     * Delete all local backup files.
     */
    public function clearLocalFiles(): JsonResponse
    {
        $count = BackupableStorage::clearLocal();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$count} files",
            'deleted_count' => $count,
        ]);
    }
}
