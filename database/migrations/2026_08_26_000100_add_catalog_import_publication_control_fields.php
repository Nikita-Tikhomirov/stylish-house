<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_import_runs', function (Blueprint $table): void {
            $table->timestamp('warnings_acknowledged_at')->nullable();
            $table->string('warnings_acknowledged_by', 191)->nullable();
            $table->string('warnings_acknowledged_sha256', 64)->nullable();
            $table->text('backup_manifest_path')->nullable();
            $table->string('backup_manifest_sha256', 64)->nullable();
            $table->string('backup_raw_sha256', 64)->nullable();
            $table->unsignedBigInteger('backup_raw_size')->nullable();
            $table->unsignedBigInteger('backup_gzip_size')->nullable();
            $table->timestamp('sitemap_generated_at')->nullable();
            $table->text('sitemap_error')->nullable();
            $table->text('publication_error')->nullable();
            $table->json('publication_journal')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->text('rollback_error')->nullable();
            $table->json('rollback_journal')->nullable();
            $table->timestamp('rollback_backup_created_at')->nullable();
            $table->text('rollback_backup_path')->nullable();
            $table->string('rollback_backup_sha256', 64)->nullable();
            $table->text('rollback_backup_manifest_path')->nullable();
            $table->string('rollback_backup_manifest_sha256', 64)->nullable();
            $table->string('rollback_backup_raw_sha256', 64)->nullable();
            $table->unsignedBigInteger('rollback_backup_raw_size')->nullable();
            $table->unsignedBigInteger('rollback_backup_gzip_size')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_import_runs', function (Blueprint $table): void {
            $table->dropColumn([
                'warnings_acknowledged_at',
                'warnings_acknowledged_by',
                'warnings_acknowledged_sha256',
                'backup_manifest_path',
                'backup_manifest_sha256',
                'backup_raw_sha256',
                'backup_raw_size',
                'backup_gzip_size',
                'sitemap_generated_at',
                'sitemap_error',
                'publication_error',
                'publication_journal',
                'rolled_back_at',
                'rollback_error',
                'rollback_journal',
                'rollback_backup_created_at',
                'rollback_backup_path',
                'rollback_backup_sha256',
                'rollback_backup_manifest_path',
                'rollback_backup_manifest_sha256',
                'rollback_backup_raw_sha256',
                'rollback_backup_raw_size',
                'rollback_backup_gzip_size',
            ]);
        });
    }
};
