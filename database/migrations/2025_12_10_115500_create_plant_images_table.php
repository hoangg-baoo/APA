<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plant_id')
                  ->constrained('plants')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();

            $table->string('image_path');
            $table->json('feature_vector')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_images');
    }
};
