<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds indexes to improve query performance for product listing and facets.
     */
    public function up(): void
    {
        // Composite index for category-based product lookups (used in facet queries)
        Schema::table('product_category', function (Blueprint $table) {
            $table->index(['category_id', 'product_id'], 'idx_category_product');
        });

        // Composite index for high-quality media lookup in ProductResource
        Schema::table('instagram_media', function (Blueprint $table) {
            $table->index(
                ['instagram_post_id', 'type', 'media_id'],
                'idx_post_type_media'
            );
        });

        // Composite indexes for common product filter combinations
        Schema::table('products', function (Blueprint $table) {
            // For group + date sorting (most common listing query)
            $table->index(['group', 'published_at'], 'idx_group_published');
            // For group + price filtering
            $table->index(['group', 'price'], 'idx_group_price');
            // For category + group filtering
            $table->index(['primary_category_id', 'group'], 'idx_category_group');
        });

        // Index for attribute association lookups by product
        Schema::table('product_attribute_value_associations', function (Blueprint $table) {
            $table->index(['product_id', 'is_temp'], 'idx_product_temp');
        });

        // Index for attribute values filtering
        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->index(['is_temp', 'score'], 'idx_temp_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_category', function (Blueprint $table) {
            $table->dropIndex('idx_category_product');
        });

        Schema::table('instagram_media', function (Blueprint $table) {
            $table->dropIndex('idx_post_type_media');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_group_published');
            $table->dropIndex('idx_group_price');
            $table->dropIndex('idx_category_group');
        });

        Schema::table('product_attribute_value_associations', function (Blueprint $table) {
            $table->dropIndex('idx_product_temp');
        });

        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->dropIndex('idx_temp_score');
        });
    }
};
