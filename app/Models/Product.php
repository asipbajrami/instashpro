<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'published_at' => 'datetime',
        'has_discount' => 'boolean',
        'media_count' => 'integer',
        'instagram_profile_id' => 'integer',
        'primary_category_id' => 'integer',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_category', 'product_id', 'category_id')
            ->withPivot(['similarity_score', 'is_temp'])
            ->withTimestamps();
    }

    /**
     * Get the primary category (denormalized for fast queries)
     */
    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    /**
     * Get the Instagram profile (denormalized for fast queries)
     */
    public function instagramProfile(): BelongsTo
    {
        return $this->belongsTo(InstagramProfile::class, 'instagram_profile_id');
    }

    /**
     * Get attribute values for this product through associations
     * FIXED: Uses correct column name 'product_attribute_value_id'
     */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttributeValue::class,
            'product_attribute_value_associations',
            'product_id',
            'product_attribute_value_id' // FIXED from 'product_attribute_id'
        )->withPivot(['is_temp'])->withTimestamps();
    }

    /**
     * Get the Instagram media associated with this product
     */
    public function getMediaAttribute(): array
    {
        if (empty($this->instagram_media_ids)) {
            return [];
        }

        $ids = explode('_', $this->instagram_media_ids);
        return InstagramMedia::whereIn('id', $ids)->get()->all();
    }
}
