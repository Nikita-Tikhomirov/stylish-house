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
        Schema::table('categories', function (Blueprint $table) {
            $table->text('first_screen_text')->nullable();
            $table->string('img')->nullable();
            $table->boolean('show_in_menu')->default(false);
            $table->boolean('show_in_catalog')->default(false);




        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('first_screen_text');
            $table->dropColumn('img');
            $table->dropColumn('show_in_menu');
            $table->dropColumn('show_in_catalog');


        });
    }
};
