#!/bin/bash

# Migrate local storage files to Cloudflare R2
# This copies all files from local public storage to R2

echo "=== Migrating Storage to Cloudflare R2 ==="

docker compose exec -T backend php artisan tinker --execute="
use Illuminate\Support\Facades\Storage;

\$localDisk = Storage::disk('public');
\$r2Disk = Storage::disk('r2');

\$files = \$localDisk->allFiles();
\$total = count(\$files);
\$uploaded = 0;
\$skipped = 0;
\$failed = 0;

echo \"Found \$total files to migrate\" . PHP_EOL;
echo PHP_EOL;

foreach (\$files as \$file) {
    try {
        // Skip if already exists on R2
        if (\$r2Disk->exists(\$file)) {
            \$skipped++;
            continue;
        }
        
        // Get file contents and upload to R2
        \$contents = \$localDisk->get(\$file);
        \$r2Disk->put(\$file, \$contents, 'public');
        \$uploaded++;
        
        if (\$uploaded % 50 == 0) {
            echo \"Uploaded \$uploaded / \$total files...\" . PHP_EOL;
        }
    } catch (\Exception \$e) {
        \$failed++;
        echo \"Failed: \$file - \" . \$e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL;
echo \"=== Migration Complete ===\" . PHP_EOL;
echo \"Uploaded: \$uploaded\" . PHP_EOL;
echo \"Skipped (already exists): \$skipped\" . PHP_EOL;
echo \"Failed: \$failed\" . PHP_EOL;
echo \"Total: \$total\" . PHP_EOL;
"

echo ""
echo "Migration finished!"
