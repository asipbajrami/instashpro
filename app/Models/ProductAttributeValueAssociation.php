<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeValueAssociation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_temp' => 'boolean',
    ];

    /**
     * Get the attribute value
     * FIXED: Uses correct column name 'product_attribute_value_id'
     */
    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeValue::class, 'product_attribute_value_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(InstagramPost::class, 'post_id');
    }
}
