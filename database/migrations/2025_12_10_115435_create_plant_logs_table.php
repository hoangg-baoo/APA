<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tank_plant_id')
                  ->constrained('tank_plants')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();

            $table->date('logged_at');
            $table->float('height')->nullable();
            $table->string('status', 100)->nullable();
            $table->text('note')->nullable();
            $table->string('image_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_logs');
    }
};
