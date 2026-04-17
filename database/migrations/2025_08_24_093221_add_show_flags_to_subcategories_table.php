<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            $table->boolean('show_in_more_cats')->default(0);
            $table->boolean('show_in_cats_filter')->default(0);
            $table->string('menu_title')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            $table->dropColumn('show_in_more_cats');
            $table->dropColumn('show_in_cats_filter');
            $table->dropColumn('menu_title');
            
        });
    }
};
