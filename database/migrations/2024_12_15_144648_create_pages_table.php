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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Заголовок страницы
            $table->string('description'); // Заголовок страницы
            $table->string('h1'); // Заголовок страницы
            $table->string('slug')->unique(); // Уникальный URL
            $table->text('content')->nullable(); // Контент страницы
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }

};
