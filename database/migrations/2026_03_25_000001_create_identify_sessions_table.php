<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identify_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('tank_id')
                ->nullable()
                ->constrained('tanks')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('source_image_path');
            $table->text('note')->nullable();

            $table->json('merged_results')->nullable();
            $table->json('confirmed_plants')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['tank_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identify_sessions');
    }
};