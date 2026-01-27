<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Support both 'children' and 'childrenRecursive' relationships
        $children = $this->relationLoaded('childrenRecursive')
            ? $this->childrenRecursive
            : $this->whenLoaded('children');

        // Get locale from request (set by controller or from query param)
        $locale = $request->get('_locale') ?? $request->get('locale', 'en');

        // Use translated name/description if translations are loaded
        $name = $this->relationLoaded('translations')
            ? $this->getTranslatedName($locale)
            : $this->name;
        $description = $this->relationLoaded('translations')
            ? $this->getTranslatedDescription($locale)
            : $this->description;

        return [
            'id' => $this->id,
            'name' => $name,
            'slug' => $this->slug,
            'description' => $this->when($description, $description),
            'product_count' => $this->whenCounted('products', $this->products_count),
            'children' => CategoryResource::collection($children),
        ];
    }
}
