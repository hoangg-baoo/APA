<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('water_log_reminders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tank_id')
                ->constrained('tanks')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->unique();

            $table->boolean('enabled')->default(false);
            $table->string('frequency', 30)->default('weekly');
            $table->time('preferred_time')->nullable();
            $table->date('start_date')->nullable();
            $table->dateTime('next_due_at')->nullable();
            $table->dateTime('last_sent_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_log_reminders');
    }
};