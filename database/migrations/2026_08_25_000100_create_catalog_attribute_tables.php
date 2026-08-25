<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_attributes', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64);
            $table->string('label');
            $table->enum('type', ['select', 'number']);
            $table->string('unit', 32)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->unique('code', 'ca_code_uq');
        });

        Schema::create('catalog_attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('catalog_attribute_id');
            $table->string('normalized_value', 191);
            $table->string('label');
            $table->decimal('numeric_value', 12, 4)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('catalog_attribute_id', 'cav_attribute_fk')
                ->references('id')
                ->on('catalog_attributes')
                ->cascadeOnDelete();
            $table->unique(
                ['catalog_attribute_id', 'normalized_value'],
                'cav_attribute_value_uq'
            );
        });

        Schema::create('catalog_import_item_attribute_value', function (Blueprint $table): void {
            $table->unsignedBigInteger('import_item_id');
            $table->unsignedBigInteger('attribute_value_id');
            $table->timestamps();

            $table->foreign('import_item_id', 'ciiav_item_fk')
                ->references('id')
                ->on('catalog_import_items')
                ->cascadeOnDelete();
            $table->foreign('attribute_value_id', 'ciiav_value_fk')
                ->references('id')
                ->on('catalog_attribute_values')
                ->cascadeOnDelete();
            $table->unique(['import_item_id', 'attribute_value_id'], 'ciiav_item_value_uq');
        });

        Schema::create('catalog_product_attribute_value', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('attribute_value_id');
            $table->unsignedBigInteger('catalog_import_run_id')->nullable();
            $table->timestamps();

            $table->foreign('product_id', 'cpav_product_fk')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
            $table->foreign('attribute_value_id', 'cpav_value_fk')
                ->references('id')
                ->on('catalog_attribute_values')
                ->cascadeOnDelete();
            $table->foreign('catalog_import_run_id', 'cpav_run_fk')
                ->references('id')
                ->on('catalog_import_runs')
                ->nullOnDelete();
            $table->unique(['product_id', 'attribute_value_id'], 'cpav_product_value_uq');
            $table->index('catalog_import_run_id', 'cpav_run_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_product_attribute_value');
        Schema::dropIfExists('catalog_import_item_attribute_value');
        Schema::dropIfExists('catalog_attribute_values');
        Schema::dropIfExists('catalog_attributes');
    }
};
