<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static create(array $array)
 */
class ProductVariant extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['product_id', 'sku', 'size', 'color', 'price', 'stock', 'is_default'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_variant_id');
    }

}
