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
        Schema::table('products', function (Blueprint $table) {
            // Essential - biggest performance wins
            $table->foreignId('instagram_profile_id')->nullable()->after('instagram_media_ids');
            $table->enum('group', ['car', 'tech'])->nullable()->after('type');
            $table->timestamp('published_at')->nullable()->after('updated_at');

            // Helpful for filtering and display
            $table->foreignId('primary_category_id')->nullable()->after('instagram_profile_id');
            $table->text('primary_image_url')->nullable()->after('primary_category_id');
            $table->text('thumbnail_url')->nullable()->after('primary_image_url');

            // Nice to have - caching seller info
            $table->string('seller_username')->nullable()->after('thumbnail_url');
            $table->string('instagram_post_id')->nullable()->after('seller_username');
            $table->unsignedTinyInteger('media_count')->default(1)->after('instagram_post_id');
            $table->boolean('has_discount')->default(false)->after('media_count');

            // Indexes for fast queries
            $table->index('instagram_profile_id');
            $table->index('group');
            $table->index('published_at');
            $table->index('primary_category_id');
            $table->index('seller_username');
            $table->index('instagram_post_id');
            $table->index('has_discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['instagram_profile_id']);
            $table->dropIndex(['group']);
            $table->dropIndex(['published_at']);
            $table->dropIndex(['primary_category_id']);
            $table->dropIndex(['seller_username']);
            $table->dropIndex(['instagram_post_id']);
            $table->dropIndex(['has_discount']);

            $table->dropColumn([
                'instagram_profile_id',
                'group',
                'published_at',
                'primary_category_id',
                'primary_image_url',
                'thumbnail_url',
                'seller_username',
                'instagram_post_id',
                'media_count',
                'has_discount',
            ]);
        });
    }
};
