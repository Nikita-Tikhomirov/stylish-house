<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('subcategories', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->string('start_material')->nullable();
        });
    }

    public function down()
    {
        Schema::table('subcategories', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->dropColumn('start_material');
        });
    }
};
