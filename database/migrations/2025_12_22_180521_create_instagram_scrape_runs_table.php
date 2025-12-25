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
        Schema::create('instagram_scrape_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_profile_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['posts', 'continuation'])->default('posts');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');

            // Counts
            $table->unsignedInteger('posts_fetched')->default(0);
            $table->unsignedInteger('posts_new')->default(0);
            $table->unsignedInteger('posts_skipped')->default(0);

            // Pagination tracking
            $table->string('end_cursor')->nullable();
            $table->boolean('has_more_pages')->default(false);

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
        Schema::dropIfExists('instagram_scrape_runs');
    }
};
