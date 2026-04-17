<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image_thumb_path')->nullable()->after('image_path');
            $table->string('fabric_thumb_path')->nullable()->after('fabric_photo');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['image_thumb_path', 'fabric_thumb_path']);
        });
    }
};

