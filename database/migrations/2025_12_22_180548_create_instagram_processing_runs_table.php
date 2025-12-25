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
        Schema::create('instagram_processing_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_profile_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');

            // Processing counts
            $table->unsignedInteger('posts_to_process')->default(0);
            $table->unsignedInteger('posts_processed')->default(0);
            $table->unsignedInteger('posts_failed')->default(0);
            $table->unsignedInteger('posts_skipped')->default(0);

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['instagram_profile_id', 'created_at']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_processing_runs');
    }
};
