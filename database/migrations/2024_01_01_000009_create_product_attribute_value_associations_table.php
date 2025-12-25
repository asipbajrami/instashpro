<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attribute_value_associations', function (Blueprint $table) {
            $table->id();
            // Specified a shorter index name because the default name is too long for MySQL
            $table->foreignId('product_attribute_value_id')
                ->constrained('product_attribute_values', 'id', 'pava_pav_id_foreign')
                ->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('instagram_posts')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_temp')->default(true);
            $table->timestamps();

            $table->unique(['product_attribute_value_id', 'post_id', 'product_id'], 'unique_association');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_value_associations');
    }
};
