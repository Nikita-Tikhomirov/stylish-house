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
            $table->string('h1')->nullable();
            $table->text('first_screenn_description')->nullable();
            $table->string('color')->nullable();
            $table->string('model')->nullable();
            $table->string('coef')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('h1');
            $table->dropColumn('first_screenn_description');
            $table->dropColumn('color');
            $table->dropColumn('model');
            $table->dropColumn('coef');
        });
    }
};
