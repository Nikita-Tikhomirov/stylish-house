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
        Schema::table('products', function (Blueprint $table) {
            // Параметры рольставен
            $table->string('installation_type')->nullable()->comment('Тип монтажа: overhead, built-in');
            $table->string('control_type')->nullable()->comment('Тип управления: strap, cardan, pim, electric');
            $table->string('lock_device')->nullable()->comment('Блокирующее устройство: rigel, shchyolka, upper, none');
            $table->boolean('ral_paint')->default(false)->comment('Покраска по RAL');
            $table->boolean('photo_print')->default(false)->comment('Нанесение фотопечати');
            
            // Цены для каждого типа монтажа
            $table->decimal('overhead_price', 8, 2)->default(0)->comment('Цена за накладной монтаж');
            $table->decimal('builtin_price', 8, 2)->default(0)->comment('Цена за встроенный монтаж');
            
            // Цены для каждого типа управления
            $table->decimal('strap_price', 8, 2)->default(0)->comment('Цена за ленточный привод');
            $table->decimal('cardan_price', 8, 2)->default(0)->comment('Цена за воротковый привод');
            $table->decimal('pim_price', 8, 2)->default(0)->comment('Цена за ПИМ');
            $table->decimal('electric_price', 8, 2)->default(0)->comment('Цена за электропривод');
            
            // Цены для каждого блокирующего устройства
            $table->decimal('rigel_price', 8, 2)->default(0)->comment('Цена за ригельный замок');
            $table->decimal('shchyolka_price', 8, 2)->default(0)->comment('Цена за щеколду');
            $table->decimal('upper_price', 8, 2)->default(0)->comment('Цена за верхние замки');
            
            // Цены доп опций
            $table->decimal('ral_price', 8, 2)->default(0)->comment('Цена за покраску по RAL');
            $table->decimal('photo_price', 8, 2)->default(0)->comment('Цена за фотопечать');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = ['installation_type', 'control_type', 'lock_device', 'ral_paint', 'photo_print',
                       'overhead_price', 'builtin_price', 'strap_price', 'cardan_price', 
                       'pim_price', 'electric_price', 'rigel_price', 'shchyolka_price', 
                       'upper_price', 'ral_price', 'photo_price'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
