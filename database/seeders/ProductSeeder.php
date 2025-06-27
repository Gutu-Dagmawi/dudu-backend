<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use App\Enums\Gender;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Storage::disk('public')->makeDirectory('product_images');
        Storage::disk('public')->makeDirectory('variant_images');

        foreach (range(1, 10) as $i) {
            $product = Product::create([
                'id' => Str::uuid(),
                'type' => 'Shirt',
                'name' => 'T-Shirt ' . $i,
                'slug' => 't-shirt-' . $i,
                'description' => 'A high quality cotton shirt #' . $i,
                'gender' => Gender::UNISEX,
                'material' => 'cotton',
                'brand' => ['Nike', 'Adidas', 'Puma'][rand(0, 2)],
            ]);

            // Add product images
            foreach (range(1, 2) as $j) {
                ProductImage::create([
                    'id' => Str::uuid(),
                    'product_id' => $product->id,
                    'url' => 'product_images/dummy_' . $j . '.jpg',
                    'is_primary' => $j === 1,
                ]);
            }

            // Add variants
            foreach (['S', 'M', 'L'] as $index => $size) {
                $variant = ProductVariant::create([
                    'id' => Str::uuid(),
                    'product_id' => $product->id,
                    'sku' => 'SKU-' . strtoupper(Str::random(6)),
                    'size' => $size,
                    'color' => ['Red', 'Blue', 'Black'][rand(0, 2)],
                    'price' => rand(1500, 3000) / 100,
                    'stock' => rand(10, 50),
                    'is_default' => $index === 0,
                ]);

                foreach (range(1, 2) as $k) {
                    ProductImage::create([
                        'id' => Str::uuid(),
                        'product_variant_id' => $variant->id,
                        'url' => fake()->imageUrl(400, 400, 'fashion', true)
                        ,
                        'is_primary' => $k === 1,
                    ]);
                }
            }
        }
    }
}
