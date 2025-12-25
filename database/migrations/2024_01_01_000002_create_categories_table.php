<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->integer('score')->default(0);
            $table->boolean('is_temp')->default(true);
            $table->timestamps();

            $table->index('parent_id');
            $table->index(['is_temp', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
