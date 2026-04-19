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
            $table->unsignedInteger('min_price')->nullable()->after('min_height');
            $table->timestamp('min_price_updated_at')->nullable()->after('min_price');
            $table->string('min_price_error')->nullable()->after('min_price_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['min_price', 'min_price_updated_at', 'min_price_error']);
        });
    }
};
