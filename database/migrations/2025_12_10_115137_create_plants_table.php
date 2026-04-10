<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            $table->float('ph_min')->nullable();
            $table->float('ph_max')->nullable();
            $table->float('temp_min')->nullable();
            $table->float('temp_max')->nullable();

            $table->enum('light_level', ['low', 'medium', 'high'])->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->nullable();

            $table->string('image_sample')->nullable();
            $table->text('care_guide')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plants');
    }
};
