<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identify_regions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('identify_session_id')
                ->constrained('identify_sessions')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('crop_image_path');
            $table->json('crop_box')->nullable();
            $table->json('query_vector')->nullable();
            $table->json('match_results')->nullable();

            $table->timestamps();

            $table->index(['identify_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identify_regions');
    }
};