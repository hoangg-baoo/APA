<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tank_id')
                ->constrained('tanks')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('name');
            $table->string('device_uid', 100)->unique();
            $table->string('device_key_hash', 64)->unique();

            $table->boolean('is_active')->default(true);
            $table->dateTime('last_seen_at')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['tank_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_devices');
    }
};