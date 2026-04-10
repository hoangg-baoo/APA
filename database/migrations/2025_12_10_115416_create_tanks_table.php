<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tanks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained()           // Mặc định tham chiếu tới bảng users, cột id (foreign key users.id)
                  ->cascadeOnDelete()       // Nếu user bị xóa thì tự động xóa luôn các tank của user đó
                  ->cascadeOnUpdate();      // Nếu users.id đổi (hiếm) thì cập nhật user_id tương ứng (cascade)

            $table->string('name');
            $table->string('size', 50)->nullable();
            $table->float('volume_liters')->nullable();
            $table->string('substrate')->nullable();
            $table->string('light')->nullable();
            $table->boolean('co2')->default(false);
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanks');
    }
};
