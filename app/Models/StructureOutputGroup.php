<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class StructureOutputGroup extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'used_for',
        'description',
    ];

    /**
     * Get all structure outputs belonging to this group
     */
    public function structureOutputs(): HasMany
    {
        return $this->hasMany(StructureOutput::class, 'used_for', 'used_for');
    }

    public function searchableAs(): string
    {
        return 'structure_output_groups';
    }

    public function toSearchableArray(): array
    {
        // Typesense will auto-generate embeddings using built-in models
        return [
            'id' => (string) $this->id,
            'used_for' => $this->used_for,
            'description' => $this->description,
            'updated_at' => $this->updated_at?->timestamp ?? now()->timestamp,
        ];
    }
}
