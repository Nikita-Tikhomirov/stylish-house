<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icon_cards', function (Blueprint $table) {
            $table->id();
            $table->string('icon_class'); // для хранения класса иконки, например, 'fas fa-truck'
            $table->string('title'); // заголовок карточки
            $table->text('text'); // текст карточки
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icon_cards');
    }
};
