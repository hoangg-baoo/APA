<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('water_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tank_id')
                  ->constrained('tanks')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();

            $table->dateTime('logged_at');

            $table->float('ph')->nullable();
            $table->float('temperature')->nullable();
            $table->float('no3')->nullable();

            $table->json('other_params')->nullable();

            $table->timestamps();

            $table->index('logged_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_logs');
    }
};
