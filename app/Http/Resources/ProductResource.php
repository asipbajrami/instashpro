<?php

namespace App\Http\Resources;

use App\Models\InstagramMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Get Instagram post link for this product
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

        $media = InstagramMedia::find($mediaIds[0]);
        if ($media && $media->shortcode) {
            return 'https://www.instagram.com/p/' . $media->shortcode . '/';
        }

        return null;
    }

    /**
     * Get images for this specific product based on instagram_media_ids
     */
    protected function getImages(): array
    {
        if (empty($this->instagram_media_ids)) {
            return [];
        }

        // Deduplicate media IDs to prevent duplicate images
        $mediaIds = array_unique(array_filter(explode('_', $this->instagram_media_ids)));

        if (empty($mediaIds)) {
            return [];
        }

        $mediaItems = InstagramMedia::whereIn('id', $mediaIds)
            ->orderByRaw('FIELD(id, ' . implode(',', $mediaIds) . ')')
            ->get();

        $images = [];
        $seenIds = []; // Track seen image IDs to prevent duplicates
        foreach ($mediaItems as $index => $media) {
            $highQualityMedia = null;
            if (in_array($media->type, ['image_mid', 'carousel_mid'])) {
                $highType = str_replace('_mid', '_high', $media->type);
                $highQualityMedia = InstagramMedia::where('instagram_post_id', $media->instagram_post_id)
                    ->where('type', $highType)
                    ->where('media_id', $media->media_id)
                    ->first();
            }

            $finalMedia = $highQualityMedia ?? $media;

            // Skip if we've already added this image
            if (in_array($finalMedia->id, $seenIds)) {
                continue;
            }
            $seenIds[] = $finalMedia->id;

            $images[] = [
                'id' => $finalMedia->id,
                'url' => $finalMedia->media_path ? url('/storage/' . $finalMedia->media_path) : null,
                'type' => $finalMedia->type,
            ];
        }

        // Reverse order so main Instagram image (stored last) appears first
        $images = array_reverse($images);

        // Mark first image as primary
        if (!empty($images)) {
            $images[0]['is_primary'] = true;
            for ($i = 1; $i < count($images); $i++) {
                $images[$i]['is_primary'] = false;
            }
        }

        return $images;
    }
}
