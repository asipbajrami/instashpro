<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncStorageToR2 extends Command
{
    protected $signature = 'storage:sync-to-r2 {--dry-run : Show what would be uploaded without actually uploading}';

    protected $description = 'Sync existing local storage files to Cloudflare R2';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $localDisk = Storage::disk('public');
        $r2Disk = Storage::disk('r2');

        // Get all files from local public storage
        $files = $localDisk->allFiles('posts');
        $total = count($files);

        if ($total === 0) {
            $this->info('No files found in local storage.');
            return 0;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$total} files to sync...");

        if ($dryRun) {
            foreach ($files as $file) {
                $this->line("  Would upload: {$file}");
            }
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $uploaded = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($files as $file) {
            try {
                // Skip if already exists in R2
                if ($r2Disk->exists($file)) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Upload to R2
                $content = $localDisk->get($file);
                $r2Disk->put($file, $content);
                $uploaded++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed to upload {$file}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Sync complete!");
        $this->info("  Uploaded: {$uploaded}");
        $this->info("  Skipped (already exists): {$skipped}");
        if ($failed > 0) {
            $this->error("  Failed: {$failed}");
        }

        return $failed > 0 ? 1 : 0;
    }
}
