<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('iot_device_id')
                ->constrained('iot_devices')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('type', 50);
            $table->string('name');
            $table->string('unit', 30)->nullable();

            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['iot_device_id', 'type']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensors');
    }
};