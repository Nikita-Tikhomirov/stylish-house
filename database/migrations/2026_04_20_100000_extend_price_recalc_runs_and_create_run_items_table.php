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
        Schema::table('price_recalc_runs', function (Blueprint $table) {
            $table->string('mode')->default('manual')->after('status');
            $table->unsignedBigInteger('start_id')->nullable()->after('batch_size');
            $table->unsignedBigInteger('end_id')->nullable()->after('start_id');
            $table->unsignedBigInteger('current_id')->default(0)->after('end_id');
            $table->boolean('skip_filled')->default(true)->after('current_id');
            $table->boolean('overwrite_existing')->default(false)->after('skip_filled');
            $table->unsignedInteger('total_candidates')->nullable()->after('skipped');
            $table->unsignedDecimal('progress_percent', 5, 2)->nullable()->after('total_candidates');
            $table->unsignedInteger('eta_seconds')->nullable()->after('progress_percent');
            $table->string('stop_reason')->nullable()->after('eta_seconds');
        });

        Schema::create('price_recalc_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('price_recalc_runs')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->string('status'); // updated|skipped|error
            $table->unsignedInteger('old_min_price')->nullable();
            $table->unsignedInteger('new_min_price')->nullable();
            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('processed_at');
            $table->timestamps();

            $table->index(['run_id', 'status']);
            $table->index(['run_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_recalc_run_items');

        Schema::table('price_recalc_runs', function (Blueprint $table) {
            $table->dropColumn([
                'mode',
                'start_id',
                'end_id',
                'current_id',
                'skip_filled',
                'overwrite_existing',
                'total_candidates',
                'progress_percent',
                'eta_seconds',
                'stop_reason',
            ]);
        });
    }
};
