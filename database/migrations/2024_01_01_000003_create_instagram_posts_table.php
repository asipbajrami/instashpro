<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_posts', function (Blueprint $table) {
            $table->id();
            $table->string('post_id')->unique();
            $table->string('ig_id')->index();
            $table->string('username')->index();
            $table->string('shortcode')->unique();
            $table->unsignedTinyInteger('media_type')->default(1); // 1=Photo, 2=Video, 8=Album
            $table->boolean('is_video')->default(false);
            $table->text('video_url')->nullable();
            $table->integer('video_view_count')->nullable();
            $table->boolean('has_audio')->nullable();
            $table->text('display_url')->nullable();
            $table->integer('height')->nullable();
            $table->integer('width')->nullable();
            $table->text('caption')->nullable();
            $table->text('caption_original')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->json('image')->nullable();
            $table->json('images_carousel')->nullable();
            $table->json('llm_categories')->nullable();
            $table->string('used_for')->nullable(); // post type: phone, laptop, etc.
            $table->boolean('processed_structure')->default(false);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->index(['username', 'processed_structure']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_posts');
    }
};
