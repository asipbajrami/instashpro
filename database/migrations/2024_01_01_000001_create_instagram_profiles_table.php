<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('full_name')->nullable();
            $table->string('ig_id')->index();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->text('biography')->nullable();
            $table->json('bio_links')->nullable();
            $table->integer('follower_count')->default(0);
            $table->integer('following_count')->default(0);
            $table->integer('media_count')->default(0);
            $table->text('profile_pic_url')->nullable();
            $table->text('profile_pic_url_hd')->nullable();
            $table->boolean('is_private')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_business')->default(false);
            $table->string('business_category')->nullable();
            $table->text('external_url')->nullable();
            $table->string('category')->nullable();
            $table->json('pronouns')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_profiles');
    }
};
