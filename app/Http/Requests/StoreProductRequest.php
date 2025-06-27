<?php
// app/Http/Requests/StoreProductRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:Shoe,Shirt,Pant',
            'name' => 'required|string',
            'slug' => 'required|string|unique:products,slug',
            'description' => 'required|string',
            'gender' => 'required|in:male,female,unisex',
            'material' => 'nullable|string',
            'brand' => 'nullable|string',

            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',

            'variants' => 'required|array|min:1',
            'variants.*.size' => 'nullable|string',
            'variants.*.color' => 'nullable|string',
            'variants.*.price' => 'nullable|numeric',
            'variants.*.stock' => 'required|integer',
            'variants.*.is_default' => 'boolean',
            'variants.*.sku' => 'nullable|string|unique:product_variants,sku',
            'variants.*.images' => 'nullable|array',
            'variants.*.images.*' => 'image|max:2048',
        ];
    }
}
