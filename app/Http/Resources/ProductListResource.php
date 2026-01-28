<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Lightweight product resource for catalog/list views.
 * Only includes essential fields needed for product cards.
 * Uses denormalized thumbnail_url - no media queries needed.
 */
class ProductListResource extends JsonResource
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
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'currency' => $this->currency ?? 'ALL',
            'thumbnail_url' => $this->getThumbnailUrl(),
            'images' => [], // Empty for catalog - frontend uses thumbnail_url
            'seller_username' => $this->seller_username,
            'published_at' => $this->published_at?->toISOString(),
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
}
