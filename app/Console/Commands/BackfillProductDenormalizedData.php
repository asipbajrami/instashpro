<?php

namespace App\Console\Commands;

use App\Models\InstagramMedia;
use App\Models\InstagramPost;
use App\Models\InstagramProfile;
use App\Models\Product;
use Illuminate\Console\Command;

class BackfillProductDenormalizedData extends Command
{
    protected $signature = 'products:backfill-denormalized {--chunk=100 : Number of products to process per batch}';
    protected $description = 'Backfill denormalized columns for existing products';

    private array $typeToGroup = [
        'car' => 'car',
        'motorcycle' => 'car',
        'scooter' => 'car',
        'truck' => 'car',
        'van' => 'car',
        'suv' => 'car',
        'electric_vehicle' => 'car',
        'phone' => 'tech',
        'laptop' => 'tech',
        'computer' => 'tech',
        'monitor' => 'tech',
        'tablet' => 'tech',
        'smartwatch' => 'tech',
        'headphones' => 'tech',
        'camera' => 'tech',
        'gaming_console' => 'tech',
        'general_electronics' => 'tech',
    ];

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');
        $totalProducts = Product::count();

        $this->info("Starting backfill for {$totalProducts} products...");

        // Preload all media to post mappings for efficiency
        $this->info('Loading media to post mappings...');
        $mediaToPost = InstagramMedia::pluck('instagram_post_id', 'id')->toArray();
        $this->info('Loaded ' . count($mediaToPost) . ' media records');

        // Preload all post data
        $this->info('Loading post data...');
        $posts = InstagramPost::select('post_id', 'ig_id', 'username', 'published_at', 'display_url', 'thumbnail_url')
            ->get()
            ->keyBy('post_id');
        $this->info('Loaded ' . $posts->count() . ' posts');

        // Preload all profiles (by both ig_id and username)
        $this->info('Loading profiles...');
        $profilesById = InstagramProfile::select('id', 'ig_id', 'username')
            ->get()
            ->keyBy('ig_id');
        $profilesByUsername = InstagramProfile::select('id', 'ig_id', 'username')
            ->get()
            ->keyBy('username');
        $this->info('Loaded ' . $profilesById->count() . ' profiles');

        // Preload media paths for local URLs
        $this->info('Loading media paths...');
        $mediaPaths = InstagramMedia::pluck('media_path', 'id')->toArray();
        $this->info('Loaded ' . count($mediaPaths) . ' media paths');

        $progressBar = $this->output->createProgressBar($totalProducts);
        $progressBar->start();

        $updated = 0;
        $errors = 0;

        Product::with('categories')->chunkById($chunkSize, function ($products) use (
            &$updated,
            &$errors,
            $mediaToPost,
            $posts,
            $profilesById,
            $profilesByUsername,
            $mediaPaths,
            $progressBar
        ) {
            foreach ($products as $product) {
                try {
                    $data = $this->computeDenormalizedData($product, $mediaToPost, $posts, $profilesById, $profilesByUsername, $mediaPaths);

                    if (!empty($data)) {
                        Product::where('id', $product->id)->update($data);
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("\nError processing product {$product->id}: " . $e->getMessage());
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine();

        $this->info("Backfill complete!");
        $this->info("Updated: {$updated}");
        $this->info("Errors: {$errors}");

        return Command::SUCCESS;
    }

    private function computeDenormalizedData(
        Product $product,
        array $mediaToPost,
        $posts,
        $profilesById,
        $profilesByUsername,
        array $mediaPaths
    ): array {
        $data = [];

        // 1. Compute group from type
        if ($product->type && isset($this->typeToGroup[$product->type])) {
            $data['group'] = $this->typeToGroup[$product->type];
        }

        // 2. Has discount
        $data['has_discount'] = $product->discount_price > 0 && $product->discount_price < $product->price;

        // 3. Primary category
        $primaryCategory = $product->categories->first();
        if ($primaryCategory) {
            $data['primary_category_id'] = $primaryCategory->id;
        }

        // 4. Media count
        $mediaIds = array_filter(explode('_', $product->instagram_media_ids ?? ''));
        $data['media_count'] = max(1, count($mediaIds));

        // 5. Get post data from first media ID
        if (!empty($mediaIds)) {
            $firstMediaId = (int) $mediaIds[0];
            $postId = $mediaToPost[$firstMediaId] ?? null;

            if ($postId && isset($posts[$postId])) {
                $post = $posts[$postId];

                // Get profile info - try ig_id first, then username
                $profile = null;
                if ($post->ig_id && isset($profilesById[$post->ig_id])) {
                    $profile = $profilesById[$post->ig_id];
                } elseif (isset($post->username) && isset($profilesByUsername[$post->username])) {
                    $profile = $profilesByUsername[$post->username];
                }

                if ($profile) {
                    $data['instagram_profile_id'] = $profile->id;
                    $data['seller_username'] = $profile->username;
                }

                // Post ID for reference
                $data['instagram_post_id'] = $postId;

                // Published at (from Instagram's published_at)
                if ($post->published_at) {
                    $data['published_at'] = $post->published_at;
                }

                // Image URLs - prioritize local storage
                $mediaPath = $mediaPaths[$firstMediaId] ?? null;
                if ($mediaPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($mediaPath)) {
                    $localUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($mediaPath);
                    $data['primary_image_url'] = $localUrl;
                    $data['thumbnail_url'] = $localUrl;
                } else {
                    // Fallback to CDN URLs
                    if ($post->display_url) {
                        $data['primary_image_url'] = $post->display_url;
                    }
                    if ($post->thumbnail_url) {
                        $data['thumbnail_url'] = $post->thumbnail_url;
                    }
                }
            }
        }

        return $data;
    }
}
