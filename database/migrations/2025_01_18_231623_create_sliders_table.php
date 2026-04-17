<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('image_path');  // Путь к изображению
            $table->string('title');       // Заголовок
            $table->text('description')->nullable();  // Описание
            $table->string('link')->nullable();       // Ссылка
            $table->timestamps();         // Дата создания и обновления
        });
    }

    public function down()
    {
        Schema::dropIfExists('sliders');
    }
};
