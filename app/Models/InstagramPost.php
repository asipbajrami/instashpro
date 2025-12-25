<?php

namespace App\Models;

use App\Enums\MediaType;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Laravel\Scout\Searchable;

class InstagramPost extends Model
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $casts = [
        'media_type' => MediaType::class,
        'llm_categories' => 'array',
        'image' => 'array',
        'images_carousel' => 'array',
        'is_video' => 'boolean',
        'has_audio' => 'boolean',
        'processed_structure' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function media(): HasMany
    {
        return $this->hasMany(InstagramMedia::class, 'instagram_post_id', 'post_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(InstagramProfile::class, 'ig_id', 'ig_id');
    }

    /**
     * Get labels/attribute values for this post
     */
    public function labels()
    {
        return ProductAttributeValue::select('product_attribute_values.*')
            ->join('product_attribute_value_associations', 'product_attribute_values.id', '=', 'product_attribute_value_associations.product_attribute_value_id')
            ->where('product_attribute_value_associations.post_id', $this->id)
            ->get();
    }

    public function searchableAs(): string
    {
        return 'instagram_posts';
    }

    public function toSearchableArray(): array
    {
        // Clean caption for search indexing
        $cleanCaption = preg_replace('/[^(\x20-\x7F)]*/', '', $this->caption ?? '');

        // Typesense will auto-generate caption embedding using built-in model
        // Image embedding is optional and handled separately if needed
        return [
            'id' => (string) $this->id,
            'caption' => $cleanCaption,
            'updated_at' => $this->updated_at?->timestamp ?? now()->timestamp,
        ];
    }

    public static function savedImageToBase64(string $imagePath): ?string
    {
        if (!file_exists($imagePath)) {
            return null;
        }

        try {
            $imageData = file_get_contents($imagePath);
            return base64_encode($imageData);
        } catch (Exception $e) {
            return null;
        }
    }
}
