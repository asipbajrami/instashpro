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
            $table->integer('scrape_interval_hours')->default(24)->after('status');
            $table->timestamp('last_scraped_at')->nullable()->after('scrape_interval_hours');
            $table->timestamp('next_scrape_at')->nullable()->after('last_scraped_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instagram_profiles', function (Blueprint $table) {
            $table->dropColumn(['scrape_interval_hours', 'last_scraped_at', 'next_scrape_at']);
        });
    }
};
