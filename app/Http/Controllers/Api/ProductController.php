<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Exception;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $products = Product::with(['variants', 'variants.images'])->get();


        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     * @throws Throwable
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        if ($request->expectsJson()) {
            Log::info('✅ JSON expected');
        } else {
            Log::info('❌ JSON NOT expected');
        }
        Log::info('Request input:', $request->all());

        $data = $request->validated();

        DB::beginTransaction();

        try {
            $product = Product::create([
                'type' => $data['type'],
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'gender' => $data['gender'],
                'material' => $data['material'] ?? null,
                'brand' => $data['brand'] ?? null,
            ]);

            // Store main product images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('product_images', 'public');

                    $product->images()->create([
                        'url' => $path,
                        'is_primary' => $index === 0,
                    ]);
                }
            }

            // Create variants
            foreach ($data['variants'] as $variantData) {
                $variant = $product->variants()->create([
                    'size' => $variantData['size'] ?? null,
                    'color' => $variantData['color'] ?? null,
                    'price' => $variantData['price'] ?? null,
                    'stock' => $variantData['stock'],
                    'is_default' => $variantData['is_default'] ?? false,
                    'sku' => $variantData['sku'] ?? Str::uuid(),
                ]);

                // Save images per variant
                if (isset($variantData['images'])) {
                    foreach ($variantData['images'] as $index => $imageFile) {
                        $path = $imageFile->store('variant_images', 'public');

                        ProductImage::create([
                            'product_variant_id' => $variant->id,
                            'url' => $path,
                            'is_primary' => $index === 0,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json(['message' => 'Product created successfully'], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(StoreProductRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $product = Product::findOrFail($id);

            // Update only fields sent (partial update)
            $product->update(collect($data)->only([
                'type', 'name', 'slug', 'description', 'gender', 'material', 'brand'
            ])->toArray());

            // Accept an optional array of image IDs to delete
            if (isset($data['delete_image_ids']) && is_array($data['delete_image_ids'])) {
                $imagesToDelete = $product->images()->whereIn('id', $data['delete_image_ids'])->get();
                foreach ($imagesToDelete as $image) {
                    // Delete file from storage if needed
                    Storage::disk('public')->delete($image->url);
                    $image->delete();
                }
            }

            // --- HANDLE NEW PRODUCT IMAGES ---
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('product_images', 'public');

                    $product->images()->create([
                        'url' => $path,
                        'is_primary' => $index === 0,
                    ]);
                }
            }


            // Delete variants (optional)
            if (isset($data['delete_variant_ids']) && is_array($data['delete_variant_ids'])) {
                $variantsToDelete = $product->variants()->whereIn('id', $data['delete_variant_ids'])->get();
                foreach ($variantsToDelete as $variant) {
                    // Delete associated images and files first
                    foreach ($variant->images as $variantImage) {
                        Storage::disk('public')->delete($variantImage->url);
                        $variantImage->delete();
                    }
                    $variant->delete();
                }
            }

            foreach ($data['variants'] as $variantData) {
                if (isset($variantData['id'])) {
                    // Update existing variant
                    $variant = $product->variants()->find($variantData['id']);
                    if ($variant) {
                        $variant->update(collect($variantData)->except(['id', 'images', 'delete_image_ids'])->toArray());

                        // Delete variant images if requested
                        if (isset($variantData['delete_image_ids']) && is_array($variantData['delete_image_ids'])) {
                            $imagesToDelete = $variant->images()->whereIn('id', $variantData['delete_image_ids'])->get();
                            foreach ($imagesToDelete as $img) {
                                Storage::disk('public')->delete($img->url);
                                $img->delete();
                            }
                        }

                        // Add new images to variant
                    } else {
                        // Variant id provided but not found, create new
                        $variant = $product->variants()->create(collect($variantData)->except(['images', 'delete_image_ids'])->toArray());

                    }
                } else {
                    // Create new variant if no ID
                    $variant = $product->variants()->create(collect($variantData)->except(['images', 'delete_image_ids'])->toArray());

                }
                if (isset($variantData['images'])) {
                    foreach ($variantData['images'] as $index => $imageFile) {
                        $path = $imageFile->store('variant_images', 'public');

                        ProductImage::create([
                            'product_variant_id' => $variant->id,
                            'url' => $path,
                            'is_primary' => $index === 0,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json(['message' => 'Product updated successfully']);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

            // Deleting the product will cascade delete variants and images based on your FK constraints
            $product->delete();

            return response()->json(['message' => 'Product deleted successfully']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
