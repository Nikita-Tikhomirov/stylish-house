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
        Schema::create('work_examples', function (Blueprint $table) {
            $table->id();
            $table->string('image');
            $table->string('title')->nullable();  // Название изображения
            $table->text('description')->nullable();  // Описание изображения
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_examples');

    }

};
