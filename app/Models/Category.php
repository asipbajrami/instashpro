<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Category extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'parent_id',
        'score',
        'is_temp'
    ];

    protected $casts = [
        'is_temp' => 'boolean',
        'score' => 'integer',
    ];

    /**
     * Transient property for embedding (not stored in DB, only sent to Typesense)
     */
    public ?array $embedding_e5_small = null;

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Recursive relationship to load all nested children
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()
            ->with('childrenRecursive')
            ->withCount('products')
            ->where('is_temp', false)
            ->orderBy('score', 'desc');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_category', 'category_id', 'product_id')
            ->withPivot(['similarity_score', 'is_temp'])
            ->withTimestamps();
    }

    public function searchableAs(): string
    {
        return 'category';
    }

    public function toSearchableArray(): array
    {
        $array = [
            'id' => (string) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'updated_at' => $this->updated_at?->timestamp ?? now()->timestamp,
            'score' => (int) ($this->score ?? 0),
            'is_temp' => (bool) ($this->is_temp ?? false),
        ];

        if ($this->embedding_e5_small) {
            $array['embedding_e5_small'] = $this->embedding_e5_small;
        }

        return $array;
    }
}
