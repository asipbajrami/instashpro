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

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->when($this->description, $this->description),
            'product_count' => $this->whenCounted('products', $this->products_count),
            'children' => CategoryResource::collection($children),
        ];
    }
}
