<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();

            $table->string('title');
            $table->text('content');
            $table->string('image_path')->nullable();

            /**
             * ✅ NEW: Approval workflow
             * pending  = user vừa đăng, chờ admin duyệt
             * approved = admin duyệt, được hiển thị public
             * rejected = admin từ chối, không hiển thị public
             */
            $table->string('status', 20)->default('pending')->index();

            // Admin duyệt ai + lúc nào (optional)
            $table->foreignId('reviewed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->cascadeOnUpdate();

            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            // Index bổ trợ (tùy chọn nhưng nên có)
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
