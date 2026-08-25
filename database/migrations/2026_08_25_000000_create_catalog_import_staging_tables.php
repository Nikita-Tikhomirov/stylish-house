<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_import_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 64);
            $table->string('external_run_id', 128);
            $table->string('status', 32);
            $table->json('config')->nullable();
            $table->unsignedInteger('source_count')->default(0);
            $table->unsignedInteger('page_count')->default(0);
            $table->unsignedInteger('unique_product_count')->default(0);
            $table->unsignedInteger('image_count')->default(0);
            $table->unsignedInteger('membership_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('backup_created_at')->nullable();
            $table->text('backup_path')->nullable();
            $table->string('backup_sha256', 64)->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_run_id'], 'cir_provider_run_uq');
        });

        Schema::create('catalog_import_sources', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('catalog_import_run_id');
            $table->string('label');
            $table->text('source_url');
            $table->string('target_slug');
            $table->boolean('enabled')->default(true);
            $table->string('status', 32);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('pages_count')->default(0);
            $table->unsignedInteger('items_count')->default(0);
            $table->text('next_page_url')->nullable();
            $table->string('rewritten_title')->nullable();
            $table->string('rewritten_h1')->nullable();
            $table->text('rewritten_intro')->nullable();
            $table->longText('rewritten_description')->nullable();
            $table->longText('rewritten_seo')->nullable();
            $table->string('review_status', 32)->default('needs_review');
            $table->text('review_notes')->nullable();
            $table->json('warnings')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('published_subcategory_id')->nullable();
            $table->boolean('created_subcategory')->default(false);
            $table->json('publication_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('catalog_import_run_id', 'cis_run_fk')
                ->references('id')
                ->on('catalog_import_runs')
                ->cascadeOnDelete();
            $table->foreign('published_subcategory_id', 'cis_subcategory_fk')
                ->references('id')
                ->on('subcategories')
                ->nullOnDelete();
            $table->unique(['catalog_import_run_id', 'target_slug'], 'cis_run_slug_uq');
        });

        Schema::create('catalog_import_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('catalog_import_run_id');
            $table->string('provider', 64);
            $table->string('external_id', 128);
            $table->text('source_url');
            $table->string('source_title')->nullable();
            $table->longText('source_description')->nullable();
            $table->decimal('source_price', 12, 2)->nullable();
            $table->text('source_image_path')->nullable();
            $table->string('rewritten_title')->nullable();
            $table->text('rewritten_summary')->nullable();
            $table->longText('rewritten_description')->nullable();
            $table->string('rewritten_slug')->nullable();
            $table->string('review_status', 32)->default('needs_review');
            $table->text('review_notes')->nullable();
            $table->json('warnings')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('published_product_id')->nullable();
            $table->boolean('created_product')->default(false);
            $table->json('publication_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('catalog_import_run_id', 'cii_run_fk')
                ->references('id')
                ->on('catalog_import_runs')
                ->cascadeOnDelete();
            $table->foreign('published_product_id', 'cii_product_fk')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
            $table->unique(
                ['catalog_import_run_id', 'provider', 'external_id'],
                'cii_run_provider_external_uq'
            );
        });

        Schema::create('catalog_import_item_source', function (Blueprint $table): void {
            $table->unsignedBigInteger('import_item_id');
            $table->unsignedBigInteger('import_source_id');
            $table->timestamps();

            $table->foreign('import_item_id', 'ciis_item_fk')
                ->references('id')
                ->on('catalog_import_items')
                ->cascadeOnDelete();
            $table->foreign('import_source_id', 'ciis_source_fk')
                ->references('id')
                ->on('catalog_import_sources')
                ->cascadeOnDelete();
            $table->unique(['import_item_id', 'import_source_id'], 'ciis_item_source_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_import_item_source');
        Schema::dropIfExists('catalog_import_items');
        Schema::dropIfExists('catalog_import_sources');
        Schema::dropIfExists('catalog_import_runs');
    }
};
