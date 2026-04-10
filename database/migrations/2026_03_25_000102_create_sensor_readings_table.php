<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tank_id')
                ->constrained('tanks')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('sensor_id')
                ->constrained('sensors')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('type', 50);
            $table->decimal('numeric_value', 12, 4)->nullable();
            $table->dateTime('recorded_at');
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->index(['tank_id', 'type', 'recorded_at']);
            $table->index(['sensor_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};