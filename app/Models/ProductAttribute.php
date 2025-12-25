<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttribute extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function structureOutputs(): HasMany
    {
        return $this->hasMany(StructureOutput::class, 'product_attribute_id');
    }
}
