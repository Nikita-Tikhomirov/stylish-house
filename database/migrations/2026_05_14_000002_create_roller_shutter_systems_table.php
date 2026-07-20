<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roller_shutter_systems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('title');           // system name (e.g. "ПИМ")
            $table->string('image')->nullable(); // system illustration
            $table->text('description')->nullable(); // HTML description
            $table->text('components')->nullable();  // component list (plain text, one per line)
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roller_shutter_systems');
    }
};
