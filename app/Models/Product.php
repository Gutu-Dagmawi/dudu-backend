<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @method static create(array $array)
 * @method static findOrFail(string $id)
 */
class Product extends Model
{
    use HasFactory;

    // UUIDs
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'type',
        'name',
        'slug',
        'description',
        'material',
        'brand',
        'gender',
    ];

    protected $casts = [
        'gender' => Gender::class,
    ];

    /**
     * Boot and assign UUID on creation
     */
    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->id = $model->id ?: (string)Str::uuid();
        });

        static::deleting(function ($product) {
            // Delete product images files
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->url);
            }

            // Delete variant images files
            foreach ($product->variants as $variant) {
                foreach ($variant->images as $vImage) {
                    Storage::disk('public')->delete($vImage->url);
                }
            }
        });
    }

    /**
     * Resolve child model instance based on type (STI)
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $instance = parent::newFromBuilder($attributes, $connection);

        if (!empty($attributes->type)) {
            $class = 'App\\Models\\' . $attributes->type;
            if (class_exists($class)) {
                $child = (new $class)->newInstance([], true);
                $child->setRawAttributes((array) $attributes, true);
                return $child;
            }
        }

        return $instance;
    }

    /**
     * Product variants (size/color/price)
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Default (main) variant
     */
    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    /**
     * Product images (optional)
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }


}
