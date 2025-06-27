<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');

            $table->string('sku')->unique()->nullable(); // Optional identifier
            $table->string('size')->nullable(); // e.g., S, M, L, XL
            $table->string('color')->nullable(); // e.g., Black, Red

            $table->decimal('price', 10, 2)->nullable(); // Override base price
            $table->integer('stock')->default(0);
            $table->boolean('is_default')->default(false); // Main/default variant

            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
