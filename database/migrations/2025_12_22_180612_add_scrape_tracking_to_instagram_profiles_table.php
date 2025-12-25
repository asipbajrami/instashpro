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
        Schema::table('instagram_profiles', function (Blueprint $table) {
            $table->unsignedInteger('local_post_count')->default(0)->after('media_count');
            $table->boolean('initial_scrape_done')->default(false)->after('local_post_count');
            $table->timestamp('initial_scrape_at')->nullable()->after('initial_scrape_done');
            $table->unsignedInteger('posts_per_request')->default(12)->after('initial_scrape_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instagram_profiles', function (Blueprint $table) {
            $table->dropColumn(['local_post_count', 'initial_scrape_done', 'initial_scrape_at', 'posts_per_request']);
        });
    }
};
