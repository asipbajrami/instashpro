#!/bin/bash

# Thumbnail Migration Script
# Run this after deploying code changes to migrate existing data

echo "Starting thumbnail migration..."

php artisan tinker --execute="
// 1. Delete mid images (files + DB records)
echo '1. Deleting mid images...' . PHP_EOL;
\$midMedia = \App\Models\InstagramMedia::whereIn('type', ['image_mid', 'carousel_mid'])->get();
\$deletedFiles = 0;
foreach (\$midMedia as \$media) {
    if (\$media->media_path && \Illuminate\Support\Facades\Storage::disk('public')->exists(\$media->media_path)) {
        \Illuminate\Support\Facades\Storage::disk('public')->delete(\$media->media_path);
        \$deletedFiles++;
    }
    \$media->delete();
}
echo \"   Deleted \$deletedFiles files and \" . count(\$midMedia) . \" records\" . PHP_EOL;

// 2. Generate thumbnails for existing high-res images
echo '2. Generating thumbnails...' . PHP_EOL;
\$created = 0;
\App\Models\InstagramMedia::whereIn('type', ['image_high', 'carousel_high'])->chunk(100, function(\$items) use (&\$created) {
    foreach (\$items as \$media) {
        \$thumbType = str_replace('_high', '_thumb', \$media->type);
        \$thumbPath = str_replace('/high.jpg', '/thumb.jpg', \$media->media_path);
        if (\App\Models\InstagramMedia::where('instagram_post_id', \$media->instagram_post_id)->where('type', \$thumbType)->where('media_id', \$media->media_id)->exists()) continue;
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists(\$media->media_path)) continue;
        \$imageData = \Illuminate\Support\Facades\Storage::disk('public')->get(\$media->media_path);
        \$image = @imagecreatefromstring(\$imageData);
        if (!\$image) continue;
        \$w = imagesx(\$image); \$h = imagesy(\$image);
        \$tw = 400; \$th = (int)round(\$h * (\$tw / \$w));
        \$thumb = imagecreatetruecolor(\$tw, \$th);
        imagecopyresampled(\$thumb, \$image, 0, 0, 0, 0, \$tw, \$th, \$w, \$h);
        ob_start(); imagejpeg(\$thumb, null, 75); \$thumbData = ob_get_clean();
        imagedestroy(\$image); imagedestroy(\$thumb);
        \Illuminate\Support\Facades\Storage::disk('public')->put(\$thumbPath, \$thumbData);
        \App\Models\InstagramMedia::create(['instagram_post_id' => \$media->instagram_post_id, 'media_path' => \$thumbPath, 'shortcode' => \$media->shortcode, 'type' => \$thumbType, 'used_for' => 'image', 'status' => 'downloaded', 'media_id' => \$media->media_id]);
        \$created++;
    }
});
echo \"   Created \$created thumbnails\" . PHP_EOL;

// 3. Update products to use thumb.jpg
echo '3. Updating product thumbnail URLs...' . PHP_EOL;
\$updated = \App\Models\Product::whereNotNull('thumbnail_url')->where('thumbnail_url', 'like', '%/high.jpg')->update(['thumbnail_url' => \Illuminate\Support\Facades\DB::raw(\"REPLACE(thumbnail_url, '/high.jpg', '/thumb.jpg')\")]);
echo \"   Updated \$updated products\" . PHP_EOL;

echo PHP_EOL . 'Done!' . PHP_EOL;
"

echo "Migration complete!"
