<?php

namespace App\Console\Commands;

use App\Models\InstagramMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateThumbnails extends Command
{
    protected $signature = 'media:generate-thumbnails {--force : Regenerate existing thumbnails}';

    protected $description = 'Generate thumbnails for existing high-resolution images';

    public function handle(): int
    {
        $force = $this->option('force');

        $query = InstagramMedia::whereIn('type', ['image_high', 'carousel_high']);
        $total = $query->count();

        if ($total === 0) {
            $this->info('No high-resolution images found.');
            return 0;
        }

        $this->info("Processing {$total} high-resolution images...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        $query->chunk(100, function ($mediaItems) use ($force, &$created, &$skipped, &$failed, $bar) {
            foreach ($mediaItems as $media) {
                $result = $this->processMedia($media, $force);

                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'skipped') {
                    $skipped++;
                } else {
                    $failed++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Done! Created: {$created}, Skipped: {$skipped}, Failed: {$failed}");

        return 0;
    }

    protected function processMedia(InstagramMedia $media, bool $force): string
    {
        $thumbType = str_replace('_high', '_thumb', $media->type);
        $thumbPath = str_replace('/high.jpg', '/thumb.jpg', $media->media_path);

        // Check if thumbnail already exists
        if (!$force) {
            $existingThumb = InstagramMedia::where('instagram_post_id', $media->instagram_post_id)
                ->where('type', $thumbType)
                ->where('media_id', $media->media_id)
                ->exists();

            if ($existingThumb) {
                return 'skipped';
            }
        }

        // Check if source file exists
        if (!Storage::disk('r2')->exists($media->media_path)) {
            return 'failed';
        }

        try {
            $imageData = Storage::disk('r2')->get($media->media_path);
            $image = imagecreatefromstring($imageData);

            if (!$image) {
                return 'failed';
            }

            $origWidth = imagesx($image);
            $origHeight = imagesy($image);

            // Resize to 400px width, maintain aspect ratio
            $thumbWidth = 400;
            $thumbHeight = (int) round($origHeight * ($thumbWidth / $origWidth));

            $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
            imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $origWidth, $origHeight);

            // Save with 75% quality
            ob_start();
            imagejpeg($thumb, null, 75);
            $thumbData = ob_get_clean();

            imagedestroy($image);
            imagedestroy($thumb);

            // Save thumbnail file
            Storage::disk('r2')->put($thumbPath, $thumbData);

            // Create media record
            InstagramMedia::updateOrCreate(
                [
                    'instagram_post_id' => $media->instagram_post_id,
                    'media_path' => $thumbPath,
                ],
                [
                    'shortcode' => $media->shortcode,
                    'type' => $thumbType,
                    'used_for' => 'image',
                    'status' => 'downloaded',
                    'media_id' => $media->media_id,
                ]
            );

            return 'created';
        } catch (\Exception $e) {
            return 'failed';
        }
    }
}
