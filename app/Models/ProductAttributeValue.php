<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class ProductAttributeValue extends Model
{
    use HasFactory, Searchable;

    protected $guarded = ['id'];

    protected $casts = [
        'is_temp' => 'boolean',
        'score' => 'integer',
    ];

    /**
     * Transient property for embedding (not stored in DB, only sent to Typesense)
     */
    public ?array $embedding_text = null;

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }

    public function searchableAs(): string
    {
        return 'product_attribute_values';
    }

    public function toSearchableArray(): array
    {
        $array = [
            'id' => (string) $this->id,
            'product_attribute_id' => (string) $this->product_attribute_id,
            'ai_value' => $this->ai_value ?? $this->value,
            'updated_at' => $this->updated_at?->timestamp ?? now()->timestamp,
            'is_temp' => (bool) $this->is_temp,
            'score' => (int) ($this->score ?? 0),
        ];

        if ($this->embedding_text) {
            $array['embedding_text'] = $this->embedding_text;
        }

        return $array;
    }
}
