<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            $table->string('plumbing_calc_title')->nullable()->after('template_variant');
            $table->string('plumbing_calc_subtitle')->nullable()->after('plumbing_calc_title');
            $table->text('plumbing_calc_description')->nullable()->after('plumbing_calc_subtitle');
            $table->json('plumbing_calc_images')->nullable()->after('plumbing_calc_description');
        });
    }

    public function down(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            $table->dropColumn('plumbing_calc_images');
            $table->dropColumn('plumbing_calc_description');
            $table->dropColumn('plumbing_calc_subtitle');
            $table->dropColumn('plumbing_calc_title');
        });
    }
};
