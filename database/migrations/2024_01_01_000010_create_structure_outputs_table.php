<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structure_outputs', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('type');
            $table->string('description');
            $table->string('parent_key')->nullable();
            $table->string('used_for');
            $table->boolean('required')->default(false);
            $table->text('enum_values')->nullable(); // underscore delimited
            $table->foreignId('product_attribute_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['key', 'used_for']);
            $table->index('used_for');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structure_outputs');
    }
};
