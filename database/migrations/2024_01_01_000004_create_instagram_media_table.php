<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_media', function (Blueprint $table) {
            $table->id();
            $table->string('instagram_post_id')->index();
            $table->string('shortcode')->index();
            $table->string('media_id')->nullable()->index();
            $table->string('type'); // image_high, image_mid, carousel_high, carousel_mid, video, frame
            $table->string('media_path');
            $table->string('used_for')->nullable();
            $table->enum('status', ['pending', 'downloaded', 'processed', 'failed'])->default('pending');
            $table->timestamps();

            $table->index(['instagram_post_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_media');
    }
};
