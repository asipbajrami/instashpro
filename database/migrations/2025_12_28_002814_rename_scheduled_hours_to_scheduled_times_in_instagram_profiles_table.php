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
            $table->renameColumn('scheduled_hours', 'scheduled_times');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instagram_profiles', function (Blueprint $table) {
            $table->renameColumn('scheduled_times', 'scheduled_hours');
        });
    }
};
