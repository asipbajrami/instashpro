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
            $table->json('scheduled_hours')->nullable()->after('scrape_interval_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instagram_profiles', function (Blueprint $table) {
            $table->dropColumn('scheduled_hours');
        });
    }
};
