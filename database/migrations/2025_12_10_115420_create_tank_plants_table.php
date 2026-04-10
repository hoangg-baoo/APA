<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tank_plants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tank_id')
                  ->constrained('tanks')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();

            $table->foreignId('plant_id')
                  ->constrained('plants')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();

            $table->date('planted_at')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['tank_id', 'plant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tank_plants');
    }
};
