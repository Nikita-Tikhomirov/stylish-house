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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->timestamps();
            $table->string('category');
            $table->string('subcategory');
            $table->string('image_path')->nullable(); // Поле для фото

            // Поля для хранения ID сопутствующих и альтернативных товаров
            $table->json('related_product_ids')->nullable(); // Сопутствующие товары
            $table->json('alternative_product_ids')->nullable(); // Альтернативные товары
            


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
