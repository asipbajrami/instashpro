<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    /**
     * Additional facets data to include
     */
    protected ?array $facets = null;

    /**
     * Set facets data
     */
    public function withFacets(array $facets): self
    {
        $this->facets = $facets;
        return $this;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => ProductResource::collection($this->collection),
            'pagination' => [
                'current_page' => $this->currentPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
                'total_pages' => $this->lastPage(),
                'has_more' => $this->hasMorePages(),
            ],
            'facets' => $this->facets,
        ];
    }

    /**
     * Customize the response
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}
