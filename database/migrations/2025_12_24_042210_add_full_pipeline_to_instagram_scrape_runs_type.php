<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE instagram_scrape_runs MODIFY COLUMN type ENUM('posts', 'continuation', 'full_pipeline') DEFAULT 'posts'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE instagram_scrape_runs MODIFY COLUMN type ENUM('posts', 'continuation') DEFAULT 'posts'");
    }
};
