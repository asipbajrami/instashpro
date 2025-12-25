<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            $table->text('value');
            $table->text('ai_value')->nullable();
            $table->string('type_value')->nullable();
            $table->boolean('is_temp')->default(true);
            $table->integer('score')->default(0);
            $table->timestamps();

            $table->index(['product_attribute_id', 'is_temp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
    }
};
