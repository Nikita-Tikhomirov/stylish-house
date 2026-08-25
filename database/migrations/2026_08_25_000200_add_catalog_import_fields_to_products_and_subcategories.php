<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Laravel 10 cannot reverse foreign keys added to existing SQLite tables.
        $supportsAlterTableForeignKeys = Schema::getConnection()->getDriverName() !== 'sqlite';

        Schema::table('products', function (Blueprint $table) use ($supportsAlterTableForeignKeys): void {
            $table->string('source_provider', 64)->nullable();
            $table->string('source_external_id', 128)->nullable();
            $table->text('source_url')->nullable();
            $table->decimal('source_price', 12, 2)->nullable();
            $table->unsignedBigInteger('import_run_id')->nullable();
            $table->boolean('calculator_enabled')->default(true);

            if ($supportsAlterTableForeignKeys) {
                $table->foreign('import_run_id', 'products_import_run_fk')
                    ->references('id')
                    ->on('catalog_import_runs')
                    ->nullOnDelete();
            }
            $table->unique(
                ['source_provider', 'source_external_id'],
                'products_source_identity_uq'
            );
        });

        Schema::table('subcategories', function (Blueprint $table) use ($supportsAlterTableForeignKeys): void {
            $table->boolean('is_import_collection')->default(false);
            $table->unsignedBigInteger('import_run_id')->nullable();

            if ($supportsAlterTableForeignKeys) {
                $table->foreign('import_run_id', 'subcategories_import_run_fk')
                    ->references('id')
                    ->on('catalog_import_runs')
                    ->nullOnDelete();
            }
        });

        Schema::create('catalog_collection_product', function (Blueprint $table): void {
            $table->unsignedBigInteger('subcategory_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('catalog_import_run_id')->nullable();
            $table->timestamps();

            $table->foreign('subcategory_id', 'ccp_subcategory_fk')
                ->references('id')
                ->on('subcategories')
                ->cascadeOnDelete();
            $table->foreign('product_id', 'ccp_product_fk')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
            $table->foreign('catalog_import_run_id', 'ccp_run_fk')
                ->references('id')
                ->on('catalog_import_runs')
                ->nullOnDelete();
            $table->unique(['subcategory_id', 'product_id'], 'ccp_subcategory_product_uq');
            $table->index('catalog_import_run_id', 'ccp_run_idx');
        });
    }

    public function down(): void
    {
        $supportsAlterTableForeignKeys = Schema::getConnection()->getDriverName() !== 'sqlite';

        Schema::dropIfExists('catalog_collection_product');

        if ($supportsAlterTableForeignKeys) {
            Schema::table('subcategories', function (Blueprint $table): void {
                $table->dropForeign('subcategories_import_run_fk');
            });
        }
        Schema::table('subcategories', function (Blueprint $table): void {
            $table->dropColumn(['is_import_collection', 'import_run_id']);
        });

        Schema::table('products', function (Blueprint $table) use ($supportsAlterTableForeignKeys): void {
            if ($supportsAlterTableForeignKeys) {
                $table->dropForeign('products_import_run_fk');
            }
            $table->dropUnique('products_source_identity_uq');
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'source_provider',
                'source_external_id',
                'source_url',
                'source_price',
                'import_run_id',
                'calculator_enabled',
            ]);
        });
    }
};
