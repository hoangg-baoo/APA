<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
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

            $table->string('title');
            $table->text('content');
            $table->string('image_path')->nullable();

            $table->enum('status', ['open', 'resolved'])
                  ->default('open');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
