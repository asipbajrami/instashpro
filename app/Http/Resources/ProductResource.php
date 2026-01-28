<?php

namespace App\Http\Resources;

use App\Models\InstagramMedia;
use App\Services\MediaPreloader;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Full product resource for detail views.
 * Includes all images and instagram link.
 * For catalog views, use ProductListResource instead.
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'monthly_price' => $this->monthly_price,
            'currency' => $this->currency ?? 'ALL',
            'thumbnail_url' => $this->getThumbnailUrl(),
            'images' => $this->getImages(),
            'categories' => $this->whenLoaded('categories', function () {
                return $this->categories->map(fn ($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                ]);
            }),
            'attributes' => $this->whenLoaded('attributeValues', function () {
                return $this->attributeValues->map(fn ($attr) => [
                    'attribute_id' => $attr->product_attribute_id,
                    'name' => $attr->attribute->name ?? null,
                    'value' => $attr->value,
                ]);
            }),
            'instagram_link' => $this->getInstagramLink(),
            'seller_username' => $this->seller_username,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Get thumbnail URL, converting legacy localhost URLs to R2 URLs.
     */
    protected function getThumbnailUrl(): ?string
    {
        if (empty($this->thumbnail_url)) {
            return null;
        }

        // Extract path from legacy localhost URLs
        if (preg_match('#/storage/(posts/.+)$#', $this->thumbnail_url, $matches)) {
            return Storage::disk('r2')->url($matches[1]);
        }

        // Already an R2 URL or external URL
        return $this->thumbnail_url;
    }

    /**
     * Get Instagram post link for this product.
     */
    protected function getInstagramLink(): ?string
    {
        if (empty($this->instagram_media_ids)) {
            return null;
        }

        $mediaIds = array_filter(explode('_', $this->instagram_media_ids));
        if (empty($mediaIds)) {
            return null;
        }

        // Use preloaded cache or direct query
        if (MediaPreloader::isPreloaded()) {
            $media = MediaPreloader::getById($mediaIds[0]);
        } else {
            $media = InstagramMedia::find($mediaIds[0]);
        }

        if ($media && $media->shortcode) {
            return 'https://www.instagram.com/p/' . $media->shortcode . '/';
        }

        return null;
    }

    /**
     * Get images for this specific product based on instagram_media_ids.
     */
    protected function getImages(): array
    {
        if (empty($this->instagram_media_ids)) {
            return [];
        }

        $mediaIds = array_unique(array_filter(explode('_', $this->instagram_media_ids)));
        if (empty($mediaIds)) {
            return [];
        }

        // Use preloaded cache or direct query
        if (MediaPreloader::isPreloaded()) {
            $mediaItems = collect($mediaIds)
                ->map(fn ($id) => MediaPreloader::getById($id))
                ->filter();
        } else {
            $mediaItems = InstagramMedia::whereIn('id', $mediaIds)
                ->orderByRaw('FIELD(id, ' . implode(',', $mediaIds) . ')')
                ->get();
        }

        $images = [];
        $seenIds = [];
        foreach ($mediaItems as $media) {
            if (in_array($media->id, $seenIds)) {
                continue;
            }
            $seenIds[] = $media->id;

            $images[] = [
                'id' => $media->id,
                'url' => $media->media_path ? Storage::disk('r2')->url($media->media_path) : null,
                'type' => $media->type,
            ];
        }

        $images = array_reverse($images);

        if (!empty($images)) {
            $images[0]['is_primary'] = true;
            for ($i = 1; $i < count($images); $i++) {
                $images[$i]['is_primary'] = false;
            }
        }

        return $images;
    }
}
