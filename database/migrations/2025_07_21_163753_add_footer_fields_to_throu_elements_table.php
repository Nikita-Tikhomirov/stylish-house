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
        Schema::table('throu_elements', function (Blueprint $table) {
            $table->text('text_after_logo')->nullable();
            $table->json('curtain_subcategories')->nullable();
            $table->json('blind_subcategories')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('throu_elements', function (Blueprint $table) {
            $table->dropColumn('text_after_logo');
            $table->dropColumn('curtain_subcategories');
            $table->dropColumn('blind_subcategories');
            
        });
    }
};
