<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structure_output_groups', function (Blueprint $table) {
            $table->id();
            $table->string('used_for')->unique();
            $table->text('description');
            $table->timestamps();

            $table->index('used_for');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structure_output_groups');
    }
};
