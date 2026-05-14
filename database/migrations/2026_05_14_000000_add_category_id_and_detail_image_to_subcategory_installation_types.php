<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subcategory_installation_types', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('subcategory_id')->constrained('categories')->onDelete('cascade');
            $table->string('detail_image')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('subcategory_installation_types', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            $table->dropColumn('detail_image');
        });
    }
};
